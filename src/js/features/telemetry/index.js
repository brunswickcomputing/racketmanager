import { perfMarksEnabled, telemetryEnabled } from '../../config/app-config.js';
import { recordEvent } from '../../utils/logger.js';

/**
 * Initialize lightweight telemetry for Phase 0.
 * - Adds optional jQuery AJAX performance marks and basic event recording.
 * - No network transmission by default; logs stay local via logger.
 */
export function initializeTelemetry() {
  if (!telemetryEnabled() && !perfMarksEnabled()) return;

  // Namespaced bindings to avoid duplicates
  jQuery(document)
    .off('.rmTelemetry')
    .on('ajaxSend.rmTelemetry', function (_e, _jqXHR, settings) {
      if (perfMarksEnabled()) {
        try { performance.mark('rm:ajax:start'); } catch (_) { /* no-op */ }
      }
      recordEvent('ajax_send', { url: settings && settings.url });
    })
    .on('ajaxComplete.rmTelemetry', function (_e, _jqXHR, settings) {
      if (perfMarksEnabled()) {
        try {
          performance.mark('rm:ajax:end');
          performance.measure('rm:ajax:duration', 'rm:ajax:start', 'rm:ajax:end');
        } catch (_) { /* no-op */ }
      }
      recordEvent('ajax_complete', { url: settings && settings.url });
    });
}
