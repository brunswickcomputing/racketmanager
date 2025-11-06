(function(){
  // Ensure global namespace exists (match templates: Racketmanager)
  window.Racketmanager = window.Racketmanager || {};

  /**
   * Toggle all row checkboxes within the given form to match the state of the
   * header (select-all) checkbox found in the table header.
   *
   * Usage from templates (inline):
   *   onclick="Racketmanager.checkAll(document.getElementById('some-form-id'))"
   *
   * The function will look for a checkbox inside the form's <thead> to use as
   * the master, then apply its checked state to all other checkboxes inside the
   * form (typically the <tbody> rows).
   *
   * @param {HTMLFormElement} form
   */
  window.Racketmanager.checkAll = function(form){
    if (!form || !(form instanceof HTMLFormElement)) return;

    // Try to locate the master checkbox inside the table header of this form
    var master = form.querySelector('thead input[type="checkbox"]');

    // Fallbacks in case markup differs
    if (!master) {
      // Common ids seen across templates
      master = form.querySelector('#checkAll, #checkAllSeasons, #event-select-all, #check-all-teams, #check-all-matches, #check-all-competitions, #checkALL, #checkAllInvoices, #event-all');
    }

    // If still not found, infer desired state: if any row is unchecked, we will check all; otherwise uncheck all
    var desiredState;
    if (master) {
      desiredState = !!master.checked;
    } else {
      var rows = form.querySelectorAll('tbody input[type="checkbox"]:not(:disabled)');
      desiredState = Array.prototype.some.call(rows, function(cb){ return !cb.checked; });
    }

    // Toggle all checkboxes inside the form except the master itself
    var inputs = form.querySelectorAll('input[type="checkbox"]');
    Array.prototype.forEach.call(inputs, function(cb){
      if (cb === master) return; // keep master as-is
      if (cb.disabled) return;
      cb.checked = desiredState;
    });
  };
})();