// set-calculator.js (moved into main src/js module bundle)
// Provides set score validation and auto-calculation helpers via delegated focusout listeners.

function SetCalculator(inputdata) {
	if (!inputdata || !inputdata.id) return;
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	let fieldRef = inputdata.id;
	let team = "#" + fieldRef;
	let fieldSplit = fieldRef.split('_');
	let setLength;
	if (fieldSplit.length === 4) {
		setLength = 7;
	} else {
		setLength = 5;
	}
	let setRef = fieldRef.substring(0, setLength);
	let teamRefAlt;
	let teamRef = fieldRef.substr(fieldRef.length - 1, 1);
	if (teamRef === "1") {
		teamRefAlt = "2";
	} else {
		teamRefAlt = "1";
	}
	let teamScore;
	if (inputdata.value === '') {
        teamScore = null;
	} else {
        teamScore = Number.parseInt(inputdata.value, 10);
	}
	let teamAlt = "#" + setRef + "_player" + teamRefAlt;
	let teamDataAlt = jQuery(teamAlt)[0];
	if (!teamDataAlt) return;
	let teamScoreAlt;
	if (teamDataAlt.value === '') {
        teamScoreAlt = null;
	} else {
        teamScoreAlt = Number.parseInt(teamDataAlt.value, 10);
	}
	let tieBreak = "#" + setRef + "_tiebreak";
	let tieBreakWrapper = tieBreak + '_wrapper';
	let tieBreakData = jQuery(tieBreak)[0];
	let tieBreakScore;
	if (tieBreakData && tieBreakData.value !== '') {
		tieBreakScore = Number.parseInt(tieBreakData.value, 10);
	}
	let setGroup = '#' + setRef;
	let maxWin = jQuery(setGroup).data('maxwin');
	let minWin = jQuery(setGroup).data('minwin');
	let maxLoss = jQuery(setGroup).data('maxloss');
	let minLoss = jQuery(setGroup).data('minloss');
	let tiebreakSet = jQuery(setGroup).data('tiebreakset');
	if (teamRef === "1") {
		if (teamScoreAlt === null) {
			teamScoreAlt = CalculateAltScore(teamScore, maxWin, maxLoss, minWin);
		}
	} else if (teamRef === "2") {
		if (teamScoreAlt === null) {
			teamScoreAlt = CalculateAltScore(teamScore, maxWin, maxLoss, minWin);
		}
		if ((teamScore === maxWin && teamScoreAlt === tiebreakSet) || (teamScoreAlt === maxWin && teamScore === tiebreakSet)) {
			jQuery(tieBreakWrapper).show();
			jQuery(tieBreak).focus();
		} else {
			tieBreakScore = null;
			jQuery(tieBreakWrapper).hide();
		}
	}
	jQuery(team).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(teamAlt).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(tieBreak).removeClass(classes.inputError);
	if (teamScore > teamScoreAlt) {
		SetValidator(team, teamAlt, teamScore, teamScoreAlt, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin);
	} else if (teamScore < teamScoreAlt) {
		SetValidator(teamAlt, team, teamScoreAlt, teamScore, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin);
	} else if (teamScore === teamScoreAlt) {
		if (!Number.isNaN(teamScore)) {
			jQuery(team).addClass(classes.inputError);
			jQuery(teamAlt).addClass(classes.inputError)
		}
	}
	if (!Number.isNaN(teamScore)) {
		jQuery(team).val(teamScore);
	}
	if (!Number.isNaN(teamScoreAlt)) {
		jQuery(teamAlt).val(teamScoreAlt);
	}
	if (!Number.isNaN(tieBreakScore)) {
		jQuery(tieBreak).val(tieBreakScore);
	}
}

function CalculateAltScore(teamScore, maxWin, maxLoss, minWin) {
	let teamScoreAlt = '';
	if (teamScore === minWin) {
		if ((teamScore + 2) < maxWin) {
			teamScoreAlt = teamScore + 2;
		} else {
			teamScoreAlt = maxWin;
		}
	} else if (teamScore === maxWin) {
		teamScoreAlt = minWin;
	} else if (null !== teamScore) {
		if (teamScore === maxLoss) {
			teamScoreAlt = maxWin;
		} else if (teamScore < minWin) {
			teamScoreAlt = minWin;
		}
	}
	return teamScoreAlt
}

function SetValidator(team1, team2, team1Score, team2Score, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin) {
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	if (team1Score > maxWin) {
		jQuery(team1).addClass(classes.inputError);
	} else if (team1Score === minWin && team2Score > minLoss && maxWin !== minWin) {
		jQuery(team1).addClass(classes.inputError);
		jQuery(team2).addClass(classes.inputError);
	} else if (team1Score === maxWin) {
		if (team2Score < maxLoss && maxWin !== minWin) {
			jQuery(team1).addClass(classes.inputError);
			jQuery(team2).addClass(classes.inputError);
		} else if (team2Score > maxLoss) {
			jQuery(team1).addClass(classes.won);
			if ('' === tieBreakScore) {
				jQuery(tieBreak).addClass(classes.inputError);
			}
		} else {
			jQuery(team1).addClass(classes.won)
		}
	} else if (team1Score > minWin && team2Score < minLoss) {
		jQuery(team1).addClass(classes.inputError);
	} else if (team1Score > minWin && team2Score > minLoss && team2Score !== (team1Score - 2)) {
		jQuery(team1).addClass(classes.inputError);
	} else {
		jQuery(team1).addClass(classes.won);
	}
}

function SetCalculatorTieBreak(inputdata) {
	if (!inputdata || !inputdata.id) return;
	let classes = {
		inputError: 'input-validation-error'
	}
	let fieldRef = inputdata.id;
	let tieBreak = "#" + fieldRef;
	let tieBreakScore = Number.parseInt(inputdata.value, 10);
	if (Number.isNaN(tieBreakScore)) {
		jQuery(tieBreak).addClass(classes.inputError);
	} else {
		jQuery(tieBreak).removeClass(classes.inputError);
		jQuery(tieBreak).removeClass('is-invalid');
	}
}

function handleScoreBlur(e) {
  const target = e.target;
  const el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) return;
  const action = el.getAttribute('data-action') || (el.dataset ? el.dataset.action : null);
  if (action !== 'set-calculator' && action !== 'set-calculator-tiebreak') return;

  try {
    if (action === 'set-calculator') {
      SetCalculator(el);
    } else if (action === 'set-calculator-tiebreak') {
      SetCalculatorTieBreak(el);
    }
  } catch (_) {
    // Graceful handling of unexpected malformed input
  }
}

let delegationRegistered = false;

export function initializeSetCalculator() {
  if (!delegationRegistered && typeof document !== 'undefined' && typeof document.addEventListener === 'function') {
    document.addEventListener('focusout', handleScoreBlur);
    delegationRegistered = true;
  }
}

export { SetCalculator, SetCalculatorTieBreak };
