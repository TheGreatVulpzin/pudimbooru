<?php

declare(strict_types=1);

namespace Shimmie2;

final class DatabaseTest extends ShimmiePHPUnitTestCase
{
    public function testCountDatabase(): void
    {
        self::assertGreaterThan(0, Ctx::$database->count_tables());
    }

    public function testSchemaObjectDetection(): void
    {
        self::assertTrue(Ctx::$database->table_exists("users"));
        self::assertFalse(Ctx::$database->table_exists("missing_table"));
        self::assertTrue(Ctx::$database->column_exists("users", "name"));
        self::assertFalse(Ctx::$database->column_exists("users", "missing_column"));
        self::assertTrue(Ctx::$database->index_exists("users", "users_name_idx"));
        self::assertFalse(Ctx::$database->index_exists("users", "missing_index"));
    }
}
