// Racketmanager legacy frontend file (minimal)
// Phase 10 — Stage E: All legacy frontend functions removed.
// Client behavior now lives in src/js/ modules with delegated handlers.

// Preserve only the loading modal selector for compatibility with templates
var Racketmanager = (window.Racketmanager = window.Racketmanager || {});
Racketmanager.loadingModal = Racketmanager.loadingModal || '#loadingModal';

// ----- Stage C: Legacy Neutralizer (auto-generated) -----
// If not explicitly disabled, neutralize migrated legacy functions to avoid collisions with modular code.
(function(){
  try {
    var disable = !(globalThis && globalThis.RACKETMANAGER_DISABLE_LEGACY === false);
    if (!disable) return; // Legacy enabled explicitly (e.g., during rollback)
    globalThis.Racketmanager = globalThis.Racketmanager || {};
    var warn = function(name){
      return function(){
        try { console.warn('Racketmanager.' + name + ' is disabled; use modular delegated handlers instead.'); } catch(_){ }
      };
    };
    var fns = [
      'printScoreCard', 'playerSearch', 'partnerModal', 'partnerSave',
      'setEventPrice', 'clearPrice', 'setTotalPrice',
      'setPaymentStatus', 'withdrawTournament', 'confirmTournamentWithdraw',
      'showTeamOrderPlayers', 'validateTeamOrder', 'get_event_team_match_dropdown', 'teamEditModal', 'show_set_team_button',
      'clubRoleModal', 'setClubRole', 'entryRequest',
      'updateMatchResults', 'setMatchDate', 'resetMatchResult', 'resetMatchScores', 'matchHeader', 'matchOptions', 'switchHomeAway',
      'viewMatch', 'switchTab', 'getMessageFromResponse', 'updateResults', 'updateTeam'
    ];
    for (var i = 0; i < fns.length; i++) {
      var n = fns[i];
      if (typeof globalThis.Racketmanager[n] === 'function') {
        globalThis.Racketmanager[n] = warn(n);
      }
    }
  } catch(_) { /* no-op */ }
})();
// ----- End Stage C Neutralizer -----
