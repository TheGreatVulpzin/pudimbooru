<?php

declare(strict_types=1);

namespace Shimmie2;

use PHPUnit\Framework\TestCase;

class MicroHTMLTest extends TestCase
{
    public function test_date(): void
    {
        $html = (string)SHM_DATE("2012-06-23 16:14:22");

        self::assertStringContainsString("datetime='2012-06-23T16:14:22+00:00'", $html);
        self::assertStringContainsString("2012", $html);
        self::assertStringContainsString("16:14", $html);
    }
}
