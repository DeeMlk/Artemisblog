/**
 * Reescopa o CSS do tema Artemis sob o wrapper .artemis-blog.
 *
 * - Renomeia .container -> .artemis-container (evita colisão com o tema anfitrião).
 * - Prefixa cada seletor com .artemis-blog (html/body/:root/* viram .artemis-blog).
 * - Preserva @keyframes intactos; recursa em @media/@supports.
 * - Comentários são preservados como trivia e ignorados na contagem de chaves.
 */
const fs = require('fs');

const inPath = process.argv[2];
const outPath = process.argv[3];
if (!inPath || !outPath) {
	console.error('uso: node scope-css.js <in.css> <out.css>');
	process.exit(1);
}

let css = fs.readFileSync(inPath, 'utf8');

// 1) .container -> .artemis-container (word boundary; pega .container e .container.narrow)
css = css.replace(/\.container\b/g, '.artemis-container');

const WRAP = '.artemis-blog';

// Prefixa uma lista de seletores (separada por vírgula) já SEM comentários/trivia.
function prefixSelectorList(sel) {
	const parts = sel.split(',').map((s) => s.trim()).filter(Boolean);
	const out = [];
	for (let s of parts) {
		let mapped;
		if (s === ':root' || s === 'html' || s === 'body') {
			mapped = WRAP;
		} else if (/^html\b/.test(s)) {
			mapped = s.replace(/^html\b/, WRAP);
		} else if (/^body\b/.test(s)) {
			mapped = s.replace(/^body\b/, WRAP);
		} else {
			mapped = WRAP + ' ' + s;
		}
		if (!out.includes(mapped)) out.push(mapped);
	}
	return out.join(', ');
}

// Separa comentários/whitespace iniciais (trivia) do código real do prelude.
function splitLead(prelude) {
	const m = prelude.match(/^((?:\s|\/\*[\s\S]*?\*\/)*)([\s\S]*)$/);
	return { lead: m[1], code: m[2].trim() };
}

// Tokeniza uma string CSS em blocos top-level {prelude, body} ou trivia {text},
// pulando comentários na contagem de chaves.
function tokenize(str) {
	const tokens = [];
	let i = 0;
	const n = str.length;
	let buf = '';
	while (i < n) {
		if (str[i] === '/' && str[i + 1] === '*') {
			let end = str.indexOf('*/', i + 2);
			if (end === -1) end = n - 2;
			buf += str.slice(i, end + 2);
			i = end + 2;
			continue;
		}
		if (str[i] === '{') {
			const prelude = buf;
			buf = '';
			let depth = 1;
			let j = i + 1;
			let body = '';
			while (j < n && depth > 0) {
				if (str[j] === '/' && str[j + 1] === '*') {
					let end = str.indexOf('*/', j + 2);
					if (end === -1) end = n - 2;
					body += str.slice(j, end + 2);
					j = end + 2;
					continue;
				}
				const c = str[j];
				if (c === '{') {
					depth++;
				} else if (c === '}') {
					depth--;
					if (depth === 0) { j++; break; }
				}
				body += c;
				j++;
			}
			tokens.push({ type: 'block', prelude, body });
			i = j;
		} else {
			buf += str[i];
			i++;
		}
	}
	if (buf.length) tokens.push({ type: 'text', text: buf });
	return tokens;
}

function scopeBlocks(str) {
	const tokens = tokenize(str);
	let out = '';
	for (const t of tokens) {
		if (t.type === 'text') {
			out += t.text;
			continue;
		}
		const { lead, code } = splitLead(t.prelude);
		if (code.startsWith('@')) {
			const lower = code.toLowerCase();
			if (lower.startsWith('@keyframes') || lower.startsWith('@-webkit-keyframes') || lower.startsWith('@font-face') || lower.startsWith('@page')) {
				out += lead + code + '{' + t.body + '}';
			} else if (lower.startsWith('@media') || lower.startsWith('@supports')) {
				out += lead + code + '{' + scopeBlocks(t.body) + '}';
			} else {
				out += lead + code + '{' + t.body + '}';
			}
		} else if (code) {
			out += lead + prefixSelectorList(code) + '{' + t.body + '}';
		} else {
			out += t.prelude + '{' + t.body + '}';
		}
	}
	return out;
}

const header = `/*!
 * Artemis Blog (plugin) — estilos do miolo do blog.
 * Todas as regras são escopadas sob .artemis-blog para não vazar para o tema
 * anfitrião. Gerado do main.css do tema Artemis via scope-css.js — não edite à mão.
 */
`;

const result = scopeBlocks(css);
fs.writeFileSync(outPath, header + result);

// Autocheck: nenhuma regra de estilo pode ficar sem .artemis-blog.
// (ignora @keyframes/@font-face; recursa em @media/@supports)
function collectUnscoped(str) {
	const bad = [];
	for (const t of tokenize(str)) {
		if (t.type !== 'block') continue;
		const { code } = splitLead(t.prelude);
		if (code.startsWith('@')) {
			const lower = code.toLowerCase();
			if (lower.startsWith('@media') || lower.startsWith('@supports')) {
				bad.push(...collectUnscoped(t.body));
			}
		} else if (code) {
			for (const sel of code.split(',').map((s) => s.trim()).filter(Boolean)) {
				if (!sel.startsWith('.artemis-blog')) bad.push(sel);
			}
		}
	}
	return bad;
}

const unscoped = collectUnscoped(result);
const openCount = (result.match(/\{/g) || []).length;
const closeCount = (result.match(/\}/g) || []).length;
console.log('OK ->', outPath);
console.log('chaves: ' + openCount + ' { / ' + closeCount + ' }  (balanceadas: ' + (openCount === closeCount) + ')');
console.log('at-rules prefixadas por engano (.artemis-blog @): ' + (result.match(/\.artemis-blog\s+@/g) || []).length);
console.log('seletores SEM escopo: ' + unscoped.length);
if (unscoped.length) console.log('  -> ' + unscoped.slice(0, 20).join('  |  '));
