<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserNotificationsInfo extends ExtensionInfo
{
    public const KEY = "user_notifications";

    public string $name = "User Notifications";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::GENERAL;
    public string $description = "Sends account notification emails to users";
    public array $dependencies = [MailInfo::KEY];
    public ?string $documentation =
        "Sends notification emails for account changes.
<br><br>
Current template:
<ul>
  <li><b>Password Changed Email</b>: sent when a password is changed from the site user/admin panel.</li>
  <li><b>Email Verification Email</b>: sent to verify a new email address.</li>
  <li><b>Email Changed Old Address Email</b>: sent to the previous email address after a change.</li>
</ul>
This extension ignores password reset completions; those are handled by the Password Reset extension.
<br><br>
Template placeholders use double braces:
<ul>
  <li><code>{{username}}</code>: affected account username.</li>
  <li><code>{{site}}</code>: site title.</li>
  <li><code>{{actor}}</code>: user who performed the change.</li>
  <li><code>{{verification_link}}</code>: email verification URL.</li>
  <li><code>{{old_email}}</code>: previous email address.</li>
  <li><code>{{new_email}}</code>: new email address.</li>
</ul>";
}
