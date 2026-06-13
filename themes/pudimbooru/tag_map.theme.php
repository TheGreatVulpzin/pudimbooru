<?php

declare(strict_types=1);

namespace Shimmie2;

class PudimbooruTagMapTheme extends TagMapTheme
{
    protected function display_nav(): void
    {
        Ctx::$page->set_layout("no-left");
        parent::display_nav();
    }
}
