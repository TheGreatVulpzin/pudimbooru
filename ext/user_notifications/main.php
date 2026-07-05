<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserNotifications extends Extension
{
    public const KEY = "user_notifications";

    #[EventListener]
    public function onUserPasswordChanged(UserPasswordChangedEvent $event): void
    {
        if ($event->source !== "user_admin") {
            return;
        }

        if ($event->user->email === null || $event->user->email === "") {
            return;
        }

        $mail = MailTemplate::send(new UserPasswordChangedEmailConfig(), $event->user->email, [
            'username' => $event->user->name,
            'site' => Ctx::$config->get(SetupConfig::TITLE),
            'actor' => $event->actor->name,
        ]);

        if (!$mail->sent) {
            Log::error("user_notifications", "Password changed email failed for user #{$event->user->id}");
        }
    }
}
