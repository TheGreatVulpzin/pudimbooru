<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{B, P, emptyHTML};

final class UserNotifications extends Extension
{
    public const KEY = "user_notifications";

    private const VERIFICATION_LIFETIME_HOURS = 24;
    private const VERIFICATION_RESEND_COOLDOWN_MINUTES = 10;

    #[EventListener]
    public function onUserLogin(UserLoginEvent $event): void
    {
        if ($event->user->email !== null && $event->user->email !== "" && !$event->user->email_verified) {
            Ctx::$page->flash("Seu e-mail ainda nao foi verificado. Verifique sua caixa de entrada ou reenvie a verificacao pelo seu perfil.");
        }
    }

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
            Ctx::$page->flash("E-mail verificado");
            Ctx::$page->set_redirect(Ctx::$user->is_anonymous() ? make_link("user_admin/login") : make_link("user"));
        }

        if ($event->page_matches("user_notifications/send_verification", method: "POST")) {
            if (Ctx::$user->email === null || Ctx::$user->email === "") {
                throw new InvalidInput("Nenhum endereço de e-mail para verificar");
            }
            if (!$this->canSendVerificationEmail(Ctx::$user, Ctx::$user->email)) {
                Ctx::$page->flash("Aguarde 10 minutos antes de reenviar o e-mail de verificação.");
                Ctx::$page->set_redirect(make_link("user"));
                return;
            }
            if ($this->sendVerificationEmail(Ctx::$user, Ctx::$user->email)) {
                Ctx::$page->flash("E-mail de verificação enviado.");
            } else {
                Ctx::$page->flash("Não foi possível enviar o e-mail de verificação. Confira os logs do sistema.");
            }
            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $viewer = Ctx::$user;
        if ($viewer->id !== $event->display_user->id && $viewer->class->name !== "admin") {
            return;
        }

        if ($event->display_user->email === null || $event->display_user->email === "") {
            $event->add_part(emptyHTML("Conta: Sem e-mail"), 20);
        } elseif ($event->display_user->email_verified) {
            $event->add_part(emptyHTML("Conta: ", B("Verificada")), 20);
        } else {
            $event->add_part(emptyHTML("Conta: E-mail não verificado"), 20);
        }
    }

    #[EventListener]
    public function onUserOperationsBuilding(UserOperationsBuildingEvent $event): void
    {
        if ($event->user->id !== Ctx::$user->id || $event->user->email === null || $event->user->email === "" || $event->user->email_verified) {
            return;
        }

        $event->add_part(emptyHTML(
            P("Seu e-mail ainda não foi verificado."),
            SHM_SIMPLE_FORM(
                make_link("user_notifications/send_verification"),
                SHM_SUBMIT("Reenviar verificação de e-mail")
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
        } else {
            Log::info("user_notifications", "Password changed email sent for user #{$event->user->id}");
        }
    }

    #[EventListener]
    public function onUserEmailChanged(UserEmailChangedEvent $event): void
    {
        if ($event->oldEmail === $event->newEmail && $event->user->email_verified) {
            return;
        }

        if ($event->newEmail === null || $event->newEmail === "") {
            return;
        }

        if ($event->source === "user_creation") {
            $this->setEmailVerified($event->user, false);
            $event->user->email_verified = false;
        }

        if (!$this->canSendVerificationEmail($event->user, $event->newEmail)) {
            $event->verificationRateLimited = true;
            Log::info("user_notifications", "Email verification cooldown for user #{$event->user->id}");
            return;
        }

        $event->verificationSent = $this->sendVerificationEmail($event->user, $event->newEmail);
        if ($event->source === "user_creation" && $event->user->id === Ctx::$user->id) {
            Ctx::$page->flash(
                $event->verificationSent
                    ? "Enviamos um e-mail de verificacao. Confirme seu e-mail para ativar a verificacao da conta."
                    : "Nao foi possivel enviar o e-mail de verificacao. Confira os logs do sistema."
            );
        }
        Log::info("user_notifications", "Email verification pending for user #{$event->user->id}");
    }

    private function sendVerificationEmail(User $user, string $email): bool
    {
        if ($email === "") {
            return false;
        }

        if (!Mail::isDeliveryEnabled()) {
            Log::warning("user_notifications", "Email verification skipped for user #{$user->id} because email delivery is disabled");
            return false;
        }

        $this->deleteExpiredTokens();
        Ctx::$database->execute(
            "UPDATE user_email_verification_tokens SET used = :used WHERE user_id = :user_id AND email = :email AND used = :unused",
            ["used" => true, "unused" => false, "user_id" => $user->id, "email" => $email]
        );

        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 60 * 60 * self::VERIFICATION_LIFETIME_HOURS);
        Ctx::$database->execute(
            "INSERT INTO user_email_verification_tokens (user_id, email, token_hash, expires) VALUES (:user_id, :email, :token_hash, :expires)",
            [
                "user_id" => $user->id,
                "email" => $email,
                "token_hash" => self::hashToken($token),
                "expires" => $expires,
            ]
        );

        $link = (string)make_link("user_notifications/verify_email", ["token" => $token])->asAbsolute();
        $mail = MailTemplate::send(new UserEmailVerificationEmailConfig(), $email, [
            'username' => $user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
            'verification_link' => $link,
        ]);

        if (!$mail->sent) {
            Log::error("user_notifications", "Email verification failed for user #{$user->id}");
            return false;
        } else {
            Log::info("user_notifications", "Email verification sent for user #{$user->id}");
            return true;
        }
    }

    private function canSendVerificationEmail(User $user, string $email): bool
    {
        $cutoff = date("Y-m-d H:i:s", time() - 60 * self::VERIFICATION_RESEND_COOLDOWN_MINUTES);
        $recent = Ctx::$database->get_one(
            "SELECT COUNT(*) FROM user_email_verification_tokens WHERE user_id = :user_id AND email = :email AND used = :used AND created >= :cutoff",
            [
                "user_id" => $user->id,
                "email" => $email,
                "used" => false,
                "cutoff" => $cutoff,
            ]
        );

        return (int)$recent === 0;
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
            throw new InvalidInput("Link de verificação de e-mail inválido ou expirado");
        }

        $user = User::by_id((int)$row["user_id"]);
        $email = (string)$row["email"];
        $oldEmail = $user->email;
        if ($oldEmail !== $email) {
            $user->set_email($email);
            $user = User::by_id($user->id);
            if ($oldEmail !== null && $oldEmail !== "") {
                $this->sendEmailChangedOldAddressNotice($user, $oldEmail, $email);
            }
        }

        $this->setEmailVerified($user, true);
        Ctx::$database->execute(
            "UPDATE user_email_verification_tokens SET used = :used WHERE id = :id",
            ["used" => true, "id" => $row["id"]]
        );
        Log::info("user_notifications", "Verified email for user #{$user->id}");
    }

    private function sendEmailChangedOldAddressNotice(User $user, string $oldEmail, string $newEmail): void
    {
        $mail = MailTemplate::send(new UserEmailChangedOldEmailConfig(), $oldEmail, [
            'username' => $user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
            'old_email' => $oldEmail,
            'new_email' => $newEmail,
            'actor' => $user->name,
        ]);

        if (!$mail->sent) {
            Log::error("user_notifications", "Email change notice failed for user #{$user->id}");
        } else {
            Log::info("user_notifications", "Email change notice sent for user #{$user->id}");
        }
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
