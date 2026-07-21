<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationTest extends ShimmiePHPUnitTestCase
{
    public function testDatabaseUpgradeIsIdempotentWhenVersionIsStale(): void
    {
        Ctx::$config->set("ext_user_moderation_version", 0);

        (new UserModeration())->onDatabaseUpgrade(new DatabaseUpgradeEvent());

        self::assertSame(3, Ctx::$config->get("ext_user_moderation_version", ConfigType::INT));
    }
}
