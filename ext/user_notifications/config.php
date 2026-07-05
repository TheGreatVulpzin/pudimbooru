<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserPasswordChangedEmailConfig extends MailTemplateConfigGroup
{
    public const KEY = "user_notifications";
    public ?string $title = "Password Changed Email";

    public function get_template_prefix(): string
    {
        return "user_password_changed_email";
    }

    public function get_default_subject(): string
    {
        return "Your Pudimbooru password was changed";
    }

    public function get_default_text_body(): string
    {
        return "Hello {{username}},\n\nYour password on {{site}} was changed.\n\nIf this was you, no action is needed.\n\nIf this was not you, contact the site staff immediately.";
    }

    public function get_default_html_body(): string
    {
        return "<p>Hello {{username}},</p>\n<p>Your password on {{site}} was changed.</p>\n<p>If this was you, no action is needed.</p>\n<p>If this was not you, contact the site staff immediately.</p>";
    }

    public function get_placeholders(): array
    {
        return ["{{username}}", "{{site}}", "{{actor}}"];
    }
}
