<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailTest extends ShimmiePHPUnitTestCase
{
    public function testBuildDsn(): void
    {
        Ctx::$config->set(MailConfig::SMTP_HOST, "smtp.example.com");
        Ctx::$config->set(MailConfig::SMTP_PORT, 465);
        Ctx::$config->set(MailConfig::SMTP_USERNAME, "user@example.com");
        Ctx::$config->set(MailConfig::SMTP_PASSWORD, "secret password");
        Ctx::$config->set(MailConfig::SMTP_ENCRYPTION, "ssl");

        self::assertSame(
            "smtps://user%40example.com:secret%20password@smtp.example.com:465",
            Mail::buildDsn()
        );
    }

    public function testBuildEmail(): void
    {
        Ctx::$config->set(MailConfig::FROM_ADDRESS, "admin@example.com");
        Ctx::$config->set(MailConfig::FROM_NAME, "Pudimbooru");
        Ctx::$config->set(MailConfig::REPLY_TO_ADDRESS, "support@example.com");

        $event = new MailSendEvent(
            "user@example.com",
            "Subject",
            "Text body",
            "<p>HTML body</p>",
            ["X-Test" => "yes"]
        );
        $email = Mail::buildEmail($event);

        self::assertSame("Subject", $email->getSubject());
        self::assertSame("Text body", $email->getTextBody());
        self::assertSame("<p>HTML body</p>", $email->getHtmlBody());
        self::assertSame("admin@example.com", $email->getFrom()[0]->getAddress());
        self::assertSame("user@example.com", $email->getTo()[0]->getAddress());
        self::assertSame("support@example.com", $email->getReplyTo()[0]->getAddress());
        self::assertSame("yes", $email->getHeaders()->get("X-Test")?->getBodyAsString());
    }

    public function testBuildEmailWithPerMessageSender(): void
    {
        Ctx::$config->set(MailConfig::FROM_ADDRESS, "admin@example.com");
        Ctx::$config->set(MailConfig::FROM_NAME, "Pudimbooru");
        Ctx::$config->set(MailConfig::REPLY_TO_ADDRESS, "support@example.com");

        $event = new MailSendEvent(
            "user@example.com",
            "Subject",
            "Text body",
            fromAddress: "accounts@example.com",
            fromName: "Accounts",
            replyToAddress: "helpdesk@example.com"
        );
        $email = Mail::buildEmail($event);

        self::assertSame("accounts@example.com", $email->getFrom()[0]->getAddress());
        self::assertSame("Accounts", $email->getFrom()[0]->getName());
        self::assertSame("helpdesk@example.com", $email->getReplyTo()[0]->getAddress());
    }

    public function testDisabledDeliverySkipsSend(): void
    {
        Ctx::$config->set(MailConfig::ENABLED, false);

        try {
            $event = new MailSendEvent(
                "user@example.com",
                "Subject",
                "Text body",
            );
            (new Mail())->onMailSend($event);

            self::assertFalse($event->sent);
            self::assertSame(Mail::DELIVERY_DISABLED_MESSAGE, $event->error);
        } finally {
            Ctx::$config->set(MailConfig::ENABLED, true);
        }
    }
}
