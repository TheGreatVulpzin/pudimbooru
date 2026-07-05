<?php

declare(strict_types=1);

namespace Shimmie2;

final class MailSendEvent extends Event
{
    public bool $sent = false;
    public ?string $error = null;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $to,
        public string $subject,
        public string $textBody,
        public ?string $htmlBody = null,
        public array $headers = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        public ?string $replyToAddress = null,
    ) {
        parent::__construct();
    }
}
