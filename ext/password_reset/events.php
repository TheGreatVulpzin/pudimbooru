<?php

declare(strict_types=1);

namespace Shimmie2;

final class PasswordResetRequestEvent extends Event
{
    public function __construct(public string $login)
    {
        parent::__construct();
    }
}
