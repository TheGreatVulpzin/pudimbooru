<?php

declare(strict_types=1);

namespace Shimmie2;

use GQLA\{Field, Mutation, Type};
use MicroCRUD\{ActionColumn, DateColumn, EnumColumn, IntegerColumn, Table, TextColumn};

use function MicroHTML\{A, B, P, emptyHTML};

use MicroHTML\HTMLElement;
use Symfony\Component\Console\Input\InputOption;

final class UserNameColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return A(["href" => make_link("user/{$row[$this->name]}")], $row[$this->name]);
    }
}

final class UserActionColumn extends ActionColumn
{
    public function __construct()
    {
        parent::__construct("id");
        $this->sortable = false;
    }

    public function display(array $row): HTMLElement
    {
        return A(["href" => search_link(["user={$row['name']}"])], "Posts");
    }
}

final class UserEmailVerifiedColumn extends TextColumn
{
    public function display(array $row): HTMLElement
    {
        return bool_escape($row[$this->name] ?? false) ? B("Sim") : emptyHTML("Não");
    }
}

final class UserTable extends Table
{
    public function __construct(\FFSPHP\PDO $db, bool $show_email_verified = false)
    {
        $classes = [];
        foreach (UserClass::$known_classes as $cls) {
            $classes[$cls->name] = $cls->name;
        }
        ksort($classes);
        parent::__construct($db);
        $this->table = "users";
        $this->base_query = "SELECT * FROM users";
        $this->size = 100;
        $this->limit = 1000000;
        $this->set_columns([
            new IntegerColumn("id", "ID"),
            new UserNameColumn("name", "Name"),
            new EnumColumn("class", "Class", $classes),
            ...($show_email_verified && UserNotificationsInfo::is_enabled() ? [new UserEmailVerifiedColumn("email_verified", "Verificado")] : []),
            // Added later, for admins only
            // new TextColumn("email", "Email"),
            new DateColumn("joindate", "Join Date"),
            new DateColumn("last_active", "Last Active"),
            new UserActionColumn(),
        ]);
        $this->order_by = ["id DESC"];
        $this->table_attrs = ["class" => "zebra form"];
    }
}

final class UserCreationException extends SCoreException
{
}

#[Type]
final class LoginResult
{
    public function __construct(
        #[Field]
        public User $user,
        #[Field]
        public ?string $session = null,
        #[Field]
        public ?string $error = null,
    ) {
    }

    #[Mutation]
    public static function login(string $username, string $password): LoginResult
    {
        try {
            $duser = User::by_name_and_pass($username, $password);
            return new LoginResult(
                $duser,
                $duser->get_session_id(),
                null
            );
        } catch (UserNotFound $ex) {
            return new LoginResult(
                User::get_anonymous(),
                null,
                "No user found"
            );
        }
    }

    #[Mutation]
    public static function create_user(string $username, string $password1, string $password2, string $email): LoginResult
    {
        try {
            $uce = send_event(new UserCreationEvent($username, $password1, $password2, $email, true));
            return new LoginResult(
                $uce->get_user(),
                $uce->get_user()->get_session_id(),
                null
            );
        } catch (UserCreationException $ex) {
            return new LoginResult(
                User::get_anonymous(),
                null,
                $ex->getMessage()
            );
        }
    }
}

/** @extends Extension<UserPageTheme> */
final class UserPage extends Extension
{
    public const KEY = "user";

    #[EventListener]
    public function onUserLogin(UserLoginEvent $event): void
    {
        Ctx::$user = $event->user;

        // Update last_active if it's out of date (not today)
        $current_date = date('Y-m-d');
        $last_active = Ctx::$database->get_one(
            "SELECT DATE(last_active) FROM users WHERE id = :id",
            ["id" => $event->user->id]
        );
        if ($last_active !== $current_date) {
            Ctx::$database->execute(
                "UPDATE users SET last_active = now() WHERE id = :id",
                ["id" => $event->user->id]
            );
        }
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $definition = $event->app->getDefinition();
        $definition->addOption(new InputOption(
            '--user',
            '-u',
            InputOption::VALUE_REQUIRED,
            'Log in as the given user'
        ));
    }

    #[EventListener]
    public function onCliRun(CliRunEvent $event): void
    {
        if ($event->input->hasParameterOption(['--user', '-u'])) {
            $name = $event->input->getParameterOption(['--user', '-u']);
            send_event(new UserLoginEvent(User::by_name($name)));
        } else {
            send_event(new UserLoginEvent(User::get_anonymous()));
        }
    }

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        UserClass::$loading = UserClassSource::DEFAULT;
        $_all_false = [];
        $_all_true = [];
        foreach (PermissionGroup::get_subclasses(all: true) as $class) {
            foreach ($class->getConstants() as $k => $v) {
                assert(is_string($v));
                $_all_false[$v] = false;
                $_all_true[$v] = true;
            }
        }
        new UserClass("base", null, $_all_false);
        new UserClass("admin", null, $_all_true);

        // Anonymous users can't do anything except sign
        // up to become regular users
        new UserClass(
            "anonymous",
            "base",
            [
                UserAccountsPermission::CREATE_USER => true,
                UserAccountsPermission::SKIP_LOGIN_CAPTCHA => true,
                PasswordResetPermission::REQUEST_PASSWORD_RESET => true,
            ],
            description: "The default class for people who are not logged in",
        );

        // Users can control themselves, upload new content,
        // and do basic edits on content that they own
        new UserClass(
            "user",
            "base",
            [
                ArtistsPermission::EDIT_ARTIST_INFO => true,
                ArtistsPermission::EDIT_IMAGE_ARTIST => true,
                BulkActionsPermission::PERFORM_BULK_ACTIONS => true,
                BulkDownloadPermission::BULK_DOWNLOAD => true,
                CommentPermission::CREATE_COMMENT => true,
                CommentPermission::SKIP_CAPTCHA => true,
                FavouritesPermission::EDIT_FAVOURITES => true,
                ForumPermission::FORUM_CREATE => true,
                ImagePermission::CREATE_IMAGE => true,
                IndexPermission::BIG_SEARCH => true,
                NotesPermission::CREATE => true,
                NotesPermission::EDIT => true,
                NotesPermission::REQUEST => true,
                NumericScorePermission::CREATE_VOTE => true,
                PoolsPermission::CREATE => true,
                PoolsPermission::UPDATE => true,
                PostDescriptionPermission::EDIT_OWN_IMAGE_DESCRIPTIONS => true,
                PostSourcePermission::EDIT_OWN_IMAGE_SOURCE => true,
                PostTagsPermission::EDIT_OWN_IMAGE_TAG => true,
                PostTitlesPermission::EDIT_OWN_IMAGE_TITLE => true,
                PrivateImagePermission::SET_PRIVATE_IMAGE => true,
                PrivMsgPermission::READ_PM => true,
                PrivMsgPermission::SEND_PM => true,
                RatingsPermission::EDIT_IMAGE_RATING => true,
                RelationshipsPermission::EDIT_OWN_IMAGE_RELATIONSHIPS => true,
                ReportImagePermission::CREATE_IMAGE_REPORT => true,
                TermsPermission::SKIP_TERMS => true,
                UserAccountsPermission::CHANGE_USER_SETTING => true,
                PasswordResetPermission::REQUEST_PASSWORD_RESET => true,
            ],
            description: "The default class for people who are logged in",
        );
        UserClass::$loading = UserClassSource::UNKNOWN;
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        $database = Ctx::$database;
        $user = Ctx::$user;
        $page = Ctx::$page;

        $this->show_user_info();

        if ($event->page_matches("user_admin/login", method: "GET")) {
            $this->theme->display_login_page();
        }
        if ($event->page_matches("user_admin/login", method: "POST", authed: false)) {
            $this->page_login($event->POST->req('user'), $event->POST->req('pass'));
        }
        if ($event->page_matches("user_admin/recover", method: "POST")) {
            $this->page_recover($event->POST->req('username'));
        }
        if ($event->page_matches("user_admin/create", method: "GET", permission: UserAccountsPermission::CREATE_USER)) {
            if (!Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED)) {
                $this->theme->display_signups_disabled();
                return;
            }
            $this->theme->display_signup_page();
        }
        if ($event->page_matches("user_admin/create", method: "POST", authed: false, permission: UserAccountsPermission::CREATE_USER)) {
            if (!Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED)) {
                $this->theme->display_signups_disabled();
                return;
            }
            try {
                $uce = send_event(
                    new UserCreationEvent(
                        $event->POST->req('name'),
                        $event->POST->req('pass1'),
                        $event->POST->req('pass2'),
                        $event->POST->req('email'),
                        true
                    )
                );
                $uce->get_user()->set_login_cookie();
                Ctx::$page->set_redirect(make_link("user"));
            } catch (UserCreationException $ex) {
                throw new InvalidInput($ex->getMessage());
            }
        }
        if ($event->page_matches("user_admin/create_other", method: "POST", permission: UserAccountsPermission::CREATE_OTHER_USER)) {
            send_event(
                new UserCreationEvent(
                    $event->POST->req("name"),
                    $event->POST->req("pass1"),
                    $event->POST->req("pass2"),
                    $event->POST->req("email"),
                    false
                )
            );
            $page->set_redirect(make_link("admin"));
            $page->flash("Created new user");
        }
        if ($event->page_matches("user_admin/list", method: "GET")) {
            if (!UserAccountsPermission::can_view_user_list($user)) {
                throw new PermissionDenied("You do not have permission to view users");
            }
            $t = new UserTable($database->raw_db(), $user->class->name === "admin");
            $t->token = $user->get_auth_token();
            $t->inputs = $event->GET->toArray();
            if ($user->can(UserAccountsPermission::EDIT_USER_INFO)) {
                $col = new TextColumn("email", "Email");
                // $t->columns[] = $col;
                array_splice($t->columns, 2, 0, [$col]);
            }
            $page->set_title("Users");
            $this->theme->display_navigation();
            $page->add_block(new Block(null, emptyHTML($t->table($t->query()), $t->paginator())));
        }
        if ($event->page_matches("user_admin/logout", method: "GET")) {
            // FIXME: security
            $this->page_logout();
        }

        if ($event->page_matches("user_admin/change_name", method: "POST", permission: UserAccountsPermission::EDIT_USER_NAME)) {
            $duser = User::by_id(int_escape($event->POST->req('id')));
            $name = $this->validate_user_name($event->POST->req('name'));
            $this->assert_can_manage_user($user, $duser, UserAccountsPermission::EDIT_USER_NAME);
            $duser->set_name($name);
            $page->flash("Username changed");
            // TODO: set login cookie if user changed themselves
            $this->redirect_to_user($duser);
        }
        if ($event->page_matches("user_admin/change_pass", method: "POST")) {
            $duser = User::by_id(int_escape($event->POST->req('id')));
            $pass1 = $event->POST->req('pass1');
            $pass2 = $event->POST->req('pass2');
            $this->assert_can_manage_user($user, $duser, UserAccountsPermission::EDIT_USER_PASSWORD);
            if ($pass1 !== $pass2) {
                throw new InvalidInput("Senhas não coincidem");
            } else {
                $duser->set_password($pass1);
                send_event(new UserPasswordChangedEvent($duser, $user));
                if ($duser->id === $user->id) {
                    $duser->set_login_cookie();
                }
                $page->flash("Password changed");
                $this->redirect_to_user($duser);
            }
        }
        if ($event->page_matches("user_admin/change_email", method: "POST")) {
            $duser = User::by_id(int_escape($event->POST->req('id')));
            $address = $event->POST->req('address');
            $this->assert_can_manage_user($user, $duser, UserAccountsPermission::EDIT_USER_INFO);
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidInput("Endereço de e-mail inválido");
            }
            User::assert_email_available($address, $duser);
            if ($duser->email === $address && $duser->email_verified) {
                $page->flash("Este e-mail já está verificado.");
            } else {
                $emailEvent = send_event(new UserEmailChangedEvent($duser, $user, $duser->email, $address));
                if ($emailEvent->verificationRateLimited) {
                    $page->flash("Aguarde 10 minutos antes de reenviar o e-mail de verificação.");
                } elseif ($emailEvent->verificationSent) {
                    $page->flash("Enviamos um e-mail para você verificar a conta. O endereço só será alterado depois da confirmação.");
                } else {
                    $page->flash("Não foi possível enviar o e-mail de verificação. Confira os logs do sistema.");
                }
            }
            $this->redirect_to_user($duser);
        }
        if ($event->page_matches("user_admin/change_class", method: "POST", permission: UserAccountsPermission::EDIT_USER_CLASS)) {
            $duser = User::by_id(int_escape($event->POST->req('id')));
            $class = $event->POST->req('class');
            $this->assert_can_manage_user($user, $duser, UserAccountsPermission::EDIT_USER_CLASS);
            $duser->set_class($class);
            $page->flash("Class changed");
            $this->redirect_to_user($duser);
        }
        if ($event->page_matches("user_admin/delete_user", method: "POST", permission: UserAccountsPermission::DELETE_USER)) {
            $duser = User::by_id(int_escape($event->POST->req('id')));
            $this->assert_can_manage_user($user, $duser, UserAccountsPermission::DELETE_USER);
            $this->delete_user(
                $duser->id,
                $event->POST->get("with_images") === "on",
                $event->POST->get("with_comments") === "on"
            );
        }

        if ($event->page_matches("user/{name}")) {
            $display_user = User::by_name($event->get_arg('name'));
            if ($display_user->id === Ctx::$config->get(UserAccountsConfig::ANON_ID)) {
                throw new UserNotFound("usuário não encontrado");
            }
            $e = send_event(new UserPageBuildingEvent($display_user));
            $this->display_stats($e);
        } elseif ($event->page_matches("user")) {
            $page->set_redirect(make_link("user/" . $user->name));
        }
    }

    #[EventListener]
    public function onUserPageBuilding(UserPageBuildingEvent $event): void
    {
        $duser = $event->display_user;
        $class = $duser->class;

        $event->add_part(emptyHTML("Entrou: ", SHM_DATE($duser->join_date)), 10);
        if (Ctx::$user->name === $duser->name) {
            $event->add_part(emptyHTML("IP Atual: " . Network::get_real_ip()), 80);
        }
        $event->add_part(emptyHTML("Class: {$class->name}"), 90);

        /** @var BuildAvatarEvent $avatar_e */
        $avatar_e = send_event(new BuildAvatarEvent($duser));
        $av = $avatar_e->html;
        if ($av) {
            $event->add_part($av, 0);
        } elseif ($duser->id === Ctx::$user->id) {
            if (AvatarPostInfo::is_enabled() || AvatarGravatarInfo::is_enabled()) {
                $part = emptyHTML(P("Sem avatar?"));
                if (AvatarPostInfo::is_enabled()) {
                    $part->appendChild(P(
                        "Você pode colocar qualquer post como avatar clicando \"Definir como Avatar\" em ",
                        "nos controles de posts em qualquer post, ou definindo manualmente nas suas ",
                        A(["href" => make_link("user_config")], "configurações de usuário")
                    ));
                }
                if (AvatarGravatarInfo::is_enabled()) {
                    $part->appendChild(P(
                        "You can set a ",
                        A(["href" => "https://gravatar.com"], "Gravatar"),
                        " avatar by using the same email address here and there"
                    ));
                }
                $event->add_part($part, 0);
            }
        }
    }

    #[EventListener]
    public function onPageNavBuilding(PageNavBuildingEvent $event): void
    {
        if (Ctx::$user->is_anonymous()) {
            $event->add_nav_link(make_link('user_admin/login'), "Account", category: "user", order: 10);
        } else {
            $event->add_nav_link(make_link('user'), "Account", ["user"], "user", 10);
        }
    }

    private function validate_user_name(string $input): string
    {
        if (strlen($input) < 1) {
            throw new InvalidInput("Username deve ser pelo menos 1 caractere");
        } elseif (!\Safe\preg_match('/^[a-zA-Z0-9-_]+$/', $input)) {
            throw new InvalidInput(
                "Usuário contém caracteres inválidos. Caracteres permitidos são ".
                "letras, números, hífen, e underline"
            );
        }
        try {
            User::by_name($input);
            throw new InvalidInput("Este username já existe");
        } catch (UserNotFound $ex) {
            // user not found is good
        }
        return $input;
    }

    private function display_stats(UserPageBuildingEvent $event): void
    {
        $user = Ctx::$user;

        $this->theme->display_user_page($event->display_user, $event->get_parts());

        $is_self = $user->id === $event->display_user->id;

        if (!$is_self && $this->user_can_view_operations($user, $event->display_user)) {
            $uobe = send_event(new UserOperationsBuildingEvent($event->display_user, $event->display_user->get_config()));
            Ctx::$page->add_block(new Block("Operations", $this->theme->build_operations($event->display_user, $uobe), "main", 60));
        }

        if ($is_self) {
            $ubbe = send_event(new UserBlockBuildingEvent());
            $this->theme->display_user_links($user, $ubbe->get_parts());
        }
        if (
            $user->can(UserAccountsPermission::VIEW_USER_IPS) &&
            !$is_self &&
            ($event->display_user->id !== Ctx::$config->get(UserAccountsConfig::ANON_ID)) # don't show anon's IP list, it is le huge
        ) {
            $this->theme->display_ip_list(
                $this->count_upload_ips($event->display_user),
                $this->count_comment_ips($event->display_user),
                $this->count_log_ips($event->display_user)
            );
        }
    }

    #[EventListener]
    public function onPageSubNavBuilding(PageSubNavBuildingEvent $event): void
    {
        if ($event->parent === "system") {
            if (UserAccountsPermission::can_view_user_list(Ctx::$user)) {
                $event->add_nav_link(make_link('user_admin/list'), "User List", ["user_admin"]);
            }
        }

        if ($event->parent === "user" && !Ctx::$user->is_anonymous()) {
            $event->add_nav_link(make_link('user_admin/logout'), "Log Out", order: 90);
        }
    }

    #[EventListener]
    public function onUserBlockBuilding(UserBlockBuildingEvent $event): void
    {
        $event->add_link("My Profile", make_link("user"), 0);
        if (UserAccountsPermission::can_view_user_list(Ctx::$user)) {
            $event->add_link("User List", make_link("user_admin/list"), 87);
        }
        $event->add_link("Log Out", make_link("user_admin/logout"), 99);
    }

    #[EventListener]
    public function onAdminBuilding(AdminBuildingEvent $event): void
    {
        if (Ctx::$user->can(UserAccountsPermission::CREATE_OTHER_USER)) {
            $this->theme->display_user_creator();
        }
    }

    #[EventListener]
    public function onUserCreation(UserCreationEvent $event): void
    {
        $name = $event->username;
        //$pass = $event->password;
        //$email = $event->email;

        if (!Ctx::$user->can(UserAccountsPermission::CREATE_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        if (!Ctx::$config->get(UserAccountsConfig::SIGNUP_ENABLED) && !Ctx::$user->can(UserAccountsPermission::CREATE_OTHER_USER)) {
            throw new UserCreationException("Account creation is currently disabled");
        }
        try {
            $name = $this->validate_user_name($name);
        } catch (InvalidInput $ex) {
            throw new UserCreationException("Invalid username: " . $ex->getMessage());
        }
        if (!Captcha::check(UserAccountsPermission::SKIP_SIGNUP_CAPTCHA)) {
            throw new UserCreationException("Error in captcha");
        }
        if ($event->password !== $event->password2) {
            throw new UserCreationException("Passwords don't match");
        }
        if (
            // Users who can create other users (ie, admins) are exempt
            // from the email requirement
            !Ctx::$user->can(UserAccountsPermission::CREATE_OTHER_USER) &&
            (Ctx::$config->get(UserAccountsConfig::USER_EMAIL_REQUIRED) && empty($event->email))
        ) {
            throw new UserCreationException("Email address is required");
        }

        $email = $event->email ?: null;
        if ($email !== null) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new UserCreationException("Invalid email address");
            }
            try {
                User::assert_email_available($email);
            } catch (InvalidInput $ex) {
                throw new UserCreationException($ex->getMessage());
            }
        }

        // if there are currently no admins, the new user should be one
        $need_admin = (Ctx::$database->get_one("SELECT COUNT(*) FROM users WHERE class='admin'") === 0);
        $class = $need_admin ? 'admin' : 'user';

        Ctx::$database->execute(
            "INSERT INTO users (name, pass, joindate, email, class) VALUES (:username, :hash, now(), :email, :class)",
            ["username" => $event->username, "hash" => '', "email" => $email, "class" => $class]
        );
        $new_user = User::by_name($event->username);
        $new_user->set_password($event->password);
        Log::info("user", "Created User @{$event->username}");

        if ($event->login) {
            send_event(new UserLoginEvent($new_user));
        }

        $event->set_user($new_user);
        if ($new_user->email !== null && $new_user->email !== "") {
            send_event(new UserEmailChangedEvent($new_user, Ctx::$user, null, $new_user->email, "user_creation"));
        }
    }

    public const USER_SEARCH_REGEX = "/^(?:poster|user)(!?)[=:](.*)$/i";
    public const USER_ID_SEARCH_REGEX = "/^(?:poster|user)_id(!?)[=:]([0-9]+)$/i";

    /**
     * @param string[] $context
     */
    public static function has_user_query(array $context): bool
    {
        foreach ($context as $term) {
            if (
                \Safe\preg_match(self::USER_SEARCH_REGEX, $term) ||
                \Safe\preg_match(self::USER_ID_SEARCH_REGEX, $term)
            ) {
                return true;
            }
        }
        return false;
    }

    #[EventListener]
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if ($matches = $event->matches(self::USER_SEARCH_REGEX)) {
            $duser = User::by_name($matches[2]);
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= {$duser->id}"));
        } elseif ($matches = $event->matches(self::USER_ID_SEARCH_REGEX)) {
            $user_id = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.owner_id {$matches[1]}= $user_id"));
        } elseif (Ctx::$user->can(UserAccountsPermission::VIEW_USER_IPS) && $matches = $event->matches("/^(?:poster|user)_ip[=:]([0-9\.]+)$/i")) {
            $user_ip = $matches[1]; // FIXME: ip_escape?
            $event->add_querylet(new Querylet("images.owner_ip = '$user_ip'"));
        }
    }

    #[EventListener]
    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $event->add_section("Users", $this->theme->get_help_html());
        }
    }

    private function show_user_info(): void
    {
        // user info is shown on all pages
        if (Ctx::$user->is_anonymous()) {
            $this->theme->display_login_block();
        } else {
            $ubbe = send_event(new UserBlockBuildingEvent());
            $this->theme->display_user_block(Ctx::$user, $ubbe->get_parts());
        }
    }

    private function page_login(string $name, string $pass): void
    {
        if (!Captcha::check(UserAccountsPermission::SKIP_LOGIN_CAPTCHA)) {
            throw new PermissionDenied("Captcha failed");
        }

        $duser = User::by_name_and_pass($name, $pass);
        send_event(new UserLoginEvent($duser));
        $duser->set_login_cookie();

        if (Ctx::$config->get(UserAccountsConfig::LOGIN_REDIRECT) === "previous") {
            Ctx::$page->set_redirect(Url::referer_or(ignore: ["user/"]));
        } else {
            Ctx::$page->set_redirect(make_link("user"));
        }
    }

    private function page_logout(): void
    {
        Ctx::$page->add_cookie("session", "", time() + 60 * 60 * 24 * Ctx::$config->get(UserAccountsConfig::LOGIN_MEMORY));
        if (Ctx::$config->get(UserAccountsConfig::PURGE_COOKIE)) {
            # to keep as few versions of content as possible,
            # make cookies all-or-nothing
            Ctx::$page->add_cookie("user", "", time() + 60 * 60 * 24 * Ctx::$config->get(UserAccountsConfig::LOGIN_MEMORY));
        }
        Log::info("user", "Logged out");
        Ctx::$page->set_redirect(make_link());
    }

    private function page_recover(string $username): void
    {
        if (PasswordResetInfo::is_enabled()) {
            send_event(new PasswordResetRequestEvent($username));
            return;
        }

        throw new ServerError("Email sending not implemented");
    }

    private function assert_can_manage_user(User $viewer, User $target, string $permission): void
    {
        if ($viewer->is_anonymous()) {
            throw new PermissionDenied("You aren't logged in");
        }

        if (!UserAccountsPermission::can_manage_user($viewer, $target, $permission)) {
            throw new PermissionDenied("You do not have permission to manage this user");
        }
    }

    private function user_can_view_operations(User $viewer, User $display_user): bool
    {
        if ($viewer->is_anonymous()) {
            return false;
        }

        return UserAccountsPermission::can_manage_anything_for_user($viewer, $display_user);
    }

    private function redirect_to_user(User $duser): void
    {
        if (Ctx::$user->id === $duser->id) {
            Ctx::$page->set_redirect(make_link("user"));
        } else {
            Ctx::$page->set_redirect(make_link("user/{$duser->name}"));
        }
    }

    /**
     * @return array<string, int>
     */
    private function count_upload_ips(User $duser): array
    {
        $database = Ctx::$database;
        return $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(images.id) AS count
				FROM images
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id" => $duser->id]);
    }

    /**
     * @return array<string, int>
     */
    private function count_comment_ips(User $duser): array
    {
        $database = Ctx::$database;
        return $database->get_pairs("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count
				FROM comments
				WHERE owner_id=:id
				GROUP BY owner_ip
				ORDER BY max(posted) DESC", ["id" => $duser->id]);
    }

    /**
     * @return array<string, int>
     */
    private function count_log_ips(User $duser): array
    {
        if (!LogDatabaseInfo::is_enabled()) {
            return [];
        }
        $database = Ctx::$database;
        return $database->get_pairs("
				SELECT
					address,
					COUNT(id) AS count
				FROM score_log
				WHERE username=:username
				GROUP BY address
				ORDER BY MAX(date_sent) DESC", ["username" => $duser->name]);
    }

    private function delete_user(int $uid, bool $with_images = false, bool $with_comments = false): void
    {
        $database = Ctx::$database;

        Ctx::$event_bus->set_timeout(null);

        $duser = User::by_id($uid);
        Log::warning("user", "Deleting user #{$uid} (@{$duser->name})");

        if ($with_images) {
            Log::warning("user", "Deleting user #{$uid} (@{$duser->name})'s uploads");
            $image_ids = $database->get_col("SELECT id FROM images WHERE owner_id = :owner_id", ["owner_id" => $uid]);
            foreach ($image_ids as $image_id) {
                $image = Post::by_id((int) $image_id);
                if ($image) {
                    send_event(new PostDeletionEvent($image));
                }
            }
        } else {
            $database->execute(
                "UPDATE images SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                ["new_owner_id" => Ctx::$config->get(UserAccountsConfig::ANON_ID), "old_owner_id" => $uid]
            );
        }

        if ($with_comments) {
            Log::warning("user", "Deleting user #{$uid} (@{$duser->name})'s comments");
            $database->execute("DELETE FROM comments WHERE owner_id = :owner_id", ["owner_id" => $uid]);
        } else {
            $database->execute(
                "UPDATE comments SET owner_id = :new_owner_id WHERE owner_id = :old_owner_id",
                ["new_owner_id" => Ctx::$config->get(UserAccountsConfig::ANON_ID), "old_owner_id" => $uid]
            );
        }

        send_event(new UserDeletionEvent($uid));

        $database->execute(
            "DELETE FROM users WHERE id = :id",
            ["id" => $uid]
        );

        Ctx::$page->set_redirect(make_link());
    }
}
