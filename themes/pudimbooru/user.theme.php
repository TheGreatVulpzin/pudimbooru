<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\{A, INPUT, LABEL, P, SMALL, TABLE, TBODY, TD, TFOOT, TH, TR, emptyHTML, joinHTML};
use function MicroHTML\rawHTML;

use MicroHTML\HTMLElement;

class PudimbooruUserPageTheme extends UserPageTheme
{
    public function display_login_page(): void
    {
        Ctx::$page->set_title("Entrar");
        Ctx::$page->set_layout("no-left");
        $html = SHM_SIMPLE_FORM(
            make_link("user_admin/login"),
            TABLE(
                ["summary" => "Formulário de login"],
                TR(
                    TD(["width" => "70"], LABEL(["for" => "user"], "Nome")),
                    TD(["width" => "70"], INPUT(["type" => "text", "name" => "user", "id" => "user"]))
                ),
                TR(
                    TD(LABEL(["for" => "pass"], "Senha")),
                    TD(INPUT(["type" => "password", "name" => "pass", "id" => "pass"]))
                ),
                TR(
                    TD(["colspan" => "2"], SHM_SUBMIT("Entrar"))
                )
            )
        );
        if (Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED)) {
            $html->appendChild(SMALL(A(["href" => make_link("user_admin/create")], "Criar conta")));
        }
        Ctx::$page->add_block(new Block("Entrar", $html, "main", 90));
    }

    /**
     * @param array<int, array{name: string|HTMLElement, link: Url}> $parts
     */
    public function display_user_links(User $user, array $parts): void
    {
        // no block in this theme
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
        Ctx::$page->add_block(new Block("Links do usuário", joinHTML(" ", $html), "user", 90, is_content: false));
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
                        TH("Nome"),
                        TD(INPUT(["type" => 'text', "name" => 'name', "required" => true]))
                    ),
                    TR(
                        TH("Senha"),
                        TD(INPUT(["type" => 'password', "name" => 'pass1', "required" => true]))
                    ),
                    TR(
                        TH(rawHTML("Repetir&nbsp;senha")),
                        TD(INPUT(["type" => 'password', "name" => 'pass2', "required" => true]))
                    ),
                    TR(
                        TH($email_required ? "E-mail" : rawHTML("E-mail&nbsp;(opcional)")),
                        TD(INPUT(["type" => 'email', "name" => 'email', "required" => $email_required]))
                    ),
                    $captcha ? TR(
                        TD(["colspan" => "2"], $captcha)
                    ) : null,
                ),
                TFOOT(
                    TR(TD(["colspan" => "2"], INPUT(["type" => "submit", "value" => "Criar conta"])))
                )
            )
        );

        $html = emptyHTML(
            $tac ? P($tac) : null,
            $form
        );

        Ctx::$page->set_title("Criar conta");
        Ctx::$page->add_block(new Block("Cadastro", $html));
    }

    /**
     * @param array<\MicroHTML\HTMLElement|string> $stats
     */
    public function display_user_page(User $duser, array $stats): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_user_page($duser, $stats);
    }
}
