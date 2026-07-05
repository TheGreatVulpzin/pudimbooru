<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, DIV, H3, INPUT, SECTION, TABLE, TBODY, TD, TFOOT, TH, TR};

use MicroHTML\HTMLElement;

class MailTheme extends Themelet
{
    /**
     * @param array<Block> $configBlocks
     */
    public function display_manager_page(array $configBlocks, ?string $recipient): void
    {
        usort($configBlocks, Block::cmp(...));

        $blocks = DIV(["class" => "setupblocks"]);
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
        $this->display_test_block($recipient);
    }

    public function display_test_block(?string $recipient): void
    {
        $form = SHM_SIMPLE_FORM(
            make_link("mail/test"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Recipient"),
                        TD(INPUT(["type" => "email", "name" => "to", "value" => $recipient, "required" => true]))
                    )
                ),
                TFOOT(
                    TR(TD(["colspan" => 2], SHM_SUBMIT("Send test email")))
                )
            )
        );

        Ctx::$page->add_block(new Block("Mail Test", $form, "main", 80));
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
