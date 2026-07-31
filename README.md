# Artemis Blog (Plugin)

Estrutura de blog da **Artemis** empacotada como **plugin** WordPress, para rodar dentro de qualquer site/tema sem depender do tema ou do page builder do anfitrião.

As telas do blog (home de posts, post, arquivos, busca) são renderizadas em **modo canvas**: o plugin monta o documento HTML inteiro com **cabeçalho e rodapé próprios**, ignorando o tema/page builder do anfitrião — assim o layout do blog não é quebrado pelo builder do site. Todo o visual é escopado sob `.artemis-blog` para não vazar para o resto do site, e o menu do cabeçalho reaproveita automaticamente o menu do tema.

É uma **versão genérica**: nenhuma identidade de cliente vem embutida — você instala e estiliza pelo painel, na mão ou com o botão **Importar estilo do site**, que lê a identidade visual do site anfitrião e preenche as cores e fontes do blog automaticamente.

- **Versão:** 1.4.0
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
- **Importar estilo do site:** o botão **🎨 Importar estilo do site** (no topo do painel) lê a identidade visual do próprio site anfitrião — paleta global do Elementor (kit ativo), estilos globais do tema (theme.json) e, por último, o CSS público da página inicial — e preenche as cores e fontes do blog de uma vez. Só toca em cores/fontes (não mexe em textos de CTA, logos nem na página do blog); tudo continua ajustável depois.
- **CTAs com métricas:** o **Artemis Convert** vem embutido (views/cliques/CTR).

## Instalação

1. Instale o plugin, de um dos dois jeitos:
   - **Pelo painel:** **Plugins → Adicionar novo → Enviar plugin** e escolha o
     `artemis-blog-<versão>.zip`; ou
   - **Por FTP/SSH:** copie a pasta `artemis-blog/` para `wp-content/plugins/`.
2. Ative **Artemis Blog** em **Plugins**.
3. Crie uma página vazia (ex.: "Blog") e selecione-a em **Artemis Blog → Página do blog**
   (equivale a definir a *Página de posts* em **Configurações → Leitura**).
4. Estilize em **Artemis Blog**: clique em **🎨 Importar estilo do site** para herdar as cores e fontes do site automaticamente, e/ou ajuste na mão cores (cabeçalho, miolo, rodapé), fontes, **logos**, **CTA global**, **WhatsApp** e rodapé.
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
  style-importer.php      botão "Importar estilo do site" (herda cores/fontes do site anfitrião)
  convert-loader.php      Artemis Convert embutido
  demo-seeder.php         importação manual de conteúdo de exemplo
templates/                blog-home, single, archive, author, search + parts/
assets/                   css/main.css (escopado), js/main.js (miolo)
languages/                arquivos .pot para tradução
```

## Licença

GPL v2 ou posterior.
