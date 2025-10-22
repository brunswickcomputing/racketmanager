/**
 * Reset Match Scores - Modularized
 * Mirrors legacy Racketmanager.resetMatchScores behavior.
 */

/**
 * Reset all inputs and UI classes for a given form id
 * @param {string} formId - id of the form element (without #)
 */
export function resetMatchScoresByFormId(formId) {
  if (!formId) return;
  const selectorForm = `#${formId}`;

  // Reset inputs
  jQuery(selectorForm).find(':input').each(function () {
    switch (this.type) {
      case 'password':
      case 'text':
      case 'textarea':
      case 'file':
      case 'select-one':
      case 'select-multiple':
      case 'date':
      case 'number':
      case 'tel':
      case 'email':
        jQuery(this).val('');
        break;
      case 'checkbox':
      case 'radio':
        this.checked = false;
        break;
      default:
        break;
    }
  });

  // Clear messages and status classes
  let selector = selectorForm + ' .match__message';
  jQuery(selector)
    .removeClass('match-warning')
    .addClass('d-none')
    .html('');
  selector = selectorForm + ' .winner';
  jQuery(selector).removeClass('winner');
  selector = selectorForm + ' .loser';
  jQuery(selector).removeClass('loser');
  selector = selectorForm + ' .tie';
  jQuery(selector).removeClass('tie');
}

/**
 * Initialize delegated handler for reset links/buttons
 */
export function initializeResetMatchScores() {
  jQuery(document)
    .off('click.racketmanager.resetScores', '[data-action="reset-match-scores"]')
    .on('click.racketmanager.resetScores', '[data-action="reset-match-scores"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      const formId = this.getAttribute('data-form-id') || this.dataset.formId;
      if (formId) {
        resetMatchScoresByFormId(formId);
      }
    });
}
