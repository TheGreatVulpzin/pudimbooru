# Social Meta

Objetivo: gerar previews consistentes em português sem confundir quem enviou um post com quem criou a obra.

## Arquitetura

- [x] Definir um documento social neutro, sem detalhes de Open Graph ou Twitter.
- [x] Permitir que extensões de página publiquem esse documento por evento.
- [x] Centralizar normalização, truncamento natural e renderização dos metadados.
- [x] Manter um fallback seguro quando não houver coletor específico.

## Páginas

- [x] Posts: título opcional, ID, descrição, tags originais, mídia e data.
- [x] Início: nome, descrição e identidade visual do site.
- [x] Busca por tags: consulta explícita e imagem padrão estável.
- [x] Wiki: título, trecho do artigo e data, com identidade visual como fallback.
- [x] Coleções: título, descrição, quantidade, data e primeiro post público como capa.
- [x] Fórum: título do tópico, trecho inicial e data, sem atribuição de autoria.
- [x] Perfis: somente informações públicas e sem tratar avatar como autoria de posts.
- [x] Páginas privadas ou administrativas: não gerar preview de conteúdo e emitir `noindex`.

## Saídas

- [x] HTML `description`, URL canônica e Open Graph.
- [x] X/Twitter Cards com fallback equivalente.
- [x] Descoberta oEmbed sem campos de autor.
- [x] Favicon e data semântica, sem prometer footer em previews automáticos.
- [x] Cor lateral padrão com substituição opcional por tipo de página.
- [x] Dados estruturados para buscadores quando forem semanticamente seguros.

## Texto

- [x] Usar `Post #ID: Título`, sem travessão.
- [x] Preservar tags diretamente, sem inferir artistas ou autoria.
- [x] Preferir corte no fim da frase; depois em pontuação; por último em palavra.
- [x] Nunca cortar palavra, URL ou tag pela metade.
- [x] Manter limites configuráveis para título, descrição e tags.
- [x] Permitir personalizar o texto alternativo da imagem padrão.
- [x] Permitir personalizar o separador entre descrição e tags.

## Qualidade

- [x] Cobrir o renderer, fallback, privacidade, oEmbed e truncamento com testes.
- [ ] Ampliar a suíte integrada de coletores com fixtures de Wiki, coleções e fórum.
- [x] Validar formatação, análise estática dirigida e suíte da extensão.
- [ ] Testar previews reais sem depender de comportamento não documentado.
