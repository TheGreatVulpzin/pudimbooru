<?php

declare(strict_types=1);

namespace Shimmie2;

require_once __DIR__ . '/locale.php';

use function MicroHTML\{A, BR, emptyHTML};

class PudimbooruHelpPagesTheme extends HelpPagesTheme
{
    /**
     * @param array<string,string> $pages
     */
    public function display_help_page(string $title, array $pages): void
    {
        Ctx::$page->set_title(PudimbooruLocale::translate("Help") . " - " . PudimbooruLocale::translate($title));
        $this->display_navigation();
    }
}
