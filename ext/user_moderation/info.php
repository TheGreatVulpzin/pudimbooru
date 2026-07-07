<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationInfo extends ExtensionInfo
{
    public const KEY = "user_moderation";

    public string $name = "User Moderation";
    public array $authors = self::SHISH_AUTHOR;
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Temporarily restrict users by moving them into moderation classes";
    public array $dependencies = ["user", "ipban"];
}
