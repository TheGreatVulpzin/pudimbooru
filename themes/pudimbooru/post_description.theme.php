<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use MicroHTML\HTMLElement;

use function MicroHTML\{TEXTAREA, rawHTML};

class PudimbooruPostDescriptionTheme extends PostDescriptionTheme
{
    public function get_description_editor_html(string $raw_description, Post $image): HTMLElement
    {
        $tfe = send_event(new TextFormattingEvent($raw_description));

        return SHM_POST_INFO(
            PudimbooruLocale::post_description("Description"),
            rawHTML($tfe->formatted),
            PostDescriptionPermission::can_edit_image_descriptions(Ctx::$user, $image)
            ? TEXTAREA([
                "type" => "text",
                "name" => "description",
                "id" => "description_editor",
                ], $raw_description)
            : null
        );
    }
}
