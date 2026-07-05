<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetInfo extends ExtensionInfo
{
    public const KEY = "password_reset";

    public string $name = "Password Reset";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Allows users to reset their password by email";
    public array $dependencies = [MailInfo::KEY, UserNotificationsInfo::KEY];
    public ?string $documentation =
        "Adds a secure password reset flow by email.
<br><br>
It provides two mail templates in the Mail Manager:
<ul>
  <li><b>Password Reset Email</b>: sent with the reset link.</li>
  <li><b>Password Reset Success Email</b>: sent after a reset link is used successfully.</li>
</ul>
Template placeholders use double braces:
<ul>
  <li><code>{{link}}</code>: password reset URL, only available in the reset request email.</li>
  <li><code>{{username}}</code>: account username.</li>
  <li><code>{{site}}</code>: site title.</li>
</ul>";
}
