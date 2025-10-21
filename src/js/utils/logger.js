import { AppConfig, telemetryEnabled } from '../config/app-config.js';

const levels = ['debug', 'info', 'warn', 'error'];

function shouldLog(level) {
  const configured = AppConfig.logging?.level || 'warn';
  if (configured === 'none') return false;
  const idx = levels.indexOf(level);
  const cfgIdx = levels.indexOf(configured);
  if (cfgIdx === -1) return false;
  return idx >= cfgIdx;
}

function safeConsole(method, args) {
  try {
    /* eslint-disable no-console */
    if (typeof console !== 'undefined' && typeof console[method] === 'function') {
      console[method](...args);
    }
    /* eslint-enable no-console */
  } catch (_) {
    // no-op
  }
}

export const logger = {
  debug: (...args) => shouldLog('debug') && safeConsole('debug', args),
  info: (...args) => shouldLog('info') && safeConsole('info', args),
  warn: (...args) => shouldLog('warn') && safeConsole('warn', args),
  error: (...args) => shouldLog('error') && safeConsole('error', args),
};

// Optional telemetry sink (no network by default). Placeholder for future wiring.
export function recordEvent(name, data = {}) {
  if (!telemetryEnabled()) return;
  // Intentionally only logs locally for Phase 0; no PII included.
  logger.debug('[telemetry]', name, data);
}
