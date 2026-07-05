<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetInfo extends ExtensionInfo
{
    public const KEY = "password_reset";

    public string $name = "Password Reset";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Allows users to reset their password by email";
    public array $dependencies = [MailInfo::KEY];
}
