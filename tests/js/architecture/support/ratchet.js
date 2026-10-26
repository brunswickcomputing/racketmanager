// Ratchet assertion shared by the guardrail test and its engine tests: evaluate a rule, diff against
// the baseline and throw an actionable error when new debt is found (or record the current debt when
// RM_ARCH_UPDATE_BASELINE=1 was given).

const { Baseline, ENV_UPDATE } = require('./baseline');
const { evaluate } = require('./rules');

const MAX_LISTED_VIOLATIONS = 40;

/**
 * @param {object} rule
 * @param {Array} violations
 * @param {object|null} diff result of Baseline#compare
 * @returns {string}
 */
function failureMessage(rule, violations, diff) {
  const lines = [];
  lines.push(
    `Architecture rule \`${rule.id}\` (${rule.description}) has ${violations.length} new violation(s):`
  );
  for (const violation of violations.slice(0, MAX_LISTED_VIOLATIONS)) {
    lines.push(`  - ${violation.describe()}`);
  }
  if (violations.length > MAX_LISTED_VIOLATIONS) {
    lines.push(`  … and ${violations.length - MAX_LISTED_VIOLATIONS} more`);
  }
  if (diff) {
    const exceeded = Object.entries(diff.newViolations).filter(([, entry]) => entry.allowed > 0);
    if (exceeded.length > 0) {
      lines.push(
        '  Keys whose occurrence count now exceeds the baseline (every occurrence is listed above):'
      );
      for (const [key, entry] of exceeded) {
        lines.push(`    ${key}: ${entry.count} > ${entry.allowed} tolerated`);
      }
    }
    lines.push(
      `  The baseline covers ${diff.accepted} of ${diff.total} current violation(s) of this rule.`
    );
  }
  lines.push('');
  lines.push(`Remediation: ${rule.remediation}`);
  lines.push(
    'A failing guardrail means new legacy debt was introduced. Fix the code rather than regenerating the baseline'
  );
  lines.push(`(${ENV_UPDATE}=1 npm test -- tests/js/architecture is only for removing debt or intentional renames).`);
  return lines.join('\n');
}

/**
 * @param {object} rule
 * @param {object} diff
 * @returns {string}
 */
function staleMessage(rule, diff) {
  const keys = Object.keys(diff.stale);
  let removed = 0;
  for (const entry of Object.values(diff.stale)) {
    removed += entry.allowed - entry.count;
  }
  const lines = [
    `[architecture] ${rule.id}: ${removed} baseline occurrence(s) in ${keys.length} key(s) no longer exist — regenerate with ${ENV_UPDATE}=1 npm test -- tests/js/architecture to prune them:`,
  ];
  for (const key of keys.slice(0, MAX_LISTED_VIOLATIONS)) {
    lines.push(`  - ${key} (${diff.stale[key].count} of ${diff.stale[key].allowed} tolerated remain)`);
  }
  if (keys.length > MAX_LISTED_VIOLATIONS) {
    lines.push(`  … and ${keys.length - MAX_LISTED_VIOLATIONS} more`);
  }
  return `${lines.join('\n')}\n`;
}

/**
 * Evaluate `rule` over `files` and compare with the baseline file at `baselinePath`.
 *
 * @param {object} rule
 * @param {Array<{file: string, source: string}>} files
 * @param {{baselinePath?: string, update?: boolean, warn?: (message: string) => void}} [options]
 * @returns {{violations: Array, diff: object|null, updated: boolean}}
 * @throws {Error} when new violations exist (and update mode is off)
 */
function assertRuleHolds(rule, files, options = {}) {
  const baselinePath = options.baselinePath;
  const warn = options.warn || ((message) => process.stderr.write(message));
  const violations = evaluate(rule, files);
  const baseline = Baseline.load(baselinePath);

  if (options.update) {
    baseline.withRuleViolations(rule.id, violations).write(baselinePath);
    return { violations, diff: null, updated: true };
  }

  const diff = baseline.compare(rule.id, violations);
  if (diff.hasStaleEntries) {
    warn(staleMessage(rule, diff));
  }
  if (diff.hasNewViolations) {
    throw new Error(failureMessage(rule, diff.newViolationList, diff));
  }
  return { violations, diff, updated: false };
}

module.exports = {
  assertRuleHolds,
  failureMessage,
  staleMessage,
  MAX_LISTED_VIOLATIONS,
};
