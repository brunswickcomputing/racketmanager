
/**
 * Checkbox conditional visibility handlers
 */

import { handleCheckboxConditional } from '../../utils/dom-utils.js';

export function initializeCheckboxConditionals() {
    // Checkboxes without modals
    jQuery(".noModal:checkbox").click(function (event) {
        handleCheckboxConditional(event.target);
    });
}
