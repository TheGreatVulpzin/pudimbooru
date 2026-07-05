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
        self::assertSame("yes", $email->getHeaders()->get("X-Test")?->getBodyAsString());
    }
}
