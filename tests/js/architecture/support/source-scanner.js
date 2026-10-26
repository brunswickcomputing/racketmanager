// Source scanning helpers for the JS / template architecture guardrails.
//
// JS files are tokenised/parsed with @babel/parser (already installed as a dependency of the
// project's @babel/core devDependency — no new package), so comments, strings, template-literal
// text and regex literals can never be mistaken for code and the regex-vs-division ambiguity does
// not exist. Rules then inspect the token stream (identifier references) or walk the AST
// (assignments). Templates are scanned as raw text because the inline-handler pattern is an HTML
// attribute, which may legitimately sit inside a PHP string.

const fs = require('fs');
const path = require('path');
const { parse } = require('@babel/parser');

const PLUGIN_ROOT = path.resolve(__dirname, '..', '..', '..', '..');

const JS_ROOT = 'src/js';
const TEMPLATES_ROOT = 'templates';

/**
 * Recursively list files under `relativeDir` (relative to the plugin root) with the given extension.
 * Paths are returned relative to the plugin root, with forward slashes, sorted for determinism.
 *
 * @param {string} relativeDir
 * @param {string} extension e.g. '.js'
 * @param {string} [root]
 * @returns {string[]}
 */
function walk(relativeDir, extension, root = PLUGIN_ROOT) {
  const absoluteDir = path.join(root, relativeDir);
  if (!fs.existsSync(absoluteDir)) {
    return [];
  }
  const result = [];
  const visit = (dir) => {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
      const absolute = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        visit(absolute);
      } else if (entry.isFile() && entry.name.endsWith(extension)) {
        result.push(path.relative(root, absolute).split(path.sep).join('/'));
      }
    }
  };
  visit(absoluteDir);
  return result.sort();
}

// `unambiguous` picks module vs script from the presence of import/export; `errorRecovery` keeps
// tokens/AST for files with recoverable syntax slips; comments are not attached to nodes (they are
// still present in `tokens` as CommentLine/CommentBlock entries, which `isNameToken` ignores).
const PARSE_OPTIONS = {
  sourceType: 'unambiguous',
  tokens: true,
  errorRecovery: true,
  attachComment: false,
};

const parseCache = new Map();

/**
 * Parse one JS source with @babel/parser, memoised per process by source text.
 *
 * @param {string} source
 * @returns {{ast: object|null, tokens: object[], error: string|null, errorLine: number}}
 *   `error` is set (and `ast` is null, `tokens` empty) only when Babel cannot recover; rules report
 *   that as a violation so an unparseable file can never silently bypass a rule.
 */
function parseJs(source) {
  if (parseCache.has(source)) {
    return parseCache.get(source);
  }
  let result;
  try {
    const ast = parse(source, PARSE_OPTIONS);
    result = { ast, tokens: ast.tokens || [], error: null, errorLine: 0 };
  } catch (caught) {
    result = {
      ast: null,
      tokens: [],
      error: String(caught.message || caught).replace(/\s*\(\d+:\d+\)\s*$/, ''),
      errorLine: (caught.loc && caught.loc.line) || 1,
    };
  }
  parseCache.set(source, result);
  return result;
}

/**
 * Whether a Babel token is an identifier (`name`) token. Keywords, punctuation, literals and the
 * comment entries Babel keeps in `tokens` are not.
 *
 * @param {object|undefined} token
 * @returns {boolean}
 */
function isNameToken(token) {
  return Boolean(token && token.type && token.type.label === 'name');
}

/**
 * Whether a Babel token is the given punctuator, e.g. `(`.
 *
 * @param {object|undefined} token
 * @param {string} label
 * @returns {boolean}
 */
function isPunctuatorToken(token, label) {
  return Boolean(token && token.type && token.type.label === label);
}

// Node properties that never contain child nodes (or would make the walk cyclic / wasteful).
const NON_CHILD_KEYS = new Set([
  'loc',
  'start',
  'end',
  'range',
  'extra',
  'comments',
  'leadingComments',
  'trailingComments',
  'innerComments',
  'tokens',
  'errors',
]);

/**
 * Depth-first walk over every AST node (objects with a string `type`), calling `visit(node)` on each.
 * Deliberately dependency-free (no @babel/traverse) — the rules only need read-only enumeration.
 *
 * @param {object|object[]|null} node
 * @param {(node: object) => void} visit
 */
function walkAst(node, visit) {
  if (!node || typeof node !== 'object') {
    return;
  }
  if (Array.isArray(node)) {
    for (const child of node) {
      walkAst(child, visit);
    }
    return;
  }
  if (typeof node.type === 'string') {
    visit(node);
  }
  for (const key of Object.keys(node)) {
    if (NON_CHILD_KEYS.has(key)) {
      continue;
    }
    const value = node[key];
    if (value && typeof value === 'object') {
      walkAst(value, visit);
    }
  }
}

/**
 * 1-based line number of `index` within `source`.
 *
 * @param {string} source
 * @param {number} index
 * @returns {number}
 */
function lineOf(source, index) {
  let line = 1;
  for (let i = 0; i < index && i < source.length; i++) {
    if (source[i] === '\n') {
      line++;
    }
  }
  return line;
}

const cache = new Map();

/**
 * Read all files of one scope once per process.
 *
 * @param {'js'|'templates'} scope
 * @param {string} [root]
 * @returns {Array<{file: string, source: string}>}
 */
function readScope(scope, root = PLUGIN_ROOT) {
  const cacheKey = `${root}|${scope}`;
  if (!cache.has(cacheKey)) {
    const files =
      scope === 'js' ? walk(JS_ROOT, '.js', root) : walk(TEMPLATES_ROOT, '.php', root);
    cache.set(
      cacheKey,
      files.map((file) => ({ file, source: fs.readFileSync(path.join(root, file), 'utf8') }))
    );
  }
  return cache.get(cacheKey);
}

module.exports = {
  PLUGIN_ROOT,
  JS_ROOT,
  TEMPLATES_ROOT,
  walk,
  parseJs,
  isNameToken,
  isPunctuatorToken,
  walkAst,
  lineOf,
  readScope,
};
