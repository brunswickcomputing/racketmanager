/**
 * Pricing & Totals (Phase 5)
 * Modularized implementation of legacy pricing helpers:
 * - setEventPrice(eventId)
 * - clearEventPrice(eventId)
 * - updateTotalPrice()
 *
 * DOM contracts (from templates/entry/entry-tournament.php):
 * - Hidden event fee per event: #eventFee-<eventId>
 * - Formatted per-event price span: #event-price-fmt-<eventId>
 * - Hidden per-event price amount input (class .event-price-amt): #event-price-<eventId>
 * - Competition fee hidden field: #competitionFee (if present)
 * - Total formatted container: #priceCostTotalFmt
 * - Total hidden input: #priceCostTotal
 */

import { currencyFormat } from '../../utils/currency.js';

const COMPETITION_FEE = '#competitionFee';
const EVENT_PRICE_AMT_SELECTOR = '.event-price-amt';
const TOTAL_FMT = '#priceCostTotalFmt';
const TOTAL_VAL = '#priceCostTotal';

/**
 * Update a single event price from its fee field and refresh totals
 * @param {number|string} eventId
 */
export function setEventPrice(eventId) {
  const feeField = `#eventFee-${eventId}`;
  const eventFee = jQuery(feeField).val();
  const price = Number(eventFee || 0);
  if (price > 0) {
    const fmt = currencyFormat(price);
    const eventPriceId = `#event-price-${eventId}`;
    const eventPriceFmtId = `#event-price-fmt-${eventId}`;
    jQuery(eventPriceId).val(price);
    jQuery(eventPriceFmtId).html(fmt);
  }
  updateTotalPrice();
}

/**
 * Clear a single event price and refresh totals
 * @param {number|string} eventId
 */
export function clearEventPrice(eventId) {
  const eventPriceId = `#event-price-${eventId}`;
  const eventPriceFmtId = `#event-price-fmt-${eventId}`;
  jQuery(eventPriceId).val('');
  jQuery(eventPriceFmtId).html('');
  updateTotalPrice();
}

/**
 * Recalculate the total price across competition fee and selected event prices
 */
export function updateTotalPrice() {
  const competitionFee = Number(jQuery(COMPETITION_FEE).val() || 0);
  const eventPrices = jQuery(EVENT_PRICE_AMT_SELECTOR);
  let total = competitionFee;
  for (const element of eventPrices) {
    const v = Number(element.value || 0);
    total += v;
  }
  let fmt = '';
  if (total > 0) {
    fmt = 'Total: ' + currencyFormat(total);
  }
  jQuery(TOTAL_FMT).html(fmt);
  jQuery(TOTAL_VAL).val(total);
}

/**
 * Initialize pricing feature. Optionally recompute totals on load.
 */
export function initializePricing() {
  // Recompute total once on startup to reflect any pre-populated values
  try { updateTotalPrice(); } catch (_) { /* no-op */ }
}
