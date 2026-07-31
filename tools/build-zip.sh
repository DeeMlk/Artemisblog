#!/usr/bin/env bash
#
# Empacota o plugin num .zip instalável por "Plugins → Adicionar novo → Enviar".
#
# Usa git archive de propósito: só entra no pacote o que está COMMITADO, então
# nenhum arquivo solto do ambiente de desenvolvimento vaza para o cliente. O que
# fica de fora (tools/, .github/, CLAUDE.md) é declarado em .gitattributes com
# export-ignore — um lugar só, sem lista duplicada aqui.
#
# O zip contém UMA pasta raiz artemis-blog/ — é assim que o WordPress descobre o
# nome da pasta de destino. Zipar o conteúdo solto instalaria os arquivos direto
# em wp-content/plugins/, justamente o cenário que o guard anti-conflito de
# artemis-blog.php tenta consertar depois.
#
# Uso (a partir da raiz do repositório):
#   bash tools/build-zip.sh          # empacota o HEAD
#   bash tools/build-zip.sh v1.4.0   # empacota uma tag/commit específico
#
set -euo pipefail

RAIZ="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
NOME="artemis-blog"
DIST="${RAIZ}/dist"
REF="${1:-HEAD}"

cd "${RAIZ}"

# A versão sai do header do plugin — mesma fonte do cache-buster dos assets.
VERSAO="$( grep -m1 -E '^\s*\*\s*Version:' "${NOME}.php" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '\r' )"
if [ -z "${VERSAO}" ]; then
	echo "erro: não achei a linha 'Version:' em ${NOME}.php" >&2
	exit 1
fi

# Avisa (sem abortar) se há trabalho não commitado: ele NÃO vai para o zip.
if [ -n "$( git status --porcelain )" ]; then
	echo "aviso: há alterações não commitadas — elas ficam de fora do pacote." >&2
fi

# Sintaxe antes de empacotar: um fatal de PHP não pode sair daqui.
find . -name '*.php' -not -path './.git/*' -not -path './dist/*' -print0 \
	| xargs -0 -n1 php -l > /dev/null

mkdir -p "${DIST}"
ARQUIVO="${DIST}/${NOME}-${VERSAO}.zip"
rm -f "${ARQUIVO}"

git archive --format=zip --prefix="${NOME}/" -o "${ARQUIVO}" "${REF}"

echo "${ARQUIVO}"
unzip -l "${ARQUIVO}" | tail -1
