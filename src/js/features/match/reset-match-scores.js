/**
 * Reset Match Scores - Modularized
 * Mirrors legacy Racketmanager.resetMatchScores behaviour.
 */

/**
 * Reset all inputs and UI classes for a given container
 * @param {jQuery|string} container - jQuery object or selector for the container
 */
export function resetScoresInContainer(container) {
  const $container = jQuery(container);
  if (!$container.length) return;

  // Reset inputs
  $container.find(':input').each(function () {
    switch (this.type) {
      case 'password':
      case 'text':
      case 'textarea':
      case 'file':
      case 'date':
      case 'number':
      case 'tel':
      case 'email':
        jQuery(this).val('');
        // Trigger blur to update calculator if needed
        if (typeof globalThis.SetCalculator === 'function' && this.id?.includes('_player')) {
            globalThis.SetCalculator(this);
        }
        break;
      case 'select-one':
      case 'select-multiple':
        jQuery(this).val('0');
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
  $container.find('.match__message')
    .removeClass('match-warning')
    .addClass('d-none')
    .html('');
  $container.find('.match__status')
    .removeClass('winner loser tie')
    .html('');
  $container.find('.winner').removeClass('winner');
  $container.find('.loser').removeClass('loser');
  $container.find('.tie').removeClass('tie');
  
  // Also clear any validation error classes
  $container.find('.is-invalid').removeClass('is-invalid');
  $container.find('.input-validation-error').removeClass('input-validation-error');
  $container.find('.match-points.tie-break').hide();
  $container.find('[name^="match_status"]').val('');
  $container.find('.match-points__cell-input--won').removeClass('match-points__cell-input--won');
}

/**
 * Reset all inputs and UI classes for a given form id
 * @param {string} formId - id of the form element (without #)
 */
export function resetMatchScoresByFormId(formId) {
  if (!formId) return;
  resetScoresInContainer(`#${formId}`);
}

/**
 * Initialize delegated handler for reset links/buttons
 */
export function initializeResetMatchScores() {
  jQuery(document)
    .off('click.racketmanager.resetScores', '[data-action="reset-match-scores"]')
    .on('click.racketmanager.resetScores', '[data-action="reset-match-scores"]', function (e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();
      
      const formId = this.dataset.formId;
      if (formId) {
        resetMatchScoresByFormId(formId);
        return;
      }

      const rubberId = this.dataset.rubberId;
      if (rubberId) {
        // Find the rubber container by ID
        const $container = jQuery(`#rubber-${rubberId}`);
        if ($container.length) {
          resetScoresInContainer($container);
        }
      }
    });
}
