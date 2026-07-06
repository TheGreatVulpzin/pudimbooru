<?php

declare(strict_types=1);

namespace Shimmie2;

/** @extends Extension<UserConfigEditorTheme> */
final class UserConfigEditor extends Extension
{
    public const KEY = "user_config_editor";

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "user" && Ctx::$user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $event->add_nav_link(make_link('user_config'), "User Options", order: 40);
        }
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::CHANGE_USER_SETTING)) {
            $event->add_link("User Options", make_link("user_config"), 40);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $database = Ctx::$database;

        if ($event->page_matches("user_config", method: "GET", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $blocks = [];
            foreach (UserConfigGroup::get_subclasses() as $class) {
                $group = $class->newInstance();
                if ($group::is_enabled()) {
                    $block = $this->theme->config_group_to_block(Ctx::$user->get_config(), $group);
                    if ($block) {
                        $blocks[] = $block;
                    }
                }
            }
            $this->theme->display_user_config_page($blocks, Ctx::$user);
            $this->display_user_operations(Ctx::$user);
        }
        if ($event->page_matches("user_config/save", method: "POST", permission: UserAccountsPermission::CHANGE_USER_SETTING)) {
            $duser = User::by_id(int_escape($event->POST->req('id')));

            if (Ctx::$user->id !== $duser->id && !Ctx::$user->can(UserAccountsPermission::CHANGE_OTHER_USER_SETTING)) {
                throw new PermissionDenied("You do not have permission to change other user's settings");
            }

            send_event(new ConfigSaveEvent($duser->get_config(), ConfigSaveEvent::postToSettings($event->POST)));
            Ctx::$page->flash("Config saved");
            Ctx::$page->set_redirect(make_link("user_config"));
        }
    }

    private function display_user_operations(User $user): void
    {
        $theme = Themelet::get_for_extension_class(UserPage::class);
        if (!$theme instanceof UserPageTheme) {
            return;
        }

        $uobe = send_event(new UserOperationsBuildingEvent($user, $user->get_config()));
        Ctx::$page->add_block(new Block("Operations", $theme->build_operations($user, $uobe), "main", 60));
    }
}
