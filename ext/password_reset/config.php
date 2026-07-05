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

final class PasswordResetEmailConfig extends MailTemplateConfigGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Reset Email";

    public function get_template_prefix(): string
    {
        return "password_reset_email";
    }

    public function get_default_subject(): string
    {
        return "Reset your Pudimbooru password";
    }

    public function get_default_text_body(): string
    {
        return "Hello \$usuario,\n\nUse this link to reset your password:\n\n\$link\n\nIf you did not request this, ignore this email.";
    }

    public function get_default_html_body(): string
    {
        return "<p>Hello \$usuario,</p>\n<p>Use this link to reset your password:</p>\n<p><a href=\"\$link\">Reset password</a></p>\n<p>If you did not request this, ignore this email.</p>";
    }

    public function get_placeholders(): array
    {
        return ["\$link", "\$usuario", "\$username", "\$site"];
    }
}
