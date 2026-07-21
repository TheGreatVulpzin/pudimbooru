<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetTest extends ShimmiePHPUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!\in_array("password_reset_tokens", Ctx::$database->get_table_names(), true)) {
            (new PasswordReset())->onDatabaseUpgrade(new DatabaseUpgradeEvent());
        }
        Ctx::$database->execute("DELETE FROM password_reset_tokens");
    }

    public function testUnknownAccountDoesNotCreateToken(): void
    {
        (new PasswordReset())->requestReset("missing-user");

        self::assertSame(0, Ctx::$database->get_one("SELECT COUNT(*) FROM password_reset_tokens"));
    }

    public function testDatabaseUpgradeIsIdempotentWhenVersionIsStale(): void
    {
        Ctx::$config->set("ext_password_reset_version", 0);

        (new PasswordReset())->onDatabaseUpgrade(new DatabaseUpgradeEvent());

        self::assertSame(1, Ctx::$config->get("ext_password_reset_version", ConfigType::INT));
    }

    public function testUserWithoutEmailDoesNotCreateToken(): void
    {
        Ctx::$database->execute("UPDATE users SET email = NULL WHERE name = :name", ["name" => self::USER_NAME]);

        (new PasswordReset())->requestReset(self::USER_NAME);

        self::assertSame(0, Ctx::$database->get_one("SELECT COUNT(*) FROM password_reset_tokens"));
    }

    public function testExpiredTokenFails(): void
    {
        $token = "expired-token";
        $this->insertToken(User::by_name(self::USER_NAME), $token, date("Y-m-d H:i:s", time() - 60));

        self::assertException(InvalidInput::class, function () use ($token) {
            (new PasswordReset())->resetPassword($token, "new-password", "new-password");
        });
    }

    public function testValidTokenChangesPasswordOnce(): void
    {
        $token = "valid-token";
        $this->insertToken(User::by_name(self::USER_NAME), $token, date("Y-m-d H:i:s", time() + 3600));

        (new PasswordReset())->resetPassword($token, "new-password", "new-password");
        User::by_name_and_pass(self::USER_NAME, "new-password");

        self::assertException(InvalidInput::class, function () use ($token) {
            (new PasswordReset())->resetPassword($token, "another-password", "another-password");
        });
    }

    public function testRateLimitBlocksExtraTokens(): void
    {
        $user = User::by_name(self::USER_NAME);
        Ctx::$config->set(PasswordResetConfig::RATE_LIMIT_COUNT, 1);
        Ctx::$config->set(PasswordResetConfig::RATE_LIMIT_WINDOW, 60);
        $this->insertToken($user, "existing-token", date("Y-m-d H:i:s", time() + 3600));

        (new PasswordReset())->requestReset(self::USER_NAME);

        self::assertSame(1, Ctx::$database->get_one("SELECT COUNT(*) FROM password_reset_tokens"));
    }

    private function insertToken(User $user, string $token, string $expires): void
    {
        Ctx::$database->execute(
            "INSERT INTO password_reset_tokens (user_id, token_hash, expires, request_ip) VALUES (:user_id, :token_hash, :expires, :request_ip)",
            [
                "user_id" => $user->id,
                "token_hash" => self::hashToken($token),
                "expires" => $expires,
                "request_ip" => "127.0.0.1",
            ]
        );
    }

    private static function hashToken(string $token): string
    {
        $method = new \ReflectionMethod(PasswordReset::class, "hashToken");
        return $method->invoke(null, $token);
    }
}
