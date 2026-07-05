<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailPermission extends PermissionGroup
{
    public const KEY = "mail";
    public ?string $title = "Mail";

    #[PermissionMeta("Manage mail settings")]
    public const MANAGE_MAIL_SETTINGS = "manage_mail_settings";
}
