<?php

declare(strict_types=1);

namespace Shimmie2;

final class TermsConfig extends ConfigGroup
{
    public const KEY = "terms";
    public ?string $title = "Terms & Conditions Wall";

    #[ConfigMeta(
        "Message",
        ConfigType::STRING,
        default: "Cookies podem ser usados. Por favor leia nossa [url=site://wiki/privacy]política de privacidade[/url] para mais informações.\nPor concordar a entrar, você concorda com nossas [url=site://wiki/rules]regras[/url] e [url=site://wiki/terms_of_service]termos de serviço[/url].",
        input: ConfigInput::TEXTAREA
    )]
    public const MESSAGE = 'terms_message';
}
