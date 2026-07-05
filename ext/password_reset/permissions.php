<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetPermission extends PermissionGroup
{
    public const KEY = "password_reset";
    public ?string $title = "Password Reset";

    #[PermissionMeta("Request password reset")]
    public const REQUEST_PASSWORD_RESET = "request_password_reset";
}
