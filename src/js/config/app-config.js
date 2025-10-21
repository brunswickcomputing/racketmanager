/**
 * App configuration and feature flags
 * Values can be overridden via a global RACKETMANAGER_CONFIG (e.g. wp_localize_script)
 */

const DEFAULT_CONFIG = {
  env: 'production', // 'development' | 'production'
  flags: {
    enableTelemetry: false, // off in production by default
    enablePerfMarks: false,
  },
  logging: {
    level: 'warn', // 'debug' | 'info' | 'warn' | 'error' | 'none'
  }
};

function normalize(config) {
  const out = { ...DEFAULT_CONFIG, ...(config || {}) };
  out.flags = { ...DEFAULT_CONFIG.flags, ...(config && config.flags) };
  out.logging = { ...DEFAULT_CONFIG.logging, ...(config && config.logging) };

  // Safety: production defaults to telemetry off and conservative logging
  if (out.env === 'production') {
    out.flags.enableTelemetry = false;
    if (!config || !config.logging || !config.logging.level) {
      out.logging.level = 'warn';
    }
  }
  return out;
}

// Read global if provided, else defaults
const GLOBAL_CFG = (typeof globalThis !== 'undefined' && globalThis.RACKETMANAGER_CONFIG) || {};
export const AppConfig = normalize(GLOBAL_CFG);

export function isDev() {
  return AppConfig.env === 'development';
}

export function telemetryEnabled() {
  return !!AppConfig.flags.enableTelemetry;
}

export function perfMarksEnabled() {
  return !!AppConfig.flags.enablePerfMarks && telemetryEnabled();
}
