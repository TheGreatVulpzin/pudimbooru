<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationConfig extends ConfigGroup
{
    public const KEY = "user_moderation";
    public ?string $title = "User Moderation";

    #[ConfigMeta("Ban evasion mode", ConfigType::STRING, default: "auto", options: [
        "Auto-ban" => "auto",
        "Detect only" => "detect",
        "Off" => "off",
    ])]
    public const BAN_EVASION_MODE = "user_moderation_ban_evasion_mode";

    #[ConfigMeta("IP ban mode for account bans", ConfigType::STRING, default: "ghost", options: [
        "Ghost" => "ghost",
        "Anon Ghost" => "anon-ghost",
        "Block" => "block",
    ])]
    public const IP_BAN_MODE = "user_moderation_ip_ban_mode";

    #[ConfigMeta("Max IPs linked per account ban", ConfigType::INT, default: 5)]
    public const MAX_IPS_PER_BAN = "user_moderation_max_ips_per_ban";
}
