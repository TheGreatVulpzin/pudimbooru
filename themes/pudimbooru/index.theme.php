<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\DIV;

use MicroHTML\HTMLElement;

use function MicroHTML\{A, INPUT, P, SPAN, emptyHTML};

class PudimbooruIndexTheme extends IndexTheme
{
    /**
     * @param Post[] $images
     */
    public function display_page(array $images): void
    {
        $this->display_shortwiki();

        $this->display_page_header($images);

        $nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
        Ctx::$page->add_block(new Block("Buscar", $nav, "left", 0));

        if (count($images) > 0) {
            $this->display_page_images($images);
        } else {
            $this->display_none_found();
        }
    }

    /**
     * @param search-term-array $search_terms
     */
    protected function build_navigation(int $page_number, int $total_pages, array $search_terms): HTMLElement
    {
        return SHM_FORM(
            action: search_link(),
            method: 'GET',
            children: [
                P(),
                INPUT([
                    "name" => 'search',
                    "type" => 'text',
                    "value" => SearchTerm::implode($search_terms),
                    "class" => 'autocomplete_tags',
                    "style" => 'width:75%'
                ]),
                INPUT([
                    "type" => 'submit',
                    "value" => 'Ir',
                    "style" => 'width:20%'
                ]),
            ]
        );
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
                Ctx::$page->set_subheading("Página {$this->page_number} / {$this->total_pages}");
            }
        }

        Ctx::$page->set_title($page_title);
    }

    protected function display_none_found(): void
    {
        Ctx::$page->add_block(new Block(null, emptyHTML(
            SPAN(
                "Nenhum post corresponde à busca, ",
                A(
                    ["href" => Url::referer_or(make_link("post/list"))],
                    "voltar"
                )
            )
        )));
    }

    /**
     * @param Post[] $images
     */
    protected function build_table(array $images, ?string $query): HTMLElement
    {
        $table = DIV(["class" => "shm-image-list", "data-query" => $query]);
        foreach ($images as $image) {
            $table->appendChild($this->build_thumb($image));
        }
        return $table;
    }
}
