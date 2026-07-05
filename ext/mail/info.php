<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailInfo extends ExtensionInfo
{
    public const KEY = "mail";

    public string $name = "Mail";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Provides reusable SMTP email delivery for other extensions";
}
