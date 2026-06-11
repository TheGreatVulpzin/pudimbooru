<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{BR, P, emptyHTML};

use MicroHTML\HTMLElement;

class MediaTheme extends Themelet
{
    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P('Pesquisando por posts baseados no tipo de mídia.'),
            SHM_COMMAND_EXAMPLE('content=audio', 'Pesquisa por posts que contém aúdio, incluindo vídeos e arquivos de áudio.'),
            SHM_COMMAND_EXAMPLE('content=video', 'Pesquisa por posts que contém video, incluindo GIFs animados.'),
            //
            BR(),
            P("Pesquisando por dimensões."),
            SHM_COMMAND_EXAMPLE("size=640x480", "Pesquisa por posts com exatamente 640 pixels de largura por 480 pixels de altura."),
            SHM_COMMAND_EXAMPLE("size>1920x1080", "Pesquisa por posts com uma largura maior que 1920 e uma altura maior que 1080."),
            SHM_COMMAND_EXAMPLE("width=1000", "Pesquisa por posts com exatamente 1000 pixels de largura."),
            SHM_COMMAND_EXAMPLE("height=1000", "Pesquisa por posts com exatamente 1000 pixels de altura."),
            SHM_COMMAND_EXAMPLE("ratio=4:3", "Pesquisa por posts com uma proporção de aspecto de 4:3."),
            SHM_COMMAND_EXAMPLE("ratio>16:9", "Pesquisa por posts com uma proporção de aspecto maior que 16:9."),
            //
            BR(),
            P("Pesquisando posts por duração."),
            P("Sufixos disponíveis são ms, s, m, h, d, e y. Um número por si só será interpretado como milissegundos. Buscas usando = não funcionarão a menos que o tempo seja especificado até o milissegundo."),
            SHM_COMMAND_EXAMPLE("length>=1h", "Pesquise por posts que são mais longos que uma hora."),
            SHM_COMMAND_EXAMPLE("length<=10h15m", "Pesquisa por posts que são mais curtos que 10 horas e 15 minutos."),
            SHM_COMMAND_EXAMPLE("length>=10000", "Pesquisa por posts que são mais longos que 10.000 milissegundos, ou 10 segundos."),
        );
    }
}
