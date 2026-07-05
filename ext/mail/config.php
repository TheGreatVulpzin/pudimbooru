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

    #[ConfigMeta("Test recipient", ConfigType::STRING, advanced: true, permission: MailPermission::MANAGE_MAIL_SETTINGS)]
    public const TEST_RECIPIENT = "mail_test_recipient";
}
