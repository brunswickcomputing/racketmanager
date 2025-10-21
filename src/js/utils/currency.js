/**
 * Currency utilities
 * - Provides currencyFormat with graceful fallback
 */

/**
 * Format a numeric amount to a currency string.
 * Tries to use any existing global currencyFormat for parity with legacy.
 * Falls back to Intl.NumberFormat (GBP) if unavailable.
 * @param {number|string} amount
 * @returns {string}
 */
export function currencyFormat(amount) {
  // Coerce to number safely
  const num = Number(amount || 0);
  // Prefer legacy global formatter if present
  try {
    if (typeof globalThis.currencyFormat === 'function') {
      return globalThis.currencyFormat(num);
    }
  } catch (_) { /* no-op */ }

  try {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(num);
  } catch (_) {
    // Simple fallback
    return '£' + (Math.round(num * 100) / 100).toFixed(2);
  }
}
