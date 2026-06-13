<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use MicroHTML\HTMLElement;

use function MicroHTML\{A, BR, DIV, INPUT, P, TABLE, TD, TR, emptyHTML, joinHTML};

class PudimbooruViewPostTheme extends ViewPostTheme
{
    /**
     * @param HTMLElement[] $editor_parts
     * @param HTMLElement[] $sidebar_parts
     */
    public function display_page(Post $image, array $editor_parts, array $sidebar_parts): void
    {
        Ctx::$page->set_heading($image->get_tag_list());
        Ctx::$page->add_block(new Block(PudimbooruLocale::general_ui("Search"), $this->build_navigation($image), "left", 0));
        Ctx::$page->add_block(new Block(PudimbooruLocale::general_ui("Information"), $this->build_stats($image), "left", 15));
        Ctx::$page->add_block(new Block(null, $this->build_info($image, $editor_parts), "main", 15));
    }

    /**
     * @param HTMLElement[] $parts
     */
    public function display_admin_block(array $parts): void
    {
        if (count($parts) > 0) {
            Ctx::$page->add_block(new Block(PudimbooruLocale::post_view("Post Controls"), DIV(["class" => "post_controls"], joinHTML("", $parts)), "left", 50));
        }
    }

    protected function build_pin(Post $image): HTMLElement
    {
        if ($this->is_ordered_search()) {
            return A(["href" => make_link()], PudimbooruLocale::post_view("Index"));
        } else {
            $query = $this->get_query();
            return joinHTML(" | ", [
                A(["href" => make_link("post/prev/{$image->id}", $query), "class" => "prevlink"], PudimbooruLocale::post_view("Prev")),
                A(["href" => make_link()], PudimbooruLocale::post_view("Index")),
                A(["href" => make_link("post/next/{$image->id}", $query), "class" => "nextlink"], PudimbooruLocale::post_view("Next")),
            ]);
        }
    }

    protected function build_navigation(Post $image): HTMLElement
    {
        $pin = $this->build_pin($image);

        $search = SHM_FORM(
            action: search_link(),
            method: 'GET',
            children: [
                INPUT([
                    "name" => 'search',
                    "type" => 'text',
                    "class" => 'autocomplete_tags',
                ]),
                INPUT([
                    "type" => 'submit',
                    "value" => PudimbooruLocale::post_view("Find"),
                    "style" => 'display: none;'
                ]),
            ]
        );

        return emptyHTML($pin, P(), $search);
    }

    /**
     * @param HTMLElement[] $editor_parts
     * @param HTMLElement[] $sidebar_parts
     */
    protected function build_info(Post $image, array $editor_parts, array $sidebar_parts = []): HTMLElement
    {
        if (count($editor_parts) === 0) {
            return emptyHTML($image->is_locked() ? "[" . PudimbooruLocale::post_view("Post Locked") . "]" : "");
        }

        if (
            (!$image->is_locked() || Ctx::$user->can(PostLockPermission::EDIT_IMAGE_LOCK)) &&
            Ctx::$user->can(PostTagsPermission::EDIT_IMAGE_TAG)
        ) {
            $editor_parts[] = TR(TD(
                ["colspan" => 4],
                INPUT(["class" => "view", "type" => "button", "value" => PudimbooruLocale::post_view("Edit"), "onclick" => "clearViewMode()"]),
                INPUT(["class" => "edit", "type" => "submit", "value" => PudimbooruLocale::post_view("Set")])
            ));
        }

        // Add sidebar parts (like avatars) to the first row
        if (count($sidebar_parts) > 0) {
            $sidebar_content = DIV(["class" => "image-info-sidebar"], ...$sidebar_parts);
            array_values($editor_parts)[0]->appendChild(
                TD(
                    ["class" => "image-info-sidebar-box", "rowspan" => count($editor_parts) - 2],
                    $sidebar_content
                )
            );
        }

        return SHM_SIMPLE_FORM(
            make_link("post/set"),
            INPUT(["type" => "hidden", "name" => "image_id", "value" => $image->id]),
            TABLE(
                [
                    "class" => "image_info form",
                ],
                ...$editor_parts,
            ),
        );
    }

    protected function build_stats(Post $image): HTMLElement
    {
        $owner = $image->get_owner()->name;
        $ip = Ctx::$user->can(IPBanPermission::VIEW_IP) ? " ({$image->owner_ip})" : "";

        $parts = [
            "ID: {$image->id}",
            emptyHTML(PudimbooruLocale::post_view("Uploader") . ": ", A(["href" => make_link("user/$owner")], $owner . $ip)),
            emptyHTML(PudimbooruLocale::post_view("Date") . ": ", SHM_DATE($image->posted)),
            PudimbooruLocale::post_view("Size") . ": " . to_shorthand_int($image->filesize) . " ({$image->width}x{$image->height})",
            PudimbooruLocale::post_view("Type") . ": {$image->get_mime()}",
        ];
        if ($image->video_codec !== null) {
            $parts[] = PudimbooruLocale::post_view("Video Codec") . ": {$image->video_codec->name}";
        }
        if ($image->length !== null) {
            $parts[] = PudimbooruLocale::post_view("Length") . ": " . format_milliseconds($image->length);
        }
        if ($image->source !== null) {
            $parts[] = emptyHTML(PudimbooruLocale::post_view("Source") . ": ", A(["href" => $image->source], "link"));
        }
        if (RatingsInfo::is_enabled()) {
            $rating = $image['rating'] ?? "?";
            $h_rating = Ratings::rating_to_human($rating);
            $parts[] = emptyHTML(PudimbooruLocale::post_view("Rating") . ": ", A(["href" => search_link(["rating=$rating"])], $h_rating));
        }

        return joinHTML(BR(), $parts);
    }
}
