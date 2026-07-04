<?php

declare(strict_types=1);

namespace Shimmie2;

final class VulpstileInfo extends ExtensionInfo
{
    public const KEY = "vulpstile";

    public string $name = "Vulpstile";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::MODERATION;
    public string $description = "Adds Turnstile";
}
