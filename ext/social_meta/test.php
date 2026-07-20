<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;

final class SocialMetaTest extends ShimmiePHPUnitTestCase
{
    public function testPostMetadata(): void
    {
        self::log_in_as_user();
        $post_id = $this->create_post("tests/pbx_screenshot.jpg", "gato_azul teste");

        Ctx::$config->set(SetupConfig::TITLE, "Pudimbooru");
        self::get_page("post/view/$post_id");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());

        self::assertStringContainsString("<link rel='canonical' href='", $headers);
        self::assertStringContainsString("/post/view/$post_id' />", $headers);
        self::assertStringContainsString("<meta property='og:title' content='Post #$post_id' />", $headers);
        self::assertStringContainsString("<meta property='og:type' content='article' />", $headers);
        self::assertStringContainsString("<meta property='og:description' content='Tags: gato_azul, teste' />", $headers);
        self::assertStringContainsString("<meta property='og:image:type' content='image/jpeg' />", $headers);
        self::assertStringContainsString("<meta property='og:image:width' content='640' />", $headers);
        self::assertStringContainsString("<meta property='og:image:height' content='480' />", $headers);
        self::assertStringContainsString("<meta property='og:locale' content='pt_BR' />", $headers);
        self::assertStringContainsString("<meta name='theme-color' content='#5865F2' />", $headers);
        self::assertStringContainsString("<meta name='twitter:card' content='summary_large_image' />", $headers);
        self::assertStringContainsString("<meta name='twitter:image' content='", $headers);
        self::assertStringNotContainsString("property='twitter:", $headers);
        self::assertStringContainsString("application/json+oembed", $headers);
        self::assertStringContainsString("application/ld+json", $headers);
        self::assertStringContainsString("property='article:published_time'", $headers);
        self::assertStringNotContainsString("author", $headers);
        self::assertStringNotContainsString("application/activity+json", $headers);
        self::assertSame("Post #$post_id | Pudimbooru", Ctx::$page->title);
        self::assertSame(1, substr_count($headers, "property='og:title'"));
        self::assertSame(1, substr_count($headers, "name='description'"));
        self::assertSame(1, substr_count($headers, "rel='canonical'"));
    }

    public function testGeneralPageMetadataUsesSiteDescription(): void
    {
        Ctx::$config->set(SetupConfig::TITLE, "Pudimbooru");
        Ctx::$config->set(SiteDescriptionConfig::DESCRIPTION, "Galeria brasileira de imagens.");
        Ctx::$config->set(SocialMetaConfig::DEFAULT_IMAGE_ALT, "Logo oficial de {site}.");

        self::get_page("post/list");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());

        self::assertStringContainsString("<meta name='description' content='Galeria brasileira de imagens.' />", $headers);
        self::assertStringContainsString("<meta property='og:site_name' content='Pudimbooru' />", $headers);
        self::assertStringContainsString("<meta property='og:locale' content='pt_BR' />", $headers);
        self::assertStringContainsString("<meta property='og:image:alt' content='Logo oficial de Pudimbooru.' />", $headers);
        self::assertStringContainsString("<meta name='twitter:description' content='Galeria brasileira de imagens.' />", $headers);
    }

    public function testThemeColorUsesKindOverrideAndSafeFallback(): void
    {
        Ctx::$config->set(SocialMetaConfig::THEME_COLOR, "#123456");
        Ctx::$config->set(SocialMetaConfig::THEME_COLOR_HELP, "abcdef");

        self::get_page("help/search");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());
        self::assertStringContainsString("<meta name='theme-color' content='#ABCDEF' />", $headers);

        Ctx::$config->set(SocialMetaConfig::THEME_COLOR_HELP, "cor-inválida");
        self::get_page("help/search");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());
        self::assertStringContainsString("<meta name='theme-color' content='#123456' />", $headers);

        Ctx::$config->set(SocialMetaConfig::THEME_COLOR, "também-inválida");
        self::get_page("help/search");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());
        self::assertStringContainsString("<meta name='theme-color' content='#5865F2' />", $headers);
    }

    public function testCustomTemplates(): void
    {
        self::log_in_as_user();
        $post_id = $this->create_post("tests/pbx_screenshot.jpg", "gato_azul teste");
        Ctx::$config->set(SocialMetaConfig::POST_TITLE_MODE, "custom");
        Ctx::$config->set(SocialMetaConfig::POST_TITLE_TEMPLATE, "Coleção engraçada #{id}: {tags}");
        Ctx::$config->set(SocialMetaConfig::POST_DESCRIPTION_MODE, "custom");
        Ctx::$config->set(SocialMetaConfig::POST_DESCRIPTION_TEMPLATE, "Veja {filename} no {site}");

        self::get_page("post/view/$post_id");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());

        self::assertStringContainsString("<meta property='og:title' content='Coleção engraçada #$post_id: gato_azul, teste' />", $headers);
        self::assertStringNotContainsString("&ccedil;", $headers);
        self::assertStringContainsString("<meta property='og:description' content='Veja pbx_screenshot.jpg no Shimmie | Tags: gato_azul, teste' />", $headers);
    }

    public function testPostTitleAndDescriptionIgnoreBbcode(): void
    {
        self::log_in_as_user();
        $post_id = $this->create_post("tests/pbx_screenshot.jpg", "teste");
        $post = Post::by_id_ex($post_id);
        send_event(new PostTitleSetEvent($post, "[b]Título[/b] com [i]BBCode[/i]"));
        send_event(new PostDescriptionSetEvent($post_id, "Uma [url=https://example.com]descrição[/url] [quote]citada[/quote]."));

        Ctx::$config->set(SetupConfig::TITLE, "Pudimbooru");
        self::get_page("post/view/$post_id");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());

        self::assertStringContainsString(
            "<meta property='og:title' content='Post #$post_id: Título com BBCode' />",
            $headers,
        );
        self::assertStringContainsString(
            "<meta property='og:description' content='Uma descrição citada. | Tags: teste' />",
            $headers,
        );
        self::assertStringNotContainsString("[b]", $headers);
        self::assertStringNotContainsString("[url=", $headers);
    }

    public function testNaturalTruncationAndWholeTags(): void
    {
        $text = "Primeira frase completa. Segunda frase que não deve aparecer inteira.";
        self::assertSame("Primeira frase completa…", SocialMetaText::truncate($text, 38));
        self::assertSame(
            "Tags: artist:daisylenerd…",
            SocialMetaText::tags(["artist:daisylenerd", "character:teemo"], 30),
        );
        self::assertSame("", SocialMetaText::optional("None"));
        self::assertSame("", SocialMetaText::optional(" null "));
        self::assertSame("Descrição normal", SocialMetaText::optional("Descrição normal"));
        self::assertSame(
            "Olá mundo citado segredo x < y fim",
            SocialMetaText::plain(
                "[b]Olá[/b] [url=https://example.com]mundo[/url] [quote]citado[/quote] "
                . "[spoiler]segredo[/spoiler] [code]x < y[/code] "
                . "[img]https://example.com/image.png[/img] [color=red]fim[/color]",
            ),
        );
        self::assertSame("sem fechar", SocialMetaText::plain("[b]sem fechar"));
        self::assertSame("Coleção", SocialMetaText::plain("Cole&ccedil;&atilde;o"));
    }

    public function testOembedHasProviderButNoAuthor(): void
    {
        Ctx::$config->set(SetupConfig::TITLE, "Pudimbooru");
        self::get_page("social_meta/oembed", [
            "url" => (string)make_link("post/list")->asAbsolute(),
            "title" => "Posts recentes",
            "thumbnail_url" => (string)make_link("thumb/incompleta.jpg")->asAbsolute(),
        ]);

        $payload = json_decode(Ctx::$page->data, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame("Pudimbooru", $payload["provider_name"]);
        self::assertSame("Posts recentes", $payload["title"]);
        self::assertArrayNotHasKey("author_name", $payload);
        self::assertArrayNotHasKey("author_url", $payload);
        self::assertArrayNotHasKey("thumbnail_url", $payload);
    }

    public function testOembedRejectsExternalUrl(): void
    {
        self::get_page("social_meta/oembed", [
            "url" => "https://example.com/post/1",
            "title" => "Externo",
        ]);

        self::assertSame(400, Ctx::$page->code);
    }

    public function testOembedOnlyIncludesCompleteThumbnail(): void
    {
        self::get_page("social_meta/oembed", [
            "url" => (string)make_link("post/view/1")->asAbsolute(),
            "title" => "Post #1",
            "thumbnail_url" => (string)make_link("thumb/1.jpg")->asAbsolute(),
            "thumbnail_width" => "320",
            "thumbnail_height" => "200",
        ]);

        $payload = json_decode(Ctx::$page->data, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(320, $payload["thumbnail_width"]);
        self::assertSame(200, $payload["thumbnail_height"]);
        self::assertArrayHasKey("thumbnail_url", $payload);
    }

    public function testHomeAndHelpMetadataAreRendered(): void
    {
        Ctx::$config->set(SetupConfig::TITLE, "Pudimbooru");
        Ctx::$config->set(SetupConfig::HTML_LANGUAGE, "pt-BR");
        self::get_page("home");
        self::assertStringContainsString("<html lang='pt-BR'>", Ctx::$page->data);
        self::assertStringContainsString("property='og:title' content='Pudimbooru'", Ctx::$page->data);
        self::assertSame(1, substr_count(Ctx::$page->data, "property='og:title'"));

        self::get_page("help/search");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());
        self::assertStringContainsString("property='og:title' content='Ajuda: Pesquisar'", $headers);
        self::assertStringContainsString("property='og:description'", $headers);
        self::assertSame("Ajuda: Pesquisar | Pudimbooru", Ctx::$page->title);
    }

    public function testPrivatePagesDoNotExposeSocialPreview(): void
    {
        self::get_page("user_admin/login");
        $headers = (string)emptyHTML(...Ctx::$page->get_all_html_headers());

        self::assertStringContainsString("content='noindex, nofollow, noarchive'", $headers);
        self::assertStringNotContainsString("property='og:title'", $headers);
    }
}
