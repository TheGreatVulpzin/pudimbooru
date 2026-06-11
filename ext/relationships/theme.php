<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, P, SPAN, emptyHTML, joinHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT};

class RelationshipsTheme extends Themelet
{
    public function relationship_info(Post $image): void
    {
        $parent = Search::get_posts([$image['parent_id']]);
        if (!empty($parent)) {
            $visible_siblings = Relationships::has_siblings($image->id)
                ? Relationships::get_siblings($image->id)
                : [];
            $html = emptyHTML(
                $visible_siblings
                    ? SPAN("This post has a parent and " . count($visible_siblings) . (count($visible_siblings) > 1 ? " siblings" : " sibling"))
                    : SPAN("This post has a parent"),
                " ",
                A(["href" => "#", "id" => "relationships-parent-toggle", "class" => "shm-relationships-parent-toggle"], "« hide"),
                DIV(
                    ["class" => "shm-relationships-parent-thumbs"],
                    DIV(["class" => "shm-parent-thumbs"], $this->build_thumb(Post::by_id_ex($image['parent_id']))),
                    DIV(["class" => "shm-sibling-thumbs"], joinHTML("", array_map(fn ($s) => $this->build_thumb($s), $visible_siblings))),
                )
            );
            Ctx::$page->add_block(new Block(null, $html, "main", 5, "PostRelationshipsParent"));
        }

        if ($image['has_children']) {
            $visible_children = Relationships::get_children($image->id);
            if (!empty($visible_children)) {
                $html = emptyHTML(
                    SPAN(
                        "This post has ",
                        A(["href" => search_link(['parent='.$image->id])], "child posts"),
                        " ",
                        A(["href" => "#", "id" => "relationships-child-toggle", "class" => "shm-relationships-child-toggle"], "« hide")
                    ),
                    DIV(
                        ["class" => "shm-relationships-child-thumbs"],
                        DIV(["class" => "shm-child-thumbs"], ...array_map(fn ($child) => $this->build_thumb($child), $visible_children)),
                    )
                );
                Ctx::$page->add_block(new Block(null, $html, "main", 5, "PostRelationshipsChildren"));
            }
        }
    }

    public function get_parent_editor_html(Post $image): HTMLElement
    {
        return SHM_POST_INFO(
            "Parent",
            (string)$image['parent_id'] ?: "None",
            Ctx::$user->can(RelationshipsPermission::EDIT_IMAGE_RELATIONSHIPS)
                ? INPUT(["type" => "number", "name" => "parent", "value" => $image['parent_id']])
                : null
        );
    }


    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Pesquisar por posts que têm relações de pai/filho."),
            SHM_COMMAND_EXAMPLE("parent=any", "Pesquisa por posts que têm um pai."),
            SHM_COMMAND_EXAMPLE("parent=none", "Pesquisa por posts que não têm um pai."),
            SHM_COMMAND_EXAMPLE("parent=123", "Pesquisa por posts que têm a imagem 123 definida como pai."),
            SHM_COMMAND_EXAMPLE("child=any", "Pesquisa por posts que têm pelo menos 1 filho."),
            SHM_COMMAND_EXAMPLE("child=none", "Pesquisa por posts que não têm filhos.")
        );
    }
}
