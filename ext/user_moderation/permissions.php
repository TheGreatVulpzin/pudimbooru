<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationPermission extends PermissionGroup
{
    public const KEY = "user_moderation";

    #[PermissionMeta("Moderate users")]
    public const MODERATE_USERS = "moderate_users";

    #[PermissionMeta("View user moderation history")]
    public const VIEW_USER_MODERATION = "view_user_moderation";
}
