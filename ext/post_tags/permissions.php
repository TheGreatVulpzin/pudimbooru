<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostTagsPermission extends PermissionGroup
{
    public const KEY = "post_tags";

    #[PermissionMeta("Edit post tag")]
    public const EDIT_IMAGE_TAG = "edit_image_tag";

    #[PermissionMeta("Edit own post tag")]
    public const EDIT_OWN_IMAGE_TAG = "edit_own_image_tag";

    #[PermissionMeta("Bulk edit post tag")]
    public const BULK_EDIT_IMAGE_TAG = "bulk_edit_image_tag";

    #[PermissionMeta("Mass tag edit")]
    public const MASS_TAG_EDIT = "mass_tag_edit";

    public static function can_edit_image_tag(User $user, Post $image): bool
    {
        return $user->can(self::EDIT_IMAGE_TAG) ||
            ($image->is_owned_by($user) && $user->can(self::EDIT_OWN_IMAGE_TAG));
    }
}
