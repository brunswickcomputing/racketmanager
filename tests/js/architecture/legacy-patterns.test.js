// Architecture guardrail (ratchet) for JS and template legacy patterns.
//
// One test per rule in support/rules.js. Each rule's current debt is recorded in baseline.json
// (`{ rule: { "file|detail": count } }`, line-independent keys with a tolerated occurrence count);
// a test fails when a key is new or its count grew, and only warns (STDERR) when baseline entries
// are stale. Regenerate the baseline after *removing* debt with:
//
//     RM_ARCH_UPDATE_BASELINE=1 npm test -- tests/js/architecture
//
// Companion of the PHP suite in tests/Architecture (vendor/bin/phpunit --testsuite Architecture);
// the rule ids are cross-referenced from docs/ddd-architecture-audit-2026-10.md (Appendix A.2,
// backlog item 16).

const fs = require('fs');
const { RULES } = require('./support/rules');
const { readScope } = require('./support/source-scanner');
const { Baseline, DEFAULT_PATH, updateRequested } = require('./support/baseline');
const { assertRuleHolds } = require('./support/ratchet');

const update = updateRequested();

describe('JS / template legacy-pattern guardrails', () => {
  test.each(RULES.map((rule) => [rule.id, rule]))('%s', (id, rule) => {
    const files = readScope(rule.scope);
    expect(files.length).toBeGreaterThan(0);
    // Throws with the violation list and remediation hint when new debt was introduced.
    const result = assertRuleHolds(rule, files, { update });
    expect(result.violations).toBeInstanceOf(Array);
  });
});

describe('baseline integrity', () => {
  const skipInUpdateMode = update ? test.skip : test;

  skipInUpdateMode('baseline.json exists and is canonically sorted', () => {
    expect(fs.existsSync(DEFAULT_PATH)).toBe(true);
    const baseline = Baseline.load(DEFAULT_PATH);
    expect(baseline.isSortedOnDisk(DEFAULT_PATH)).toBe(true);
  });

  test('baseline names only known rule ids', () => {
    const known = RULES.map((rule) => rule.id);
    const baseline = Baseline.load(DEFAULT_PATH);
    for (const ruleId of baseline.ruleIds()) {
      expect(known).toContain(ruleId);
    }
  });

  test('every baseline key is `file|detail` with the file inside the rule scope', () => {
    // A key whose file no longer exists is a *stale* entry (warned about by the rule test); a key
    // outside the rule's scope, or without the `file|detail` shape, is a corrupt baseline.
    const baseline = Baseline.load(DEFAULT_PATH);
    const outOfScope = [];
    for (const rule of RULES) {
      const prefix = rule.scope === 'js' ? 'src/js/' : 'templates/';
      for (const key of Object.keys(baseline.entriesFor(rule.id))) {
        const [file, detail] = key.split('|');
        if (!file.startsWith(prefix) || !detail) {
          outOfScope.push(`${rule.id}: ${key}`);
        }
      }
    }
    expect(outOfScope).toEqual([]);
  });

  test('rule ids are unique, well formed and carry description + remediation', () => {
    const ids = RULES.map((rule) => rule.id);
    expect(new Set(ids).size).toBe(ids.length);
    for (const rule of RULES) {
      expect(rule.id).toMatch(/^(js|templates)\.[a-z_]+$/);
      expect(rule.description.length).toBeGreaterThan(0);
      expect(rule.remediation).toMatch(/Backlog item/);
      expect(['js', 'templates']).toContain(rule.scope);
    }
  });
});
