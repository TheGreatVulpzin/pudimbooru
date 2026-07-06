<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TBODY, TD, TR};
use function MicroHTML\{TEXTAREA};

class BiographyTheme extends Themelet
{
    public function display_biography(string $bio): void
    {
        Ctx::$page->add_block(new Block("Sobre mim", format_text($bio), "main", 30, "about-me"));
    }

    public function display_composer(User $duser, string $bio): void
    {
        Ctx::$page->add_block(new Block("Sobre mim", $this->get_composer($duser, $bio), "main", 30));
    }

    public function get_composer(User $duser, string $bio): HTMLElement
    {
        return SHM_USER_FORM(
            $duser,
            make_link("user/{$duser->name}/biography"),
            "Sobre mim",
            TBODY(
                TR(TD(["colspan" => "2"], TEXTAREA(["rows" => "6", "name" => "biography"], $bio)))
            ),
            "Salvar"
        );
    }
}
