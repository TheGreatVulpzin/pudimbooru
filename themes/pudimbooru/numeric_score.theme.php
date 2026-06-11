<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\{A, BR, DIV, H3, INPUT, P, emptyHTML};

use MicroHTML\HTMLElement;

class PudimbooruNumericScoreTheme extends NumericScoreTheme
{
    public function get_voter(Post $image): void
    {
        $vote_form = function (int $image_id, int $vote, string $text): HTMLElement {
            return SHM_SIMPLE_FORM(
                make_link("numeric_score/vote"),
                INPUT(['type' => 'hidden', 'name' => 'image_id', 'value' => $image_id]),
                INPUT(['type' => 'hidden', 'name' => 'vote', 'value' => $vote]),
                SHM_SUBMIT($text)
            );
        };
        $remove_votes = null;
        $voters = null;
        if (Ctx::$user->can(NumericScorePermission::EDIT_OTHER_VOTE)) {
            $remove_votes = SHM_SIMPLE_FORM(
                make_link("numeric_score/remove_votes_on"),
                INPUT(['type' => 'hidden', 'name' => 'image_id', 'value' => $image->id]),
                SHM_SUBMIT(PudimbooruLocale::score("Remove All Votes"))
            );
            $votes_url_json = json_encode(make_link("numeric_score/votes/$image->id"));
            $voters = emptyHTML(
                BR(),
                DIV(
                    ["id" => "votes-content"],
                    A(
                        [
                            "href" => make_link("numeric_score/votes/$image->id"),
                            "onclick" => 'fetch(' . $votes_url_json . ').then(r => r.text()).then(html => document.getElementById("votes-content").innerHTML = html); return false;',
                        ],
                        PudimbooruLocale::score("See All Votes")
                    )
                ),
            );
        }
        $html = emptyHTML(
            $vote_form($image->id, 1, PudimbooruLocale::score("Vote Up")),
            $vote_form($image->id, 0, PudimbooruLocale::score("Remove Vote")),
            $vote_form($image->id, -1, PudimbooruLocale::score("Vote Down")),
            $remove_votes,
            $voters
        );
        Ctx::$page->add_block(new Block(PudimbooruLocale::score("Post Score") . ": " . $image['numeric_score'], $html, "left", 20, id: "Post_Scoreleft"));
    }

    public function get_nuller(User $duser): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("numeric_score/remove_votes_by"),
            INPUT(["type" => "hidden", "name" => "user_id", "value" => $duser->id]),
            SHM_SUBMIT(PudimbooruLocale::score("Delete all votes by this user"))
        );
        Ctx::$page->add_block(new Block(PudimbooruLocale::score("Votes"), $html, "main", 80));
    }

    /**
     * @param Post[] $images
     */
    public function view_popular(
        array $images,
        string $current,
        Url $b_dte,
        Url $f_dte,
    ): void {
        $pop_images = [];
        foreach ($images as $image) {
            $pop_images[] = $this->build_thumb($image);
        }

        $html = emptyHTML(
            H3(
                ["style" => "text-align: center;"],
                A(["href" => $b_dte], "<<"),
                " $current ",
                A(["href" => $f_dte], ">>")
            ),
            DIV(["class" => "shm-image-list"], ...$pop_images)
        );

        Ctx::$page->set_title(PudimbooruLocale::score("Popular Posts"));
        $this->display_navigation();
        Ctx::$page->add_block(new Block(null, $html, "main", 30));
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Pesquisa por posts que receberam votos numéricos pelo score ou pelo votante."),
            SHM_COMMAND_EXAMPLE("score=1", "Pesquisa por posts com uma pontuação de 1"),
            SHM_COMMAND_EXAMPLE("score>0", "Pesquisa por posts com uma pontuação de 1 ou mais"),
            P("Pode usar <, <=, >, >=, ou =."),
            SHM_COMMAND_EXAMPLE("upvoted_by=username", "Pesquisa por posts votados positivamente por 'username'"),
            SHM_COMMAND_EXAMPLE("downvoted_by=username", "Pesquisa por posts votados negativamente por 'username'"),
            SHM_COMMAND_EXAMPLE("order=score_desc", "Pesquisa por posts ordenados pelo score em ordem decrescente"),
            SHM_COMMAND_EXAMPLE("order=score_asc", "Pesquisa por posts ordenados pelo score em ordem crescente")
        );
    }
}
