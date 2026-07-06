<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostSourcePermission extends PermissionGroup
{
    public const KEY = "post_source";

    #[PermissionMeta("Edit post source")]
    public const EDIT_IMAGE_SOURCE = "edit_image_source";

    #[PermissionMeta("Edit own post source")]
    public const EDIT_OWN_IMAGE_SOURCE = "edit_own_image_source";

    #[PermissionMeta("Bulk edit post source")]
    public const BULK_EDIT_IMAGE_SOURCE = "bulk_edit_image_source";

    public static function can_edit_image_source(User $user, Post $image): bool
    {
        return $user->can(self::EDIT_IMAGE_SOURCE) ||
            ($image->is_owned_by($user) && $user->can(self::EDIT_OWN_IMAGE_SOURCE));
    }
}
