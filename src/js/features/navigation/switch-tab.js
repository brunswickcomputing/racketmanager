/**
 * Switch Tab (List/Grid) - Modularized from legacy Racketmanager.switchTab
 * Uses delegated handler and data-action="switch-tab" with data-tabid="tab-list|tab-grid".
 */

/**
 * Perform the tab switch logic
 * @param {HTMLElement} elem - The clicked button element
 */
export function switchTab(elem) {
  if (!elem) return;
  const selectedTab = (elem.getAttribute('data-tabid') || '').toLowerCase();
  const $matches = jQuery('.match');
  const $matchGroup = jQuery('.match-group');
  const $tabList = jQuery('#tab-list');
  const $tabGrid = jQuery('#tab-grid');

  switch (selectedTab) {
    case 'tab-grid':
      $matchGroup.addClass('match-group--grid');
      $matches.removeClass('match--list');
      $tabList.removeClass('active');
      $matches.removeClass('match--list');
      $tabGrid.addClass('active');
      break;
    case 'tab-list':
      $matchGroup.removeClass('match-group--grid');
      $tabGrid.removeClass('active');
      $matches.addClass('match--list');
      $tabList.addClass('active');
      break;
    default:
      // no-op
      break;
  }
}

/**
 * Initialize delegated handler for switch-tab
 */
export function initializeSwitchTab() {
  jQuery(document)
    .off('click.racketmanager.switchTab', '[data-action="switch-tab"]')
    .on('click.racketmanager.switchTab', '[data-action="switch-tab"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      return switchTab(this);
    });
}
