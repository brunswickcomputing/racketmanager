/**
 * Rubber Status UI utilities (modular)
 * Replaces legacy Racketmanager.setRubberStatusMessages / setRubberStatusClasses / setTeamMessage
 */

/**
 * Update a single team message block
 * @param {string} messageRef - CSS selector for the message element
 * @param {string} teamMessage - Message text (or falsy to clear)
 */
export function setTeamMessage(messageRef, teamMessage) {
    if (teamMessage) {
        jQuery(messageRef).html(teamMessage);
        jQuery(messageRef).removeClass('d-none');
        jQuery(messageRef).addClass('match-warning');
    } else {
        jQuery(messageRef).addClass('d-none');
        jQuery(messageRef).removeClass('match-warning');
        jQuery(messageRef).html('');
    }
}

/**
 * Apply status messages for both teams for a given rubber number
 * @param {number|string} rubberNumber
 * @param {Array<[string,string]>} statusMessages - entries like [[teamRef, message], ...]
 */
export function setRubberStatusMessages(rubberNumber, statusMessages) {
    for (let i in statusMessages) {
        const statusMessage = statusMessages[i];
        const teamRef = statusMessage[0];
        const teamMessage = statusMessage[1];
        const messageRef = '#match-message-' + rubberNumber + '-' + teamRef;
        setTeamMessage(messageRef, teamMessage);
    }
}

/**
 * Apply CSS status classes for both teams for a given rubber number
 * @param {number|string} rubberNumber
 * @param {Array<[string,string]>} statusClasses - entries like [[teamRef, className], ...]
 */
export function setRubberStatusClasses(rubberNumber, statusClasses) {
    for (let i in statusClasses) {
        const statusClass = statusClasses[i];
        const teamRef = statusClass[0];
        const teamClass = statusClass[1];
        const statusRef = '#match-status-' + rubberNumber + '-' + teamRef;
        jQuery(statusRef).removeClass('winner loser tie');
        if (teamClass) {
            jQuery(statusRef).addClass(teamClass);
        }
    }
}
