<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetConfig extends ConfigGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Reset";

    #[ConfigMeta("Token lifetime (minutes)", ConfigType::INT, default: 60, advanced: true)]
    public const TOKEN_LIFETIME = "password_reset_token_lifetime";
}
