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
        if ($event->page_matches("mail_manager", method: "GET", permission: MailPermission::MANAGE_MAIL_SETTINGS)) {
            $blocks = [];
            $groups = [new MailConfig()];
            foreach (MailTemplateConfigGroup::get_subclasses() as $class) {
                $groups[] = $class->newInstance();
            }
            foreach ($groups as $group) {
                if ($group::is_enabled()) {
                    $block = $this->theme->config_group_to_block(Ctx::$config, $group);
                    if ($block !== null) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->theme->display_manager_page($blocks, Ctx::$config->get(MailConfig::TEST_RECIPIENT));
        }

        if ($event->page_matches("mail_manager/save", method: "POST", permission: MailPermission::MANAGE_MAIL_SETTINGS)) {
            send_event(new ConfigSaveEvent(Ctx::$config, ConfigSaveEvent::postToSettings($event->POST)));
            Ctx::$page->flash("Mail settings saved");
            Ctx::$page->set_redirect(Url::referer_or(make_link("mail_manager")));
        }

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
            Ctx::$page->set_redirect(make_link("mail_manager"));
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system" && Ctx::$user->can(MailPermission::MANAGE_MAIL_SETTINGS)) {
            $event->add_nav_link(make_link("mail_manager"), "Gerenciador de E-mail");
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
        $fromAddress = $event->fromAddress ?: Ctx::$config->get(MailConfig::FROM_ADDRESS);
        if (!$fromAddress) {
            throw new ServerError("Mail from address is not configured");
        }

        $fromName = $event->fromName ?: Ctx::$config->get(MailConfig::FROM_NAME);
        $replyToAddress = $event->replyToAddress ?: Ctx::$config->get(MailConfig::REPLY_TO_ADDRESS);
        $email = (new Email())
            ->from(new Address($fromAddress, $fromName))
            ->to($event->to)
            ->subject($event->subject)
            ->text($event->textBody);

        if ($replyToAddress !== null && $replyToAddress !== "") {
            $email->replyTo($replyToAddress);
        }

        if ($event->htmlBody !== null) {
            $email->html($event->htmlBody);
        }

        foreach ($event->headers as $name => $value) {
            $email->getHeaders()->addTextHeader($name, $value);
        }

        return $email;
    }
}

final class MailTemplate
{
    /**
     * @param array<string, string> $placeholders
     * @param array<string, string> $headers
     */
    public static function send(MailTemplateConfigGroup $template, string $to, array $placeholders, array $headers = []): MailSendEvent
    {
        $event = new MailSendEvent(
            to: $to,
            subject: self::render(self::requiredConfigString($template->get_subject_key()), $placeholders),
            textBody: self::render(self::requiredConfigString($template->get_text_body_key()), $placeholders),
            htmlBody: self::html(self::requiredConfigString($template->get_html_body_key()), $placeholders),
            headers: $headers,
            fromAddress: self::optionalConfigString($template->get_from_address_key()),
            fromName: self::optionalConfigString($template->get_from_name_key()),
            replyToAddress: self::optionalConfigString($template->get_reply_to_address_key()),
        );
        return send_event($event);
    }

    /**
     * @param array<string, string> $placeholders
     */
    private static function render(string $template, array $placeholders): string
    {
        return strtr($template, $placeholders);
    }

    /**
     * @param array<string, string> $placeholders
     */
    private static function html(string $template, array $placeholders): ?string
    {
        $html = self::render($template, $placeholders);
        return $html === "" ? null : $html;
    }

    private static function requiredConfigString(string $key): string
    {
        $value = Ctx::$config->get($key);
        if (is_string($value)) {
            return $value;
        }
        throw new ServerError("Mail template config '$key' is invalid");
    }

    private static function optionalConfigString(string $key): ?string
    {
        $value = Ctx::$config->get($key);
        if ($value === null || $value === "") {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        throw new ServerError("Mail template config '$key' is invalid");
    }
}
