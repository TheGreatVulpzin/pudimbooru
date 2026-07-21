<?php

declare(strict_types=1);

namespace Shimmie2;

final class SocialMetaConfig extends ConfigGroup
{
    public const KEY = "social_meta";
    public ?string $title = "Metadados sociais e SEO";

    #[ConfigMeta("Nome do site", ConfigType::STRING, help: "Deixe vazio para usar o título geral do site.")]
    public const SITE_NAME = "social_meta_site_name";

    #[ConfigMeta("Localidade Open Graph", ConfigType::STRING, default: "pt_BR", advanced: true)]
    public const LOCALE = "social_meta_locale";

    #[ConfigMeta("Título do post", ConfigType::STRING, default: "title_id", options: [
        "Título e ID" => "title_id",
        "Título; fallback no ID" => "title",
        "ID e tags" => "id_tags",
        "Somente tags" => "tags",
        "Template personalizado" => "custom",
    ])]
    public const POST_TITLE_MODE = "social_meta_post_title_mode";

    #[ConfigMeta(
        "Template de título",
        ConfigType::STRING,
        default: "Post #{id}: {title}",
        help: "Usado no modo personalizado. Campos: {id}, {title}, {tags}, {site}, {description}, {mime}, {filename}.",
        advanced: true,
    )]
    public const POST_TITLE_TEMPLATE = "social_meta_post_title_template";

    #[ConfigMeta("Descrição do post", ConfigType::STRING, default: "description_tags", options: [
        "Descrição; fallback em tags" => "description_tags",
        "Resumo gerado com ID e tags" => "generated",
        "Somente tags" => "tags",
        "Descrição geral do site" => "site",
        "Template personalizado" => "custom",
    ])]
    public const POST_DESCRIPTION_MODE = "social_meta_post_description_mode";

    #[ConfigMeta(
        "Template de descrição",
        ConfigType::STRING,
        default: "Post #{id} no {site}.",
        help: "Usado no modo personalizado. Campos: {id}, {title}, {tags}, {site}, {description}, {mime}, {filename}.",
        advanced: true,
    )]
    public const POST_DESCRIPTION_TEMPLATE = "social_meta_post_description_template";

    #[ConfigMeta("Imagem do embed", ConfigType::STRING, default: "compatible_original", options: [
        "Original compatível; senão thumbnail" => "compatible_original",
        "Sempre arquivo original" => "original",
        "Sempre thumbnail" => "thumbnail",
    ])]
    public const POST_IMAGE_MODE = "social_meta_post_image_mode";

    #[ConfigMeta("Anexar áudio/vídeo direto no Open Graph", ConfigType::BOOL, default: true, advanced: true)]
    public const DIRECT_MEDIA = "social_meta_direct_media";

    #[ConfigMeta("Imagem padrão das outras páginas", ConfigType::STRING, help: "URL absoluta ou caminho do site. Vazio usa apple-touch-icon.png.")]
    public const DEFAULT_IMAGE = "social_meta_default_image";

    #[ConfigMeta(
        "Texto alternativo da imagem padrão",
        ConfigType::STRING,
        default: "Identidade visual de {site}.",
        help: "Usado em páginas sem mídia própria. Campo disponível: {site}.",
    )]
    public const DEFAULT_IMAGE_ALT = "social_meta_default_image_alt";

    #[ConfigMeta("Favicon dos previews", ConfigType::STRING, help: "URL absoluta ou caminho do site. Vazio usa apple-touch-icon.png.")]
    public const FAVICON = "social_meta_favicon";

    #[ConfigMeta(
        "Cor padrão dos embeds",
        ConfigType::STRING,
        default: "#5865F2",
        help: "Cor hexadecimal da lateral no Discord. Exemplo: #D4A017.",
    )]
    public const THEME_COLOR = "social_meta_theme_color";

    #[ConfigMeta("Cor dos posts", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_POST = "social_meta_theme_color_post";

    #[ConfigMeta("Cor da Wiki", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_WIKI = "social_meta_theme_color_wiki";

    #[ConfigMeta("Cor da Ajuda", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_HELP = "social_meta_theme_color_help";

    #[ConfigMeta("Cor das coleções", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_POOL = "social_meta_theme_color_pool";

    #[ConfigMeta("Cor do fórum", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_FORUM = "social_meta_theme_color_forum";

    #[ConfigMeta("Cor dos perfis", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_PROFILE = "social_meta_theme_color_profile";

    #[ConfigMeta("Cor das buscas e listagens", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_SEARCH = "social_meta_theme_color_search";

    #[ConfigMeta("Cor da página inicial", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_HOME = "social_meta_theme_color_home";

    #[ConfigMeta("Cor das outras páginas", ConfigType::STRING, help: "Vazio usa a cor padrão.", advanced: true)]
    public const THEME_COLOR_PAGE = "social_meta_theme_color_page";

    #[ConfigMeta("Conta do site no X/Twitter", ConfigType::STRING, help: "Exemplo: @pudimbooru", advanced: true)]
    public const TWITTER_SITE = "social_meta_twitter_site";

    #[ConfigMeta("Limite do título", ConfigType::INT, default: 80, advanced: true)]
    public const TITLE_LIMIT = "social_meta_title_limit";

    #[ConfigMeta("Limite da descrição", ConfigType::INT, default: 220, advanced: true)]
    public const DESCRIPTION_LIMIT = "social_meta_description_limit";

    #[ConfigMeta("Limite da linha de tags", ConfigType::INT, default: 180, advanced: true)]
    public const TAGS_LIMIT = "social_meta_tags_limit";

    #[ConfigMeta(
        "Separador entre descrição e tags",
        ConfigType::STRING,
        default: "|",
        help: "O espaçamento ao redor é aplicado automaticamente. Vazio usa apenas um espaço.",
        advanced: true,
    )]
    public const DESCRIPTION_TAGS_SEPARATOR = "social_meta_description_tags_separator";
}
