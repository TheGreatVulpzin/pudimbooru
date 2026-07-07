<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationConfig extends ConfigGroup
{
    public const KEY = "user_moderation";
    public ?string $title = "User Moderation";

    #[ConfigMeta("Auto-ban ban evasion", ConfigType::BOOL, default: true)]
    public const AUTO_BAN_EVASION = "user_moderation_auto_ban_evasion";

    #[ConfigMeta("IP ban mode for account bans", ConfigType::STRING, default: "ghost", options: [
        "Ghost" => "ghost",
        "Anon Ghost" => "anon-ghost",
        "Block" => "block",
    ])]
    public const IP_BAN_MODE = "user_moderation_ip_ban_mode";

    #[ConfigMeta("Max IPs linked per account ban", ConfigType::INT, default: 5)]
    public const MAX_IPS_PER_BAN = "user_moderation_max_ips_per_ban";
}
