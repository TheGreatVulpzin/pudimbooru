<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{DIV, INPUT, SCRIPT, SMALL, SPAN, TABLE, TD, TH, TR, emptyHTML};

use MicroHTML\HTMLElement;

class PudimbooruUploadTheme extends UploadTheme
{
    public function display_block(): void
    {
        // this theme links to /upload
    }

    public function display_page(): void
    {
        Ctx::$page->set_layout("no-left");

        $limits = get_upload_limits();

        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $max_size = $limits['shm_filesize'];
        $max_kb = to_shorthand_int($max_size);
        $max_total_size = $limits['shm_post'];
        $max_total_kb = to_shorthand_int($max_total_size);
        $upload_list = $this->build_upload_list();

        $common_fields = emptyHTML();
        $ucbe = send_event(new UploadCommonBuildingEvent());
        foreach ($ucbe->get_parts() as $part) {
            $common_fields->appendChild($part);
        }
        $captcha = Captcha::get_html(UploadPermission::SKIP_UPLOAD_CAPTCHA);

        $form = SHM_FORM(make_link("upload"), multipart: true, id: "file_upload");
        $form->appendChild(
            TABLE(
                ["id" => "large_upload_form", "class" => "form"],
                $common_fields,
                $upload_list,
                $captcha ? TR(
                    TD(["colspan" => "7"], $captcha)
                ) : null,
                TR(
                    TD(["colspan" => "7"], INPUT(["id" => "uploadbutton", "type" => "submit", "value" => "Publicar"]))
                ),
            )
        );
        $html = emptyHTML(
            $form,
            SMALL(
                "(",
                $max_size > 0 ? "Limite por arquivo $max_kb" : null,
                $max_size > 0 && $max_total_size > 0 ? " / " : null,
                $max_total_size > 0 ? "Limite total $max_total_kb" : null,
                " / Total atual ",
                SPAN(["id" => "upload_size_tracker"], "0KB"),
                ")"
            ),
            SCRIPT("
            window.shm_max_size = $max_size;
            window.shm_max_total_size = $max_total_size;
            ")
        );

        $page = Ctx::$page;
        $page->set_title("Enviar");
        $this->display_navigation();
        $page->add_block(new Block("Enviar", $html, "main", 20));
        if ($tl_enabled) {
            $page->add_block(new Block("Bookmarklets", $this->build_bookmarklets(), "left", 20));
        }
    }

    protected function build_upload_list(): HTMLElement
    {
        $upload_list = emptyHTML();
        $upload_count = Ctx::$config->get(UploadConfig::COUNT);
        $tl_enabled = (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) !== "none");
        $accept = $this->get_accept();

        $headers = emptyHTML();
        $uhbe = send_event(new UploadHeaderBuildingEvent());
        foreach ($uhbe->get_parts() as $part) {
            $headers->appendChild(
                TH("Específico do post $part")
            );
        }

        $upload_list->appendChild(
            TR(
                ["class" => "header"],
                TH(["colspan" => 2], "Selecionar arquivo"),
                TH($tl_enabled ? "ou URL" : null),
                $headers,
            )
        );

        for ($i = 0; $i < $upload_count; $i++) {
            $specific_fields = emptyHTML();
            $usfbe = send_event(new UploadSpecificBuildingEvent((string)$i));
            foreach ($usfbe->get_parts() as $part) {
                $specific_fields->appendChild($part);
            }

            $upload_list->appendChild(
                TR(
                    TD(
                        ["colspan" => 2, "style" => "white-space: nowrap;"],
                        DIV([
                            "id" => "canceldata{$i}",
                            "style" => "display:inline;margin-right:5px;font-size:15px;visibility:hidden;",
                            "onclick" => "document.getElementById('data{$i}').value='';updateTracker();",
                        ], "✖"),
                        INPUT([
                            "type" => "file",
                            "id" => "data{$i}",
                            "name" => "data{$i}[]",
                            "accept" => $accept,
                            "multiple" => true,
                        ]),
                    ),
                    TD(
                        $tl_enabled ? INPUT([
                            "type" => "text",
                            "name" => "url{$i}",
                            "value" => ($i === 0) ? @$_GET['url'] : null,
                        ]) : null
                    ),
                    $specific_fields,
                )
            );
        }

        return $upload_list;
    }
}
