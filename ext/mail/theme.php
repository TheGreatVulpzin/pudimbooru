<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, TABLE, TBODY, TD, TFOOT, TH, TR};

class MailTheme extends Themelet
{
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
}
