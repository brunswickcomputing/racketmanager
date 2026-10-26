// Rule catalogue for the JS / template legacy-pattern guardrails. Rules are data: each entry has an
// id, a human description, the remediation hint (backlog item in docs/ddd-architecture-audit-2026-10.md),
// a scope ('js' => src/js/**/*.js, 'templates' => templates/**/*.php) and a `scan(file, source)`
// function returning violations for one file.

const { parseJs, isNameToken, isPunctuatorToken, walkAst, lineOf } = require('./source-scanner');

/**
 * A single violation. `key()` is line-independent so the baseline survives unrelated edits; the
 * baseline tolerates a *count* per key, so one more occurrence of a known key still fails.
 */
class Violation {
  constructor(ruleId, file, line, detail) {
    this.ruleId = ruleId;
    this.file = file;
    this.line = line;
    this.detail = detail;
  }

  key() {
    return `${this.file}|${this.detail}`;
  }

  describe() {
    return `${this.file}:${this.line} ${this.detail}`;
  }
}

/**
 * Collect every match of a global regex as a violation.
 *
 * @param {string} ruleId
 * @param {string} file
 * @param {string} haystack text the regex runs over (may be stripped)
 * @param {string} original text used for line numbers (same length as haystack)
 * @param {RegExp} regex must have the `g` flag
 * @param {(match: RegExpExecArray) => string|null} detailOf return null to skip a match
 * @returns {Violation[]}
 */
function collect(ruleId, file, haystack, original, regex, detailOf) {
  const violations = [];
  regex.lastIndex = 0;
  let match;
  while ((match = regex.exec(haystack)) !== null) {
    const detail = detailOf(match);
    if (detail !== null) {
      violations.push(new Violation(ruleId, file, lineOf(original, match.index), detail));
    }
    if (match[0].length === 0) {
      regex.lastIndex++;
    }
  }
  return violations;
}

/**
 * A file Babel cannot parse even with error recovery is reported as a violation (keyed on the
 * message, not the position) rather than skipped, so a syntax error can never hide debt.
 *
 * @param {string} ruleId
 * @param {string} file
 * @param {{error: string, errorLine: number}} parsed
 * @returns {Violation}
 */
function parseErrorViolation(ruleId, file, parsed) {
  return new Violation(ruleId, file, parsed.errorLine, `parse error: ${parsed.error}`);
}

/**
 * jQuery references in the token stream: the `jQuery` identifier (any position — call, property,
 * `window.jQuery`, `{ jQuery }`), or the `$` identifier immediately followed by `(`. Because Babel
 * tokenises `foo$` and `$$` as single identifiers they are not counted; `$.ajax` is deliberately not
 * counted either — it is the `$(...)` wrapper that the backlog item retires. Comments, strings,
 * template text and regex literals are not identifier tokens.
 *
 * @param {object[]} tokens
 * @returns {Array<{line: number, detail: 'jQuery'|'$('}>}
 */
function jqueryReferences(tokens) {
  const references = [];
  for (let index = 0; index < tokens.length; index++) {
    const token = tokens[index];
    if (!isNameToken(token)) {
      continue;
    }
    if (token.value === 'jQuery') {
      references.push({ line: token.loc.start.line, detail: 'jQuery' });
    } else if (token.value === '$' && isPunctuatorToken(tokens[index + 1], '(')) {
      references.push({ line: token.loc.start.line, detail: '$(' });
    }
  }
  return references;
}

// Objects an assignment target may hang off to count as declaring a global. `self` is deliberately
// not included: it is commonly a local alias (`const self = this`), so it would need scope analysis.
const GLOBAL_OBJECTS = new Set(['window', 'globalThis']);

// `window.location = url` is navigation, not a global declaration. This is the only property-level
// exemption; nested members (`window.location.href`, `window.Racketmanager.x`) are not direct
// members of the global object and are therefore never matched in the first place.
const GLOBAL_ASSIGNMENT_EXEMPT = new Set(['location']);

/**
 * Describe a direct member of a global object: `window.X`, `window['X']` (literal key) or
 * `window[expr]` (dynamic key — `name` is null). Returns null for anything else, including
 * `foo.window.X` (object is a MemberExpression, not the `window` identifier).
 *
 * @param {object} node AST node (expected MemberExpression)
 * @returns {{object: string, name: string|null}|null}
 */
function globalMember(node) {
  if (!node || node.type !== 'MemberExpression' || node.object.type !== 'Identifier') {
    return null;
  }
  if (!GLOBAL_OBJECTS.has(node.object.name)) {
    return null;
  }
  const { property } = node;
  if (!node.computed && property.type === 'Identifier') {
    return { object: node.object.name, name: property.name };
  }
  if (node.computed && property.type === 'StringLiteral') {
    return { object: node.object.name, name: property.value };
  }
  if (node.computed && property.type === 'TemplateLiteral' && property.expressions.length === 0) {
    return { object: node.object.name, name: property.quasis[0].value.cooked };
  }
  return { object: node.object.name, name: null };
}

function describeGlobalMember(member) {
  return member.name === null ? `${member.object}[dynamic]` : `${member.object}.${member.name}`;
}

function isEmptyObjectLiteral(node) {
  return Boolean(node) && node.type === 'ObjectExpression' && node.properties.length === 0;
}

/**
 * The accepted namespace-bootstrapping idiom: `window.X = window.X || {}` (same object and name on
 * both sides, empty object literal), including its `??` and logical-assignment spellings
 * (`window.X ||= {}`, `window.X ??= {}`).
 *
 * @param {object} node AssignmentExpression
 * @returns {boolean}
 */
function isNamespaceIdiom(node) {
  const target = globalMember(node.left);
  if (!target || target.name === null) {
    return false;
  }
  if ((node.operator === '||=' || node.operator === '??=') && isEmptyObjectLiteral(node.right)) {
    return true;
  }
  if (node.operator !== '=') {
    return false;
  }
  const rhs = node.right;
  if (rhs.type !== 'LogicalExpression' || (rhs.operator !== '||' && rhs.operator !== '??')) {
    return false;
  }
  const fallback = globalMember(rhs.left);
  return (
    fallback !== null &&
    fallback.object === target.object &&
    fallback.name === target.name &&
    isEmptyObjectLiteral(rhs.right)
  );
}

/**
 * Every AssignmentExpression (any operator: `=`, `||=`, `??=`, `&&=`, `+=` …) whose target is a
 * direct member of `window`/`globalThis`, minus the exemptions above.
 *
 * @param {object} ast Babel File node
 * @returns {Array<{line: number, detail: string}>}
 */
function globalAssignments(ast) {
  const found = [];
  walkAst(ast.program, (node) => {
    if (node.type !== 'AssignmentExpression') {
      return;
    }
    const target = globalMember(node.left);
    if (!target) {
      return;
    }
    if (target.name !== null && GLOBAL_ASSIGNMENT_EXEMPT.has(target.name)) {
      return;
    }
    if (isNamespaceIdiom(node)) {
      return;
    }
    found.push({ line: node.loc.start.line, detail: describeGlobalMember(target) });
  });
  return found;
}

// Inline DOM event-handler attributes in templates (`onclick=`, `onChange=`, `onblur=` …). Restricted to
// real DOM event names so words such as `online=` or PHP identifiers cannot match.
const INLINE_HANDLER_PATTERN =
  /\son(click|dblclick|change|input|submit|reset|load|unload|beforeunload|blur|focus|focusin|focusout|keyup|keydown|keypress|mouseover|mouseout|mousedown|mouseup|mousemove|mouseenter|mouseleave|select|scroll|resize|error|abort|toggle|dragstart|dragend|dragover|dragenter|dragleave|drag|drop|touchstart|touchend|touchmove|touchcancel|paste|copy|cut|wheel|contextmenu|invalid|search|hashchange|popstate)\s*=/gi;

const RULES = [
  {
    id: 'js.jquery_reference',
    description: 'jQuery identifier or $( wrapper in src/js',
    remediation:
      'Backlog item 16: replace jQuery DOM/AJAX calls with the native helpers in src/js/utils/dom-utils.js and fetch-based AJAX; retire utils/jquery-helpers.js. New modules must not reference jQuery.',
    scope: 'js',
    scan(file, source) {
      const parsed = parseJs(source);
      if (parsed.error !== null) {
        return [parseErrorViolation(this.id, file, parsed)];
      }
      return jqueryReferences(parsed.tokens).map(
        ({ line, detail }) => new Violation(this.id, file, line, detail)
      );
    },
  },
  {
    id: 'js.window_global_assignment',
    description: 'assignment to a window.* / globalThis.* global in src/js',
    remediation:
      'Backlog item 16: export the function from its module and wire it through the delegated data-action handlers instead of declaring a window.*/globalThis.* global (window.location navigation and the `window.Racketmanager = window.Racketmanager || {}` namespace idiom are allowed).',
    scope: 'js',
    scan(file, source) {
      const parsed = parseJs(source);
      if (parsed.error !== null) {
        return [parseErrorViolation(this.id, file, parsed)];
      }
      return globalAssignments(parsed.ast).map(
        ({ line, detail }) => new Violation(this.id, file, line, detail)
      );
    },
  },
  {
    id: 'templates.inline_event_handler',
    description: 'inline on*= event-handler attribute in a template',
    remediation:
      'Backlog item 16 / L10: replace the inline handler with a data-action attribute handled by the delegated listeners in src/js (see features/navigation and admin/utils.js).',
    scope: 'templates',
    scan(file, source) {
      return collect(this.id, file, source, source, INLINE_HANDLER_PATTERN, (match) =>
        `on${match[1].toLowerCase()}=`
      );
    },
  },
];

/**
 * @param {string} id
 * @returns {object}
 */
function getRule(id) {
  const rule = RULES.find((candidate) => candidate.id === id);
  if (!rule) {
    throw new Error(`Unknown architecture rule ${id}`);
  }
  return rule;
}

/**
 * Evaluate one rule against a list of `{file, source}` records.
 *
 * @param {object} rule
 * @param {Array<{file: string, source: string}>} files
 * @returns {Violation[]}
 */
function evaluate(rule, files) {
  const violations = [];
  for (const { file, source } of files) {
    violations.push(...rule.scan(file, source));
  }
  return violations;
}

module.exports = {
  RULES,
  Violation,
  getRule,
  evaluate,
  INLINE_HANDLER_PATTERN,
  GLOBAL_OBJECTS,
  GLOBAL_ASSIGNMENT_EXEMPT,
  jqueryReferences,
  globalMember,
  globalAssignments,
  isNamespaceIdiom,
};
