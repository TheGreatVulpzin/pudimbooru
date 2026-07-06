<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{emptyHTML, joinHTML};
use function MicroHTML\{HR, P, UL};

use MicroHTML\HTMLElement;

class MimeSystemTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        $mimes = DataHandlerExtension::get_all_supported_mimes();
        $exts = array_map(fn ($mime) => FileExtension::get_for_mime($mime), $mimes);
        return emptyHTML(
            P("Busque por posts pela extensão"),
            SHM_COMMAND_EXAMPLE("ext=jpg", "Pesquisa por posts com a extensão 'jpg'"),
            P("Essas extensão estão disponíveis no sistema:"),
            UL(joinHTML(", ", $exts)),
            HR(),
            P("Procure por posts pelo tipo de MIME"),
            SHM_COMMAND_EXAMPLE("mime=image/jpeg", "Pesquisa por posts com o tipo de MIME 'image/jpeg'"),
            P("Esses tipos MIME estão disponíveis no sistema:"),
            UL(joinHTML(", ", array_map(fn ($mime) => (string)$mime, $mimes))),
        );
    }
}
