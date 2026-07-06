<?php

declare(strict_types=1);

namespace Shimmie2;

final class RelationshipsPermission extends PermissionGroup
{
    public const KEY = "relationships";

    #[PermissionMeta("Edit post relationships")]
    public const EDIT_IMAGE_RELATIONSHIPS = "edit_image_relationships";

    #[PermissionMeta("Edit own post relationships")]
    public const EDIT_OWN_IMAGE_RELATIONSHIPS = "edit_own_image_relationships";

    #[PermissionMeta("Bulk-edit post relationships")]
    public const BULK_PARENT_CHILD = "bulk_parent_child";

    public static function can_edit_image_relationships(User $user, Post $image): bool
    {
        return $user->can(self::EDIT_IMAGE_RELATIONSHIPS) ||
            ($image->owner_id === $user->id && $user->can(self::EDIT_OWN_IMAGE_RELATIONSHIPS));
    }
}
