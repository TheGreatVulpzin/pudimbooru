<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserNotificationsTest extends ShimmiePHPUnitTestCase
{
    public function testDatabaseUpgradeIsIdempotentWhenVersionIsStale(): void
    {
        Ctx::$config->set("ext_user_notifications_version", 0);

        (new UserNotifications())->onDatabaseUpgrade(new DatabaseUpgradeEvent());

        self::assertSame(1, Ctx::$config->get("ext_user_notifications_version", ConfigType::INT));
    }
}
