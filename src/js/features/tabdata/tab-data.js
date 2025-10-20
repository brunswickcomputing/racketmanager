/**
 * Tab data loading functionality
 * Handles dynamic content loading for tabs
 */

import { getAjaxUrl, getAjaxNonce } from '../../config/ajax-config.js';
import { attachMatchViewEventListeners } from '../navigation/match-viewer.js';

/**
 * Initialize tab data handlers
 */
export function initializeTabData() {
    attachTabDataEventListeners();
    attachTabDataLinkEventListeners();
}

/**
 * Re-initialize event listeners after dynamic content load
 */
function reinitializeEventListeners() {
    attachTabDataEventListeners();
    attachTabDataLinkEventListeners();
    attachMatchViewEventListeners();
}

/**
 * Attach event listeners for tab data elements
 */
function attachTabDataEventListeners() {
    const tabDataElements = document.querySelectorAll('.tabData');

    tabDataElements.forEach(tab => {
        // Remove existing listener to avoid duplicates
        tab.removeEventListener('click', handleTabDataClick);
        // Add new listener
        tab.addEventListener('click', handleTabDataClick);
    });
}

/**
 * Handle tab data click event
 *
 * @param {Event} e - Click event
 */
function handleTabDataClick(e) {
    const target = e.currentTarget;
    const type = target.dataset.type;
    const typeId = target.dataset.typeId;
    const season = target.dataset.season;
    const name = target.dataset.name;
    const competitionType = target.dataset.competitionType;

    tabData(e, type, typeId, season, name, competitionType);
}

/**
 * Attach event listeners for tab data link elements
 */
function attachTabDataLinkEventListeners() {
    const tabDataLinks = document.querySelectorAll('.tabDataLink');

    tabDataLinks.forEach(link => {
        // Remove existing listener to avoid duplicates
        link.removeEventListener('click', handleTabDataLinkClick);
        // Add new listener
        link.addEventListener('click', handleTabDataLinkClick);
    });
}

/**
 * Handle tab data link click event
 *
 * @param {Event} e - Click event
 */
function handleTabDataLinkClick(e) {
    const target = e.currentTarget;
    const type = target.dataset.type;
    const typeId = target.dataset.typeId;
    const season = target.dataset.season;
    const link = target.dataset.link;
    const linkId = target.dataset.linkId;
    const linkType = target.dataset.linkType;

    tabDataLink(e, type, typeId, season, link, linkId, linkType);
}

/**
 * Load tab data via AJAX
 *
 * @param {Event} e - Event object
 * @param {string} type - Entity type (league, competition, tournament, etc.)
 * @param {number} targetId - Entity ID
 * @param {string} season - Season
 * @param {string} name - Entity name
 * @param {string} competitionType - Competition type
 */
export function tabData(e, type, targetId, season, name, competitionType) {
    e.preventDefault();

    // Get active tab to determine content type
    const activeTab = e.currentTarget;
    const target = activeTab.getAttribute('data-bs-target') ||
        activeTab.getAttribute('aria-controls');

    if (!target) {
        console.error('No tab target found');
        return;
    }
    // Remove # prefix if present to get the clean target name
    const tabTarget = target.replace('#', '');

    // Build the new URL that would be created
    const newUrl = buildTabUrl(type, season, name, tabTarget, competitionType);

    // Check if URL would change - if not, skip AJAX call
    if (window.location.href === newUrl) {
        console.log('Tab already loaded (URL unchanged), skipping AJAX call');
        return;
    }

    const targetContent = document.querySelector(target);
    if (targetContent) {
        targetContent.innerHTML = '';
    }

    // Build the tab content selector (adds TabContent suffix)
    const tabContentSelector = '#' + type + 'TabContent';

    // Show loading state
    const tabContent = document.querySelector(tabContentSelector);
    if (tabContent) {
        showLoadingState(tabContent);
    }

    // Build AJAX data
    const ajaxData = {
        action: 'racketmanager_get_tab_data',
        security: getAjaxNonce(),
        target: type,
        targetId: targetId,
        season: season,
        name: name,
        competitionType: competitionType,
        tab: tabTarget
    };

    // Make AJAX request
    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: ajaxData,
        success: function(response) {
            if (target) {
                targetContent.innerHTML = response;

                // Re-initialize event listeners for new content
                reinitializeEventListeners();

                // Update URL without reload
                if (history.pushState) {
                    history.pushState({ html: response }, '', newUrl);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Tab data load error:', error);
            if (tabContent) {
                showErrorState(tabContent, 'Failed to load content');
            }
        },
        complete: function() {
            if (tabContent) {
                hideLoadingState(tabContent);
            }
        }
    });
}

/**
 * Load tab data via link/navigation
 *
 * @param {Event} e - Event object
 * @param {string} type - Entity type
 * @param {number} typeId - Entity ID
 * @param {string|null} season - Season
 * @param {string} link - URL path
 * @param {string} linkId - Link identifier
 * @param {string} linkType - Link type
 */
export function tabDataLink(e, type, typeId, season, link, linkId, linkType) {
    e.preventDefault();

    // Build the tab content selector
    const tabContentSelector = '#' + type + 'TabContent';
    const tabContent = document.querySelector(tabContentSelector);

    if (tabContent) {
        showLoadingState(tabContent);
    }

    // Get tab references
    const tab = linkType;
    const tabDataRef = '#' + tab;
    const tabRef = tabDataRef + '-tab';

    // Check if we need to switch tabs
    const activeTab = document.querySelector('.tab-pane.active');
    const activeTabName = activeTab ? activeTab.id : '';

    if (activeTabName !== tab) {
        // Deactivate all tab buttons
        const tabButtons = document.querySelectorAll('#myTab li > button');
        tabButtons.forEach(button => button.classList.remove('active'));

        // Activate target tab button
        const targetTabButton = document.querySelector(tabRef);
        if (targetTabButton) {
            targetTabButton.classList.add('active');
        }

        // Hide all tab panes
        const tabPanes = document.querySelectorAll('.tab-pane');
        tabPanes.forEach(pane => {
            pane.classList.remove('active', 'show');
            pane.classList.add('fade');
        });

        // Show target tab pane
        const targetTabPane = document.querySelector(tabDataRef);
        if (targetTabPane) {
            targetTabPane.classList.remove('fade');
            targetTabPane.classList.add('active');
            targetTabPane.style.display = '';
        }
    }

    // Clear target tab content
    const targetTabPane = document.querySelector(tabDataRef);
    if (targetTabPane) {
        targetTabPane.innerHTML = '';
    }

    // Build AJAX data
    const ajaxData = {
        action: 'racketmanager_get_tab_data',
        security: getAjaxNonce(),
        target: type,
        targetId: typeId,
        season: season || '',
        tab: linkType,
        link_id: linkId
    };

    // Make AJAX request
    jQuery.ajax({
        url: getAjaxUrl(),
        type: 'POST',
        data: ajaxData,
        success: function(response) {
            if (targetTabPane) {
                targetTabPane.innerHTML = response;

                // Re-initialize event listeners for new content
                reinitializeEventListeners();

                // Update URL without reload
                if (history.pushState) {
                    const url = new URL(window.location.href);
                    const newURL = url.protocol + '//' + url.hostname + link;
                    history.pushState(document.querySelector('#pageContentTab').innerHTML, '', newURL);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Tab data link load error:', error);
            if (targetTabPane) {
                showErrorState(targetTabPane, 'Failed to load content');
            }
        },
        complete: function() {
            if (tabContent) {
                hideLoadingState(tabContent);
            }
        }
    });
}

/**
 * Show loading state
 *
 * @param {HTMLElement} element - Element to show loading in
 */
function showLoadingState(element) {
    element.classList.add('is-loading');
}

/**
 * Hide loading state
 *
 * @param {HTMLElement} element - Element to hide loading from
 */
function hideLoadingState(element) {
    element.classList.remove('is-loading');
}

/**
 * Show error state
 *
 * @param {HTMLElement} element - Element to show error in
 * @param {string} message - Error message
 */
function showErrorState(element, message) {
    const error = document.createElement('div');
    error.className = 'alert alert-danger';
    error.textContent = message;
    element.innerHTML = '';
    element.appendChild(error);
}

/**
 * Build tab URL
 *
 * @param {string} type - Entity type
 * @param {string} season - Season
 * @param {string} name - Entity name
 * @param {string} tab - Tab identifier
 * @param {string} competitionType - Competition type
 * @returns {string} URL
 */
function buildTabUrl(type, season, name, tab, competitionType) {
    const base = window.location.origin;
    const tabName = tab.replace('#', '');
    let newPath = '';

    // Build URL path based on entity type
    if (type === 'tournament') {
        newPath = '/tournament/' + name + '/';
    } else if (type === 'event') {
        newPath = '/' + competitionType + 's/' + name + '/' + season + '/';
    } else if (type === 'competition') {
        newPath = '/' + name + '/' + season + '/';
    } else if (type === 'league') {
        newPath = '/' + competitionType + '/' + name + '/' + season + '/';
    }

    // Add tab to path
    if (newPath) {
        return base + newPath + tabName + '/';
    }

    // Fallback
    return base + '/' + type + '/' + name + '/' + tabName + '/';
}
/**
 * Attach to global Racketmanager object for backward compatibility
 */
export function attachTabDataToGlobal() {
    if (!window.Racketmanager) {
        window.Racketmanager = {};
    }

    window.Racketmanager.tabData = tabData;
    window.Racketmanager.tabDataLink = tabDataLink;
}
