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

final class UserEmailVerificationEmailConfig extends MailTemplateConfigGroup
{
    public const KEY = "user_notifications";
    public ?string $title = "Email Verification Email";

    public function get_template_prefix(): string
    {
        return "user_email_verification_email";
    }

    public function get_default_subject(): string
    {
        return "Verify your Pudimbooru email";
    }

    public function get_default_text_body(): string
    {
        return "Hello {{username}},\n\nUse this link to verify your email on {{site}}:\n\n{{verification_link}}\n\nIf you did not request this, ignore this email.";
    }

    public function get_default_html_body(): string
    {
        return "<p>Hello {{username}},</p>\n<p>Use this link to verify your email on {{site}}:</p>\n<p><a href=\"{{verification_link}}\">Verify email</a></p>\n<p>If you did not request this, ignore this email.</p>";
    }

    public function get_placeholders(): array
    {
        return ["{{username}}", "{{site}}", "{{verification_link}}"];
    }
}

final class UserEmailChangedOldEmailConfig extends MailTemplateConfigGroup
{
    public const KEY = "user_notifications";
    public ?string $title = "Email Changed Old Address Email";

    public function get_template_prefix(): string
    {
        return "user_email_changed_old_email";
    }

    public function get_default_subject(): string
    {
        return "Your Pudimbooru email address was changed";
    }

    public function get_default_text_body(): string
    {
        return "Hello {{username}},\n\nThe email address for your account on {{site}} was changed from {{old_email}} to {{new_email}}.\n\nIf this was not you, contact the site staff immediately.";
    }

    public function get_default_html_body(): string
    {
        return "<p>Hello {{username}},</p>\n<p>The email address for your account on {{site}} was changed from {{old_email}} to {{new_email}}.</p>\n<p>If this was not you, contact the site staff immediately.</p>";
    }

    public function get_placeholders(): array
    {
        return ["{{username}}", "{{site}}", "{{old_email}}", "{{new_email}}", "{{actor}}"];
    }
}
