<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserNotificationsInfo extends ExtensionInfo
{
    public const KEY = "user_notifications";

    public string $name = "User Notifications";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Sends account notification emails to users";
    public array $dependencies = [MailInfo::KEY];
}
