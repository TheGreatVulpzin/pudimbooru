<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Brazilian Portuguese UI strings for the pudimbooru theme.
 */
final class PudimbooruLocale
{
    /** @var array<string, string> */
    private const STRINGS = [
        // Navigation
        "Posts" => "Posts",
        "All" => "Todos",
        "Tags" => "Tags",
        "Popular Tags" => "Tags Populares",
        "Map" => "Mapa",
        "Alphabetic" => "Alfabético",
        "Popularity" => "Popularidade",
        "Comments" => "Comentários",
        "Help" => "Ajuda",
        "Navigation" => "Navegação",
        "Return" => "Voltar",
        "Wiki" => "Wiki",
        "Rules" => "Regras",
        "Page list" => "Lista de Páginas",
        "Upload" => "Enviar",
        "Guidelines" => "Diretrizes",
        "Account" => "Conta",
        "Log Out" => "Sair",
        "User List" => "Lista de Usuários",
        "Board Config" => "Configuração",
        "Board Admin" => "Administração",
        "System" => "Sistema",
        "Stats" => "Estatísticas",
        "Top" => "Top",
        "Top 100" => "Top 100",
        "Forum" => "Fórum",
        "Pools" => "Coleções",
        "List" => "Lista",
        "Create" => "Criar",
        "Changes" => "Alterações",
        "Notes" => "Notas",
        "Requests" => "Pedidos",
        "Updates" => "Atualizações",
        "Login" => "Entrar",
        "User Links" => "Links do Usuário",
        "Signup" => "Cadastro",
        "Upload Status" => "Status do Envio",
        "No images uploaded" => "Nenhuma Imagem Enviada",
        "Bookmarklets" => "Bookmarklets",
        "My Favorites" => "Meus Favoritos",
        "Random Post" => "Post Aleatório",
        "Shuffle" => "Embaralhar",
        "Feed" => "Feed",
        "Trash" => "Lixeira",
        "Pending Approval" => "Aprovação Pendente",
        "Reported Posts" => "Posts Denunciados",
        "Tag Changes" => "Alterações de Tags",
        "Source Changes" => "Alterações de Fonte",
        "Aliases" => "Aliases",
        "Tag Categories" => "Categorias de Tags",
        "Popular Posts" => "Posts Populares",
        "Popular by Day" => "Popular do Dia",
        "Popular by Month" => "Popular do Mês",
        "Popular by Year" => "Popular do Ano",
        "Event Log" => "Log de Eventos",
        "IP Bans" => "Bans de IP",
        "Post Bans" => "Bans de Posts",
        "Extension Manager" => "Gerenciador de Extensões",
        "Permission Manager" => "Gerenciador de Permissões",
        "Blocks Editor" => "Editor de Blocos",
        "Blotter Editor" => "Editor de Avisos",
        "Tips Editor" => "Editor de Dicas",
        "Auto-Tag" => "Auto-Tag",
        "UnTags" => "UnTags",
        "Cron Upload" => "Envio CRON",
        "User Options" => "Opções do Usuário",
        "System Info" => "Info do Sistema",
        "My Profile" => "Meu Perfil",
        "Pool Changes" => "Alterações em Coleções",
        "Alias Editor" => "Editor de Aliases",
        "Write a new thread" => "Escreva uma Publicação Nova",
        "Pool Navigation" => "Navegação das Coleções",

        // Post view labels
        "Uploader" => "Enviador",
        "Date" => "Data",
        "Size" => "Tamanho",
        "Type" => "Tipo",
        "Video Codec" => "Codec de Vídeo",
        "Length" => "Duração",
        "Source" => "Fonte",
        "Rating" => "Classificação",
        "Post Controls" => "Controles do Post",
        "Post Locked" => "[Post Bloqueado]",
        "Edit" => "Editar",
        "Set" => "Definir",
        "Find" => "Encontrar",
        "Prev" => "Anterior",
        "Next" => "Próximo",
        "Index" => "Índice",

        // Score extension
        "Vote Up" => "Votar Positivo",
        "Remove Vote" => "Remover Voto",
        "Vote Down" => "Votar Negativo",
        "Remove All Votes" => "Remover Todos os Votos",
        "See All Votes" => "Ver Todos os Votos",
        "Post Score" => "Pontuação do Post",
        "Delete all votes by this user" => "Deletar todos os votos deste usuário",
        "Votes" => "Votos",
        "Place" => "Posição",
        "Amount" => "Quantidade",
        "Sticky:" => "Fixar:",
        "Public" => "Público",
        "Recent Changes" => "Alterações Recentes",

        // Post description
        "Description" => "Descrição",

        // General UI
        "Search" => "Buscar",
        "Go" => "Ir",
        "Information" => "Informações",
        "Back" => "voltar",
        "Contact" => "Contato",
        "No posts match search" => "Nenhum post corresponde à busca",
        "Page %d / %d" => "Página %d / %d",
        // "Pudimbooru Tagline" => "Pudim(©) aos seus respectivos donos, rodando [Pudimbooru](https://github.com/TheGreatVulpzin/pudimbooru), baseado no [Shimmie](https://github.com/shish/shimmie2/)",
        "Blotter updated" => "Avisos atualizados",
        "There are no threads to show." => "Não há publicações para mostrar.",
        "Note Requests" => "Pedidos de Notas",
        "Common Tags" => "Tags Comuns",

        // Blotter / pools / stats / upload / help / user
        "Blotter" => "Avisos",
        "Pool" => "Coleção",
        "Pool table" => "Tabela de Coleções",
        "System Statistics" => "Estatísticas do Sistema",
        "Upload" => "Enviar",
        "Publish" => "Publicar",
        "Select file" => "Selecionar arquivo",
        "or URL" => "ou URL",
        "Limit per file %s" => "Limite por arquivo %s",
        "Total limit %s" => "Limite total %s",
        "Current total" => "Total atual",
        "Post specific %s" => "%s Especificas por Post",
        "Bookmarklets" => "Bookmarklets",
        "Login form" => "Formulário de login",
        "Signup" => "Cadastro",
        "Create Account" => "Criar conta",
        "Name" => "Nome",
        "Password" => "Senha",
        "Repeat password" => "Repetir senha",
        "Email" => "E-mail",
        "Email (optional)" => "E-mail (opcional)",
        "Uploaded from" => "Enviado de",
        "Commented from" => "Comentado de",
        "Logged Events" => "Eventos Registrados",
        "IPs" => "IPs",
        "User ID: %s" => "ID do usuário: %s",
        "%s's Page" => "Página de %s",
        "Formatting" => "Formatação",
        "BBCode" => "BBCode",
        "Basic Formatting tags:" => "Tags básicas de formatação:",
        "More format tags:" => "Mais tags de formatação:",

        // Blotter
        "Important?" => "Importante?",
        "Action" => "Ação",
        "Add" => "Adicionar",
        "Yes" => "Sim",
        "No" => "Não",
        "Blotter Editor" => "Editor de Avisos",
        "Blotter Entries" => "Entradas do Blotter",
        "No blotter entries yet." => "Ainda não há avisos.",
        "Empty." => "Vazio.",
        "Blotter updated: %s" => "Avisos atualizados: %s",
        "Show/Hide" => "Mostrar/Ocultar",
        "Show All" => "Mostrar Tudo",

        // Pools
        "Creator" => "Criador",
        "Post Count" => "Contagem de Posts",
        "Order By" => "Ordenar por",
        "Create Pool" => "Criar Coleção",
        "Pool Index" => "Índice de Coleções",
        "Pool Changes" => "Alterações em Coleções",
        "Recently created" => "Recentemente criadas",
        "Last updated" => "Últimas atualizações",
        "Search" => "Buscar",
        "Import" => "Importar",
        "Edit Pool" => "Editar Coleção",
        "Order Pool" => "Ordenar Coleção",
        "Reverse Order" => "Inverter Ordem",
        "Post/List View" => "Visualizar Lista/Post",
        "Delete Pool" => "Deletar Coleção",
        "Are you sure that you want to delete this pool?" => "Tem certeza de que deseja excluir esta coleção?",
        "Check All" => "Marcar Todos",
        "Uncheck All" => "Desmarcar Todos",
        "Manage Pool" => "Gerenciar Coleção",
        "Viewing Posts" => "Visualizando Posts",
        "Importing Posts" => "Importando Posts",
        "Please enter a tag" => "Por favor, insira uma tag",

        // Upload
        "Disk nearly full, uploads disabled" => "Disco quase cheio, uploads desabilitados",
        "File limit %s" => "Limite de arquivo %s",
        "Upload attempted, but nothing succeeded and nothing failed?" => "Upload tentado, mas nada foi bem-sucedido e nada falhou?",
        "Upload to %s" => "Enviar para %s",
        "Drag & drop onto your bookmarks toolbar, then click when looking at a post" => "Arraste e solte na barra de favoritos, então clique ao ver um post",
        "Click when looking at a post page. Works on sites running Shimmie / Danbooru / Gelbooru. (This also grabs the tags / rating / source!)" => "Clique ao ver uma página de post. Funciona em sites Shimmie / Danbooru / Gelbooru. (Também captura tags / classificação / fonte!)",
        "Larger Form" => "Formulário Maior",
        "Post" => "Enviar",

        // Help / Wiki
        "Searching" => "Pesquisar",
        "Licenses" => "Licenças",
        "Software Licenses" => "Licenças de Software",
        "Wiki Index" => "Índice Wiki",
        "All Wiki Pages" => "Todas as Páginas Wiki",
        "Editor" => "Editor",
        "Compare Revisions" => "Comparar Revisões",
        "Save" => "Salvar",
        "Title" => "Título",
        "Description" => "Descrição",
        "Add Selected" => "Adicionar Selecionados",
        "Sorting Pool" => "Ordenar Coleção",
        "Order" => "Ordenar",
        "Sorting Posts" => "Ordenando Posts",
        "Change Description" => "Alterar Descrição",
        "Remove Selected" => "Remover Selecionados",
        "Editing Pool" => "Editando Coleção",
        "Editing Description" => "Editando Descrição",
        "Editing Posts" => "Editando Posts",
        "Pool" => "Coleção",
        "Revert" => "Reverter",
        "Updater" => "Atualizador",
        "Delete" => "Deletar",
        "Delete All" => "Deletar Todos",
        "Revision %s" => "Revisão %s",
        "Wiki page list" => "Lista de Páginas Wiki",
        "Lock page:" => "Bloquear página:",

        // User
        "Login There" => "Login Aqui",
        "There should be a login box to the left" => "Deve haver uma caixa de login à esquerda",
        "Logged in as %s" => "Logado como %s",
        "User Links" => "Links do Usuário",
        "Create Account" => "Criar Conta",
        "Repeat Password" => "Repetir Senha",
        "Email (Optional)" => "E-mail (Opcional)",
        "Create User" => "Criar Usuário",
        "Signups Disabled" => "Cadastros Desabilitados",
        "Log In" => "Entrar",
        "Captcha" => "Captcha",
        "Change Name" => "Alterar Nome",
        "New name" => "Novo nome",
        "Change Password" => "Alterar Senha",
        "Repeat password" => "Repetir senha",
        "Change Email" => "Alterar E-mail",
        "Address" => "Endereço",
        "Set" => "Definir",
        "Change Class" => "Alterar Classe",
        "Delete User" => "Deletar Usuário",
        "Delete images" => "Deletar imagens",
        "Delete comments" => "Deletar comentários",
        "Unlock" => "Desbloquear",
        "No Name" => "Sem Nome",

        // Forum UI
        "Title" => "Título",
        "Message" => "Mensagem",
        "Max characters allowed: %s" => "Máximo de caracteres permitidos: %s",
        "Sticky:" => "Fixar:",
        "Reply" => "Responder",
        "Return" => "Voltar",
        "User" => "Usuário",
        "Author" => "Autor",
        "Updated" => "Atualizado",
        "Responses" => "Respostas",
        "Actions" => "Ações",
        "Delete" => "Deletar",
        "Delete Thread" => "Deletar Tópico",
        "Answer to this thread" => "Responder a este tópico",
        "Admin Actions" => "Ações de Admin"
    ];

    

    public static function nav(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }

    public static function post_view(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }

    public static function score(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }

    public static function post_description(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }

    public static function general_ui(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }

    public static function translate(string $label): string
    {
        return self::STRINGS[$label] ?? $label;
    }
}
