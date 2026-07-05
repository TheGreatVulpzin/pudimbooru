<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Mailer\{Mailer as SymfonyMailer, Transport};
use Symfony\Component\Mime\{Address, Email};

/** @extends Extension<MailTheme> */
final class Mail extends Extension
{
    public const KEY = "mail";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("mail/test", method: "POST", permission: MailPermission::MANAGE_MAIL_SETTINGS)) {
            $to = $event->POST->req("to");
            $mail = send_event(new MailSendEvent(
                $to,
                "Pudimbooru mail test",
                "This is a test email from Pudimbooru."
            ));

            if ($mail->sent) {
                Ctx::$config->set(MailConfig::TEST_RECIPIENT, $to);
                Ctx::$page->flash("Test email sent");
            } else {
                throw new ServerError($mail->error ?? "Unable to send test email");
            }
            Ctx::$page->set_redirect(make_link("admin"));
        }
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        if (Ctx::$user->can(MailPermission::MANAGE_MAIL_SETTINGS)) {
            $this->theme->display_test_block(Ctx::$config->get(MailConfig::TEST_RECIPIENT));
        }
    }

    #[EventListener]
    public function onMailSend(MailSendEvent $event): void
    {
        try {
            $mailer = new SymfonyMailer(Transport::fromDsn(self::buildDsn()));
            $mailer->send(self::buildEmail($event));
            $event->sent = true;
            Log::info("mail", "Sent email to {$event->to}");
        } catch (\Throwable $ex) {
            $event->error = $ex->getMessage();
            Log::error("mail", "Failed to send email to {$event->to}: {$ex->getMessage()}");
        }
    }

    public static function buildDsn(): string
    {
        $host = Ctx::$config->get(MailConfig::SMTP_HOST);
        if (!$host) {
            throw new ServerError("SMTP host is not configured");
        }

        $port = Ctx::$config->get(MailConfig::SMTP_PORT);
        $username = Ctx::$config->get(MailConfig::SMTP_USERNAME);
        $password = Ctx::$config->get(MailConfig::SMTP_PASSWORD);
        $encryption = Ctx::$config->get(MailConfig::SMTP_ENCRYPTION);

        $scheme = $encryption === "ssl" ? "smtps" : "smtp";
        $auth = "";
        if ($username !== null && $username !== "") {
            $auth = rawurlencode($username);
            if ($password !== null && $password !== "") {
                $auth .= ":" . rawurlencode($password);
            }
            $auth .= "@";
        }

        $dsn = "$scheme://$auth$host:$port";
        if ($encryption === "none") {
            $dsn .= "?auto_tls=0";
        }
        return $dsn;
    }

    public static function buildEmail(MailSendEvent $event): Email
    {
        $fromAddress = Ctx::$config->get(MailConfig::FROM_ADDRESS);
        if (!$fromAddress) {
            throw new ServerError("Mail from address is not configured");
        }

        $fromName = Ctx::$config->get(MailConfig::FROM_NAME);
        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to($event->to)
            ->subject($event->subject)
            ->text($event->textBody);

        if ($event->htmlBody !== null) {
            $email->html($event->htmlBody);
        }

        foreach ($event->headers as $name => $value) {
            $email->getHeaders()->addTextHeader($name, $value);
        }

        return $email;
    }
}
