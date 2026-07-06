<?php

declare(strict_types=1);

namespace Shimmie2;

final class PostDescriptionPermission extends PermissionGroup
{
    public const KEY = "post_description";

    #[PermissionMeta("Edit post descriptions")]
    public const EDIT_IMAGE_DESCRIPTIONS = "edit_image_descriptions";

    #[PermissionMeta("Edit own post descriptions")]
    public const EDIT_OWN_IMAGE_DESCRIPTIONS = "edit_own_image_descriptions";

    public static function can_edit_image_descriptions(User $user, Post $image): bool
    {
        return $user->can(self::EDIT_IMAGE_DESCRIPTIONS) ||
            ($image->owner_id === $user->id && $user->can(self::EDIT_OWN_IMAGE_DESCRIPTIONS));
    }
}
