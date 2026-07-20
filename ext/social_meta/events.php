<?php

declare(strict_types=1);

namespace Shimmie2;

/** Semantic page data. Rendering details belong to SocialMetaRenderer. */
final class SocialMetaPageData
{
    /** @param string[] $tags */
    public function __construct(
        public string $kind,
        public string $title,
        public Url $canonical,
        public string $description = "",
        public array $tags = [],
        public ?Post $representative_post = null,
        public ?string $published_at = null,
        public bool $indexable = true,
    ) {
    }
}

final class SocialMetaDataEvent extends Event
{
    public function __construct(public readonly SocialMetaPageData $data)
    {
        parent::__construct();
    }
}

/** Normalized document consumed by every social metadata renderer. */
final class SocialMetaDocument
{
    /** @param string[] $tags */
    public function __construct(
        public string $kind,
        public string $title,
        public string $description,
        public array $tags,
        public Url $canonical,
        public Url $image_url,
        public string $image_alt,
        public ?string $image_type = null,
        public ?int $image_width = null,
        public ?int $image_height = null,
        public ?string $published_at = null,
        public bool $indexable = true,
    ) {
    }
}
