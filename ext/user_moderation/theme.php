<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, BR, INPUT, LABEL, OPTION, P, SELECT, SMALL, TABLE, TBODY, TD, TFOOT, TH, THEAD, TR, emptyHTML};

use MicroHTML\HTMLElement;

class UserModerationTheme extends Themelet
{
    /**
     * @param array<string, mixed>|null $active
     * @param array<array<string, mixed>> $history
     */
    public function display_user_moderation_block(User $target, ?array $active, array $history, bool $can_moderate): void
    {
        Ctx::$page->add_block(new Block(
            "Moderação",
            $this->build_user_moderation_panel($target, $active, $history, $can_moderate),
            "main",
            65
        ));
    }

    /**
     * @param array<array<string, mixed>> $active
     * @param array<array<string, mixed>> $history
     */
    public function display_moderation_list(array $active, array $history): void
    {
        Ctx::$page->set_title("User Moderation");
        $this->display_navigation();
        Ctx::$page->add_block(new Block(
            "Usuários banidos / silenciados",
            count($active) > 0 ? $this->build_active_table($active) : P("Nenhum usuário banido ou silenciado no momento."),
            "main",
            10
        ));
        Ctx::$page->add_block(new Block(
            "Histórico de moderação",
            count($history) > 0 ? $this->build_history_table($history) : P("Sem histórico de moderação."),
            "main",
            20
        ));
    }

    /**
     * @param array<string, mixed>|null $active
     * @param array<array<string, mixed>> $history
     */
    private function build_user_moderation_panel(User $target, ?array $active, array $history, bool $can_moderate): HTMLElement
    {
        return emptyHTML(
            $active !== null ? P(
                "Ação ativa: ",
                $this->format_action((string)$active["action"]),
                " até ",
                $active["expires"] === null ? "sem expiração" : (string)$active["expires"],
                ". Motivo: ",
                (string)$active["reason"],
                " IPs: ",
                $this->format_ip_evidence($active["ip_evidence"] ?? [])
            ) : P("Nenhuma ação ativa."),
            $can_moderate ? $this->build_create_form($target) : null,
            $can_moderate && $active !== null ? $this->build_revoke_form((int)$active["id"]) : null,
            count($history) > 0 ? $this->build_history_table($history) : SMALL("Sem histórico de moderação.")
        );
    }

    private function build_create_form(User $target): HTMLElement
    {
        $action = SELECT(
            ["name" => "action"],
            OPTION(["value" => "silence"], "Silenciar"),
            OPTION(["value" => "ban"], "Banir / restringir")
        );

        return SHM_SIMPLE_FORM(
            make_link("user_moderation/create"),
            TABLE(
                ["class" => "form"],
                TBODY(
                    TR(TH("Ação"), TD($action)),
                    TR(TH("Motivo"), TD(INPUT(["type" => "text", "name" => "reason", "required" => true]))),
                    TR(TH("Expira"), TD(
                        INPUT(["type" => "text", "name" => "expires", "placeholder" => "YYYY-MM-DD HH:MM ou 3 days"]),
                        BR(),
                        SMALL("Vazio = sem expiração")
                    )),
                    TR(TD(["colspan" => 2], INPUT(["type" => "hidden", "name" => "user_id", "value" => $target->id])))
                ),
                TFOOT(TR(TD(["colspan" => 2], INPUT(["type" => "submit", "value" => "Aplicar moderação"]))))
            )
        );
    }

    private function build_revoke_form(int $action_id): HTMLElement
    {
        return SHM_SIMPLE_FORM(
            make_link("user_moderation/revoke"),
            emptyHTML(
                INPUT(["type" => "hidden", "name" => "action_id", "value" => $action_id]),
                LABEL("Motivo da revogação ", INPUT(["type" => "text", "name" => "reason", "value" => "Revogado por moderador"])),
                " ",
                INPUT(["type" => "submit", "value" => "Revogar ação ativa"])
            )
        );
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    private function build_active_table(array $rows): HTMLElement
    {
        $body = TBODY();
        foreach ($rows as $row) {
            $body->appendChild(TR(
                TD(A(["href" => make_link("user/" . $row["target_name"])], (string)$row["target_name"])),
                TD($this->format_action((string)$row["action"])),
                TD((string)$row["moderator_name"]),
                TD((string)$row["applied_class"]),
                TD((string)$row["reason"]),
                TD($this->format_ip_evidence($row["ip_evidence"] ?? [])),
                TD((string)$row["created"]),
                TD($row["expires"] === null ? "Nunca" : (string)$row["expires"])
            ));
        }

        return TABLE(
            ["class" => "zebra"],
            THEAD(TR(
                TH("Usuário"),
                TH("Ação"),
                TH("Moderador"),
                TH("Cargo atual"),
                TH("Motivo"),
                TH("IPs"),
                TH("Criada"),
                TH("Expira")
            )),
            $body
        );
    }

    /**
     * @param array<array<string, mixed>> $rows
     */
    private function build_history_table(array $rows): HTMLElement
    {
        $body = TBODY();
        foreach ($rows as $row) {
            $body->appendChild(TR(
                TD(A(["href" => make_link("user/" . $row["target_name"])], (string)$row["target_name"])),
                TD($this->format_action((string)$row["action"])),
                TD((string)$row["moderator_name"]),
                TD((string)$row["previous_class"], " -> ", (string)$row["applied_class"]),
                TD((string)$row["reason"]),
                TD($this->format_ip_evidence($row["ip_evidence"] ?? [])),
                TD((string)$row["created"]),
                TD($row["expires"] === null ? "Nunca" : (string)$row["expires"]),
                TD((bool)$row["revoked"] ? "Encerrada" : "Ativa")
            ));
        }

        return TABLE(
            ["class" => "zebra"],
            THEAD(TR(
                TH("Usuário"),
                TH("Ação"),
                TH("Moderador"),
                TH("Cargo"),
                TH("Motivo"),
                TH("IPs"),
                TH("Criada"),
                TH("Expira"),
                TH("Status")
            )),
            $body
        );
    }

    private function format_action(string $action): string
    {
        return match ($action) {
            "ban" => "Banimento",
            "silence" => "Silêncio",
            default => $action,
        };
    }

    /**
     * @param array<array{ip: string, source: string}> $evidence
     */
    private function format_ip_evidence(array $evidence): HTMLElement|string
    {
        if (count($evidence) === 0) {
            return "-";
        }

        $items = [];
        foreach ($evidence as $row) {
            $items[] = "{$row["ip"]} ({$row["source"]})";
        }

        return SMALL(implode(", ", $items));
    }
}
