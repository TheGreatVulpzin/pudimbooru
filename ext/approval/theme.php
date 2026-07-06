<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BUTTON,P};
use function MicroHTML\emptyHTML;

use MicroHTML\HTMLElement;

class ApprovalTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        $help_text = emptyHTML(
            P("Pesquisar por posts que estão aprovados/não aprovados."),
            SHM_COMMAND_EXAMPLE("approved=yes", "Pesquisa por posts que foram aprovados."),
        );

        if (Ctx::$user->can(ApprovalPermission::APPROVE_IMAGE)) {
            $help_text = emptyHTML(
                $help_text,
                SHM_COMMAND_EXAMPLE("approved=no", "Pesquisa por posts que não foram aprovados.")
            );
        } else {
            $help_text = emptyHTML(
                $help_text,
                SHM_COMMAND_EXAMPLE("approved=no", "Pesquisa por seus próprios posts que não foram aprovados."),
                SHM_COMMAND_EXAMPLE("approved=no user=username", "Pesquisa por seus próprios posts não aprovados (só funciona com seu próprio nome de usuário).")
            );
        }

        return $help_text;
    }

    public function display_admin_form(): void
    {
        $form = SHM_SIMPLE_FORM(
            make_link("admin/approval"),
            BUTTON(["name" => 'approval_action', "value" => 'approve_all'], "Approve All Posts"),
            " ",
            BUTTON(["name" => 'approval_action', "value" => 'disapprove_all'], "Disapprove All Posts"),
        );

        Ctx::$page->add_block(new Block("Approval", $form));
    }
}
