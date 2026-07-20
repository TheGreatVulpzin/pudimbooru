<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/renderer.php';

final class SocialMeta extends Extension
{
    public const KEY = "social_meta";

    private ?Post $displayed_post = null;
    private ?SocialMetaPageData $page_data = null;
    private bool $metadata_rendered = false;
    private string $request_path = "";

    #[EventListener(priority: 1)]
    public function onPageRequestStart(PageRequestEvent $event): void
    {
        $this->displayed_post = null;
        $this->page_data = null;
        $this->metadata_rendered = false;
        $this->request_path = $event->path;

        if ($event->page_matches("social_meta/oembed", method: "GET")) {
            $this->serve_oembed($event);
        }
    }

    #[EventListener]
    public function onSocialMetaData(SocialMetaDataEvent $event): void
    {
        $this->page_data = $event->data;
        // The Home extension serializes its complete HTML during PageRequest,
        // before PageRequestEnd. Its head therefore needs to be ready now.
        if ($event->data->kind === "home") {
            $this->render_document($event->data);
        }
    }

    #[EventListener]
    public function onDisplayingPost(DisplayingPostEvent $event): void
    {
        $this->displayed_post = $event->image;
    }

    #[EventListener(priority: 99)]
    public function onPageRequestEnd(PageRequestEvent $event): void
    {
        if (Ctx::$page->mode !== PageMode::PAGE || Ctx::$page->code !== 200) {
            return;
        }

        if ($this->is_private_route($this->request_path)) {
            $this->add_robots_noindex();
            return;
        }

        $data = $this->displayed_post !== null
            ? $this->post_data($this->displayed_post)
            : ($this->page_data ?? $this->fallback_data());
        $this->render_document($data);

        if ($this->displayed_post !== null && Ctx::$config->get(SocialMetaConfig::DIRECT_MEDIA)) {
            $this->add_direct_media($this->displayed_post);
        }
    }

    private function post_data(Post $post): SocialMetaPageData
    {
        $site_name = $this->site_name();
        $values = $this->post_values($post, $site_name);
        $fallback = "Post #{$post->id}";
        $title = match(Ctx::$config->get(SocialMetaConfig::POST_TITLE_MODE)) {
            "title" => $values["title"] ?: $fallback,
            "id_tags" => $values["tags"] === "" ? $fallback : "{$fallback}: {$values["tags"]}",
            "tags" => $values["tags"] ?: $fallback,
            "custom" => $this->render_template(
                Ctx::$config->get(SocialMetaConfig::POST_TITLE_TEMPLATE),
                $values,
            ),
            default => $values["title"] === "" ? $fallback : "{$fallback}: {$values["title"]}",
        };

        $site_description = SocialMetaText::plain($this->config_string(SiteDescriptionConfig::DESCRIPTION));
        $description = match(Ctx::$config->get(SocialMetaConfig::POST_DESCRIPTION_MODE)) {
            "generated" => "Post #{$post->id} no {$site_name}.",
            "tags" => "",
            "site" => $site_description,
            "custom" => $this->render_template(
                Ctx::$config->get(SocialMetaConfig::POST_DESCRIPTION_TEMPLATE),
                $values,
            ),
            default => $values["description"],
        };

        return new SocialMetaPageData(
            kind: "post",
            title: $title ?: $fallback,
            canonical: make_link("post/view/{$post->id}"),
            description: $description,
            tags: $post->get_tag_array(),
            representative_post: $post,
            published_at: $post->posted,
        );
    }

    /**
     * @return array{id: string, title: string, tags: string, site: string, description: string, mime: string, filename: string}
     */
    private function post_values(Post $post, string $site_name): array
    {
        $info = send_event(new PostInfoGetEvent($post))->params;
        return [
            "id" => (string)$post->id,
            "title" => SocialMetaText::plain($info["title"] ?? ""),
            "tags" => implode(", ", $post->get_tag_array()),
            "site" => $site_name,
            // The Post Description extension exposes "None" when no description
            // was supplied. It is a UI fallback, not actual post metadata.
            "description" => SocialMetaText::optional($info["description"] ?? ""),
            "mime" => $post->get_mime()->base,
            "filename" => $post->filename,
        ];
    }

    private function fallback_data(): SocialMetaPageData
    {
        $site_name = $this->site_name();
        $title = trim(Ctx::$page->title) ?: $site_name;
        $description = trim($this->config_string(SiteDescriptionConfig::DESCRIPTION));
        if ($description === "") {
            $description = "Explore imagens, vídeos e outros conteúdos publicados no {$site_name}.";
        }

        return new SocialMetaPageData(
            kind: $this->request_path === "home" ? "home" : "page",
            title: $title,
            canonical: Url::current(),
            description: $description,
        );
    }

    private function normalize(SocialMetaPageData $data): SocialMetaDocument
    {
        $title = SocialMetaText::truncate(
            $data->title,
            $this->limit(SocialMetaConfig::TITLE_LIMIT, 80),
        );
        $description = SocialMetaText::truncate(
            $data->description,
            $this->limit(SocialMetaConfig::DESCRIPTION_LIMIT, 220),
        );
        $tags = array_values(array_filter(
            array_map(
                fn (string $tag): string => SocialMetaText::plain($tag),
                $data->tags,
            ),
            fn (string $tag): bool => $tag !== "",
        ));
        $tag_line = SocialMetaText::tags(
            $tags,
            $this->limit(SocialMetaConfig::TAGS_LIMIT, 180),
        );
        $description = trim(implode($this->description_tags_separator(), array_filter(
            [$description, $tag_line],
            fn (string $part): bool => $part !== "",
        )));
        if ($description === "") {
            $description = "Veja {$title} no {$this->site_name()}.";
        }

        [$image_url, $image_type, $image_width, $image_height] = $this->image($data->representative_post);
        $image_alt = $data->kind === "post"
            ? SocialMetaText::truncate($description, 200)
            : $this->default_image_alt();

        return new SocialMetaDocument(
            kind: $data->kind,
            title: $title,
            description: $description,
            tags: $tags,
            canonical: $data->canonical->asAbsolute(),
            image_url: $image_url,
            image_alt: $image_alt,
            image_type: $image_type,
            image_width: $image_width,
            image_height: $image_height,
            published_at: $data->published_at,
            indexable: $data->indexable,
        );
    }

    private function render_document(SocialMetaPageData $data): void
    {
        $document = $this->normalize($data);
        $site_name = SocialMetaText::plain($this->site_name());
        Ctx::$page->set_title(
            $document->title === $site_name
                ? $site_name
                : "{$document->title} | {$site_name}",
        );
        if (!$this->metadata_rendered) {
            $this->renderer($document->kind)->render($document);
            $this->metadata_rendered = true;
        }
    }

    /** @return array{Url, ?string, ?int, ?int} */
    private function image(?Post $post): array
    {
        if ($post === null) {
            $default_image = trim($this->config_string(SocialMetaConfig::DEFAULT_IMAGE));
            if ($default_image === "") {
                return [$this->favicon(), MimeType::PNG, null, null];
            }
            return [$this->absolute_url($default_image), null, null, null];
        }

        $mode = Ctx::$config->get(SocialMetaConfig::POST_IMAGE_MODE);
        $mime = $post->get_mime()->base;
        $compatible = in_array($mime, [MimeType::JPEG, MimeType::PNG, MimeType::GIF, MimeType::WEBP]);
        if ($mode === "original" || ($mode === "compatible_original" && $compatible)) {
            return [$post->get_media_link()->asAbsolute(), $mime, $post->width, $post->height];
        }

        [$width, $height] = $post->get_thumb_size();
        return [
            $post->get_thumb_link()->asAbsolute(),
            (new MimeType(Ctx::$config->get(ThumbnailConfig::MIME)))->base,
            $width,
            $height,
        ];
    }

    private function add_direct_media(Post $post): void
    {
        $mime = $post->get_mime()->base;
        $url = (string)$post->get_media_link()->asAbsolute();
        if (str_starts_with($mime, "video/")) {
            $this->property("og:video", $url);
            $this->property("og:video:type", $mime);
            $this->property("og:video:width", (string)$post->width);
            $this->property("og:video:height", (string)$post->height);
        } elseif (str_starts_with($mime, "audio/")) {
            $this->property("og:audio", $url);
            $this->property("og:audio:type", $mime);
        }
    }

    private function serve_oembed(PageRequestEvent $event): void
    {
        $url = trim($event->GET->get("url") ?? "");
        if (!$this->is_local_url($url)) {
            Ctx::$page->set_code(400);
            Ctx::$page->set_data(MimeType::JSON, json_encode(["error" => "URL inválida"], JSON_THROW_ON_ERROR));
            return;
        }

        $response = [
            "version" => "1.0",
            "type" => "link",
            "title" => SocialMetaText::truncate($event->GET->get("title") ?? $this->site_name(), 80),
            "provider_name" => $this->site_name(),
            "provider_url" => (string)make_link()->asAbsolute(),
            "cache_age" => 3600,
        ];
        $thumbnail = trim($event->GET->get("thumbnail_url") ?? "");
        $thumbnail_width = $event->GET->get("thumbnail_width");
        $thumbnail_height = $event->GET->get("thumbnail_height");
        if (
            $thumbnail !== ""
            && $thumbnail_width !== null
            && $thumbnail_height !== null
            && ctype_digit($thumbnail_width)
            && ctype_digit($thumbnail_height)
            && (int)$thumbnail_width > 0
            && (int)$thumbnail_height > 0
        ) {
            $response["thumbnail_url"] = $thumbnail;
            $response["thumbnail_width"] = (int)$thumbnail_width;
            $response["thumbnail_height"] = (int)$thumbnail_height;
        }

        Ctx::$page->add_http_header("Access-Control-Allow-Origin: *");
        Ctx::$page->set_data(
            MimeType::JSON,
            json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    private function renderer(string $kind): SocialMetaRenderer
    {
        return new SocialMetaRenderer(
            $this->site_name(),
            $this->config_string(SocialMetaConfig::LOCALE, "pt_BR"),
            trim($this->config_string(SocialMetaConfig::TWITTER_SITE)),
            $this->favicon(),
            $this->theme_color($kind),
        );
    }

    private function theme_color(string $kind): string
    {
        $key = match($kind) {
            "home" => SocialMetaConfig::THEME_COLOR_HOME,
            "post" => SocialMetaConfig::THEME_COLOR_POST,
            "wiki" => SocialMetaConfig::THEME_COLOR_WIKI,
            "help" => SocialMetaConfig::THEME_COLOR_HELP,
            "pool" => SocialMetaConfig::THEME_COLOR_POOL,
            "forum" => SocialMetaConfig::THEME_COLOR_FORUM,
            "profile" => SocialMetaConfig::THEME_COLOR_PROFILE,
            "search" => SocialMetaConfig::THEME_COLOR_SEARCH,
            default => SocialMetaConfig::THEME_COLOR_PAGE,
        };
        $default = $this->normalize_theme_color(
            $this->config_string(SocialMetaConfig::THEME_COLOR),
        ) ?? "#5865F2";
        return $this->normalize_theme_color($this->config_string($key)) ?? $default;
    }

    private function normalize_theme_color(string $color): ?string
    {
        $color = trim($color);
        if (!preg_match('/^#?[0-9A-F]{6}$/i', $color)) {
            return null;
        }
        return "#" . strtoupper(ltrim($color, "#"));
    }

    private function site_name(): string
    {
        $site_name = trim($this->config_string(SocialMetaConfig::SITE_NAME));
        return $site_name ?: Ctx::$config->get(SetupConfig::TITLE);
    }

    private function favicon(): Url
    {
        $favicon = trim($this->config_string(SocialMetaConfig::FAVICON));
        return $this->absolute_url($favicon === "" ? "/apple-touch-icon.png" : $favicon);
    }

    private function default_image_alt(): string
    {
        $template = trim($this->config_string(SocialMetaConfig::DEFAULT_IMAGE_ALT));
        if ($template === "") {
            return "";
        }
        return SocialMetaText::truncate(strtr($template, ["{site}" => $this->site_name()]), 200);
    }

    private function description_tags_separator(): string
    {
        $separator = trim($this->config_string(SocialMetaConfig::DESCRIPTION_TAGS_SEPARATOR, "|"));
        return $separator === "" ? " " : " {$separator} ";
    }

    private function config_string(string $key, string $fallback = ""): string
    {
        $value = Ctx::$config->get($key);
        return is_string($value) ? $value : $fallback;
    }

    private function absolute_url(string $url): Url
    {
        if (str_contains($url, "://")) {
            return Url::parse($url);
        }
        $path = str_starts_with($url, "/") ? $url : (string)Url::base() . "/" . $url;
        return Url::parse($path)->asAbsolute();
    }

    /** @param array<string, string> $values */
    private function render_template(string $template, array $values): string
    {
        $replacements = [];
        foreach ($values as $key => $value) {
            $replacements["{{$key}}"] = $value;
        }
        return SocialMetaText::plain(strtr($template, $replacements));
    }

    private function limit(string $key, int $fallback): int
    {
        $limit = Ctx::$config->get($key);
        return is_int($limit) && $limit > 3 ? $limit : $fallback;
    }

    private function is_local_url(string $url): bool
    {
        $root = (string)Url::parse((string)Url::base() . "/")->asAbsolute();
        $same_origin = $url !== ""
            && parse_url($url, PHP_URL_SCHEME) === parse_url($root, PHP_URL_SCHEME)
            && parse_url($url, PHP_URL_HOST) === parse_url($root, PHP_URL_HOST)
            && parse_url($url, PHP_URL_PORT) === parse_url($root, PHP_URL_PORT);
        if (!$same_origin) {
            return false;
        }

        $root_path = rtrim((string)parse_url($root, PHP_URL_PATH), "/");
        $url_path = (string)parse_url($url, PHP_URL_PATH);
        return $root_path === "" || $url_path === $root_path || str_starts_with($url_path, $root_path . "/");
    }

    private function is_private_route(string $path): bool
    {
        foreach ([
            "admin", "bulk_", "cron_uploader", "ext_manager", "ipban", "password_reset",
            "perm_manager", "pm", "setup", "trash", "upload", "user_admin", "user_config",
        ] as $prefix) {
            $literal_prefix = str_ends_with($prefix, "_") && str_starts_with($path, $prefix);
            if ($literal_prefix || $path === $prefix || str_starts_with($path, $prefix . "/")) {
                return true;
            }
        }
        return false;
    }

    private function add_robots_noindex(): void
    {
        Ctx::$page->add_html_header(\MicroHTML\META([
            "name" => "robots",
            "content" => "noindex, nofollow, noarchive",
        ]), 30);
    }

    private function property(string $property, string $content): void
    {
        Ctx::$page->add_html_header(\MicroHTML\META([
            "property" => $property,
            "content" => $content,
        ]), 30);
    }
}
