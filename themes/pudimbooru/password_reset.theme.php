<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\{INPUT, P, TABLE, TBODY, TD, TFOOT, TH, TR};

class PudimbooruPasswordResetTheme extends PasswordResetTheme
{
    public function display_request_page(): void
    {
        Ctx::$page->set_title(PudimbooruLocale::translate("Reset Password"));
        Ctx::$page->set_layout("no-left");
        $form = SHM_SIMPLE_FORM(
            make_link("password_reset/request"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH(PudimbooruLocale::translate("Username or email")),
                        TD(INPUT(["type" => "text", "name" => "login", "required" => true, "autocomplete" => "username"]))
                    )
                ),
                TFOOT(TR(TD(["colspan" => 2], SHM_SUBMIT(PudimbooruLocale::translate("Send reset email")))))
            )
        );
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("Reset Password"), $form, "main", 50));
    }

    public function display_reset_page(string $token): void
    {
        Ctx::$page->set_title(PudimbooruLocale::translate("Choose New Password"));
        Ctx::$page->set_layout("no-left");
        $form = SHM_SIMPLE_FORM(
            make_link("password_reset/reset"),
            INPUT(["type" => "hidden", "name" => "token", "value" => $token]),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH(PudimbooruLocale::translate("New password")),
                        TD(INPUT(["type" => "password", "name" => "pass1", "required" => true, "autocomplete" => "new-password"]))
                    ),
                    TR(
                        TH(PudimbooruLocale::translate("Repeat password")),
                        TD(INPUT(["type" => "password", "name" => "pass2", "required" => true, "autocomplete" => "new-password"]))
                    )
                ),
                TFOOT(TR(TD(["colspan" => 2], SHM_SUBMIT(PudimbooruLocale::translate("Change password")))))
            )
        );
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("Choose New Password"), $form, "main", 50));
    }

    public function display_sent_page(): void
    {
        Ctx::$page->set_title(PudimbooruLocale::translate("Reset Password"));
        Ctx::$page->set_layout("no-left");
        Ctx::$page->add_block(new Block(
            PudimbooruLocale::translate("Reset Password"),
            P(PudimbooruLocale::translate("If the account exists and has an email address, a reset link has been sent. Please check your spam folder too.")),
            "main",
            50
        ));
    }
}
