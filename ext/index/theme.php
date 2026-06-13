<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, DIV, H2, H3, HR, INPUT, META, P, SPAN, SUP, emptyHTML};

use MicroHTML\HTMLElement;

class IndexTheme extends Themelet
{
    protected int $page_number;
    protected int $total_pages;
    /** @var search-term-array */
    protected array $search_terms;

    /**
     * @param search-term-array $search_terms
     */
    public function set_page(int $page_number, int $total_pages, array $search_terms): void
    {
        $this->page_number = $page_number;
        $this->total_pages = $total_pages;
        $this->search_terms = $search_terms;
    }

    public function display_intro(): void
    {
        $text = DIV(
            ["class" => "prose"],
            P("The first thing you'll probably want to do is create a new account; note
         that the first account you create will by default be marked as the board's
         administrator, and any further accounts will be regular users."),
            P("Once logged in you can play with the settings, install extra features,
         and of course start organising your images :-)"),
            P("This message will go away once your first image is uploaded~"),
        );
        Ctx::$page->set_title("Welcome to Shimmie ".SysConfig::getVersion(false));
        Ctx::$page->set_heading("Welcome to Shimmie");
        Ctx::$page->add_block(new Block("Nothing here yet!", $text, "main", 0));
    }

    /**
     * @param Post[] $images
     */
    public function display_page(array $images): void
    {
        $this->display_shortwiki();

        $this->display_page_header($images);

        $extra = SHM_FORM(
            action: search_link(),
            method: "GET",
            children: [
                P(),
                INPUT([
                    "type" => "search",
                    "name" => "search",
                    "value" => SearchTerm::implode($this->search_terms),
                    "placeholder" => "Search",
                    "class" => "autocomplete_tags"
                ]),
                INPUT([
                    "type" => "submit",
                    "value" => "Find",
                    "style" => "display: none;"
                ])
            ],
        );

        $this->display_navigation([
            ($this->page_number <= 1) ? null : search_link($this->search_terms, $this->page_number - 1),
            make_link(),
            ($this->page_number >= $this->total_pages) ? null : search_link($this->search_terms, $this->page_number + 1),
        ], $extra);

        if (count($images) > 0) {
            $this->display_page_images($images);
        } else {
            $this->display_none_found();
        }
    }

    /**
     * @param Post[] $images
     */
    protected function build_table(array $images, ?string $query): HTMLElement
    {
        $thumbs = array_map(fn ($image) => $this->build_thumb($image), $images);
        return DIV(["class" => "shm-image-list", "data-query" => $query], ...$thumbs);
    }

    protected function display_shortwiki(): void
    {
        if (WikiInfo::is_enabled() && Ctx::$config->get(WikiConfig::TAG_SHORTWIKIS)) {
            if (count($this->search_terms) === 1) {
                $st = SearchTerm::implode($this->search_terms);
                $wikiPage = Wiki::get_page($st);
                if ($wikiPage->id !== -1) {
                    if (TagCategoriesInfo::is_enabled()) {
                        $st = TagCategories::getTagHtml($st);
                    }
                    $short_wiki_description = emptyHTML(
                        H2($st, " ", A(["href" => make_link("wiki/$st")], SUP("ⓘ"))),
                        format_text(explode("\n", $wikiPage->body, 2)[0])
                    );
                    Ctx::$page->add_block(new Block(null, $short_wiki_description, "main", 0, "short-wiki-description"));
                }
            }
        }
    }

    /**
     * @param Post[] $images
     */
    protected function display_page_header(array $images): void
    {
        if (count($this->search_terms) === 0) {
            $page_title = Ctx::$config->get(SetupConfig::TITLE);
        } else {
            $page_title = implode(' ', $this->search_terms);
            if (count($images) > 0) {
                Ctx::$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
            }
        }
        /*
        if ($this->page_number > 1 || count($this->search_terms) > 0) {
            $page_title .= " / $page_number";
        }
        */

        Ctx::$page->set_title($page_title);
    }

    /**
     * @param Post[] $images
     */
    protected function display_page_images(array $images): void
    {
        if (count($this->search_terms) > 0) {
            if ($this->page_number > 3) {
                // only index the first pages of each term
                Ctx::$page->add_html_header(META(["name" => "robots", "content" => "noindex, nofollow"]));
            }
            $query = url_escape(SearchTerm::implode($this->search_terms));
            Ctx::$page->add_block(new Block(null, $this->build_table($images, "search=$query"), "main", 10, "image-list"));
            $this->display_paginator("post/list/$query", null, $this->page_number, $this->total_pages, true);
        } else {
            Ctx::$page->add_block(new Block(null, $this->build_table($images, null), "main", 10, "image-list"));
            $this->display_paginator("post/list", null, $this->page_number, $this->total_pages, true);
        }
    }

    protected function display_none_found(): void
    {
        Ctx::$page->add_block(new Block(null, emptyHTML(
            SPAN(
                "No posts were found to match the search criteria, ",
                A(
                    ["href" => Url::referer_or(make_link("post/list"))],
                    "go back"
                )
            )
        )));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            H3("Pesquisa por Tags"),
            P("Pesquisar é baseado principalmente em tags, com um número de palavras chaves disponíveis que permitem pesquisar baseado em propriedades dos posts."),
            SHM_COMMAND_EXAMPLE("nome_da_tag", 'Pesquisa por posts que são etiquetados com "nome_da_tag".'),
            SHM_COMMAND_EXAMPLE("nome_da_tag outra_tag", 'Pesquisa por posts que são etiquetados com "nome_da_tag" e "outra_tag".'),
            //
            BR(),
            P('Maior parte das tags podem ter um "menos" (-) no começo para indicar que você quer pesquisar posts que não contém algo.'),
            SHM_COMMAND_EXAMPLE("-nome_da_tag", 'Pesquisa por posts que não são  com "nome_da_tag".'),
            SHM_COMMAND_EXAMPLE("-nome_da_tag -outra_tag", 'Pesquisa por posts que não são etiquetados com "nome_da_tag" ou "outra_tag".'),
            SHM_COMMAND_EXAMPLE("nome_da_tag -outra_tag", 'Pesquisa por posts que são etiquetados com "nome_da_tag", mas não são etiquetados com "outra_tag".'),
            //
            BR(),
            P('Pesquisas "coringa" também são possíveis usando um "*".'),
            SHM_COMMAND_EXAMPLE("tag*", 'Pesquisa por posts que são etiquetados com "tag", "tags", "tagme", "nome_da_tag", or qualquer outra coisa que comece com "tag".'),
            SHM_COMMAND_EXAMPLE("*nome", 'Pesquisa por posts que são etiquetados com "nome", "nome_da_tag", "outra_tag" ou qualquer outra coisa que termine com "nome".'),
            //
            HR(),
            H3("Valores de comparação (<, <=, >, >=, or =)"),
            P("Por exemplo, você pode usar isso para contar tags."),
            SHM_COMMAND_EXAMPLE("tags=1", "Pesquisa por posts com exatamente 1 tag."),
            SHM_COMMAND_EXAMPLE("tags>0", "Pesquisa por posts com 1 ou mais tags."),
            //
            BR(),
            P("Pesquisando por tamanho de arquivo."),
            P("Sufixos suportados são kb, mb, and gb. São usados múltiplos de 1024."),
            SHM_COMMAND_EXAMPLE("filesize=1", "Pesquisa por posts com exatamente 1 byte em tamanho"),
            SHM_COMMAND_EXAMPLE("filesize>100mb", "Pesquisa por posts com tamanho maior que 100 megabytes."),
            //
            BR(),
            P("Pesquisando por data de postagem."),
            P("Formato da data é yyyy-mm-dd. A data de postagem inclui o componente de hora, então = não funcionará a menos que o horário seja exato."),
            SHM_COMMAND_EXAMPLE("posted>=2009-04-13", "Pesquisa por posts postados antes ou durante 2009-04-13."),
            //
            BR(),
            P("Pesquisando posts por ID."),
            SHM_COMMAND_EXAMPLE("id=1234", "Encontra o post com ID 1234."),
            SHM_COMMAND_EXAMPLE("id>1234", "Encontra posts mais recentes."),
            //
            HR(),
            H3("Atributos de posts."),
            P("Pesquisando por hash MD5."),
            SHM_COMMAND_EXAMPLE("hash=0D3512CAA964B2BA5D7851AF5951F33B", "Pesquisa por posts com a hash MD5 0D3512CAA964B2BA5D7851AF5951F33B."),
            SHM_COMMAND_EXAMPLE("md5=0D3512CAA964B2BA5D7851AF5951F33B", "Mesmo acima."),
            //
            BR(),
            P("Pesquisando por nome de arquivo."),
            SHM_COMMAND_EXAMPLE("filename=cazum8.jpg", 'Pesquisa por posts que são nomeados como "cazum8.jpg".'),
            //
            BR(),
            P("Pesquisando pela fonte."),
            SHM_COMMAND_EXAMPLE("source=https://reddit.com", 'Pesquisa por posts com a fonte de "https://reddit.com".'),
            SHM_COMMAND_EXAMPLE("source=any", "Pesquisa por posts com uma fonte definida."),
            SHM_COMMAND_EXAMPLE("source=none", "Pesquisa por posts sem uma fonte definida."),
            //
            HR(),
            H3("Ordenando resultados de busca"),
            P("Ordenamento pode ser feito usando o padrão order:field_direction."),
            P("Campos suportados: id, width, height, filesize, filename."),
            P("A direção pode ser asc ou desc, indicando ordem ascendente (123) ou descendente (321)."),
            SHM_COMMAND_EXAMPLE("order=id_asc", "Pesquisa por posts ordenados por ID, menor primeiro."),
            SHM_COMMAND_EXAMPLE("order=width_desc", "Pesquisa por posts ordenados por largura, maior primeiro."),
        );
    }
}
