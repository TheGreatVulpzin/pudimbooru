<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<PasswordResetTheme> */
final class PasswordReset extends Extension
{
    public const KEY = "password_reset";

    private const GENERIC_MESSAGE = "If the account exists and has an email address, a reset link has been sent. Please check your spam folder too.";

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 1) {
            $database->create_table("password_reset_tokens", "
                id SCORE_AIPK,
                user_id INTEGER NOT NULL,
                token_hash VARCHAR(128) NOT NULL UNIQUE,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires TIMESTAMP NOT NULL,
                used BOOLEAN NOT NULL DEFAULT FALSE,
                request_ip VARCHAR(45) NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ");
            $database->execute("CREATE INDEX password_reset_tokens__user_id ON password_reset_tokens(user_id)");
            $database->execute("CREATE INDEX password_reset_tokens__expires ON password_reset_tokens(expires)");
            $this->set_version(1);
        }
        $this->deleteExpiredTokens();
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("password_reset/request", method: "GET", permission: PasswordResetPermission::REQUEST_PASSWORD_RESET)) {
            $this->theme->display_request_page();
        }

        if ($event->page_matches("password_reset/request", method: "POST", permission: PasswordResetPermission::REQUEST_PASSWORD_RESET)) {
            $this->requestReset($event->POST->req("login"));
            $this->theme->display_sent_page();
        }

        if ($event->page_matches("password_reset/reset", method: "GET", permission: PasswordResetPermission::REQUEST_PASSWORD_RESET)) {
            $token = $event->GET->req("token");
            $this->assertTokenUsable($token);
            $this->theme->display_reset_page($token);
        }

        if ($event->page_matches("password_reset/reset", method: "POST", permission: PasswordResetPermission::REQUEST_PASSWORD_RESET)) {
            $this->resetPassword(
                $event->POST->req("token"),
                $event->POST->req("pass1"),
                $event->POST->req("pass2")
            );
            Ctx::$page->flash("Password changed");
            Ctx::$page->set_redirect(make_link("user_admin/login"));
        }
    }

    #[EventListener]
    public function onPasswordResetRequest(PasswordResetRequestEvent $event): void
    {
        $this->requestReset($event->login);
        Ctx::$page->flash(self::GENERIC_MESSAGE);
        Ctx::$page->set_redirect(make_link("user_admin/login"));
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(PasswordResetPermission::REQUEST_PASSWORD_RESET)) {
            $event->add_link("Reset Password", make_link("password_reset/request"), 98);
        }
    }

    public function requestReset(string $login): void
    {
        $this->deleteExpiredTokens();
        $user = $this->findUser($login);
        if ($user === null || $user->email === null || $user->email === "" || !$user->email_verified) {
            Log::info("password_reset", "Password reset requested for unknown, email-less, or unverified account");
            return;
        }

        $requestIp = (string)Network::get_real_ip();
        if ($this->isRateLimited($user, $requestIp)) {
            Log::warning("password_reset", "Password reset rate limited for user #{$user->id} from $requestIp");
            return;
        }

        $token = bin2hex(random_bytes(32));
        $hash = self::hashToken($token);
        $expires = date("Y-m-d H:i:s", time() + 60 * Ctx::$config->get(PasswordResetConfig::TOKEN_LIFETIME));
        Ctx::$database->execute(
            "INSERT INTO password_reset_tokens (user_id, token_hash, expires, request_ip) VALUES (:user_id, :token_hash, :expires, :request_ip)",
            [
                "user_id" => $user->id,
                "token_hash" => $hash,
                "expires" => $expires,
                "request_ip" => $requestIp,
            ]
        );

        $link = (string)make_link("password_reset/reset", ["token" => $token])->asAbsolute();
        $placeholders = [
            'link' => $link,
            'username' => $user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
        ];
        $mail = MailTemplate::send(new PasswordResetEmailConfig(), $user->email, $placeholders);

        if (!$mail->sent) {
            Log::error("password_reset", "Password reset email failed for user #{$user->id}");
        }
    }

    public function resetPassword(string $token, string $pass1, string $pass2): void
    {
        if ($pass1 !== $pass2) {
            throw new InvalidInput("Passwords don't match");
        }

        $row = $this->assertTokenUsable($token);
        $user = User::by_id((int)$row["user_id"]);
        $user->set_password($pass1);
        send_event(new UserPasswordChangedEvent($user, $user, "password_reset"));
        $this->sendPasswordResetSuccessEmail($user);
        Ctx::$database->execute(
            "UPDATE password_reset_tokens SET used = :used WHERE id = :id",
            ["used" => true, "id" => $row["id"]]
        );
        Log::info("password_reset", "Password reset for user #{$user->id}");
    }

    /**
     * @return array<string, mixed>
     */
    private function assertTokenUsable(string $token): array
    {
        $this->deleteExpiredTokens();
        $hash = self::hashToken($token);
        $row = Ctx::$database->get_row(
            "SELECT * FROM password_reset_tokens WHERE token_hash = :token_hash AND used = :used",
            ["token_hash" => $hash, "used" => false]
        );
        if ($row === null || !hash_equals((string)$row["token_hash"], $hash)) {
            throw new InvalidInput("Invalid or expired password reset link");
        }
        return $row;
    }

    private function deleteExpiredTokens(): void
    {
        Ctx::$database->execute(
            "DELETE FROM password_reset_tokens WHERE expires < CURRENT_TIMESTAMP OR used = :used",
            ["used" => true]
        );
    }

    private function findUser(string $login): ?User
    {
        $row = Ctx::$database->get_row(
            "SELECT * FROM users WHERE lower(name) = lower(:login) OR lower(email) = lower(:login) ORDER BY id LIMIT 1",
            ["login" => $login]
        );
        return $row === null ? null : new User($row);
    }

    private function isRateLimited(User $user, string $requestIp): bool
    {
        $maxRequests = Ctx::$config->get(PasswordResetConfig::RATE_LIMIT_COUNT);
        if ($maxRequests <= 0) {
            return false;
        }

        $windowMinutes = max(1, Ctx::$config->get(PasswordResetConfig::RATE_LIMIT_WINDOW));
        $cutoff = date("Y-m-d H:i:s", time() - 60 * $windowMinutes);
        $requests = Ctx::$database->get_one(
            "SELECT COUNT(*) FROM password_reset_tokens WHERE created >= :cutoff AND (user_id = :user_id OR request_ip = :request_ip)",
            [
                "cutoff" => $cutoff,
                "user_id" => $user->id,
                "request_ip" => $requestIp,
            ]
        );

        return $requests >= $maxRequests;
    }

    private function sendPasswordResetSuccessEmail(User $user): void
    {
        if ($user->email === null || $user->email === "") {
            return;
        }

        $mail = MailTemplate::send(new PasswordResetSuccessEmailConfig(), $user->email, [
            'username' => $user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
        ]);

        if (!$mail->sent) {
            Log::error("password_reset", "Password reset success email failed for user #{$user->id}");
        }
    }

    private static function hashToken(string $token): string
    {
        return hash("sha3-256", $token . SysConfig::getSecret());
    }
}
