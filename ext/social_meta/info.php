<?php

declare(strict_types=1);

namespace Shimmie2;

final class SocialMetaInfo extends ExtensionInfo
{
    public const KEY = "social_meta";

    public string $name = "Metadados sociais e SEO";
    public array $authors = self::SHISH_AUTHOR;
    public array $dependencies = [ViewPostInfo::KEY];
    public ExtensionVisibility $visibility = ExtensionVisibility::ADMIN;
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Gera metadados sociais modulares, oEmbed e URLs canônicas";
    public ?string $documentation =
        "Gera previews em português para posts, buscas e páginas de extensões. Inclui Open Graph, X/Twitter, oEmbed, dados estruturados, URL canônica e metadados de mídia, sem inferir autoria.";
}
