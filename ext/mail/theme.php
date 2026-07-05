<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, H3, INPUT, SECTION};

use MicroHTML\HTMLElement;

class MailTheme extends Themelet
{
    /**
     * @param array<Block> $configBlocks
     * @param array<array{0: Block, 1: Url, 2: string}> $toolBlocks
     */
    public function display_manager_page(array $configBlocks, array $toolBlocks): void
    {
        usort($configBlocks, Block::cmp(...));
        usort($toolBlocks, fn ($a, $b) => Block::cmp($a[0], $b[0]));

        $blocks = DIV(["class" => "setupblocks"]);
        foreach ($toolBlocks as [$block, $action, $submitLabel]) {
            $blocks->appendChild($this->tool_block_to_section($block, $action, $submitLabel));
        }
        foreach ($configBlocks as $block) {
            $blocks->appendChild($this->block_to_section($block));
        }

        $form = SHM_SIMPLE_FORM(
            make_link("mail_manager/save"),
            $blocks,
            INPUT(["class" => "setupsubmit", "type" => "submit", "value" => "Save Settings"])
        );

        $nav = @$_GET["advanced"] === "on" ?
            A(["href" => make_link("mail_manager")], "Simple") :
            A(["href" => make_link("mail_manager", ["advanced" => "on"])], "Advanced");

        Ctx::$page->set_title("Gerenciador de E-mail");
        $this->display_navigation(extra: $nav);
        Ctx::$page->add_block(new Block(null, $form, id: "Setupmain"));
    }

    private function tool_block_to_section(Block $block, Url $action, string $submitLabel): HTMLElement
    {
        $body = DIV(["class" => "blockbody"], $block->body);
        $body->appendChild(SHM_SUBMIT($submitLabel, [
            "formaction" => $action,
            "formmethod" => "post",
        ]));

        return SECTION(
            ["class" => "setupblock"],
            H3($block->header),
            $body,
        );
    }

    private function block_to_section(Block $block): HTMLElement
    {
        return SECTION(
            ["class" => "setupblock"],
            H3($block->header),
            DIV(["class" => "blockbody"], $block->body),
        );
    }
}
