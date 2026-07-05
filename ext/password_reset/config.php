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

final class PasswordResetEmailConfig extends ConfigGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Reset Email";

    #[ConfigMeta("Email subject", ConfigType::STRING, default: "Reset your Pudimbooru password")]
    public const SUBJECT = "password_reset_email_subject";

    #[ConfigMeta("Plain text email", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: "Hello \$usuario,\n\nUse this link to reset your password:\n\n\$link\n\nIf you did not request this, ignore this email.")]
    public const TEXT_BODY = "password_reset_email_text_body";

    #[ConfigMeta("HTML email", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: "<p>Hello \$usuario,</p>\n<p>Use this link to reset your password:</p>\n<p><a href=\"\$link\">Reset password</a></p>\n<p>If you did not request this, ignore this email.</p>", help: "Available placeholders: \$link, \$usuario, \$site")]
    public const HTML_BODY = "password_reset_email_html_body";
}
