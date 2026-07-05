<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{INPUT, P, TABLE, TBODY, TD, TFOOT, TH, TR};

class PasswordResetTheme extends Themelet
{
    public function display_request_page(): void
    {
        Ctx::$page->set_title("Reset Password");
        $form = SHM_SIMPLE_FORM(
            make_link("password_reset/request"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("Username or email"),
                        TD(INPUT(["type" => "text", "name" => "login", "required" => true, "autocomplete" => "username"]))
                    )
                ),
                TFOOT(TR(TD(["colspan" => 2], SHM_SUBMIT("Send reset email"))))
            )
        );
        Ctx::$page->add_block(new Block("Reset Password", $form, "main", 50));
    }

    public function display_reset_page(string $token): void
    {
        Ctx::$page->set_title("Choose New Password");
        $form = SHM_SIMPLE_FORM(
            make_link("password_reset/reset"),
            INPUT(["type" => "hidden", "name" => "token", "value" => $token]),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH("New password"),
                        TD(INPUT(["type" => "password", "name" => "pass1", "required" => true, "autocomplete" => "new-password"]))
                    ),
                    TR(
                        TH("Repeat password"),
                        TD(INPUT(["type" => "password", "name" => "pass2", "required" => true, "autocomplete" => "new-password"]))
                    )
                ),
                TFOOT(TR(TD(["colspan" => 2], SHM_SUBMIT("Change password"))))
            )
        );
        Ctx::$page->add_block(new Block("Choose New Password", $form, "main", 50));
    }

    public function display_sent_page(): void
    {
        Ctx::$page->set_title("Reset Password");
        Ctx::$page->add_block(new Block(
            "Reset Password",
            P("If the account exists and has an email address, a reset link has been sent. Please check your spam folder too."),
            "main",
            50
        ));
    }
}
