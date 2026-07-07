<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserAccountsPermission extends PermissionGroup
{
    public const KEY = "user";

    #[PermissionMeta("Sign up for own account")]
    public const CREATE_USER = "create_user";

    #[PermissionMeta("Change own settings")]
    public const CHANGE_USER_SETTING = "change_user_setting";

    #[PermissionMeta("Create other users")]
    public const CREATE_OTHER_USER = "create_other_user";

    #[PermissionMeta("View user list")]
    public const VIEW_USER_LIST = "view_user_list";

    #[PermissionMeta("Edit other users' names")]
    public const EDIT_USER_NAME = "edit_user_name";

    #[PermissionMeta("Edit other users' passwords")]
    public const EDIT_USER_PASSWORD = "edit_user_password";

    #[PermissionMeta("Edit other users' info (eg email address)")]
    public const EDIT_USER_INFO = "edit_user_info";

    #[PermissionMeta("Edit other users' classes", advanced: true)]
    public const EDIT_USER_CLASS = "edit_user_class";

    #[PermissionMeta("Delete other users")]
    public const DELETE_USER = "delete_user";

    #[PermissionMeta("Change other users' settings")]
    public const CHANGE_OTHER_USER_SETTING = "change_other_user_setting";

    #[PermissionMeta("View other users' IP history", advanced: true)]
    public const VIEW_USER_IPS = "view_user_ips";

    #[PermissionMeta("Protected", advanced: true, help: "Only admins can modify protected users (stops a moderator from changing an admin's password)")]
    public const PROTECTED = "protected";

    #[PermissionMeta("Skip signup CAPTCHA")]
    public const SKIP_SIGNUP_CAPTCHA = "bypass_signup_captcha";

    #[PermissionMeta("Skip login CAPTCHA")]
    public const SKIP_LOGIN_CAPTCHA = "bypass_login_captcha";

    #[PermissionMeta("Bypass content checks")]
    public const BYPASS_CONTENT_CHECKS = "bypass_content_checks";

    /**
     * @return list<string>
     */
    public static function get_user_management_permissions(): array
    {
        return [
            self::EDIT_USER_NAME,
            self::EDIT_USER_PASSWORD,
            self::EDIT_USER_INFO,
            self::EDIT_USER_CLASS,
            self::DELETE_USER,
            self::CHANGE_OTHER_USER_SETTING,
        ];
    }

    public static function can_manage_user_accounts(User $viewer): bool
    {
        foreach (self::get_user_management_permissions() as $permission) {
            if ($viewer->can($permission)) {
                return true;
            }
        }
        return false;
    }

    public static function can_view_user_list(User $viewer): bool
    {
        return $viewer->can(self::VIEW_USER_LIST) || self::can_manage_user_accounts($viewer);
    }

    public static function can_manage_user(User $viewer, User $target, string $permission): bool
    {
        if ($viewer->is_anonymous()) {
            return false;
        }
        if (
            $viewer->id === $target->id &&
            \in_array($permission, [self::EDIT_USER_PASSWORD, self::EDIT_USER_INFO], true)
        ) {
            return $viewer->can(self::CHANGE_USER_SETTING) || $viewer->can($permission);
        }
        if ($target->can(self::PROTECTED) && $viewer->class->name !== "admin") {
            return false;
        }
        return $viewer->can($permission);
    }

    public static function can_manage_anything_for_user(User $viewer, User $target): bool
    {
        foreach (self::get_user_management_permissions() as $permission) {
            if (self::can_manage_user($viewer, $target, $permission)) {
                return true;
            }
        }
        return false;
    }
}
