<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailInfo extends ExtensionInfo
{
    public const KEY = "mail";

    public string $name = "Mail";
    public array $authors = ["Vulpzin" => null];
    public ExtensionCategory $category = ExtensionCategory::INTEGRATION;
    public string $description = "Provides reusable SMTP email delivery for other extensions";
    public ?string $documentation =
        "The Mail extension provides SMTP delivery and a reusable template system for other extensions.
<br><br>
Templates use placeholders wrapped in double braces, for example <code>{{username}}</code>, <code>{{site}}</code>, and <code>{{link}}</code>.
Legacy placeholders like <code>\$username</code>, <code>\$usuario</code>, and <code>\$link</code> are still supported for older saved templates.
<br><br>
Extensions can add configurable email templates by extending <code>MailTemplateConfigGroup</code> and sending them with <code>MailTemplate::send()</code>.
Extensions can add operational panels to the mail manager by extending <code>MailToolConfigGroup</code>.";
}
