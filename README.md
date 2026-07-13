# Artemis Blog (Plugin)

Estrutura de blog da **Artemis** empacotada como **plugin** WordPress, para rodar dentro de qualquer site/tema sem depender do tema ou do page builder do anfitrião.

As telas do blog (home de posts, post, arquivos, busca) são renderizadas em **modo canvas**: o plugin monta o documento HTML inteiro com **cabeçalho e rodapé próprios**, ignorando o tema/page builder do anfitrião — assim o layout do blog não é quebrado pelo builder do site. Todo o visual é escopado sob `.artemis-blog` para não vazar para o resto do site, e o menu do cabeçalho reaproveita automaticamente o menu do tema.

É uma **versão genérica**: nenhuma identidade de cliente vem embutida — você instala e estiliza tudo na mão pelo painel.

- **Versão:** 1.3.0
- **Requer WordPress:** 6.0+
- **Requer PHP:** 7.4+

## Como funciona

- **Conteúdo:** usa os **Posts nativos** do WordPress (categorias e tags nativas). Nada de CPT.
- **Roteamento:** um filtro `template_include` troca o template só nas telas do blog:
  - **Página do blog** → a página que você marcar como "Página de posts" (nativa do WP).
  - **Post** (`is_singular('post')`).
  - **Arquivos** (categoria, tag, autor, data) e **busca**.
  - Todo o resto do site (home institucional, páginas do builder, CPTs do tema) segue intocado.
- **Identidade visual:** tudo editável no painel **Artemis Blog** (menu do admin) — cores de cabeçalho, miolo e rodapé, fontes, logos (header e rodapé), CTA global, WhatsApp e textos do rodapé. Salvo em opção global — não some se você trocar de tema.
- **CTAs com métricas:** o **Artemis Convert** vem embutido (views/cliques/CTR).

## Instalação

1. Copie a pasta `artemis-blog/` para `wp-content/plugins/`.
2. Ative **Artemis Blog** em **Plugins**.
3. Crie uma página vazia (ex.: "Blog") e selecione-a em **Artemis Blog → Página do blog**
   (equivale a definir a *Página de posts* em **Configurações → Leitura**).
4. Estilize tudo na mão em **Artemis Blog**: cores (cabeçalho, miolo, rodapé), fontes, **logos**, **CTA global**, **WhatsApp** e rodapé.
5. (Opcional) Importe posts de teste em **Artemis Blog → Conteúdo de exemplo**.

> **Página inicial:** para a página do blog funcionar como esperado, o site deve usar uma
> **página estática** como inicial (Configurações → Leitura → "Uma página estática"). O painel
> avisa se esse não for o caso.

## Convivência de CSS

Todo o CSS do blog é escopado sob `.artemis-blog`, então ele não afeta o tema do site. O caminho
inverso (estilos do tema/builder do anfitrião vazando para dentro do blog) é mitigado por
especificidade, mas pode exigir ajuste fino olhando o blog rodando no site real.

O `assets/css/main.css` é **gerado** a partir do CSS do tema Artemis via `scope-css.js` — não
edite à mão sem reaplicar o escopo.

## Estrutura

```
artemis-blog.php          arquivo principal (constantes, requires, ativação)
inc/
  options.php             defaults + artemis_get_option() (get_option)
  template-tags.php       breadcrumbs, paginação, meta, relacionados
  setup.php               post-thumbnails + image sizes
  assets.php              enqueue (só nas telas do blog) + CSS dinâmico escopado
  router.php              template_include + helpers de parciais/sidebar/busca
  admin-settings.php      painel "Artemis Blog" (Settings API)
  convert-loader.php      Artemis Convert embutido
  demo-seeder.php         importação manual de conteúdo de exemplo
templates/                blog-home, single, archive, author, search + parts/
assets/                   css/main.css (escopado), js/main.js (miolo)
```

## Licença

GPL v2 ou posterior.
