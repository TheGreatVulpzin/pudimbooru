<?php

declare(strict_types=1);

namespace Shimmie2;

final class UserModerationApplyEvent extends Event
{
    public function __construct(
        public User $target,
        public User $moderator,
        public string $action,
        public string $reason,
        public ?string $expires,
    ) {
        parent::__construct();
        $this->reason = trim($reason);
    }
}

final class UserModerationRevokeEvent extends Event
{
    public function __construct(
        public int $action_id,
        public User $moderator,
        public string $reason,
    ) {
        parent::__construct();
        $this->reason = trim($reason);
    }
}

/** @extends Extension<UserModerationTheme> */
final class UserModeration extends Extension
{
    public const KEY = "user_moderation";

    #[EventListener(priority: 60)]
    public function onInitExt(InitExtEvent $event): void
    {
        UserClass::$loading = UserClassSource::DEFAULT;
        if (!isset(UserClass::$known_classes["ghost"])) {
            new UserClass(
                "ghost",
                "base",
                [PrivMsgPermission::READ_PM => true],
                description: "Ghost users can log in and read limited account information, but cannot write content.",
            );
        }
        if (!isset(UserClass::$known_classes["silenced"])) {
            new UserClass(
                "silenced",
                "user",
                [
                    CommentPermission::CREATE_COMMENT => false,
                    ForumPermission::FORUM_CREATE => false,
                    PrivMsgPermission::SEND_PM => false,
                ],
                description: "Silenced users keep normal account access, but cannot comment, post in the forum, or send PMs.",
            );
        }
        UserClass::$loading = UserClassSource::UNKNOWN;
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        $database = Ctx::$database;
        if ($this->get_version() < 1) {
            $database->create_table("user_moderation_actions", "
                id SCORE_AIPK,
                user_id INTEGER NOT NULL,
                moderator_id INTEGER NOT NULL,
                action VARCHAR(16) NOT NULL,
                previous_class VARCHAR(32) NOT NULL,
                applied_class VARCHAR(32) NOT NULL,
                reason TEXT NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires TIMESTAMP NULL DEFAULT NULL,
                revoked BOOLEAN NOT NULL DEFAULT FALSE,
                revoked_at TIMESTAMP NULL DEFAULT NULL,
                revoked_by INTEGER NULL,
                revoke_reason TEXT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (moderator_id) REFERENCES users(id) ON DELETE RESTRICT,
                FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL
            ");
            $database->execute("CREATE INDEX user_moderation_actions__user_id ON user_moderation_actions(user_id)");
            $database->execute("CREATE INDEX user_moderation_actions__active ON user_moderation_actions(user_id, revoked, expires)");
            $this->set_version(1);
        }
        if ($this->get_version() < 2) {
            $database->create_table("user_moderation_ip_links", "
                id SCORE_AIPK,
                action_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                ip SCORE_INET NOT NULL,
                source VARCHAR(16) NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (action_id) REFERENCES user_moderation_actions(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(action_id, ip)
            ");
            $database->execute("CREATE INDEX user_moderation_ip_links__ip ON user_moderation_ip_links(ip)");
            $database->execute("CREATE INDEX user_moderation_ip_links__user_id ON user_moderation_ip_links(user_id)");
            $this->set_version(2);
        }
    }

    #[EventListener(priority: 20)]
    public function onPageRequestEarly(PageRequestEvent $event): void
    {
        $this->expire_actions();
        $this->sync_user(Ctx::$user);
        $this->flash_active_notice(Ctx::$user);
    }

    #[EventListener(priority: 60)]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("user_moderation/create", method: "POST", permission: UserModerationPermission::MODERATE_USERS)) {
            $target = User::by_id(int_escape($event->POST->req("user_id")));
            $expires = nullify($event->POST->get("expires"));
            if ($expires !== null) {
                $expires = date("Y-m-d H:i:s", \Safe\strtotime(trim($expires)));
            }
            send_event(new UserModerationApplyEvent(
                $target,
                Ctx::$user,
                $event->POST->req("action"),
                $event->POST->req("reason"),
                $expires
            ));
            Ctx::$page->flash("Ação de moderação aplicada.");
            Ctx::$page->set_redirect(make_link("user/" . $target->name));
        }

        if ($event->page_matches("user_moderation/revoke", method: "POST", permission: UserModerationPermission::MODERATE_USERS)) {
            $action_id = int_escape($event->POST->req("action_id"));
            send_event(new UserModerationRevokeEvent(
                $action_id,
                Ctx::$user,
                $event->POST->req("reason")
            ));
            Ctx::$page->flash("Ação de moderação revogada.");
            Ctx::$page->set_redirect(Url::referer_or());
        }

        if ($event->page_matches("user_moderation/list", method: "GET")) {
            if (!Ctx::$user->can(UserModerationPermission::VIEW_USER_MODERATION) && !Ctx::$user->can(UserModerationPermission::MODERATE_USERS)) {
                throw new PermissionDenied("You do not have permission to view user moderation history");
            }
            $this->theme->display_moderation_list($this->get_active_actions(), $this->get_history());
        }
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $viewer = Ctx::$user;
        $target = $event->display_user;
        $this->sync_user($target);
        $can_view = $viewer->can(UserModerationPermission::VIEW_USER_MODERATION) || $viewer->can(UserModerationPermission::MODERATE_USERS);
        $can_moderate = $this->can_moderate_user($viewer, $target);
        if (!$can_view && !$can_moderate) {
            return;
        }
        $this->theme->display_user_moderation_block(
            $target,
            $this->get_active_action($target),
            $this->get_history($target),
            $can_moderate
        );
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system" && (Ctx::$user->can(UserModerationPermission::VIEW_USER_MODERATION) || Ctx::$user->can(UserModerationPermission::MODERATE_USERS))) {
            $event->add_nav_link(make_link("user_moderation/list"), "User Moderation", ["user_moderation"]);
        }
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(UserModerationPermission::VIEW_USER_MODERATION) || Ctx::$user->can(UserModerationPermission::MODERATE_USERS)) {
            $event->add_link("User Moderation", make_link("user_moderation/list"), 88);
        }
    }

    #[EventListener]
    public function onUserModerationApply(UserModerationApplyEvent $event): void
    {
        if (!$this->can_moderate_user($event->moderator, $event->target)) {
            throw new PermissionDenied("You do not have permission to moderate this user");
        }
        if ($event->reason === "") {
            throw new InvalidInput("Moderation reason is required");
        }
        if ($this->get_active_action($event->target) !== null) {
            throw new InvalidInput("This user already has an active moderation action");
        }

        $applied_class = $this->action_to_class($event->action);
        $previous_class = $event->target->class->name;
        $action_id = $this->insert_action($event->target, $event->moderator, $event->action, $previous_class, $applied_class, $event->reason, $event->expires);
        $event->target->set_class($applied_class);
        $event->target->class = UserClass::get_class($applied_class);
        Log::warning(
            "user_moderation",
            "{$event->moderator->name} applied {$event->action} to {$event->target->name} until " .
            ($event->expires ?? "forever") . " because: {$event->reason}"
        );

        $this->add_ip_bans_for_account_ban($event, $action_id);
    }

    #[EventListener]
    public function onIPBanHit(IPBanHitEvent $event): void
    {
        if ($event->user->is_anonymous() || $event->user->class->name === "admin" || $event->user->can(UserAccountsPermission::PROTECTED)) {
            return;
        }
        if ($this->get_active_action($event->user) !== null) {
            return;
        }
        if (!Ctx::$config->get(UserModerationConfig::AUTO_BAN_EVASION)) {
            return;
        }

        $sources = $this->get_active_banned_accounts_for_ip($event->ip, $event->user);
        if (count($sources) === 0 || !$this->account_was_created_after_a_source_ban($event->user, $sources)) {
            return;
        }

        $moderator = User::by_id((int)$sources[0]["moderator_id"]);
        $account_list = $this->format_account_list(array_map(
            fn (array $row): string => (string)$row["target_name"],
            $sources
        ));
        $ban_ids = $this->format_ban_id_list(array_map(
            fn (array $row): int => (int)$row["id"],
            $sources
        ));
        $reason = "Evasão de Ban, conta{$account_list}, ban {$ban_ids}";
        $expires = $this->merge_source_expirations($sources);
        $this->create_system_action($event->user, $moderator, "ban", $reason, $expires);

        Log::warning(
            "user_moderation",
            "Auto-banned {$event->user->name} for ban evasion from {$event->ip}: {$reason}"
        );
    }

    #[EventListener]
    public function onUserModerationRevoke(UserModerationRevokeEvent $event): void
    {
        $row = $this->get_action($event->action_id);
        if ($row === null) {
            throw new InvalidInput("Moderation action not found");
        }
        $target = User::by_id((int)$row["user_id"]);
        if (!$this->can_moderate_user($event->moderator, $target)) {
            throw new PermissionDenied("You do not have permission to moderate this user");
        }
        $this->end_action($row, $event->moderator, $event->reason === "" ? "Revoked by moderator" : $event->reason);
    }

    private function can_moderate_user(User $viewer, User $target): bool
    {
        if ($viewer->is_anonymous() || !$viewer->can(UserModerationPermission::MODERATE_USERS)) {
            return false;
        }
        if ($viewer->id === $target->id || $target->id === Ctx::$config->get(UserAccountsConfig::ANON_ID)) {
            return false;
        }
        if ($target->can(UserAccountsPermission::PROTECTED) && $viewer->class->name !== "admin") {
            return false;
        }
        return true;
    }

    private function action_to_class(string $action): string
    {
        return match ($action) {
            "ban" => "ghost",
            "silence" => "silenced",
            default => throw new InvalidInput("Unknown moderation action"),
        };
    }

    private function sync_user(User $user): void
    {
        if ($user->is_anonymous()) {
            return;
        }
        $active = $this->get_active_action($user);
        if ($active !== null && $user->class->name !== $active["applied_class"]) {
            $user->class = UserClass::get_class((string)$active["applied_class"]);
        }
    }

    private function flash_active_notice(User $user): void
    {
        if ($user->is_anonymous()) {
            return;
        }
        $active = $this->get_active_action($user);
        if ($active === null) {
            return;
        }
        $action = $active["action"] === "silence" ? "silenciada" : "banida/restringida";
        $expires = $active["expires"] === null ? "sem expiração" : "até " . $active["expires"];
        Ctx::$page->flash("Sua conta foi {$action} {$expires}. Motivo: {$active["reason"]}");
    }

    private function expire_actions(): void
    {
        $rows = Ctx::$database->get_all(
            "SELECT * FROM user_moderation_actions
            WHERE revoked = :revoked AND expires IS NOT NULL AND expires <= CURRENT_TIMESTAMP",
            ["revoked" => false]
        );
        foreach ($rows as $row) {
            $target = User::by_id((int)$row["user_id"]);
            $this->end_action($row, null, "Expired");
            Log::info("user_moderation", "Expired {$row["action"]} for {$target->name}");
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function end_action(array $row, ?User $moderator, string $reason): void
    {
        $target = User::by_id((int)$row["user_id"]);
        $previous_class = (string)$row["previous_class"];
        if ($target->class->name === $row["applied_class"] && isset(UserClass::$known_classes[$previous_class])) {
            $target->set_class($previous_class);
            $target->class = UserClass::get_class($previous_class);
        }
        $actor = $moderator === null ? "system" : $moderator->name;
        Ctx::$database->execute(
            "UPDATE user_moderation_actions
            SET revoked = :revoked, revoked_at = CURRENT_TIMESTAMP, revoked_by = :revoked_by, revoke_reason = :reason
            WHERE id = :id",
            [
                "revoked" => true,
                "revoked_by" => $moderator?->id,
                "reason" => $reason,
                "id" => $row["id"],
            ]
        );
        Log::info(
            "user_moderation",
            "{$actor} ended {$row["action"]} for {$target->name}: {$reason}"
        );
    }

    private function add_ip_bans_for_account_ban(UserModerationApplyEvent $event, int $action_id): void
    {
        if ($event->action !== "ban" || !class_exists(AddIPBanEvent::class)) {
            return;
        }

        $ip_evidence = $this->get_recent_user_ips($event->target);
        foreach ($ip_evidence as $evidence) {
            $ip = $evidence["ip"];
            if ($ip->is_localhost()) {
                continue;
            }
            $this->link_ip_to_action($action_id, $event->target, $ip, $evidence["source"]);
            send_event(new AddIPBanEvent(
                $ip,
                Ctx::$config->get(UserModerationConfig::IP_BAN_MODE),
                "Conta banida \"{$event->target->name}\", ban #{$action_id}: {$event->reason}",
                $event->expires
            ));
        }
    }

    private function insert_action(User $target, User $moderator, string $action, string $previous_class, string $applied_class, string $reason, ?string $expires): int
    {
        Ctx::$database->execute(
            "INSERT INTO user_moderation_actions (user_id, moderator_id, action, previous_class, applied_class, reason, expires)
            VALUES (:user_id, :moderator_id, :action, :previous_class, :applied_class, :reason, :expires)",
            [
                "user_id" => $target->id,
                "moderator_id" => $moderator->id,
                "action" => $action,
                "previous_class" => $previous_class,
                "applied_class" => $applied_class,
                "reason" => $reason,
                "expires" => $expires,
            ]
        );

        return Ctx::$database->get_last_insert_id("user_moderation_actions_id_seq");
    }

    private function create_system_action(User $target, User $moderator, string $action, string $reason, ?string $expires): void
    {
        $applied_class = $this->action_to_class($action);
        $previous_class = $target->class->name;
        $this->insert_action($target, $moderator, $action, $previous_class, $applied_class, $reason, $expires);
        $target->set_class($applied_class);
        $target->class = UserClass::get_class($applied_class);
    }

    /**
     * @return array<array{ip: IPAddress, source: string}>
     */
    private function get_recent_user_ips(User $user): array
    {
        $ips = [];
        if (class_exists(LogDatabaseInfo::class) && LogDatabaseInfo::is_enabled()) {
            $ips = array_merge($ips, $this->ip_rows_to_sources(Ctx::$database->get_col(
                "SELECT address
                FROM score_log
                WHERE LOWER(username) = LOWER(:username)
                GROUP BY address
                ORDER BY MAX(date_sent) DESC",
                ["username" => $user->name]
            ), "log"));
        }
        $ips = array_merge($ips, $this->ip_rows_to_sources(Ctx::$database->get_col(
            "SELECT owner_ip
            FROM images
            WHERE owner_id = :user_id
            GROUP BY owner_ip
            ORDER BY MAX(posted) DESC",
            ["user_id" => $user->id]
        ), "post"));
        if (class_exists(CommentListInfo::class) && CommentListInfo::is_enabled()) {
            $ips = array_merge($ips, $this->ip_rows_to_sources(Ctx::$database->get_col(
                "SELECT owner_ip
                FROM comments
                WHERE owner_id = :user_id
                GROUP BY owner_ip
                ORDER BY MAX(posted) DESC",
                ["user_id" => $user->id]
            ), "comment"));
        }

        $ret = [];
        foreach ($ips as $ip => $source) {
            try {
                $ret[] = ["ip" => IPAddress::parse($ip), "source" => $source];
            } catch (\InvalidArgumentException) {
                Log::warning("user_moderation", "Ignoring invalid historical IP for {$user->name}: $ip");
            }
            if (count($ret) >= $this->get_max_ips_per_ban()) {
                break;
            }
        }
        return $ret;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function get_active_banned_accounts_for_ip(IPAddress $ip, User $exclude): array
    {
        if (Ctx::$database->get_driver_id() === DatabaseDriverID::PGSQL) {
            return Ctx::$database->get_all(
                "SELECT uma.*, target.name AS target_name
                FROM user_moderation_actions uma
                JOIN user_moderation_ip_links umil ON umil.action_id = uma.id
                JOIN users target ON target.id = uma.user_id
                WHERE uma.action = :action
                AND uma.revoked = :revoked
                AND (uma.expires IS NULL OR uma.expires > CURRENT_TIMESTAMP)
                AND uma.user_id <> :exclude_id
                AND umil.ip = CAST(:ip AS inet)
                ORDER BY uma.created DESC, uma.id DESC",
                ["action" => "ban", "revoked" => false, "exclude_id" => $exclude->id, "ip" => (string)$ip]
            );
        }

        return Ctx::$database->get_all(
            "SELECT uma.*, target.name AS target_name
            FROM user_moderation_actions uma
            JOIN user_moderation_ip_links umil ON umil.action_id = uma.id
            JOIN users target ON target.id = uma.user_id
            WHERE uma.action = :action
            AND uma.revoked = :revoked
            AND (uma.expires IS NULL OR uma.expires > CURRENT_TIMESTAMP)
            AND uma.user_id <> :exclude_id
            AND umil.ip = :ip
            ORDER BY uma.created DESC, uma.id DESC",
            ["action" => "ban", "revoked" => false, "exclude_id" => $exclude->id, "ip" => (string)$ip]
        );
    }

    /**
     * @param string[] $ips
     * @return array<string, string>
     */
    private function ip_rows_to_sources(array $ips, string $source): array
    {
        $ret = [];
        foreach ($ips as $ip) {
            if (!isset($ret[$ip])) {
                $ret[$ip] = $source;
            }
        }
        return $ret;
    }

    private function link_ip_to_action(int $action_id, User $user, IPAddress $ip, string $source): void
    {
        try {
            Ctx::$database->execute(
                "INSERT INTO user_moderation_ip_links (action_id, user_id, ip, source)
                VALUES (:action_id, :user_id, :ip, :source)",
                ["action_id" => $action_id, "user_id" => $user->id, "ip" => (string)$ip, "source" => $source]
            );
        } catch (\PDOException) {
            Log::info("user_moderation", "Skipped duplicate IP evidence {$ip} for moderation action #{$action_id}");
        }
    }

    private function get_max_ips_per_ban(): int
    {
        return max(0, Ctx::$config->get(UserModerationConfig::MAX_IPS_PER_BAN));
    }

    /**
     * @param array<array<string, mixed>> $sources
     */
    private function account_was_created_after_a_source_ban(User $user, array $sources): bool
    {
        $joined = strtotime($user->join_date);
        if ($joined === false) {
            return false;
        }
        foreach ($sources as $source) {
            $created = strtotime((string)$source["created"]);
            if ($created !== false && $joined >= $created) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<array<string, mixed>> $sources
     */
    private function merge_source_expirations(array $sources): ?string
    {
        $latest = null;
        foreach ($sources as $source) {
            if ($source["expires"] === null) {
                return null;
            }
            $expires = (string)$source["expires"];
            if ($latest === null || strtotime($expires) > strtotime($latest)) {
                $latest = $expires;
            }
        }
        return $latest;
    }

    /**
     * @param string[] $accounts
     */
    private function format_account_list(array $accounts): string
    {
        $accounts = array_values(array_unique($accounts));
        if (count($accounts) === 1) {
            return " \"{$accounts[0]}\"";
        }
        return "s " . implode(", ", array_map(fn (string $account): string => "\"$account\"", $accounts));
    }

    /**
     * @param int[] $ids
     */
    private function format_ban_id_list(array $ids): string
    {
        $ids = array_values(array_unique($ids));
        if (count($ids) === 1) {
            return "#{$ids[0]}";
        }
        return implode(", ", array_map(fn (int $id): string => "#$id", $ids));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get_action(int $action_id): ?array
    {
        return Ctx::$database->get_row("SELECT * FROM user_moderation_actions WHERE id = :id", ["id" => $action_id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function get_active_action(User $user): ?array
    {
        return Ctx::$database->get_row(
            "SELECT * FROM user_moderation_actions
            WHERE user_id = :user_id AND revoked = :revoked AND (expires IS NULL OR expires > CURRENT_TIMESTAMP)
            ORDER BY id DESC LIMIT 1",
            ["user_id" => $user->id, "revoked" => false]
        );
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function get_active_actions(): array
    {
        return Ctx::$database->get_all(
            "SELECT uma.*, target.name AS target_name, moderator.name AS moderator_name
            FROM user_moderation_actions uma
            JOIN users target ON target.id = uma.user_id
            JOIN users moderator ON moderator.id = uma.moderator_id
            WHERE uma.revoked = :revoked AND (uma.expires IS NULL OR uma.expires > CURRENT_TIMESTAMP)
            ORDER BY uma.action ASC, uma.created DESC, uma.id DESC",
            ["revoked" => false]
        );
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function get_history(?User $user = null): array
    {
        $where = "";
        $args = [];
        if ($user !== null) {
            $where = "WHERE uma.user_id = :user_id";
            $args["user_id"] = $user->id;
        }
        return Ctx::$database->get_all(
            "SELECT uma.*, target.name AS target_name, moderator.name AS moderator_name
            FROM user_moderation_actions uma
            JOIN users target ON target.id = uma.user_id
            JOIN users moderator ON moderator.id = uma.moderator_id
            {$where}
            ORDER BY uma.revoked ASC, uma.created DESC, uma.id DESC
            LIMIT 100",
            $args
        );
    }
}
