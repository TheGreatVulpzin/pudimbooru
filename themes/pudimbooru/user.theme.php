<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\{A, BR, INPUT, LABEL, P, SMALL, TABLE, TBODY, TD, TFOOT, TH, TR, emptyHTML, joinHTML};
use function MicroHTML\rawHTML;

use MicroHTML\HTMLElement;

class PudimbooruUserPageTheme extends UserPageTheme
{
    public function display_login_page(): void
    {
        Ctx::$page->set_title(PudimbooruLocale::translate("Login"));
        Ctx::$page->set_layout("no-left");
        $html = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["summary" => PudimbooruLocale::translate("Login form")],
                TR(
                    TD(["width" => "70"], LABEL(["for" => "user"], PudimbooruLocale::translate("Name"))),
                    TD(["width" => "70"], INPUT(["type" => "text", "name" => "user", "id" => "user"]))
                ),
                TR(
                    TD(LABEL(["for" => "pass"], PudimbooruLocale::translate("Password"))),
                    TD(INPUT(["type" => "password", "name" => "pass", "id" => "pass"]))
                ),
                TR(
                    TD(["colspan" => "2"], SHM_SUBMIT(PudimbooruLocale::translate("Login")))
                )
            )
        );
        if (Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], PudimbooruLocale::translate("Create Account"))));
        }
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("Login"), $html, "main", 90));
    }

    /**
     * @param array<int, array{name: string|HTMLElement, link: Url}> $parts
     */
    public function display_user_links(User $user, array $parts): void
    {
        $html = [];
        $blocked = ["Pools", "Pool Changes", "Alias Editor", "My Profile"];
        foreach ($parts as $part) {
            if (in_array($part["name"], $blocked)) {
                continue;
            }
            $name = is_string($part["name"]) ? PudimbooruLocale::nav($part["name"]) : $part["name"];
            $html[] = A(["href" => $part["link"], "class" => "tab"], $name);
        }
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("User Links"), joinHTML(BR(), $html), "user", 90, is_content: false));
    }

    public function display_login_block(): void
    {
        // no block in this theme
    }

    /**
     * @param array<array{name: string|HTMLElement, link: Url}> $parts
     */
    public function display_user_block(User $user, array $parts): void
    {
        $html = [];
        $blocked = ["Pools", "Pool Changes", "Alias Editor", "My Profile"];
        foreach ($parts as $part) {
            if (in_array($part["name"], $blocked)) {
                continue;
            }
            $name = is_string($part["name"]) ? PudimbooruLocale::nav($part["name"]) : $part["name"];
            $html[] = A(["href" => $part["link"], "class" => "tab"], $name);
        }
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("User Links"), joinHTML(BR(), $html), "left", 90, is_content: false));
    }

    public function display_signup_page(): void
    {
        Ctx::$page->set_layout("no-left");

        $tac = Ctx::$config->get(UserAccountsConfig::LOGIN_TAC) ?? "";

        if (Ctx::$config->get(UserAccountsConfig::LOGIN_TAC_BBCODE)) {
            $tac = format_text($tac);
        }

        $email_required = (
            Ctx::$config->get(UserAccountsConfig::USER_EMAIL_REQUIRED) &&
            !Ctx::$user->can(UserAccountsPermission::CREATE_OTHER_USER)
        );
        $captcha = Captcha::get_html(UserAccountsPermission::SKIP_SIGNUP_CAPTCHA);

        $form = SHM_SIMPLE_FORM(
            make_link("user_admin/create"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(
                        TH(PudimbooruLocale::translate("Name")),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH(PudimbooruLocale::translate("Password")),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(rawHTML(PudimbooruLocale::translate("Repeat password"))),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH($email_required ? PudimbooruLocale::translate("Email") : rawHTML(PudimbooruLocale::translate("Email (optional)"))),
                        TD(INPUT(["type" => 'email', "name" => 'email', "required" => $email_required]))
                    ),
                    $captcha ? TR(
                        TD(["colspan" => "2"], $captcha)
                    ) : null,
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => PudimbooruLocale::translate("Create Account")])) )
                )
            )
        );

        $html = emptyHTML(
            $tac ? P($tac) : null,
            $form
        );

        Ctx::$page->set_title(PudimbooruLocale::translate("Create Account"));
        Ctx::$page->add_block(new Block(PudimbooruLocale::translate("Signup"), $html));
    }

    /**
     * @param array<\MicroHTML\HTMLElement|string> $stats
     */
    public function display_user_page(User $duser, array $stats): void
    {
        Ctx::$page->set_title(sprintf(PudimbooruLocale::translate("%s's Page"), $duser->name));
        parent::display_user_page($duser, $stats);
    }
}
