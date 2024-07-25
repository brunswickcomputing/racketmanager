function SetCalculator(inputdata) {
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	let fieldRef = inputdata.id;
	let team = "#" + fieldRef;
	let fieldSplit = fieldRef.split('_');
	let setLength = 0;
	if (fieldSplit.length == 4) {
		setLength = 7;
	} else {
		setLength = 5;
	}
	let setRef = fieldRef.substring(0, setLength);
	let teamRefAlt = '';
	let teamRef = fieldRef.substr(fieldRef.length - 1, 1);
	if (teamRef == 1) {
		teamRefAlt = 2;
	} else {
		teamRefAlt = 1;
	}
	let teamScore = '';
	if (inputdata.value != '') {
		teamScore = parseInt(inputdata.value);
	}
	let teamAlt = "#" + setRef + "_player" + teamRefAlt;
	let teamDataAlt = jQuery(teamAlt)[0];
	let teamScoreAlt = '';
	if (teamDataAlt.value != '') {
		teamScoreAlt = parseInt(teamDataAlt.value);
	}
	let tieBreak = "#" + setRef + "_tiebreak";
	let tieBreakWrapper = tieBreak + '_wrapper';
	let tieBreakData = jQuery(tieBreak)[0];
	let tieBreakScore = '';
	if (tieBreakData.value != '') {
		tieBreakScore = parseInt(tieBreakData.value);
	}
	let setGroup = '#' + setRef;
	let maxWin = jQuery(setGroup).data('maxwin');
	let minWin = jQuery(setGroup).data('minwin');
	let maxLoss = jQuery(setGroup).data('maxloss');
	let minLoss = jQuery(setGroup).data('minloss');
	let tiebreakSet = jQuery(setGroup).data('tiebreakset');
	if (teamRef == 1) {
		if (teamScore == minWin) {
			if ('' === teamScoreAlt) {
				if ((teamScore + 2) < maxWin) {
					teamScoreAlt = teamScore + 2;
				} else {
					teamScoreAlt = maxWin;
				}
			}
		} else if (teamScore == maxWin) {
			if ('' == teamScoreAlt) {
				teamScoreAlt = minWin;
			}
		} else if ('' !== teamScore) {
			if ('' === teamScoreAlt) {
				if (teamScore === maxLoss) {
					teamScoreAlt = maxWin;
				} else if (teamScore < minWin) {
					teamScoreAlt = minWin;
				}
			}
		}
	} else if (teamRef == 2) {
		if ((teamScore == maxWin && teamScoreAlt == tiebreakSet) || (teamScoreAlt == maxWin && teamScore == tiebreakSet)) {
			jQuery(tieBreakWrapper).show();
			jQuery(tieBreak).focus();
		} else {
			tieBreakScore = '';
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
		if (!isNaN(teamScore) && '' != teamScore) {
			jQuery(team).addClass(classes.inputError);
			jQuery(teamAlt).addClass(classes.inputError)
		}
	}
	if (!isNaN(teamScore)) {
		jQuery(team).val(teamScore);
	}
	if (!isNaN(teamScoreAlt)) {
		jQuery(teamAlt).val(teamScoreAlt);
	}
	if (!isNaN(tieBreakScore)) {
		jQuery(tieBreak).val(tieBreakScore);
	}
};
function SetValidator(team1, team2, team1Score, team2Score, tieBreak, tieBreakScore, maxLoss, maxWin, minLoss, minWin) {
	let classes = {
		inputError: 'input-validation-error',
		won: 'match-points__cell-input--won'
	}
	if (team1Score > maxWin) {
		jQuery(team1).addClass(classes.inputError);
	} else if (team1Score == minWin && team2Score > minLoss && maxWin != minWin) {
		jQuery(team1).addClass(classes.inputError);
		jQuery(team2).addClass(classes.inputError);
	} else if (team1Score === maxWin) {
		if (team2Score < maxLoss && maxWin != minWin) {
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
	} else if (team1Score > minWin && team2Score > minLoss && team2Score != (team1Score - 2)) {
		jQuery(team1).addClass(classes.inputError);
	} else {
		jQuery(team1).addClass(classes.won);
	}
};
function SetCalculatorTieBreak(inputdata) {
	let classes = {
		inputError: 'input-validation-error'
	}
	let fieldRef = inputdata.id;
	let tieBreak = "#" + fieldRef;
	let tieBreakScore = parseInt(inputdata.value);
	if (isNaN(tieBreakScore)) {
		jQuery(tieBreak).addClass(classes.inputError);
	} else {
		jQuery(tieBreak).removeClass(classes.inputError);
		jQuery(tieBreak).removeClass('is-invalid');
	}
};
