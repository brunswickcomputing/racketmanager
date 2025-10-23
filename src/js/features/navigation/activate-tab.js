/**
 * Activate Tab (Login/Register) - Modularized from legacy activaTab
 * - Provides a simple helper to show a Bootstrap tab/pill by id
 * - Initializes automatically on elements that declare data-action="activate-tab"
 */

/**
 * Activate a tab or pill by its id (without the leading #)
 * @param {string} tabId
 */
export function activateTab(tabId) {
  if (!tabId) return;
  try {
    // Bootstrap 5 + jQuery plugin interface
    jQuery('.nav-tabs button[data-bs-target="#' + tabId + '"]').tab('show');
    jQuery('.nav-pills button[data-bs-target="#' + tabId + '"]').tab('show');
  } catch (_) {
    // Fallback: toggle classes manually if Bootstrap tab plugin not present
    try {
      const btn = document.querySelector('.nav-tabs button[data-bs-target="#' + tabId + '"], .nav-pills button[data-bs-target="#' + tabId + '"]');
      const pane = document.querySelector('#' + tabId);
      if (btn) {
        // Deactivate active buttons
        document.querySelectorAll('.nav-tabs .nav-link.active, .nav-pills .nav-link.active')
          .forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
      }
      if (pane) {
        // Hide all panes
        document.querySelectorAll('.tab-content .tab-pane')
          .forEach(el => { el.classList.remove('active', 'show'); el.classList.add('fade'); });
        // Show target pane
        pane.classList.remove('fade');
        pane.classList.add('active', 'show');
      }
    } catch(__) { /* no-op */ }
  }
}

/**
 * Initialize: scan for elements with data-action="activate-tab" and activate the given tab
 */
export function initializeActivateTab() {
  try {
    jQuery('[data-action="activate-tab"]').each(function () {
      const tabId = this.getAttribute('data-tabid') || jQuery(this).data('tabid');
      if (tabId) {
        activateTab(tabId);
      }
    });
  } catch (_) { /* no-op */ }
}
