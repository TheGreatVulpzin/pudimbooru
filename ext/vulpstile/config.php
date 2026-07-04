<?php

declare(strict_types=1);

namespace Shimmie2;

final class VulpstileConfig extends ConfigGroup
{
    public const KEY = "vulpstile";
    public ?string $title = "Vulpstile";

    #[ConfigMeta("Secret key", ConfigType::STRING)]
    public const VULPSTILE_PRIVKEY = "api_vulpstile_privkey";

    #[ConfigMeta("Site key", ConfigType::STRING)]
    public const VULPSTILE_PUBKEY = "api_vulpstile_pubkey";
}
