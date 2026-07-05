<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, P, emptyHTML};

final class UserNotifications extends Extension
{
    public const KEY = "user_notifications";

    private const VERIFICATION_LIFETIME_HOURS = 24;

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 1) {
            $database->execute("ALTER TABLE users ADD COLUMN email_verified BOOLEAN NOT NULL DEFAULT FALSE");
            $database->execute("UPDATE users SET email_verified = :verified WHERE email IS NOT NULL AND email <> ''", ["verified" => true]);
            $database->create_table("user_email_verification_tokens", "
                id SCORE_AIPK,
                user_id INTEGER NOT NULL,
                email VARCHAR(128) NOT NULL,
                token_hash VARCHAR(128) NOT NULL UNIQUE,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires TIMESTAMP NOT NULL,
                used BOOLEAN NOT NULL DEFAULT FALSE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ");
            $database->execute("CREATE INDEX user_email_verification_tokens__user_id ON user_email_verification_tokens(user_id)");
            $database->execute("CREATE INDEX user_email_verification_tokens__expires ON user_email_verification_tokens(expires)");
            $this->set_version(1);
        }
        $this->deleteExpiredTokens();
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("user_notifications/verify_email", method: "GET", authed: false)) {
            $this->verifyEmail($event->GET->req("token"));
            Ctx::$page->flash("Email verified");
            Ctx::$page->set_redirect(Ctx::$user->is_anonymous() ? make_link("user_admin/login") : make_link("user"));
        }

        if ($event->page_matches("user_notifications/send_verification", method: "POST")) {
            if (Ctx::$user->email === null || Ctx::$user->email === "") {
                throw new InvalidInput("No email address to verify");
            }
            $this->sendVerificationEmail(Ctx::$user);
            Ctx::$page->flash("Verification email sent");
            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        if ($event->display_user->email === null || $event->display_user->email === "") {
            $event->add_part(emptyHTML("Conta: Sem email"), 20);
        } elseif ($event->display_user->email_verified) {
            $event->add_part(emptyHTML("Conta: ", B("Verificada")), 20);
        } else {
            $event->add_part(emptyHTML("Conta: Email não verificado"), 20);
        }
    }

    #[EventListener]
    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        if ($event->user->id !== Ctx::$user->id || $event->user->email === null || $event->user->email === "" || $event->user->email_verified) {
            return;
        }

        $event->add_part(emptyHTML(
            P("Seu email ainda não foi verificado."),
            SHM_SIMPLE_FORM(
                make_link("user_notifications/send_verification"),
                SHM_SUBMIT("Reenviar verificação de email")
            )
        ), 25);
    }

    #[EventListener]
    public function onUserPasswordChanged(UserPasswordChangedEvent $event): void
    {
        if ($event->source !== "user_admin") {
            return;
        }

        if ($event->user->email === null || $event->user->email === "") {
            return;
        }

        $mail = MailTemplate::send(new UserPasswordChangedEmailConfig(), $event->user->email, [
            'username' => $event->user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
            'actor' => $event->actor->name,
        ]);

        if (!$mail->sent) {
            Log::error("user_notifications", "Password changed email failed for user #{$event->user->id}");
        }
    }

    #[EventListener]
    public function onUserEmailChanged(UserEmailChangedEvent $event): void
    {
        if ($event->oldEmail === $event->newEmail && $event->user->email_verified) {
            return;
        }

        $this->setEmailVerified($event->user, false);
        $event->user->email_verified = false;

        if ($event->oldEmail !== null && $event->oldEmail !== "" && $event->oldEmail !== $event->newEmail) {
            $mail = MailTemplate::send(new UserEmailChangedOldEmailConfig(), $event->oldEmail, [
                'username' => $event->user->name,
                'site' => Ctx::$config->get(SetupConfig::TITLE),
                'old_email' => $event->oldEmail,
                'new_email' => $event->newEmail ?? "",
                'actor' => $event->actor->name,
            ]);

            if (!$mail->sent) {
                Log::error("user_notifications", "Email change notice failed for user #{$event->user->id}");
            }
        }

        if ($event->newEmail !== null && $event->newEmail !== "") {
            $this->sendVerificationEmail($event->user);
        }
    }

    private function sendVerificationEmail(User $user): void
    {
        if ($user->email === null || $user->email === "") {
            return;
        }

        $this->deleteExpiredTokens();
        Ctx::$database->execute(
            "UPDATE user_email_verification_tokens SET used = :used WHERE user_id = :user_id AND used = :unused",
            ["used" => true, "unused" => false, "user_id" => $user->id]
        );

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 60 * 60 * self::VERIFICATION_LIFETIME_HOURS);
        Ctx::$database->execute(
            "INSERT INTO user_email_verification_tokens (user_id, email, token_hash, expires) VALUES (:user_id, :email, :token_hash, :expires)",
            [
                "user_id" => $user->id,
                "email" => $user->email,
                "token_hash" => self::hashToken($token),
                "expires" => $expires,
            ]
        );

        $link = (string)make_link("user_notifications/verify_email", ["token" => $token])->asAbsolute();
        $mail = MailTemplate::send(new UserEmailVerificationEmailConfig(), $user->email, [
            'username' => $user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
            'verification_link' => $link,
        ]);

        if (!$mail->sent) {
            Log::error("user_notifications", "Email verification failed for user #{$user->id}");
        }
    }

    private function verifyEmail(string $token): void
    {
        $this->deleteExpiredTokens();
        $hash = self::hashToken($token);
        $row = Ctx::$database->get_row(
            "SELECT * FROM user_email_verification_tokens WHERE token_hash = :token_hash AND used = :used",
            ["token_hash" => $hash, "used" => false]
        );

        if ($row === null || !hash_equals((string)$row["token_hash"], $hash)) {
            throw new InvalidInput("Invalid or expired email verification link");
        }

        $user = User::by_id((int)$row["user_id"]);
        if ($user->email !== (string)$row["email"]) {
            throw new InvalidInput("Invalid or expired email verification link");
        }

        $this->setEmailVerified($user, true);
        Ctx::$database->execute(
            "UPDATE user_email_verification_tokens SET used = :used WHERE id = :id",
            ["used" => true, "id" => $row["id"]]
        );
        Log::info("user_notifications", "Verified email for user #{$user->id}");
    }

    private function setEmailVerified(User $user, bool $verified): void
    {
        Ctx::$database->execute(
            "UPDATE users SET email_verified = :verified WHERE id = :id",
            ["verified" => $verified, "id" => $user->id]
        );
    }

    private function deleteExpiredTokens(): void
    {
        Ctx::$database->execute(
            "DELETE FROM user_email_verification_tokens WHERE expires < CURRENT_TIMESTAMP OR used = :used",
            ["used" => true]
        );
    }

    private static function hashToken(string $token): string
    {
        return hash("sha3-256", $token . SysConfig::getSecret());
    }
}
