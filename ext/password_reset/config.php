<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetConfig extends ConfigGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Reset";

    #[ConfigMeta("Token lifetime (minutes)", ConfigType::INT, default: 60, advanced: true)]
    public const TOKEN_LIFETIME = "password_reset_token_lifetime";

    #[ConfigMeta("Max reset requests", ConfigType::INT, default: 3, help: "Maximum password reset emails per account or IP inside the rate limit window. Use 0 to disable.")]
    public const RATE_LIMIT_COUNT = "password_reset_rate_limit_count";

    #[ConfigMeta("Rate limit window (minutes)", ConfigType::INT, default: 60)]
    public const RATE_LIMIT_WINDOW = "password_reset_rate_limit_window";
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

final class PasswordChangedEmailConfig extends MailTemplateConfigGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Changed Email";

    public function get_template_prefix(): string
    {
        return "password_changed_email";
    }

    public function get_default_subject(): string
    {
        return "Your Pudimbooru password was changed";
    }

    public function get_default_text_body(): string
    {
        return "Hello \$usuario,\n\nYour password on \$site was changed.\n\nIf this was you, no action is needed.\n\nIf this was not you, contact the site staff immediately.";
    }

    public function get_default_html_body(): string
    {
        return "<p>Hello \$usuario,</p>\n<p>Your password on \$site was changed.</p>\n<p>If this was you, no action is needed.</p>\n<p>If this was not you, contact the site staff immediately.</p>";
    }

    public function get_placeholders(): array
    {
        return ["\$usuario", "\$username", "\$site"];
    }
}
