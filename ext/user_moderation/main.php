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
            $this->theme->display_moderation_list($this->get_history());
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
        Ctx::$database->execute(
            "INSERT INTO user_moderation_actions (user_id, moderator_id, action, previous_class, applied_class, reason, expires)
            VALUES (:user_id, :moderator_id, :action, :previous_class, :applied_class, :reason, :expires)",
            [
                "user_id" => $event->target->id,
                "moderator_id" => $event->moderator->id,
                "action" => $event->action,
                "previous_class" => $previous_class,
                "applied_class" => $applied_class,
                "reason" => $event->reason,
                "expires" => $event->expires,
            ]
        );
        $event->target->set_class($applied_class);
        $event->target->class = UserClass::get_class($applied_class);
        Log::warning(
            "user_moderation",
            "{$event->moderator->name} applied {$event->action} to {$event->target->name} until " .
            ($event->expires ?? "forever") . " because: {$event->reason}"
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
