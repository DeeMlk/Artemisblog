# Artemis Blog — Plugin WordPress de blog em modo canvas

> **Manual de operação para o Claude Code.** Leia este arquivo inteiro antes de começar.
>
> **Princípio central do produto:** nas telas do blog o plugin assume o documento HTML
> inteiro e todo o CSS vive escopado sob `.artemis-blog`. **Nenhuma outra tela do site
> anfitrião pode mudar.** Preserve isso em toda decisão de arquitetura.
>
> **Idioma:** todo o código, comentários e documentação estão em português (pt-BR).
> Mantenha esse padrão em qualquer coisa nova.

---

## 1. O que é o Artemis Blog

Um **plugin WordPress** (não é tema) que instala uma estrutura completa de blog — home de
posts, post, arquivos, autor e busca — dentro de qualquer site WordPress, **sem depender
do tema nem do page builder do anfitrião**.

Em vez de ser um tema que o site inteiro precisa adotar, o plugin sequestra apenas as
telas do blog: monta o próprio documento HTML (cabeçalho e rodapé próprios, "modo
canvas"), enfileira os próprios assets e tira da fila o CSS do tema ativo. Home
institucional, páginas do builder e CPTs do tema seguem intocados.

- Versão atual: **1.4.0** · Requer WP 6.0+ · Requer PHP 7.4+
- Text domain: `artemis-blog` (o subplugin embutido usa `artemis-convert`)
- Licença: GPL v2+
- É uma **distribuição genérica**: nenhuma identidade de cliente deve vir embutida.
  O site é estilizado pelo painel, na mão ou pelo botão "Importar estilo do site".

---

## 2. Estrutura do repositório

Um único pacote. Não há monorepo, workspaces nem submódulos.

```
artemis-blog.php          arquivo principal: guard anti-conflito, constantes, requires, ativação
inc/
  options.php             defaults + artemis_get_option() + helpers de CTA/fonte/rodapé
  migrations.php          artemis_maybe_migrate() no init prio 1
  template-tags.php       cta_button, post_meta, breadcrumbs, pagination, related_posts
  setup.php               post-thumbnails + image sizes artemis-hero / artemis-card
  assets.php              artemis_is_blog_context(), enqueue condicional, CSS dinâmico, dequeue do tema
  canvas.php              artemis_header() / artemis_footer() + menu próprio
  router.php              template_include prio 99 + artemis_get_part/sidebar/search_form
  admin-settings.php      painel "Artemis Blog" (Settings API) + sanitização
  style-importer.php      botão "Importar estilo do site" (Elementor → theme.json → CSS da home)
  convert-loader.php      carrega o Artemis Convert embutido
  demo-seeder.php         importação manual de conteúdo de exemplo
  demo/                   content.xml (WXR) + images/ do conteúdo de exemplo
  plugins/artemis-convert/  SUBPLUGIN EMBUTIDO — tratar como vendor, não modificar
templates/
  header.php  footer.php          o documento canvas (abre e fecha o HTML)
  blog-home.php  single.php  archive.php  author.php  search.php
  parts/                          content-card/featured/related/none, sidebar, searchform,
                                  author-box, social-share, cta-inline/section/sidebar
assets/
  css/main.css            GERADO por tools/scope-css.js — não edite à mão
  js/main.js              vanilla, IIFE, sem dependências
tools/scope-css.js        reescopa o CSS do tema Artemis sob .artemis-blog
README.md                 documentação do usuário final (instalação e uso)
```

---

## 3. Como rodar e verificar (comandos verificados)

Não há build system, gerenciador de dependências nem suíte de testes. **Não existe
`composer.json`, `package.json`, `.pot`, CI nem `readme.txt` de wordpress.org.**
O que dá para rodar localmente:

```bash
# Sintaxe de todos os arquivos PHP (verificado: passa limpo nos 42 arquivos)
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l

# Regenerar o CSS escopado + autocheck (chaves balanceadas, seletores sem escopo)
node tools/scope-css.js <entrada.css> assets/css/main.css
```

> **Atenção ao `scope-css.js`:** ele consome o `main.css` do **tema Artemis original**,
> que **não está versionado neste repositório**. Sem esse arquivo-fonte em mãos, o
> comando não tem o que processar — não tente reconstruí-lo a partir do
> `assets/css/main.css` já escopado.

**Validação de comportamento é manual, num WordPress real.** O roteiro mínimo:

1. Copiar a pasta para `wp-content/plugins/` e ativar **Artemis Blog**.
2. Criar uma página vazia e marcá-la em **Artemis Blog → Página do blog**
   (equivale à "Página de posts" em Configurações → Leitura).
3. O site precisa usar uma **página estática** como inicial — o painel avisa se não for.
4. Conferir home do blog, post, arquivo de categoria, autor e busca.
5. **A regressão mais importante:** confirmar que nenhuma outra tela do site mudou.
6. (Opcional) **Artemis Blog → Conteúdo de exemplo** importa posts de teste.

---

## 4. Arquitetura

### 4.1 Bootstrap — `artemis-blog.php`

1. **Guard anti-conflito**: se `ARTEMIS_PLUGIN_VERSION` ou `artemis_get_option()` já
   existem, é porque uma cópia antiga (pasta `artemis-plugin/`) carregou primeiro. O
   arquivo desativa a cópia antiga e **aborta o próprio carregamento** (`return`), para
   evitar fatal por redeclaração.
2. Constantes: `ARTEMIS_PLUGIN_VERSION`, `ARTEMIS_PLUGIN_FILE`, `ARTEMIS_PLUGIN_DIR`,
   `ARTEMIS_PLUGIN_URL` (as duas últimas **com barra final**).
3. `require_once` dos módulos **nesta ordem** — `inc/options.php` vem primeiro porque
   define `artemis_get_option()` e os helpers que todo o resto consome:

   ```
   options.php → migrations.php → template-tags.php → setup.php →
   assets.php → canvas.php → router.php → convert-loader.php → demo-seeder.php
   ```

   Sob `is_admin()` ainda carrega `admin-settings.php` e `style-importer.php`.
4. Ativação: garante defaults, grava `artemis_db_version` e roda `flush_rewrite_rules()`
   (o CPT do Convert precisa de permalinks frescos). **A ativação nunca semeia conteúdo
   de exemplo** — isso é manual, por design: ninguém quer posts fantasmas num site em
   produção.

### 4.2 Roteamento e contexto — as duas funções que decidem tudo

- **`artemis_is_blog_context()`** (`inc/assets.php`) — `is_home() || is_singular('post')
  || is_category() || is_tag() || is_author() || is_date() || is_search()`. Filtrável por
  `artemis_is_blog_context`. **É o guard único usado por assets, dequeue e roteamento.**
  Se precisar ligar/desligar o blog em alguma tela, é aqui — não espalhe condicionais.
- **`artemis_template_include()`** (`inc/router.php`, prio 99) — mapeia o contexto para
  `templates/single.php` · `blog-home.php` · `author.php` · `search.php` · `archive.php`.

`pre_get_posts` força `posts_per_page = 6` na query principal do `is_home()`. Isso
**precisa** continuar igual ao grid de `blog-home.php` (também 6): senão `/blog/page/3/`
dá 404 quando a query principal tem menos páginas que o grid.

### 4.3 Modo canvas

`templates/header.php` abre `<!DOCTYPE html>` e vai até `<main id="site-content">`;
`templates/footer.php` fecha tudo. O `<body>` recebe
`body_class('artemis-blog artemis-canvas')` — por isso o CSS escopado também vale para o
cabeçalho e o rodapé próprios.

Os templates chamam `artemis_header()` / `artemis_footer()`, **nunca**
`get_header()`/`get_footer()`.

Complementando o isolamento, `artemis_dequeue_theme_styles()` (prio 999 no
`wp_enqueue_scripts`) remove da fila os stylesheets cujo `src` esteja sob o diretório do
tema ativo — **só do tema**; CSS do core, de blocos e de outros plugins continua.

O menu do cabeçalho (`artemis_header_menu()`, `inc/canvas.php`) tenta, em ordem: a área
própria `artemis_primary` → um menu já atribuído a uma área comum do tema
(`primary`, `main`, `menu-1`, `principal`, `header`, `top`) → `wp_page_menu()`. É o que
faz o blog vir "pronto" sem configuração.

### 4.4 Opções

Tudo vive num **único option array** `artemis_settings` (`get_option`), **não no
Customizer** — justamente para sobreviver a troca de tema.

- Leitura: `artemis_get_option( 'chave', $default )`. Faz cache `static` do array inteiro
  no primeiro acesso; string vazia e `null` caem no default.
- Escrita: sempre `update_option( 'artemis_settings', ... )` **mesclando** com o array
  atual (`wp_parse_args`/`array_merge`) — nunca sobrescreva o array inteiro.
- Exceção: a **página do blog** grava na opção nativa `page_for_posts` (efeito colateral
  dentro de `artemis_sanitize_settings()`), porque é ela que dispara `is_home()`.
- Fora do array: `artemis_db_version`, `artemis_demo_seeded`,
  `artemis_convert_analytics_enabled`.

### 4.5 CSS — escopo é o contrato

**`assets/css/main.css` é gerado — não edite à mão.** Sai do `main.css` do tema Artemis
original passado por `tools/scope-css.js`, que:

- renomeia `.container` → `.artemis-container` (evita colisão com o tema anfitrião);
- prefixa cada seletor com `.artemis-blog` (`html`/`body`/`:root` **viram** `.artemis-blog`);
- preserva `@keyframes`/`@font-face`/`@page` intactos e recursa em `@media`/`@supports`;
- imprime um autocheck no fim. **Qualquer seletor sem escopo é bug.**

Ajustes pontuais e overrides que precisam vencer o tema anfitrião ficam no CSS inline de
`artemis_dynamic_css()` (`inc/assets.php`), **não** no arquivo gerado.

As variáveis de cor/fonte são escopadas em `.artemis-blog`, **não em `:root`**:
`--artemis-color-*`, `--artemis-font-heading`, `--artemis-font-body`,
`--artemis-logo-height*`.

### 4.6 Templates e parciais

Como os arquivos vivem no plugin, **`get_template_part()` e `get_sidebar()` não
funcionam** — eles só procuram no tema. Use:

- `artemis_get_part( $slug, $name = '', $args = array() )` →
  `templates/parts/{slug}-{name}.php` (`$args` chega como `$args` no parcial, via
  `load_template`);
- `artemis_get_sidebar()`;
- `artemis_get_search_form( $echo = true )` — deliberadamente **não** usa
  `get_search_form()`, para não substituir o formulário do tema anfitrião no resto do site.

### 4.7 Artemis Convert (subplugin embutido)

Vive em `inc/plugins/artemis-convert/` **sem modificações — trate como vendor.** O
`convert-loader.php` define as constantes, registra o autoloader PSR-ish
(`Artemis_Convert\Inc\Frontend\CTA` → `inc/frontend/class-cta.php`) e chama
`\Artemis_Convert\Inc\Core\Init::init()`. Se o plugin standalone estiver ativo
(`ARTEMIS_CONVERT_VERSION` já definida), o loader aborta.

O que ele entrega: CPT `artemis_cta` (não público, menu próprio `artemis-convert`),
taxonomias `category`/`post_tag` compartilhadas com posts, meta box de configuração
(`_artemis_convert_cta_*`), injeção no `the_content` de `is_singular('post')` nas posições
`top`/`middle`/`bottom`, shortcode `[artemis-cta]` e métricas de views/cliques/CTR via
REST (`artemis-convert/v1/view` e `/click`) + `template_redirect` para cliques.

---

## 5. Estado atual — o que está pronto e o que falta

**Pronto e funcionando:**

- As cinco telas do blog, o modo canvas, o isolamento de CSS nos dois sentidos e o
  roteamento condicional.
- Painel de ajustes completo (cores, tipografia, logos, CTA global, layout, rodapé),
  com color picker e seletor de mídia.
- Importador de estilos do site anfitrião (Elementor → theme.json → CSS público da home).
- Artemis Convert embutido com CTAs e métricas.
- Seeder de conteúdo de exemplo, manual e idempotente.
- Todos os 42 arquivos PHP passam em `php -l`.

**Pontos em aberto (reais, encontrados no código):**

- **Docblocks desatualizados**: `inc/router.php:9`, `templates/single.php:5`,
  `templates/blog-home.php:5` e `inc/setup.php:3` ainda dizem que o blog roda "dentro do
  header/rodapé do tema anfitrião". É resquício da versão pré-canvas. **Se tocar nesses
  arquivos, corrija o comentário.**
- **`artemis_config_preset()`** (`inc/options.php:189`) carrega textos de CTA e um número
  de WhatsApp de um cliente específico (recuperação de dados), apesar de o plugin se
  declarar genérico. É o conteúdo do botão "Preencher tudo" — ponto a limpar numa
  generalização.
- **i18n incompleto**: o header declara `Domain Path: /languages`, mas **não existe pasta
  `languages/` nem arquivo `.pot`** no repositório. As strings estão marcadas; a extração
  nunca foi feita.
- **Fonte do CSS não versionada**: o `main.css` do tema Artemis que alimenta o
  `scope-css.js` está fora do repo, então o arquivo gerado não é reproduzível daqui.
- **Sem testes automatizados e sem CI.** A verificação de comportamento é 100% manual.

---

## 6. Receitas — as tarefas mais comuns

**Adicionar uma configuração nova** (os cinco pontos de toque; pular o 2 faz o campo ser
silenciosamente descartado no save):

1. Default em `artemis_default_settings()` (`inc/options.php`).
2. Regra em `artemis_sanitize_settings()` (`inc/admin-settings.php`), no grupo certo —
   cor / texto / URL / inteiro / checkbox / textarea.
3. Campo no render, via `artemis_field_color|text|url|textarea|number|checkbox|media`.
4. Se for cor/fonte/dimensão: expor como variável CSS em `artemis_dynamic_css()`
   (`inc/assets.php`) e consumir com `var(--artemis-...)`.
5. Se fizer parte da identidade visual: mapear em `artemis_style_build_settings()`
   (`inc/style-importer.php`) e considerar `artemis_config_preset()`.

**Adicionar um parcial**: crie `templates/parts/<slug>-<name>.php` (com o guard `ABSPATH`)
e chame `artemis_get_part( '<slug>', '<name>', $args )`.

**Mudar o CSS do miolo**: edite o CSS-fonte do tema Artemis e regenere com `scope-css.js`;
para override pontual, use o inline de `artemis_dynamic_css()`.

**Subir de versão** — bump em **três** lugares:
o header `Version:` de `artemis-blog.php`, o `define( 'ARTEMIS_PLUGIN_VERSION', ... )`
logo abaixo, e a linha `**Versão:**` do `README.md`. A constante também é o cache-buster
dos assets e o gatilho de `artemis_maybe_migrate()`.

**Adicionar uma migração**: escreva a função em `inc/migrations.php` e chame de
`artemis_maybe_migrate()`. Ela roda quando `artemis_db_version < ARTEMIS_PLUGIN_VERSION`,
ou seja, mesmo em upgrade por substituição de arquivos (sem reativar). Migrações precisam
ser **idempotentes** e **nunca** sobrescrever valor que o usuário já editou.

---

## 7. Convenções e regras a preservar (guardrails)

- **O blog não pode vazar para o site, nem o site para o blog.** Todo CSS escopado sob
  `.artemis-blog`; todo enqueue atrás de `artemis_is_blog_context()`.
- **PHP no padrão WordPress**: indentação com **tabs**, sem Yoda conditions, espaços
  dentro dos parênteses (`function foo( $bar )`), arrays em `array()` — não `[]`.
- **Guard obrigatório** no topo de todo arquivo PHP:
  `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- **Prefixos**: funções globais `artemis_*`; helpers do importador `artemis_style_*`;
  opções `artemis_*`. Classes CSS do miolo vão sem prefixo (já estão escopadas), exceto
  utilitários compartilhados: `.artemis-container`, `.artemis-btn`, `.artemis-card`,
  `.artemis-grid`.
- **i18n**: sempre `__()` / `esc_html__()` / `esc_attr__()` com o domain `artemis-blog`
  (`artemis-convert` dentro do subplugin).
- **Escape na saída, sanitize na entrada.** Campo novo no painel exige linha nova em
  `artemis_sanitize_settings()`.
- **Docblocks em português explicando o *porquê*, não o *o quê*.** Vários comentários do
  repo documentam armadilhas concretas (404 de submenu, repetição de destaques, fatal em
  PHP 8) — esse é o estilo esperado.
- **JS**: vanilla, IIFE com `'use strict'`, `var`, zero dependências. `assets/js/main.js`
  só age se achar `.artemis-blog` no documento.
- **Distribuição genérica**: nada específico de cliente fora dos guards existentes.
- **Nunca semear conteúdo automaticamente** em ativação ou migração.

---

## 8. Armadilhas conhecidas

- **`artemis_color_footer_bg` precisa ser escuro.** Além do rodapé, é o fundo dos heros de
  single/archive/author e da seção de relacionados, que têm texto branco. O importador de
  estilos tem lógica dedicada só para garantir isso.
- **Destaques da home**: `$featured_ids` é calculado em *todas* as páginas (não só na
  primeira) para excluí-los do grid de forma consistente; só o bloco visual é condicionado
  a `! is_paged()`. Mexer nisso faz posts se repetirem entre as páginas.
- **Submenus do admin**: `add_menu_page()` não cria subitem próprio. Por isso
  `admin-settings.php` re-registra "Ajustes" com o mesmo slug, e `demo-seeder.php`
  registra o dele com **prioridade 20** — se virar o primeiro subitem, o link do menu-pai
  quebra (404 em `/wp-admin/artemis-demo-content`).
- **Migrações legadas (Hangcha)** só rodam se `home_url()` contiver "hangcha"
  (`artemis_is_legacy_hangcha_site()`). **Não remova esse guard** e não adicione nada
  específico de cliente fora dele.
- **`artemis_get_option()` faz cache estático.** Se alterar `artemis_settings` no mesmo
  request e precisar reler, o cache não invalida sozinho.
- **O importador de estilos faz requests de loopback** (`wp_remote_get( home_url('/') )` +
  até 8 stylesheets do mesmo domínio). Sem loopback no ambiente, ele não acha nada e cai
  nas outras fontes — silenciosamente.
- **O seeder de demo nunca duplica** (pula slugs existentes), mas também nunca roda
  sozinho.

---

## 9. Git

Branch de desenvolvimento designada: **`claude/claude-md-docs-nzsgec`**.
Commits descritivos em português (o histórico segue esse padrão) e sempre
`git push -u origin <branch>`. **Não abra pull request sem pedido explícito.**

---

## 10. Como começar — primeiro pedido sugerido ao Claude Code

> "Leia o `CLAUDE.md` e o `README.md`. Rode `php -l` em todos os arquivos para confirmar
> que a base está limpa. Depois, corrija os docblocks pré-canvas de `inc/router.php`,
> `templates/single.php`, `templates/blog-home.php` e `inc/setup.php`, que ainda afirmam
> que o blog roda dentro do header/rodapé do tema anfitrião. Mantenha o escopo
> `.artemis-blog` e o guard `artemis_is_blog_context()` intactos. Antes de escrever
> código, me mostre o plano de mudanças."

Trabalhe em **fatias pequenas e verificáveis** (um módulo por vez), rode o `php -l` a cada
passo e lembre que a validação real é manual, num WordPress de verdade — com atenção
especial à regressão que importa: **nenhuma outra tela do site pode mudar.**
