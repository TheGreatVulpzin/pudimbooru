<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTitlesPermission extends PermissionGroup
{
    public const KEY = "post_titles";

    #[PermissionMeta("Edit post title")]
    public const EDIT_IMAGE_TITLE = "edit_image_title";

    #[PermissionMeta("Edit own post title")]
    public const EDIT_OWN_IMAGE_TITLE = "edit_own_image_title";

    public static function can_edit_image_title(User $user, Post $image): bool
    {
        return $user->can(self::EDIT_IMAGE_TITLE) ||
            ($image->owner_id === $user->id && $user->can(self::EDIT_OWN_IMAGE_TITLE));
    }
}
