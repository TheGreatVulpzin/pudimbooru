<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Brazilian Portuguese UI strings for the pudimbooru theme.
 */
final class PudimbooruLocale
{
    /** @var array<string, string> */
    private const NAV = [
        "Posts" => "Posts",
        "All" => "Todos",
        "Tags" => "Tags",
        "Map" => "Mapa",
        "Alphabetic" => "Alfabético",
        "Popularity" => "Popularidade",
        "Comments" => "Comentários",
        "Help" => "Ajuda",
        "Wiki" => "Wiki",
        "Rules" => "Regras",
        "Page list" => "Lista de páginas",
        "Upload" => "Enviar",
        "Guidelines" => "Diretrizes",
        "Account" => "Conta",
        "Log Out" => "Sair",
        "User List" => "Lista de usuários",
        "Board Config" => "Configuração",
        "Board Admin" => "Administração",
        "System" => "Sistema",
        "Stats" => "Estatísticas",
        "Top 100" => "Top 100",
        "Forum" => "Fórum",
        "Pools" => "Coleções",
        "List" => "Lista",
        "Create" => "Criar",
        "Changes" => "Alterações",
        "Notes" => "Notas",
        "Requests" => "Pedidos",
        "Updates" => "Atualizações",
        "Search" => "Buscar",
        "Information" => "Informações",
        "Login" => "Entrar",
        "User Links" => "Links do usuário",
        "Signup" => "Cadastro",
        "Upload Status" => "Status do envio",
        "No images uploaded" => "Nenhuma imagem enviada",
        "Bookmarklets" => "Bookmarklets",
        "My Favorites" => "Meus favoritos",
        "Random Post" => "Post aleatório",
        "Shuffle" => "Embaralhar",
        "Feed" => "Feed",
        "Trash" => "Lixeira",
        "Pending Approval" => "Aprovação pendente",
        "Reported Posts" => "Posts denunciados",
        "Tag Changes" => "Alterações de tags",
        "Source Changes" => "Alterações de fonte",
        "Aliases" => "Aliases",
        "Tag Categories" => "Categorias de tags",
        "Popular Posts" => "Posts populares",
        "Event Log" => "Log de eventos",
        "IP Bans" => "Bans de IP",
        "Post Bans" => "Bans de posts",
        "Extension Manager" => "Gerenciador de extensões",
        "Permission Manager" => "Gerenciador de permissões",
        "Blocks Editor" => "Editor de blocos",
        "Blotter Editor" => "Editor de avisos",
        "Tips Editor" => "Editor de dicas",
        "Auto-Tag" => "Auto-tag",
        "UnTags" => "UnTags",
        "Cron Upload" => "Envio cron",
        "User Options" => "Opções do usuário",
        "System Info" => "Info do sistema",
        "My Profile" => "Meu perfil",
        "Pool Changes" => "Alterações em coleções",
        "Alias Editor" => "Editor de aliases",
    ];

    public static function nav(string $label): string
    {
        return self::NAV[$label] ?? $label;
    }
}
