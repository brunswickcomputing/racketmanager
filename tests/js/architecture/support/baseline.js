// Ratchet baseline for the JS / template guardrails — same semantics and file format as the PHP
// suite's tests/Architecture/Support/Baseline.php:
//
//     { "<rule id>": { "<file>|<detail>": <tolerated count>, … }, … }
//
// Keys are line-independent; the value is how many occurrences of that key are tolerated. A rule
// passes while no key exceeds its tolerated count, so removing debt never fails the suite and adding
// debt always does. Entries whose count dropped (or whose key disappeared) are "stale" and are
// reported as warnings only.

const fs = require('fs');
const path = require('path');

const ENV_UPDATE = 'RM_ARCH_UPDATE_BASELINE';
const DEFAULT_PATH = path.resolve(__dirname, '..', 'baseline.json');

/**
 * @returns {boolean} whether the current process was asked to regenerate the baseline.
 */
function updateRequested(env = process.env) {
  const value = env[ENV_UPDATE];
  if (value === undefined || value === '') {
    return false;
  }
  return !['0', 'false', 'no', 'off'].includes(String(value).toLowerCase());
}

/**
 * Sort rules and keys, coerce counts to positive integers, drop empty rules. Accepts the compact list
 * form `["file|detail", …]` as well as the counted map form.
 *
 * @param {object} entries
 * @returns {Record<string, Record<string, number>>}
 */
function normalise(entries) {
  const normalised = {};
  if (!entries || typeof entries !== 'object' || Array.isArray(entries)) {
    return normalised;
  }
  for (const ruleId of Object.keys(entries).sort()) {
    const keys = entries[ruleId];
    const ruleEntries = {};
    if (Array.isArray(keys)) {
      for (const key of keys) {
        if (typeof key === 'string') {
          ruleEntries[key] = (ruleEntries[key] || 0) + 1;
        }
      }
    } else if (keys && typeof keys === 'object') {
      for (const key of Object.keys(keys)) {
        const count = Number(keys[key]);
        if (Number.isInteger(count) && count > 0) {
          ruleEntries[key] = count;
        }
      }
    }
    const sortedKeys = Object.keys(ruleEntries).sort();
    if (sortedKeys.length > 0) {
      normalised[ruleId] = {};
      for (const key of sortedKeys) {
        normalised[ruleId][key] = ruleEntries[key];
      }
    }
  }
  return normalised;
}

class Baseline {
  /**
   * @param {object} [entries] rule id => { key => tolerated count }
   */
  constructor(entries = {}) {
    this.entries = normalise(entries);
  }

  /**
   * Load a baseline file; a missing or empty file yields an empty baseline.
   *
   * @param {string} [filePath]
   * @returns {Baseline}
   */
  static load(filePath = DEFAULT_PATH) {
    if (!fs.existsSync(filePath)) {
      return new Baseline();
    }
    const json = fs.readFileSync(filePath, 'utf8');
    if (json.trim() === '') {
      return new Baseline();
    }
    let decoded;
    try {
      decoded = JSON.parse(json);
    } catch (error) {
      throw new Error(`Architecture baseline ${filePath} is not valid JSON: ${error.message}`);
    }
    if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) {
      throw new Error(`Architecture baseline ${filePath} must decode to an object.`);
    }
    return new Baseline(decoded);
  }

  ruleIds() {
    return Object.keys(this.entries);
  }

  hasRule(ruleId) {
    return Object.keys(this.entriesFor(ruleId)).length > 0;
  }

  entriesFor(ruleId) {
    return this.entries[ruleId] || {};
  }

  countFor(ruleId) {
    return Object.values(this.entriesFor(ruleId)).reduce((sum, count) => sum + count, 0);
  }

  /**
   * Compare the current violations of one rule with the tolerated entries.
   *
   * @param {string} ruleId
   * @param {Array<{key: () => string}>} violations
   * @returns {{ruleId: string, newViolations: object, stale: object, accepted: number, total: number, hasNewViolations: boolean, hasStaleEntries: boolean, newViolationList: Array}}
   */
  compare(ruleId, violations) {
    const allowed = this.entriesFor(ruleId);
    const grouped = {};
    for (const violation of violations) {
      const key = violation.key();
      (grouped[key] = grouped[key] || []).push(violation);
    }

    const newViolations = {};
    const stale = {};
    let accepted = 0;
    for (const key of Object.keys(grouped).sort()) {
      const count = grouped[key].length;
      const tolerated = allowed[key] || 0;
      if (count > tolerated) {
        newViolations[key] = { count, allowed: tolerated, violations: grouped[key] };
        accepted += tolerated;
      } else {
        accepted += count;
        if (count < tolerated) {
          stale[key] = { allowed: tolerated, count };
        }
      }
    }
    for (const key of Object.keys(allowed).sort()) {
      if (!grouped[key]) {
        stale[key] = { allowed: allowed[key], count: 0 };
      }
    }

    const newViolationList = [];
    for (const key of Object.keys(newViolations)) {
      newViolationList.push(...newViolations[key].violations);
    }

    return {
      ruleId,
      newViolations,
      stale,
      accepted,
      total: violations.length,
      hasNewViolations: newViolationList.length > 0,
      hasStaleEntries: Object.keys(stale).length > 0,
      newViolationList,
    };
  }

  /**
   * Copy whose entries for `ruleId` are exactly the given violations (empty list removes the rule).
   *
   * @param {string} ruleId
   * @param {Array<{key: () => string}>} violations
   * @returns {Baseline}
   */
  withRuleViolations(ruleId, violations) {
    const entries = { ...this.entries };
    delete entries[ruleId];
    const counts = {};
    for (const violation of violations) {
      counts[violation.key()] = (counts[violation.key()] || 0) + 1;
    }
    if (Object.keys(counts).length > 0) {
      entries[ruleId] = counts;
    }
    return new Baseline(entries);
  }

  withoutRule(ruleId) {
    return this.withRuleViolations(ruleId, []);
  }

  toArray() {
    return this.entries;
  }

  /**
   * Canonical serialisation: sorted keys, 4-space indent (matches the PHP baseline), trailing newline.
   */
  toJson() {
    if (Object.keys(this.entries).length === 0) {
      return '{}\n';
    }
    return `${JSON.stringify(this.entries, null, 4)}\n`;
  }

  write(filePath = DEFAULT_PATH) {
    fs.writeFileSync(filePath, this.toJson(), 'utf8');
  }

  isSortedOnDisk(filePath = DEFAULT_PATH) {
    return fs.existsSync(filePath) && fs.readFileSync(filePath, 'utf8') === this.toJson();
  }
}

module.exports = {
  Baseline,
  ENV_UPDATE,
  DEFAULT_PATH,
  updateRequested,
  normalise,
};
