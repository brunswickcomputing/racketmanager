// set-calculator.js (moved into main src/js module bundle)
// Provides set score validation and auto-calculation helpers via delegated focusout listeners.

const SET_CLASSES = {
	inputError: 'input-validation-error',
	won: 'match-points__cell-input--won'
};

function parseSetFieldInfo(fieldRef) {
	let fieldSplit = fieldRef.split('_');
	let setLength = fieldSplit.length === 4 ? 7 : 5;
	let setRef = fieldRef.substring(0, setLength);
	let teamRef = fieldRef.substr(fieldRef.length - 1, 1);
	let teamRefAlt = teamRef === '1' ? '2' : '1';

	return {
		fieldRef,
		team: '#' + fieldRef,
		setRef,
		teamRef,
		teamRefAlt,
		teamAlt: '#' + setRef + '_player' + teamRefAlt,
		tieBreak: '#' + setRef + '_tiebreak',
		tieBreakWrapper: '#' + setRef + '_tiebreak_wrapper',
		setGroup: '#' + setRef
	};
}

function parseScoreInput(value) {
	if (value === '' || value === null || value === undefined) {
		return null;
	}
	return Number.parseInt(value, 10);
}

function syncTiebreakVisibility(teamRef, teamScore, teamScoreAlt, maxWin, tiebreakSet, tieBreakWrapper, tieBreak) {
	if (teamRef !== '2') {
		return false;
	}
	if ((teamScore === maxWin && teamScoreAlt === tiebreakSet) || (teamScoreAlt === maxWin && teamScore === tiebreakSet)) {
		jQuery(tieBreakWrapper).show();
		jQuery(tieBreak).focus();
		return false;
	}
	jQuery(tieBreakWrapper).hide();
	return true;
}

function markInputError(...selectors) {
	selectors.forEach((sel) => {
		if (sel) {
			jQuery(sel).addClass(SET_CLASSES.inputError);
		}
	});
}

function markInputWon(selector) {
	if (selector) {
		jQuery(selector).addClass(SET_CLASSES.won);
	}
}

function validateTieBreakScore(tieBreak, tieBreakScore) {
	if ('' === tieBreakScore) {
		jQuery(tieBreak).addClass(SET_CLASSES.inputError);
	}
}

function isInvalidHighScore(team1Score, maxWin) {
	return team1Score > maxWin;
}

function isInvalidMinWinScore(team1Score, team2Score, minWin, minLoss, maxWin) {
	return team1Score === minWin && team2Score > minLoss && maxWin !== minWin;
}

function isInvalidMaxWinScore(team1Score, team2Score, maxWin, maxLoss, minWin) {
	return team1Score === maxWin && team2Score < maxLoss && maxWin !== minWin;
}

function isInvalidAdvantageScore(team1Score, team2Score, minWin, minLoss) {
	if (team1Score <= minWin) {
		return false;
	}
	return team2Score < minLoss || (team2Score > minLoss && team2Score !== (team1Score - 2));
}

function SetValidator(teams, scores, tieBreakData, limits) {
	const { team1, team2 } = teams;
	const { team1Score, team2Score } = scores;
	const { tieBreak, tieBreakScore } = tieBreakData;
	const { maxLoss, maxWin, minLoss, minWin } = limits;

	if (isInvalidHighScore(team1Score, maxWin)) {
		markInputError(team1);
		return;
	}
	if (isInvalidMinWinScore(team1Score, team2Score, minWin, minLoss, maxWin)) {
		markInputError(team1, team2);
		return;
	}
	if (isInvalidMaxWinScore(team1Score, team2Score, maxWin, maxLoss, minWin)) {
		markInputError(team1, team2);
		return;
	}
	if (team1Score === maxWin) {
		markInputWon(team1);
		if (team2Score > maxLoss) {
			validateTieBreakScore(tieBreak, tieBreakScore);
		}
		return;
	}
	if (isInvalidAdvantageScore(team1Score, team2Score, minWin, minLoss)) {
		markInputError(team1);
		return;
	}
	markInputWon(team1);
}

function applySetValidation(teams, scores, tieBreakData, limits) {
	const { team, teamAlt } = teams;
	const { teamScore, teamScoreAlt } = scores;

	if (teamScore > teamScoreAlt) {
		SetValidator(
			{ team1: team, team2: teamAlt },
			{ team1Score: teamScore, team2Score: teamScoreAlt },
			tieBreakData,
			limits
		);
	} else if (teamScore < teamScoreAlt) {
		SetValidator(
			{ team1: teamAlt, team2: team },
			{ team1Score: teamScoreAlt, team2Score: teamScore },
			tieBreakData,
			limits
		);
	} else if (teamScore === teamScoreAlt && !Number.isNaN(teamScore)) {
		jQuery(team).addClass(SET_CLASSES.inputError);
		jQuery(teamAlt).addClass(SET_CLASSES.inputError);
	}
}

function updateScoreValues(team, teamAlt, tieBreak, teamScore, teamScoreAlt, tieBreakScore) {
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

function SetCalculator(inputdata) {
	if (!inputdata?.id) {
		return;
	}
	let {
		team,
		teamRef,
		teamAlt,
		tieBreak,
		tieBreakWrapper,
		setGroup
	} = parseSetFieldInfo(inputdata.id);

	let teamDataAlt = jQuery(teamAlt)[0];
	if (!teamDataAlt) {
		return;
	}

	let teamScore = parseScoreInput(inputdata.value);
	let teamScoreAlt = parseScoreInput(teamDataAlt.value);

	let tieBreakData = jQuery(tieBreak)[0];
	let tieBreakScore;
	if (tieBreakData && tieBreakData.value !== '') {
		tieBreakScore = parseScoreInput(tieBreakData.value);
	}

	let maxWin = jQuery(setGroup).data('maxwin');
	let minWin = jQuery(setGroup).data('minwin');
	let maxLoss = jQuery(setGroup).data('maxloss');
	let minLoss = jQuery(setGroup).data('minloss');
	let tiebreakSet = jQuery(setGroup).data('tiebreakset');

	if (teamScoreAlt === null) {
		teamScoreAlt = CalculateAltScore(teamScore, maxWin, maxLoss, minWin);
	}
	const shouldClearTieBreak = syncTiebreakVisibility(teamRef, teamScore, teamScoreAlt, maxWin, tiebreakSet, tieBreakWrapper, tieBreak);
	if (shouldClearTieBreak) {
		tieBreakScore = null;
	}

	jQuery(team).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(teamAlt).removeClass('input-validation-error match-points__cell-input--won is-invalid');
	jQuery(tieBreak).removeClass(SET_CLASSES.inputError);

	applySetValidation(
		{ team, teamAlt },
		{ teamScore, teamScoreAlt },
		{ tieBreak, tieBreakScore },
		{ maxLoss, maxWin, minLoss, minWin }
	);

	updateScoreValues(team, teamAlt, tieBreak, teamScore, teamScoreAlt, tieBreakScore);
}

function CalculateAltScore(teamScore, maxWin, maxLoss, minWin) {
	if (teamScore === minWin) {
		if ((teamScore + 2) < maxWin) {
			return teamScore + 2;
		}
		return maxWin;
	}
	if (teamScore === maxWin) {
		return minWin;
	}
	if (teamScore === maxLoss) {
		return maxWin;
	}
	if (teamScore !== null && teamScore < minWin) {
		return minWin;
	}
	return '';
}

function SetCalculatorTieBreak(inputdata) {
	if (!inputdata?.id) {
		return;
	}
	let fieldRef = inputdata.id;
	let tieBreak = '#' + fieldRef;
	let tieBreakScore = Number.parseInt(inputdata.value, 10);
	if (Number.isNaN(tieBreakScore)) {
		jQuery(tieBreak).addClass(SET_CLASSES.inputError);
	} else {
		jQuery(tieBreak).removeClass(SET_CLASSES.inputError);
		jQuery(tieBreak).removeClass('is-invalid');
	}
}

function handleScoreBlur(e) {
  const target = e.target;
  const el = target && typeof target.closest === 'function' ? target.closest('[data-action]') : null;
  if (!el) {
    return;
  }
  const action = el.dataset?.action;
  if (action !== 'set-calculator' && action !== 'set-calculator-tiebreak') {
    return;
  }

  try {
    if (action === 'set-calculator') {
      SetCalculator(el);
    } else if (action === 'set-calculator-tiebreak') {
      SetCalculatorTieBreak(el);
    }
  } catch (_) {
    // Suppress calculation errors on delegated focusout so unexpected or malformed DOM inputs do not break other event handlers
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
