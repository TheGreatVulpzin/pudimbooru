<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailConfig extends ConfigGroup
{
    public const KEY = "mail";
    public ?string $title = "Mail";

    #[ConfigMeta("SMTP host", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const SMTP_HOST = "mail_smtp_host";

    #[ConfigMeta("SMTP port", ConfigType::INT, default: 587, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const SMTP_PORT = "mail_smtp_port";

    #[ConfigMeta("SMTP username", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const SMTP_USERNAME = "mail_smtp_username";

    #[ConfigMeta("SMTP password", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const SMTP_PASSWORD = "mail_smtp_password";

    #[ConfigMeta("SMTP encryption", ConfigType::STRING, default: "tls", options: [
        "STARTTLS" => "tls",
        "SSL / SMTPS" => "ssl",
        "None" => "none",
    ], permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const SMTP_ENCRYPTION = "mail_smtp_encryption";

    #[ConfigMeta("From address", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const FROM_ADDRESS = "mail_from_address";

    #[ConfigMeta("From name", ConfigType::STRING, default: "Pudimbooru", permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const FROM_NAME = "mail_from_name";

    #[ConfigMeta("Reply-To address", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const REPLY_TO_ADDRESS = "mail_reply_to_address";
}

abstract class MailToolConfigGroup extends ConfigGroup
{
    abstract public function get_action_path(): string;

    abstract public function get_submit_label(): string;
}

final class MailTestToolConfig extends MailToolConfigGroup
{
    public const KEY = "mail";
    public ?string $title = "Mail Test";
    public ?int $position = 10;

    #[ConfigMeta("Recipient", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const TEST_RECIPIENT = "mail_test_recipient";

    public function get_action_path(): string
    {
        return "mail/test";
    }

    public function get_submit_label(): string
    {
        return "Send test email";
    }
}

final class MailSmtpCheckToolConfig extends MailToolConfigGroup
{
    public const KEY = "mail";
    public ?string $title = "SMTP Check";
    public ?int $position = 11;

    public function get_config_fields(): array
    {
        return [];
    }

    public function get_action_path(): string
    {
        return "mail/check_smtp";
    }

    public function get_submit_label(): string
    {
        return "Check SMTP";
    }
}

final class MailTemplateTestToolConfig extends MailToolConfigGroup
{
    public const KEY = "mail";
    public ?string $title = "Template Test";
    public ?int $position = 12;

    #[ConfigMeta("Recipient", ConfigType::STRING, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const RECIPIENT = "mail_template_test_recipient";

    #[ConfigMeta("Template", ConfigType::STRING, options: self::class . "::get_template_options", permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const TEMPLATE = "mail_template_test_template";

    /**
     * @return array<string, string>
     */
    public static function get_template_options(): array
    {
        $options = [];
        foreach (MailTemplateConfigGroup::get_subclasses() as $class) {
            $group = $class->newInstance();
            if ($group::is_enabled()) {
                $options[$group->get_title()] = $group->get_template_prefix();
            }
        }
        return $options;
    }

    public function get_action_path(): string
    {
        return "mail/test_template";
    }

    public function get_submit_label(): string
    {
        return "Send template test";
    }
}

abstract class MailTemplateConfigGroup extends ConfigGroup
{
    abstract public function get_template_prefix(): string;

    abstract public function get_default_subject(): string;

    abstract public function get_default_text_body(): string;

    abstract public function get_default_html_body(): string;

    /**
     * @return list<string>
     */
    abstract public function get_placeholders(): array;

    public function get_from_address_key(): string
    {
        return $this->get_template_prefix() . "_from_address";
    }

    public function get_from_name_key(): string
    {
        return $this->get_template_prefix() . "_from_name";
    }

    public function get_reply_to_address_key(): string
    {
        return $this->get_template_prefix() . "_reply_to_address";
    }

    public function get_subject_key(): string
    {
        return $this->get_template_prefix() . "_subject";
    }

    public function get_text_body_key(): string
    {
        return $this->get_template_prefix() . "_text_body";
    }

    public function get_html_body_key(): string
    {
        return $this->get_template_prefix() . "_html_body";
    }

    /**
     * @return array<string, ConfigMeta>
     */
    public function get_config_fields(): array
    {
        $placeholders = implode(", ", $this->get_placeholders());

        return [
            $this->get_from_address_key() => new ConfigMeta("From address", ConfigType::STRING, help: "Leave blank to use the global mail sender."),
            $this->get_from_name_key() => new ConfigMeta("From name", ConfigType::STRING, help: "Leave blank to use the global mail sender name."),
            $this->get_reply_to_address_key() => new ConfigMeta("Reply-To address", ConfigType::STRING, help: "Leave blank to use the global Reply-To address."),
            $this->get_subject_key() => new ConfigMeta("Email subject", ConfigType::STRING, default: $this->get_default_subject()),
            $this->get_text_body_key() => new ConfigMeta("Plain text email", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: $this->get_default_text_body()),
            $this->get_html_body_key() => new ConfigMeta("HTML email", ConfigType::STRING, input: ConfigInput::TEXTAREA, default: $this->get_default_html_body(), help: "Available placeholders: $placeholders"),
        ];
    }
}
