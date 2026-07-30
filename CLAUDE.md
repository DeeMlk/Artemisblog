# CLAUDE.md

Guia para assistentes de IA que forem trabalhar neste repositório. Escrito em português porque
todo o código, comentários e documentação do projeto estão em português (pt-BR) — mantenha esse
padrão em qualquer coisa nova.

## O que é este repositório

**Artemis Blog** — um **plugin WordPress** (não é tema) que instala uma estrutura completa de blog
(home de posts, post, arquivos, autor, busca) dentro de qualquer site WordPress, sem depender do
tema nem do page builder do anfitrião.

- Versão atual: **1.4.0** · Requer WP 6.0+ · Requer PHP 7.4+
- Text domain: `artemis-blog` (o subplugin embutido usa `artemis-convert`)
- Licença: GPL v2+
- Não há build system, dependências, testes automatizados, `composer.json` nem `package.json`.
  O único script Node é `tools/scope-css.js`, rodado à mão.

A ideia central em uma frase: **nas telas do blog o plugin assume o documento HTML inteiro
("modo canvas") e todo o CSS vive escopado sob `.artemis-blog`.**

## Arquitetura

### Bootstrap — `artemis-blog.php`

1. **Guard anti-conflito**: se `ARTEMIS_PLUGIN_VERSION` ou `artemis_get_option()` já existem, é
   porque uma cópia antiga (pasta `artemis-plugin/`) carregou primeiro. O arquivo desativa a cópia
   antiga e **aborta o próprio carregamento** (`return`) para evitar fatal por redeclaração.
2. Define as constantes: `ARTEMIS_PLUGIN_VERSION`, `ARTEMIS_PLUGIN_FILE`, `ARTEMIS_PLUGIN_DIR`,
   `ARTEMIS_PLUGIN_URL` (as duas últimas **com barra final**).
3. `require_once` dos módulos **nesta ordem** — `inc/options.php` vem primeiro porque define
   `artemis_get_option()` e os helpers de CTA/fonte que todo o resto consome:

   ```
   options.php → migrations.php → template-tags.php → setup.php →
   assets.php → canvas.php → router.php → convert-loader.php → demo-seeder.php
   ```

   Sob `is_admin()` ainda carrega `admin-settings.php` e `style-importer.php`.
4. Hooks de ativação/desativação: garante defaults, grava `artemis_db_version` e roda
   `flush_rewrite_rules()` (o CPT do Convert precisa de permalinks frescos).
   **A ativação nunca semeia conteúdo de exemplo** — isso é manual, por design.

### Módulos (`inc/`)

| Arquivo | Responsabilidade |
| --- | --- |
| `options.php` | `artemis_default_settings()` (fonte única da verdade), `artemis_get_option()`, helpers de CTA/fonte/rodapé, `artemis_blog_page_id()`, `artemis_config_preset()` |
| `migrations.php` | `artemis_maybe_migrate()` no `init` prio 1, comparando `artemis_db_version` com a versão do código |
| `template-tags.php` | `artemis_cta_button()`, `artemis_post_meta()`, `artemis_post_categories()`, `artemis_breadcrumbs()`, `artemis_pagination()`, `artemis_related_posts()` |
| `setup.php` | `add_theme_support('post-thumbnails')` + image sizes `artemis-hero` (1200×720) e `artemis-card` (720×480) |
| `assets.php` | `artemis_is_blog_context()`, enqueue condicional, Google Fonts, `artemis_dynamic_css()`, dequeue do CSS do tema anfitrião, filtros de excerpt |
| `canvas.php` | `artemis_header()` / `artemis_footer()` (carregam o documento próprio), área de menu `artemis_primary`, `artemis_header_menu()` |
| `router.php` | `template_include` prio 99, `pre_get_posts`, `artemis_get_part()`, `artemis_get_sidebar()`, `artemis_get_search_form()` |
| `admin-settings.php` | Painel "Artemis Blog" via Settings API, sanitização, helpers `artemis_field_*()`, botão "Preencher tudo" |
| `style-importer.php` | Botão "Importar estilo do site" — lê Elementor → theme.json → CSS público da home e monta a paleta |
| `convert-loader.php` | Carrega o Artemis Convert embutido (constantes + autoloader + `Init::init()`) |
| `demo-seeder.php` | Importa `inc/demo/content.xml` (WXR) sob demanda, via submenu |

### Roteamento e contexto

Duas funções decidem tudo:

- **`artemis_is_blog_context()`** (`inc/assets.php`) — `is_home() || is_singular('post') ||
  is_category() || is_tag() || is_author() || is_date() || is_search()`. Filtrável por
  `artemis_is_blog_context`. **É o guard usado por assets, dequeue e roteamento.** Se você precisar
  ligar/desligar o blog em alguma tela, é aqui.
- **`artemis_template_include()`** (`inc/router.php`, prio 99) — mapeia o contexto para
  `templates/single.php` · `blog-home.php` · `author.php` · `search.php` · `archive.php`.
  Qualquer outra tela do site segue intocada.

`pre_get_posts` força `posts_per_page = 6` na query principal do `is_home()`. Isso **precisa**
continuar igual ao grid de `blog-home.php` (também 6), senão `/blog/page/3/` dá 404 quando a query
principal tem menos páginas que o grid.

### Modo canvas

`templates/header.php` abre `<!DOCTYPE html>` e vai até `<main id="site-content">`;
`templates/footer.php` fecha tudo. O `<body>` recebe `body_class('artemis-blog artemis-canvas')` —
por isso o CSS escopado também vale para cabeçalho e rodapé próprios.

Os templates chamam `artemis_header()` / `artemis_footer()`, **nunca** `get_header()`/`get_footer()`.

> Atenção: alguns docblocks antigos (`inc/router.php`, `templates/single.php`,
> `templates/blog-home.php`, `inc/setup.php`) ainda dizem que o blog roda "dentro do header/rodapé
> do tema anfitrião". Isso é resquício da versão pré-canvas — o comportamento real é o canvas.
> Se tocar nesses arquivos, corrija o comentário.

Complementando o isolamento, `artemis_dequeue_theme_styles()` (prio 999 no `wp_enqueue_scripts`)
remove da fila os stylesheets cujo `src` esteja sob o diretório do tema ativo — só do tema; CSS do
core, de blocos e de outros plugins continua.

### Opções

Tudo vive num **único option array** `artemis_settings` (`get_option`), não no Customizer —
justamente para sobreviver a troca de tema.

- Leitura: `artemis_get_option( 'chave', $default )`. Faz cache `static` do array inteiro no
  primeiro acesso; string vazia e `null` caem no default.
- Escrita: sempre `update_option( 'artemis_settings', ... )` mesclando com o array atual
  (`wp_parse_args`/`array_merge`) — nunca sobrescreva o array inteiro.
- Exceção: a **página do blog** grava na opção nativa `page_for_posts` (efeito colateral dentro de
  `artemis_sanitize_settings()`), porque é ela que dispara `is_home()`.
- Outras opções fora do array: `artemis_db_version`, `artemis_demo_seeded`,
  `artemis_convert_analytics_enabled`.

### CSS

**`assets/css/main.css` é gerado — não edite à mão.** Ele sai do `main.css` do tema Artemis
original passado por:

```bash
node tools/scope-css.js <entrada.css> assets/css/main.css
```

O script: renomeia `.container` → `.artemis-container`, prefixa cada seletor com `.artemis-blog`
(`html`/`body`/`:root` viram `.artemis-blog`), preserva `@keyframes`/`@font-face`/`@page` intactos,
recursa em `@media`/`@supports` e no fim imprime um autocheck (chaves balanceadas, at-rules
prefixadas por engano, seletores sem escopo). **Qualquer seletor sem escopo é bug.**

Ajustes pontuais e overrides que precisam vencer o tema anfitrião ficam no CSS inline de
`artemis_dynamic_css()` (`inc/assets.php`), não no arquivo gerado.

As variáveis de cor/fonte são escopadas em `.artemis-blog`, **não em `:root`**:
`--artemis-color-*`, `--artemis-font-heading`, `--artemis-font-body`, `--artemis-logo-height*`.

### Templates

```
templates/
  header.php  footer.php                (documento canvas)
  blog-home.php  single.php  archive.php  author.php  search.php
  parts/
    content-card.php  content-featured.php  content-related.php  content-none.php
    sidebar.php  searchform.php  author-box.php  social-share.php
    cta-inline.php  cta-section.php  cta-sidebar.php
```

Como os arquivos vivem no plugin, **não use `get_template_part()` nem `get_sidebar()`** — eles só
procuram no tema. Use:

- `artemis_get_part( $slug, $name = '', $args = array() )` → `templates/parts/{slug}-{name}.php`
  (`$args` chega como `$args` no parcial, via `load_template`)
- `artemis_get_sidebar()`
- `artemis_get_search_form( $echo = true )` — deliberadamente não usa `get_search_form()` para não
  substituir o formulário do tema anfitrião no resto do site

### Artemis Convert (embutido)

Vive em `inc/plugins/artemis-convert/` **sem modificações** — trate como vendor. O
`convert-loader.php` define as constantes, registra o autoloader PSR-ish
(`Artemis_Convert\Inc\Frontend\CTA` → `inc/frontend/class-cta.php`) e chama
`\Artemis_Convert\Inc\Core\Init::init()`.

Se o plugin standalone estiver ativo (`ARTEMIS_CONVERT_VERSION` já definida), o loader aborta.

O que ele faz: CPT `artemis_cta` (não público, menu próprio `artemis-convert`), taxonomias
`category`/`post_tag` compartilhadas com posts, meta box de configuração
(`_artemis_convert_cta_*`), injeção no `the_content` de `is_singular('post')` nas posições
`top`/`middle`/`bottom`, shortcode `[artemis-cta]`, e métricas de views/cliques/CTR via REST
(`artemis-convert/v1/view` e `/click`) + `template_redirect` para cliques.

## Convenções de código

- **PHP com padrão WordPress**: indentação com **tabs**, `Yoda` não é usado, espaços dentro dos
  parênteses (`function foo( $bar )`), arrays em `array()` (não `[]`).
- **Guard obrigatório** no topo de todo arquivo PHP: `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- **Prefixos**: funções globais `artemis_*`; helpers do importador `artemis_style_*`; opções
  `artemis_*`; classes CSS do miolo sem prefixo (já estão escopadas) exceto utilitários
  compartilhados (`.artemis-container`, `.artemis-btn`, `.artemis-card`, `.artemis-grid`).
- **i18n**: sempre `__()` / `esc_html__()` / `esc_attr__()` com o domain `artemis-blog`
  (`artemis-convert` dentro do subplugin). Não há `.pot` versionado.
- **Escape na saída, sanitize na entrada.** Todo campo novo do painel precisa de uma linha
  correspondente em `artemis_sanitize_settings()` — o que não estiver lá é descartado no save.
- **Docblocks em português** explicando o *porquê*, não o *o quê*. Vários comentários do repo
  documentam armadilhas concretas (404 de submenu, repetição de destaques, fatal em PHP 8) — esse é
  o estilo esperado.
- **JS**: vanilla, IIFE com `'use strict'`, `var`, sem dependências. `assets/js/main.js` só age se
  achar `.artemis-blog` no documento.

## Receitas comuns

**Adicionar uma configuração nova**

1. Default em `artemis_default_settings()` (`inc/options.php`).
2. Regra de sanitização em `artemis_sanitize_settings()` (`inc/admin-settings.php`) — no grupo
   certo (cor / texto / URL / inteiro / checkbox / textarea).
3. Campo no render, com um dos helpers `artemis_field_color|text|url|textarea|number|checkbox|media`.
4. Se for cor/fonte/dimensão, exponha como variável CSS em `artemis_dynamic_css()`
   (`inc/assets.php`) e consuma via `var(--artemis-...)`.
5. Se fizer parte da identidade visual, considere mapear em `artemis_style_build_settings()`
   (`inc/style-importer.php`) e em `artemis_config_preset()`.

**Adicionar um parcial**: crie `templates/parts/<slug>-<name>.php` (com o guard `ABSPATH`) e chame
`artemis_get_part( '<slug>', '<name>', $args )`.

**Mudar o CSS do miolo**: edite o CSS-fonte do tema Artemis e regenere com `scope-css.js`; para
override pontual, use o inline de `artemis_dynamic_css()`.

**Subir de versão**: bump em **três** lugares — o header `Version:` de `artemis-blog.php`, o
`define( 'ARTEMIS_PLUGIN_VERSION', ... )` logo abaixo, e a linha `**Versão:**` do `README.md`.
A constante também é o cache-buster dos assets e o gatilho de `artemis_maybe_migrate()`.

**Adicionar uma migração**: escreva a função em `inc/migrations.php` e chame de
`artemis_maybe_migrate()`. Ela roda quando `artemis_db_version < ARTEMIS_PLUGIN_VERSION`, ou seja,
mesmo em upgrade por substituição de arquivos (sem reativar). Migrações precisam ser idempotentes e
**nunca** sobrescrever valor que o usuário já editou.

## Armadilhas conhecidas

- **`artemis_color_footer_bg` precisa ser escuro.** Além do rodapé, é o fundo dos heros de
  single/archive/author e da seção de relacionados, que têm texto branco. O importador de estilos
  tem lógica dedicada para garantir isso.
- **Destaques da home**: `$featured_ids` é calculado em *todas* as páginas (não só na primeira)
  para poder excluí-los do grid de forma consistente; só o bloco visual é condicionado a
  `! is_paged()`. Mexer nisso faz posts se repetirem entre páginas.
- **Submenus do admin**: `add_menu_page()` não cria subitem próprio. Por isso `admin-settings.php`
  re-registra "Ajustes" com o mesmo slug, e `demo-seeder.php` registra seu submenu com
  **prioridade 20** — se virar o primeiro subitem, o link do menu-pai quebra (404 em
  `/wp-admin/artemis-demo-content`).
- **Migrações legadas (Hangcha)** só rodam se `home_url()` contiver "hangcha"
  (`artemis_is_legacy_hangcha_site()`). Não remova esse guard; e não adicione nada específico de
  cliente fora dele — a distribuição é genérica.
- **`artemis_config_preset()`** ainda carrega textos e WhatsApp de um cliente específico
  (recuperação de dados). É o conteúdo do botão "Preencher tudo"; se for generalizar o plugin, esse
  é o ponto a limpar.
- **`artemis_get_option()` faz cache estático.** Se alterar `artemis_settings` no mesmo request e
  precisar reler, o cache não invalida sozinho.
- **Importador de estilos faz requests de loopback** (`wp_remote_get( home_url('/') )` + até 8
  stylesheets do mesmo domínio). Em ambiente sem loopback ele simplesmente não encontra nada e cai
  nas outras fontes.
- **O seeder de demo nunca duplica** (pula slugs existentes), mas também nunca roda sozinho.

## Testes e verificação

Não há suíte automatizada. O que dá para fazer localmente:

```bash
# Sintaxe de todos os arquivos PHP
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l

# Autocheck do CSS escopado (imprime seletores sem .artemis-blog)
node tools/scope-css.js <entrada.css> /tmp/out.css
```

Validação de comportamento é manual, num WordPress real: ativar o plugin, marcar a página do blog,
conferir home/post/arquivo/busca, e checar que **nenhuma outra tela do site mudou** — essa é a
regressão mais importante do projeto.

## Git

Branch de desenvolvimento designada: `claude/claude-md-docs-nzsgec`. Faça commits descritivos em
português (o histórico segue esse padrão) e sempre `git push -u origin <branch>`. Não abra pull
request sem pedido explícito.
