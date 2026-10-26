// Tests for the JS/template guardrail engine itself (scanner, rules, baseline, ratchet semantics),
// run against in-memory sources and temporary baseline files — never against the committed baseline.

const fs = require('fs');
const os = require('os');
const path = require('path');
const { parseJs, walkAst, isNameToken, lineOf, walk } = require('./support/source-scanner');
const { RULES, getRule, evaluate, isNamespaceIdiom, globalMember } = require('./support/rules');
const { Baseline, updateRequested, normalise } = require('./support/baseline');
const { assertRuleHolds, failureMessage } = require('./support/ratchet');

const jquery = getRule('js.jquery_reference');
const windowGlobal = getRule('js.window_global_assignment');
const inlineHandler = getRule('templates.inline_event_handler');

const file = (name, source) => ({ file: name, source });
const keys = (violations) => violations.map((violation) => violation.key()).sort();

let tmpDir;
beforeAll(() => {
  tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'rm-arch-'));
});
afterAll(() => {
  fs.rmSync(tmpDir, { recursive: true, force: true });
});
const tmpBaseline = (name, contents) => {
  const target = path.join(tmpDir, `${name}.json`);
  if (contents !== undefined) {
    fs.writeFileSync(target, typeof contents === 'string' ? contents : JSON.stringify(contents));
  } else if (fs.existsSync(target)) {
    fs.unlinkSync(target);
  }
  return target;
};

describe('parseJs (Babel tokens + AST)', () => {
  const names = (source) => parseJs(source).tokens.filter(isNameToken).map((token) => token.value);

  test('identifier tokens exclude comments, strings, template text and regex literals', () => {
    const source = [
      '// uses jQuery',
      '/* $(document) jQuery */',
      'a("jQuery"); b(\'$(x)\');',
      'c(`$(y) ${jQuery(z) + $(q).val()} \\` $(w)`);',
      'const r = /\\$\\(jQuery/g.test(s);',
    ].join('\n');
    const parsed = parseJs(source);
    expect(parsed.error).toBeNull();
    expect(names(source)).toEqual(['a', 'b', 'c', 'jQuery', 'z', '$', 'q', 'val', 'r', 'test', 's']);
    // String tokens also carry a `value` ("jQuery" on line 3) — only identifier tokens count.
    expect(parsed.tokens.find((token) => isNameToken(token) && token.value === 'jQuery').loc.start.line).toBe(4);
  });

  test('is memoised per source text and tolerates module and script sources', () => {
    const source = 'export const x = 1;';
    expect(parseJs(source)).toBe(parseJs(source));
    expect(parseJs('var y = 2; y / 2;').ast.program.sourceType).toBe('script');
    expect(parseJs(source).ast.program.sourceType).toBe('module');
  });

  test('reports a non-recoverable syntax error without a position in the message', () => {
    const parsed = parseJs('const = ;');
    expect(parsed.ast).toBeNull();
    expect(parsed.tokens).toEqual([]);
    expect(parsed.error).toBe('Unexpected token');
    expect(parsed.errorLine).toBe(1);
  });

  test('walkAst visits nested nodes exactly once and skips location metadata', () => {
    const { ast } = parseJs('if (a) { b.c = () => ({ d: [1, 2] }); }');
    const seen = [];
    walkAst(ast.program, (node) => seen.push(node.type));
    expect(seen).toContain('AssignmentExpression');
    expect(seen).toContain('ArrowFunctionExpression');
    expect(seen.filter((type) => type === 'ObjectExpression')).toHaveLength(1);
    expect(seen).toContain('NumericLiteral');
    expect(seen.filter((type) => type === 'NumericLiteral')).toHaveLength(2);
  });

  test('lineOf converts an index to a 1-based line number', () => {
    expect(lineOf('a\nb\nc', 0)).toBe(1);
    expect(lineOf('a\nb\nc', 4)).toBe(3);
  });
});

describe('js.jquery_reference', () => {
  test('reports jQuery identifiers and $( wrappers with line numbers', () => {
    const violations = jquery.scan('src/js/a.js', 'const el = jQuery("#x");\n$(".y").hide();\nfoo$(1); $$(2); $.ajax({});');
    expect(violations.map((violation) => violation.describe())).toEqual([
      'src/js/a.js:1 jQuery',
      'src/js/a.js:2 $(',
    ]);
    expect(keys(violations)).toEqual(['src/js/a.js|$(', 'src/js/a.js|jQuery']);
  });

  test('ignores jQuery in comments, strings and regex literals', () => {
    const source = [
      '// jQuery wrapper',
      '/** @param {jQuery} $el */',
      "console.warn('jQuery UI not loaded');",
      'const re = /\\$\\(/;',
      'const t = `uses $(selector)`;',
      'export const native = document.querySelector("#x");',
    ].join('\n');
    expect(jquery.scan('src/js/clean.js', source)).toEqual([]);
  });

  test('counts references inside template expressions and after the regex/division ambiguities a lexer gets wrong', () => {
    const source = [
      'const t = `${ items.map((i) => { return `${i}-$(x)`; }).join(", ") } jQuery`; $(done);', // 1: only $(done) is code
      'i++ / 2; jQuery(x);', // 2: division after ++, then a real call
      'obj.in / 2; jQuery(y);', // 3: keyword used as property name, then division
      'const o = {a: 1} / 2; jQuery(z);', // 4: object literal, then division
      'if (ok) /jQuery/.test(s);', // 5: regex literal, must not count
      'function f() { return', // 6-7: ASI — the regex on the next line is not a division
      '/jQuery/.exec(t); }',
      'const el = window.jQuery("#x"); const alias = { jQuery };', // 8: property access and shorthand property still count
    ].join('\n');
    const violations = jquery.scan('src/js/edge.js', source);
    expect(violations.map((violation) => `${violation.line} ${violation.detail}`)).toEqual([
      '1 $(',
      '2 jQuery',
      '3 jQuery',
      '4 jQuery',
      '8 jQuery',
      '8 jQuery',
    ]);
  });

  test('reports an unparseable file as a violation instead of skipping it', () => {
    const violations = jquery.scan('src/js/broken.js', 'const = ; jQuery(x);');
    expect(violations.map((violation) => violation.key())).toEqual(['src/js/broken.js|parse error: Unexpected token']);
    expect(windowGlobal.scan('src/js/broken.js', 'const = ; window.x = 1;')).toHaveLength(1);
  });
});

describe('js.window_global_assignment', () => {
  test('reports new globals declared on window', () => {
    const source = [
      'window.activaTab = function (tab) {};',
      'window.RACKETMANAGER_DISABLE_LEGACY = true;',
      'if (!window.Racketmanager) { window.Racketmanager = {}; }',
      'window.$foo=1;',
    ].join('\n');
    expect(keys(windowGlobal.scan('src/js/g.js', source))).toEqual([
      'src/js/g.js|window.$foo',
      'src/js/g.js|window.RACKETMANAGER_DISABLE_LEGACY',
      'src/js/g.js|window.Racketmanager',
      'src/js/g.js|window.activaTab',
    ]);
  });

  test('exempts window.location navigation, the namespace idiom, comparisons and nested members', () => {
    const source = [
      'window.location = url;',
      'window.location.href = url;',
      "window['location'] = url;",
      'window.Racketmanager = window.Racketmanager || {};',
      'window.Racketmanager =window.Racketmanager||{ };',
      'window.Racketmanager = (window.Racketmanager ?? {});',
      'window.Racketmanager ||= {};',
      'window.Racketmanager ??= {};',
      'globalThis.Racketmanager = globalThis.Racketmanager || {};',
      'window.Racketmanager.tabData = tabData;',
      "if (window.Racketmanager === undefined || window.Racketmanager == null) {}",
      "if (typeof window.RACKETMANAGER_DISABLE_LEGACY === 'undefined') {}",
      "// window.legacy = true;",
      "const s = 'window.legacy = true';",
      'const t = `window.legacy = ${1}`;',
      'foo.window.legacy = true;',
      'const self = this; self.legacy = true;',
    ].join('\n');
    expect(windowGlobal.scan('src/js/ok.js', source)).toEqual([]);
  });

  test('flags compound/logical assignments, bracket-literal keys, dynamic keys and globalThis', () => {
    const source = [
      'window.counter += 1;',
      'window.flag ||= compute();',
      'window.flag2 ??= 1;',
      'window.flag3 &&= 1;',
      "window['Bracket'] = 1;",
      'window[`Tpl`] = 1;',
      'window[name] = fn;',
      'globalThis.SetCalculator = SetCalculator;',
      'globalThis["Other"] ||= {x: 1};',
      'window.ns ||= { seeded: true };',
      'window.name = "frame";',
    ].join('\n');
    expect(windowGlobal.scan('src/js/bad.js', source).map((violation) => `${violation.line} ${violation.detail}`)).toEqual([
      '1 window.counter',
      '2 window.flag',
      '3 window.flag2',
      '4 window.flag3',
      '5 window.Bracket',
      '6 window.Tpl',
      '7 window[dynamic]',
      '8 globalThis.SetCalculator',
      '9 globalThis.Other',
      '10 window.ns',
      '11 window.name',
    ]);
  });

  test('the namespace idiom requires the same object and name on both sides and an empty object literal', () => {
    const assignment = (source) => parseJs(source).ast.program.body[0].expression;
    expect(isNamespaceIdiom(assignment('window.Racketmanager = window.Racketmanager || {};'))).toBe(true);
    expect(isNamespaceIdiom(assignment('window.Racketmanager ||= {};'))).toBe(true);
    expect(isNamespaceIdiom(assignment('window.Racketmanager = window.Other || {};'))).toBe(false);
    expect(isNamespaceIdiom(assignment('window.Racketmanager = globalThis.Racketmanager || {};'))).toBe(false);
    expect(isNamespaceIdiom(assignment('window.Racketmanager = window.Racketmanager || { a: 1 };'))).toBe(false);
    expect(isNamespaceIdiom(assignment('window.Racketmanager = {};'))).toBe(false);
    expect(isNamespaceIdiom(assignment('window[key] = window[key] || {};'))).toBe(false);
    expect(windowGlobal.scan('src/js/x.js', 'window.A = window.B || {};')).toHaveLength(1);
  });

  test('globalMember only recognises direct members of window/globalThis', () => {
    const left = (source) => parseJs(source).ast.program.body[0].expression.left;
    expect(globalMember(left('window.a = 1'))).toEqual({ object: 'window', name: 'a' });
    expect(globalMember(left('globalThis["b"] = 1'))).toEqual({ object: 'globalThis', name: 'b' });
    expect(globalMember(left('window[c] = 1'))).toEqual({ object: 'window', name: null });
    expect(globalMember(left('window.a.b = 1'))).toBeNull();
    expect(globalMember(left('foo.window.a = 1'))).toBeNull();
    expect(globalMember(left('self.a = 1'))).toBeNull();
    expect(globalMember(left('a = 1'))).toBeNull();
  });
});

describe('templates.inline_event_handler', () => {
  test('reports inline handler attributes case-insensitively, keyed by the normalised attribute', () => {
    const source = [
      '<a href="#" onclick="return confirm(\'x\')">',
      '<select onChange="this.form.submit()">',
      "<?php echo '<input onblur=\"check(this)\" />'; ?>",
      '<iframe onload=\'setIframeHeight("f")\'>',
      '<button onclick ="go()">',
    ].join('\n');
    const violations = inlineHandler.scan('templates/t.php', source);
    expect(violations.map((violation) => violation.describe())).toEqual([
      'templates/t.php:1 onclick=',
      'templates/t.php:2 onchange=',
      'templates/t.php:3 onblur=',
      'templates/t.php:4 onload=',
      'templates/t.php:5 onclick=',
    ]);
  });

  test('ignores data-action attributes, PHP identifiers and non-event words', () => {
    const source = [
      '<a data-action="confirm" data-on-click="x">',
      '<?php $season = 1; $person=2; $online = true; ?>',
      '<div class="online=">',
      '<input name="reason" value="online=1">',
    ].join('\n');
    expect(inlineHandler.scan('templates/clean.php', source)).toEqual([]);
  });
});

describe('Baseline', () => {
  const v = (ruleId, name, detail) => ({ key: () => `${name}|${detail}`, ruleId, describe: () => `${name}:1 ${detail}` });

  test('normalises: sorts rules and keys, accepts the list form, drops empty rules and bad counts', () => {
    const baseline = new Baseline({
      'z.rule': ['b|x', 'a|y', 'b|x'],
      'a.rule': { 'k|d': '2', 'j|d': 0, 'i|d': -1 },
      'empty.rule': {},
    });
    expect(baseline.toArray()).toEqual({
      'a.rule': { 'k|d': 2 },
      'z.rule': { 'a|y': 1, 'b|x': 2 },
    });
    expect(baseline.countFor('z.rule')).toBe(3);
    expect(baseline.hasRule('empty.rule')).toBe(false);
    expect(normalise(null)).toEqual({});
  });

  test('compare: tolerated keys pass, growth and new keys are new, shrinkage is stale', () => {
    const baseline = new Baseline({ r: { 'f|a': 2, 'f|b': 1, 'g|c': 1 } });
    const diff = baseline.compare('r', [
      v('r', 'f', 'a'),
      v('r', 'f', 'a'),
      v('r', 'f', 'b'),
      v('r', 'f', 'b'),
      v('r', 'h', 'new'),
    ]);
    expect(Object.keys(diff.newViolations)).toEqual(['f|b', 'h|new']);
    expect(diff.newViolations['f|b']).toMatchObject({ count: 2, allowed: 1 });
    expect(diff.stale).toEqual({ 'g|c': { allowed: 1, count: 0 } });
    expect(diff.accepted).toBe(3);
    expect(diff.total).toBe(5);
    expect(diff.hasNewViolations).toBe(true);
    expect(diff.hasStaleEntries).toBe(true);
  });

  test('toJson is canonical and deterministic; load/write round-trip', () => {
    const target = tmpBaseline('roundtrip');
    const a = new Baseline({ 'b.rule': { 'y|1': 1, 'x|1': 3 }, 'a.rule': ['q|z'] });
    a.write(target);
    const b = Baseline.load(target);
    expect(b.toJson()).toBe(a.toJson());
    expect(b.isSortedOnDisk(target)).toBe(true);
    expect(a.toJson()).toBe('{\n    "a.rule": {\n        "q|z": 1\n    },\n    "b.rule": {\n        "x|1": 3,\n        "y|1": 1\n    }\n}\n');
    expect(new Baseline().toJson()).toBe('{}\n');
    expect(Baseline.load(tmpBaseline('missing')).toArray()).toEqual({});
  });

  test('unsorted file is detected as non-canonical; invalid JSON throws', () => {
    const unsorted = tmpBaseline('unsorted', '{"b.rule":{"x|1":1},"a.rule":{"y|1":1}}');
    expect(Baseline.load(unsorted).isSortedOnDisk(unsorted)).toBe(false);
    const invalid = tmpBaseline('invalid', '{not json');
    expect(() => Baseline.load(invalid)).toThrow(/not valid JSON/);
    const array = tmpBaseline('array', '[1]');
    expect(() => Baseline.load(array)).toThrow(/must decode to an object/);
  });

  test('updateRequested honours truthy values and rejects 0/false/no/off', () => {
    expect(updateRequested({})).toBe(false);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: '' })).toBe(false);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: '0' })).toBe(false);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: 'false' })).toBe(false);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: 'off' })).toBe(false);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: '1' })).toBe(true);
    expect(updateRequested({ RM_ARCH_UPDATE_BASELINE: 'yes' })).toBe(true);
  });
});

describe('assertRuleHolds (ratchet semantics)', () => {
  const files = [
    file('src/js/legacy.js', 'jQuery("#a"); jQuery("#b");\n$(".c");'),
    file('src/js/clean.js', 'document.querySelector("#a");'),
  ];

  test('passes when every violation is covered by the baseline', () => {
    const baselinePath = tmpBaseline('covered', { 'js.jquery_reference': { 'src/js/legacy.js|jQuery': 2, 'src/js/legacy.js|$(': 1 } });
    const warnings = [];
    const result = assertRuleHolds(jquery, files, { baselinePath, warn: (m) => warnings.push(m) });
    expect(result.violations).toHaveLength(3);
    expect(result.diff.hasNewViolations).toBe(false);
    expect(warnings).toEqual([]);
  });

  test('fails on a new key with the violation list and remediation hint', () => {
    const baselinePath = tmpBaseline('newkey', { 'js.jquery_reference': { 'src/js/legacy.js|jQuery': 2 } });
    let error;
    try {
      assertRuleHolds(jquery, files, { baselinePath, warn: () => {} });
    } catch (caught) {
      error = caught;
    }
    expect(error).toBeDefined();
    expect(error.message).toMatch(/`js.jquery_reference`.*has 1 new violation/);
    expect(error.message).toMatch(/- src\/js\/legacy\.js:2 \$\(/);
    expect(error.message).toMatch(/Remediation: Backlog item 16/);
    expect(error.message).toMatch(/covers 2 of 3 current violation/);
    expect(error.message).toMatch(/RM_ARCH_UPDATE_BASELINE=1 npm test/);
  });

  test('fails when an existing key grows beyond its tolerated count (occurrence-sensitive)', () => {
    const baselinePath = tmpBaseline('grown', { 'js.jquery_reference': { 'src/js/legacy.js|jQuery': 1, 'src/js/legacy.js|$(': 1 } });
    expect(() => assertRuleHolds(jquery, files, { baselinePath, warn: () => {} })).toThrow(
      /src\/js\/legacy\.js\|jQuery: 2 > 1 tolerated/
    );
  });

  test('is line-independent: moving code within a file does not fail', () => {
    const baselinePath = tmpBaseline('moved', { 'js.jquery_reference': { 'src/js/legacy.js|jQuery': 2, 'src/js/legacy.js|$(': 1 } });
    const moved = [file('src/js/legacy.js', '\n\n\n$(".c");\n\n\njQuery("#b"); jQuery("#a");')];
    expect(() => assertRuleHolds(jquery, moved, { baselinePath, warn: () => {} })).not.toThrow();
  });

  test('reports stale entries as warnings without failing', () => {
    const baselinePath = tmpBaseline('stale', {
      'js.jquery_reference': { 'src/js/legacy.js|jQuery': 5, 'src/js/legacy.js|$(': 1, 'src/js/gone.js|jQuery': 2 },
    });
    const warnings = [];
    const result = assertRuleHolds(jquery, files, { baselinePath, warn: (m) => warnings.push(m) });
    expect(result.diff.hasNewViolations).toBe(false);
    expect(warnings).toHaveLength(1);
    expect(warnings[0]).toMatch(/js\.jquery_reference: 5 baseline occurrence\(s\) in 2 key\(s\) no longer exist/);
    expect(warnings[0]).toMatch(/src\/js\/gone\.js\|jQuery \(0 of 2 tolerated remain\)/);
    expect(warnings[0]).toMatch(/src\/js\/legacy\.js\|jQuery \(2 of 5 tolerated remain\)/);
  });

  test('a missing baseline file fails on the first violation but passes a clean tree', () => {
    const baselinePath = tmpBaseline('absent');
    expect(() => assertRuleHolds(jquery, files, { baselinePath, warn: () => {} })).toThrow(/has 3 new violation/);
    expect(() => assertRuleHolds(jquery, [files[1]], { baselinePath, warn: () => {} })).not.toThrow();
  });

  test('update mode rewrites only the evaluated rule, deterministically, and keeps other rules', () => {
    const baselinePath = tmpBaseline('update', { 'js.window_global_assignment': { 'src/js/g.js|window.x': 1 }, 'js.jquery_reference': { 'src/js/old.js|jQuery': 9 } });
    const first = assertRuleHolds(jquery, files, { baselinePath, update: true });
    expect(first.updated).toBe(true);
    const afterFirst = fs.readFileSync(baselinePath, 'utf8');
    assertRuleHolds(jquery, files, { baselinePath, update: true });
    expect(fs.readFileSync(baselinePath, 'utf8')).toBe(afterFirst);
    expect(JSON.parse(afterFirst)).toEqual({
      'js.jquery_reference': { 'src/js/legacy.js|$(': 1, 'src/js/legacy.js|jQuery': 2 },
      'js.window_global_assignment': { 'src/js/g.js|window.x': 1 },
    });
    // Removing all debt removes the rule from the baseline.
    assertRuleHolds(jquery, [files[1]], { baselinePath, update: true });
    expect(JSON.parse(fs.readFileSync(baselinePath, 'utf8'))).toEqual({
      'js.window_global_assignment': { 'src/js/g.js|window.x': 1 },
    });
  });

  test('failureMessage truncates long lists', () => {
    const many = Array.from({ length: 45 }, (_, i) => ({ describe: () => `src/js/f${i}.js:1 jQuery` }));
    const message = failureMessage(jquery, many, null);
    expect(message).toMatch(/has 45 new violation/);
    expect(message).toMatch(/… and 5 more/);
  });
});

describe('catalogue', () => {
  test('every rule has a positive and a negative in-memory case', () => {
    const positives = {
      'js.jquery_reference': file('src/js/p.js', 'jQuery(x);'),
      'js.window_global_assignment': file('src/js/p.js', 'window.foo = 1;'),
      'templates.inline_event_handler': file('templates/p.php', '<a onclick="x()">'),
    };
    const negatives = {
      'js.jquery_reference': file('src/js/n.js', '// jQuery\ndocument.querySelector(x);'),
      'js.window_global_assignment': file('src/js/n.js', 'window.location = x; window.ns = window.ns || {}; globalThis.ns ||= {}; foo.window.y = 1;'),
      'templates.inline_event_handler': file('templates/n.php', '<a data-action="x">'),
    };
    for (const rule of RULES) {
      expect(positives[rule.id]).toBeDefined();
      expect(evaluate(rule, [positives[rule.id]]).length).toBeGreaterThan(0);
      expect(evaluate(rule, [negatives[rule.id]])).toEqual([]);
    }
  });

  test('getRule rejects unknown ids', () => {
    expect(() => getRule('nope.rule')).toThrow(/Unknown architecture rule/);
  });

  test('walk returns sorted plugin-relative forward-slash paths and tolerates missing dirs', () => {
    const js = walk('src/js', '.js');
    expect(js.length).toBeGreaterThan(50);
    expect(js).toEqual([...js].sort());
    expect(js.every((p) => p.startsWith('src/js/') && !p.includes('\\'))).toBe(true);
    expect(walk('does/not/exist', '.js')).toEqual([]);
  });
});
