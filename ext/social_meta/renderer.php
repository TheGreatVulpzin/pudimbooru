<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{LINK, SCRIPT, rawHTML};

use Safe\DateTimeImmutable;

final class SocialMetaText
{
    public static function optional(string $text): string
    {
        $text = self::plain($text);
        return in_array(mb_strtolower($text), ["none", "null"]) ? "" : $text;
    }

    public static function plain(string $text): string
    {
        if ($text === "") {
            return "";
        }
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, "UTF-8");
        $text = self::from_html(send_event(new TextFormattingEvent($text))->formatted);
        // Remove unsupported or malformed BBCode controls while preserving
        // their visible inner text.
        $text = \Safe\preg_replace('/\[\/?[a-z][a-z0-9_]*(?:=[^\]]*)?\]/iu', ' ', $text);
        $text = str_replace('[*]', ' ', $text);
        $text = trim(\Safe\preg_replace('/\s+/u', ' ', $text));
        return \Safe\preg_replace('/\s+([,.;:!?])/u', '$1', $text);
    }

    public static function from_html(string $html): string
    {
        $html = \Safe\preg_replace(
            '!<\/?(?:br|p|div|li|ul|ol|blockquote|pre|code|h[1-6])\b[^>]*>!iu',
            ' ',
            $html,
        );
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, "UTF-8");
        return trim(\Safe\preg_replace('/\s+/u', ' ', $text));
    }

    public static function truncate(string $text, int $limit): string
    {
        $text = self::plain($text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $room = max(1, $limit - 1);
        $head = mb_substr($text, 0, $room);
        $minimum = max(1, (int) floor($room * 0.55));
        $cut = self::last_boundary($head, '/[.!?](?=\s|$)/u', true);
        if ($cut < $minimum) {
            $cut = self::last_boundary($head, '/[;:,](?=\s|$)/u', true);
        }
        if ($cut < $minimum) {
            $cut = self::last_boundary($head, '/\s/u', false);
        }
        if ($cut <= 0) {
            return "…";
        }

        return rtrim(mb_substr($head, 0, $cut), " \t\n\r\0\x0B,;:.!?") . "…";
    }

    /** @param string[] $tags */
    public static function tags(array $tags, int $limit): string
    {
        $prefix = "Tags: ";
        $result = $prefix;
        $included = 0;
        foreach ($tags as $tag) {
            $tag = trim(self::plain($tag));
            if ($tag === "") {
                continue;
            }
            $piece = $included === 0 ? $tag : ", {$tag}";
            $suffix = $included < count($tags) - 1 ? "…" : "";
            if (mb_strlen($result . $piece . $suffix) > $limit) {
                break;
            }
            $result .= $piece;
            $included++;
        }

        if ($included === 0) {
            return "";
        }
        if ($included < count($tags)) {
            $result .= "…";
        }
        return $result;
    }

    private static function last_boundary(string $text, string $pattern, bool $include_match): int
    {
        \Safe\preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
        $found = $matches[0] ?? [];
        if ($found === []) {
            return -1;
        }
        $last = $found[count($found) - 1];
        $prefix = substr($text, 0, $last[1]);
        return mb_strlen($prefix) + ($include_match ? mb_strlen($last[0]) : 0);
    }
}

final class SocialMetaRenderer
{
    public function __construct(
        private readonly string $site_name,
        private readonly string $locale,
        private readonly string $twitter_site,
        private readonly Url $favicon,
        private readonly string $theme_color,
    ) {
    }

    public function render(SocialMetaDocument $document): void
    {
        $canonical = $document->canonical;
        $image = $document->image_url;

        Ctx::$page->add_html_header(LINK(["rel" => "canonical", "href" => $canonical]), 20);
        Ctx::$page->add_html_header(LINK([
            "rel" => "apple-touch-icon",
            "sizes" => "180x180",
            "href" => $this->favicon,
        ]), 21);

        if (!$document->indexable) {
            $this->name("robots", "noindex, nofollow, noarchive");
            return;
        }

        Ctx::$page->add_html_header(LINK([
            "rel" => "alternate",
            "type" => "application/json+oembed",
            "href" => $this->oembed_url($document),
            "title" => $this->site_name,
        ]), 22);
        $this->name("theme-color", $this->theme_color);
        $this->name("description", $document->description);
        $this->property("og:title", $document->title);
        $this->property("og:type", $document->published_at === null ? "website" : "article");
        $this->property("og:url", (string)$canonical);
        $this->property("og:image", (string)$image);
        if (str_starts_with((string)$image, "https://")) {
            $this->property("og:image:secure_url", (string)$image);
        }
        if ($document->image_type !== null) {
            $this->property("og:image:type", $document->image_type);
        }
        if ($document->image_width !== null && $document->image_width > 0) {
            $this->property("og:image:width", (string)$document->image_width);
        }
        if ($document->image_height !== null && $document->image_height > 0) {
            $this->property("og:image:height", (string)$document->image_height);
        }
        $this->property("og:image:alt", $document->image_alt);
        $this->property("og:description", $document->description);
        $this->property("og:site_name", $this->site_name);
        $this->property("og:locale", $this->locale);
        if ($document->published_at !== null) {
            $this->property("article:published_time", $this->iso_date($document->published_at));
        }

        $this->name("twitter:card", $document->image_width === null ? "summary" : "summary_large_image");
        $this->name("twitter:title", $document->title);
        $this->name("twitter:description", $document->description);
        $this->name("twitter:image", (string)$image);
        $this->name("twitter:image:alt", $document->image_alt);
        if ($this->twitter_site !== "") {
            $this->name("twitter:site", $this->twitter_site);
        }

        $this->json_ld($document);
    }

    private function oembed_url(SocialMetaDocument $document): Url
    {
        $query = [
            "url" => (string)$document->canonical,
            "title" => $document->title,
        ];
        if ($document->image_width !== null && $document->image_height !== null) {
            $query["thumbnail_url"] = (string)$document->image_url;
            $query["thumbnail_width"] = (string)$document->image_width;
            $query["thumbnail_height"] = (string)$document->image_height;
        }
        return make_link("social_meta/oembed", $query)->asAbsolute();
    }

    private function json_ld(SocialMetaDocument $document): void
    {
        $data = [
            "@context" => "https://schema.org",
            "@type" => $this->schema_type($document->kind),
            "name" => $document->title,
            "description" => $document->description,
            "url" => (string)$document->canonical,
            "image" => (string)$document->image_url,
            "isPartOf" => [
                "@type" => "WebSite",
                "name" => $this->site_name,
                "url" => (string)make_link()->asAbsolute(),
            ],
        ];
        if ($document->published_at !== null) {
            $data["datePublished"] = $this->iso_date($document->published_at);
        }
        if ($document->tags !== []) {
            $data["keywords"] = $document->tags;
        }
        $json = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        );
        Ctx::$page->add_html_header(SCRIPT(["type" => "application/ld+json"], rawHTML($json)), 40);
    }

    private function schema_type(string $kind): string
    {
        return match($kind) {
            "home" => "WebSite",
            "post" => "MediaObject",
            "wiki", "help", "forum" => "Article",
            "pool", "search" => "CollectionPage",
            "profile" => "ProfilePage",
            default => "WebPage",
        };
    }

    private function iso_date(string $date): string
    {
        try {
            return (new DateTimeImmutable($date))->format(DATE_ATOM);
        } catch (\Exception) {
            return $date;
        }
    }

    private function property(string $property, string $content): void
    {
        if ($content !== "") {
            $this->meta("property", $property, $content);
        }
    }

    private function name(string $name, string $content): void
    {
        if ($content !== "") {
            $this->meta("name", $name, $content);
        }
    }

    private function meta(string $attribute, string $key, string $content): void
    {
        $key = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
        $content = htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
        Ctx::$page->add_html_header(rawHTML("<meta {$attribute}='{$key}' content='{$content}' />"), 30);
    }
}
