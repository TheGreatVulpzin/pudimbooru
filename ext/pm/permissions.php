<?php

declare(strict_types=1);

namespace Shimmie2;

final class PrivMsgPermission extends PermissionGroup
{
    public const KEY = "pm";
    public ?string $title = "Mensagens Privadas";

    #[PermissionMeta("Enviar PMs")]
    public const SEND_PM = "send_pm";

    #[PermissionMeta("Ler PMs")]
    public const READ_PM = "read_pm";

    #[PermissionMeta("Ler PMs de outras pessoas")]
    public const VIEW_OTHER_PMS = "view_other_pms";
}
