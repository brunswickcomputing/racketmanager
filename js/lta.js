(function ($) {
	$.SetCalculator = function (currentInput, targetInput, tiebreakInput, options, scoreStatusElement, winnerElement) {
		var defaults = {
			sportId: 0,
			setOptions: {
				maxSetPoints: 6,
				maxSetPointsWithSetting: 7,
				scoreDiffAfterSetting: 2,
				settingAt: 2
			},
			pointsType: 0,
			settype_dataattribute: 'settype',
			setid_dataattribute: 'setid',
			onScoreChanged: function () { },
			delegateElement: null
		};

		var classes = {
			inputError: 'input-validation-error'
		};

		var plugin = this;
		plugin.settings = {};

		var calcTeam2Points = function (team1Points, setType) {
			var team2Points = null;
			if (team1Points <= 999) {
				var maxSetPointsWithSetting = plugin.settings.setOptions.maxSetPointsWithSetting;
				var maxSetPoints = plugin.settings.setOptions.maxSetPoints;

						if (team1Points === maxSetPoints || (team1Points === (maxSetPoints - 1) && setType !== 109)) {
							if (maxSetPointsWithSetting > 0) {
								if (maxSetPointsWithSetting === maxSetPoints) {
									team2Points = maxSetPointsWithSetting - 1;
								} else {
									// 7-6 or 7-5
									team2Points = maxSetPointsWithSetting;
								}
							} else {
								team2Points = team1Points + plugin.settings.setOptions.scoreDiffAfterSetting;
							}
						} else if (team1Points > maxSetPoints + 1 || (plugin.settings.setOptions.scoreDiffAfterSetting > 1 && team1Points > maxSetPoints)) { // (>7)-(T1-2)? or (>7)-(T1+2)
							team2Points = team1Points + plugin.settings.setOptions.scoreDiffAfterSetting;
						} else if (team1Points === maxSetPointsWithSetting === maxSetPoints) {
							team2Points = maxSetPoints - 1;
						} else {
							// 6 - (<5)
							team2Points = maxSetPoints;
						}
			}
			return team2Points;
		}

		var init = function () {
			plugin.settings = $.extend({}, defaults, options);
			// Bind elements
			plugin.currentInput = currentInput;
			plugin.targetInput = targetInput;
			plugin.tiebreakInput = tiebreakInput;
			plugin.scorestatusElement = scoreStatusElement;
			plugin.winnerElement = winnerElement;

			// Bind element event listeners
			plugin.currentInput.on('focus',
				function () {
					$(this).select();
				});
			plugin.targetInput.on('focus',
				function () {
					$(this).select();
				});
			plugin.currentInput.on('keyup',
				function () {
					toggleTiebreak();
				});
			plugin.targetInput.on('keyup',
				function () {
					toggleTiebreak();
				});

			plugin.currentInput.on('blur',
				function () { // calc t1 to t2
					if (plugin.currentInput.length > 0 && plugin.targetInput.val().length === 0) {
						var curVal = parseInt(plugin.currentInput.val(), 10);
						var setType = $(this).data(plugin.settings.settype_dataattribute);
						plugin.targetInput.val(calcTeam2Points(curVal, setType));
						toggleTiebreak();
					}

					triggerScoreChanged(plugin.currentInput, plugin.targetInput, plugin.tiebreakInput);
				});
			plugin.targetInput.on('blur',
				function () { // reverse calc t2 to t1
					if (plugin.targetInput.length > 0 && plugin.currentInput.val().length === 0) {
						var curVal = parseInt(plugin.targetInput.val(), 10);
						var setType = $(this).data(plugin.settings.settype_dataattribute);
						plugin.currentInput.val(calcTeam2Points(curVal, setType));
						toggleTiebreak();
					}

					triggerScoreChanged(plugin.targetInput, plugin.currentInput, plugin.tiebreakInput);
				});

			// Init methods
			toggleTiebreak();
		}

		// Public methods
		plugin.validateSet = function () {
			triggerScoreChanged(plugin.currentInput, plugin.targetInput, plugin.tiebreakInput);
		};

		// Private methods
		function toggleTiebreak() {
			// Enable or disable tiebreak input on initial values
			if (plugin.tiebreakInput && plugin.tiebreakInput.length > 0) {
				var isTieBreakDisabled = true;
				var currentInputValue = parseInt(plugin.currentInput.val(), 10);
				var targetInputValue = parseInt(plugin.targetInput.val(), 10);
				if ($.isNumeric(currentInputValue) && $.isNumeric(targetInputValue)) {
					var maxSetPointsWithSetting = plugin.settings.setOptions.maxSetPointsWithSetting;
					if ((currentInputValue === maxSetPointsWithSetting && targetInputValue === maxSetPointsWithSetting - 1) ||
						(targetInputValue === maxSetPointsWithSetting && currentInputValue === maxSetPointsWithSetting - 1)) {
						isTieBreakDisabled = false;
					}
				}
				if (isTieBreakDisabled) {
					// Clear when disabled
					plugin.tiebreakInput.val('');
				}
				plugin.tiebreakInput.prop('disabled', isTieBreakDisabled);
			}
		}

		var isValidSetScore = function (setTypeID, team1Score, team2Score, setID) {
			if (team1Score < 0 || team2Score < 0 || isNaN(team1Score) || isNaN(team2Score)) {
				return false;
			}

			// Allow All positive scores
			if (setTypeID === 1000 || setTypeID === 999 || setTypeID === 299) {
				return true;
			}

			var scoreStatus = plugin.scorestatusElement.val();

			// Allow all scores for retired / abandoned matches
			if (scoreStatus === '2' || scoreStatus === '7') {
				return true;
			}

			// Disallow any scores for a walkover
			if (scoreStatus === '1') {
				return false;
			}

			var highestScore = Math.max(team1Score, team2Score);
			var lowestScore = Math.min(team1Score, team2Score);
			var maxSetPointsWithSetting = plugin.settings.setOptions.maxSetPointsWithSetting;
			var maxSetPoints = plugin.settings.setOptions.maxSetPoints;

					switch (setTypeID) {
						case 103:
						case 105:
						case 101:
						case 106:
						case 107:
							// Check score
							if (team1Score === maxSetPoints && team2Score < (maxSetPoints - 1)) {
								return true;
							}
							if (team1Score > maxSetPoints && team2Score === (team1Score - 2)) {
								return true;
							}
							// Reverse score
							if (team2Score === maxSetPoints && team1Score < (maxSetPoints - 1)) {
								return true;
							}
							if (team2Score > maxSetPoints && team1Score === (team2Score - 2)) {
								return true;
							}
							break;
						case 102:
							// Check score
							if (team1Score === maxSetPoints && team2Score === (maxSetPoints - 1)) {
								return true;
							}
							// Reverse score
							if (team2Score === maxSetPoints && team1Score === (maxSetPoints - 1)) {
								return true;
							}
							break;
						case 110:
						case 109:
							if (options.setOptions.maxSetPointsWithSetting > 0 && (team1Score > maxSetPointsWithSetting || team2Score > maxSetPointsWithSetting)) {
								return false;
							}
							if (team1Score === maxSetPoints && team2Score <= (maxSetPoints - 1)) {
								return true;
							}
							if (team2Score === maxSetPoints && team1Score <= (maxSetPoints - 1)) {
								return true;
							}
							break;
						case 111:
							if (highestScore === maxSetPointsWithSetting && lowestScore === maxSetPoints) {
								return true; // ShortSet: 5 - 4 or 4 - 5
							}
							if (highestScore === maxSetPointsWithSetting && lowestScore === (maxSetPoints - 1)) {
								return true; // ShortSet: 5 - 3 or 3 - 5
							}
							if (highestScore <= maxSetPoints && lowestScore <= maxSetPoints) {
								return true; // ShortSet: <=4 - <=4
							}
							break;
						case 104:
						case 108:
						default: // Normal set
							// Check score
							if (team1Score === maxSetPointsWithSetting && team2Score === maxSetPoints && maxSetPointsWithSetting !== maxSetPoints) {
								return true;
							}
							if (team1Score === maxSetPointsWithSetting && team2Score === (maxSetPoints - 1)) {
								return true;
							}
							if (team1Score === maxSetPoints && team2Score < (maxSetPoints - 1)) {
								return true;
							}

							// Reverse score
							if (team2Score === maxSetPointsWithSetting && team1Score === maxSetPoints && maxSetPointsWithSetting !== maxSetPoints) {
								return true;
							}
							if (team2Score === maxSetPointsWithSetting && team1Score === (maxSetPoints - 1)) {
								return true;
							}
							if (team2Score === maxSetPoints && team1Score < (maxSetPoints - 1)) {
								return true;
							}
							break;
					}
			return false;
		}
		function triggerScoreChanged(team1Element, team2Element, tieBreakElement) {
			var setType = team1Element.data(plugin.settings.settype_dataattribute);
			var setIndex = parseInt(team1Element.data(plugin.settings.setid_dataattribute), 10);
			var isValidScore = (team1Element.val() === '' && team2Element.val() === '') ||
				(isValidSetScore(parseInt(setType, 10), parseInt(team1Element.val(), 10), parseInt(team2Element.val(), 10), setIndex) && $.isNumeric(team1Element.val()) && $.isNumeric(team2Element.val()));

			// Remove all error classes
			team1Element.removeClass(classes.inputError);
			team2Element.removeClass(classes.inputError);
			tieBreakElement.removeClass(classes.inputError);

			// Add error class when invalid score
			if (!isValidScore) {
				team1Element.addClass(classes.inputError);
				team2Element.addClass(classes.inputError);
			}

			// Trigger score changed
			plugin.settings.onScoreChanged(team1Element, team2Element, tieBreakElement, isValidScore);
		}

		//init
		init();
	}
})(jQuery);

$.ScoreCalculator = function (elem, opt) {
	var defaults = {
		sportId: 0,
		setOptions: {
			maxSetPoints: 6,
			maxSetPointsWithSetting: 7,
			scoreDiffAfterSetting: 2,
			settingAt: 2
		},
		setOptionsLastSet: {
			maxSetPoints: 6,
			maxSetPointsWithSetting: 7,
			scoreDiffAfterSetting: 2,
			settingAt: 2
		},
		pointsType: 0,
		setBoxCollection: [],
		scorestatusElement: null,
		winnerElement: null,
		onScoreChanged: function (team1Element, team2Element, tieBreakElement, isValidScore, winnerID) { }
	};
	var options = $.extend(defaults, opt);
	var plugin = this;

	var setCalcItems = [];

	var init = function () {
		var setCalOptions = {
			sportId: options.sportId,
			setOptions: options.setOptions,
			onScoreChanged: plugin.onScoreChanged
		}
		var setCalOptionsLastSet = {
			sportId: options.sportId,
			setOptions: options.setOptionsLastSet,
			onScoreChanged: plugin.onScoreChanged
		}
		for (var si = 0; si < options.setBoxCollection.length; si++) {
			setCalcItems.push(new $.SetCalculator(
				$(options.setBoxCollection[si].t1s),
				$(options.setBoxCollection[si].t2s),
				$(options.setBoxCollection[si].tb),
				(si === (options.setBoxCollection.length - 1)) ? setCalOptionsLastSet : setCalOptions,
				options.scorestatusElement,
				options.winnerElement)
			);
		}
	}

	// Private functions 
	var calculateWinner = function () {
		var scoreStatus = parseInt(options.scorestatusElement.val(), 10);

		if (scoreStatus === 1 || scoreStatus === 2 || scoreStatus === 3) {
			return parseInt(options.winnerElement.val(), 10);
		}

		if (scoreStatus === 7 || scoreStatus === 20) {
			return 0;
		}

		var winner = 0;
		var team1Points = 0, team2Points = 0, team1Games = 0, team2Games = 0;
		var team1Score, team2Score, index;

		if (options.pointsType === 2) { // PointsType 2 = KnltbIndoor
			for (index = 0; index < options.setBoxCollection.length; index++) {
				team1Score = parseInt(options.setBoxCollection[index].t1s.val(), 10);
				team2Score = parseInt(options.setBoxCollection[index].t2s.val(), 10);
				if (isNaN(team1Score) || isNaN(team2Score)) {
					continue;
				}
				team1Games += team1Score;
				team2Games += team2Score;
				var setIsFinished = (team1Score === 5 && team2Score === 4) ||
					(team1Score === 4 && team2Score === 5) ||
					(team1Score === 5 && team2Score === 3) ||
					(team1Score === 3 && team2Score === 5) ||
					(team1Score === 4 && team2Score < 3) ||
					(team1Score < 3 && team2Score === 4);

				if (setIsFinished) {
					if (team1Score === 5 && team2Score === 4) {
						team1Points += 2;
						team2Points += 1;
					} else if (team1Score === 4 && team2Score === 5) {
						team1Points += 1;
						team2Points += 2;
					} else if (team1Score > team2Score) {
						team1Points += 3;
					} else {
						team2Points += 3;
					}
				} else {
					if (team1Score - team2Score >= 2) {
						team1Points += 2;
					} else if (team1Score - team2Score <= -2) {
						team2Points += 2;
					} else {
						team1Points += 1;
						team2Points += 1;
					}
				}
			}

			if (team1Points === 0 && team2Points === 0) {
				winner = 0;
			} else if (team1Points > team2Points) {
				winner = 1;
			} else if (team1Points < team2Points) {
				winner = 2;
			} else if (team1Points === team2Points) {
				if (team1Games > team2Games) {
					winner = 1;
				} else if (team2Games > team1Games) {
					winner = 2;
				} else {
					winner = 3;
				}
			}

		} else {
			// Default winner calculator
			var ties = 0;
			for (index = 0; index < options.setBoxCollection.length; index++) {
				team1Score = parseInt(options.setBoxCollection[index].t1s.val(), 10);
				team2Score = parseInt(options.setBoxCollection[index].t2s.val(), 10);
				if (isNaN(team1Score) || isNaN(team2Score)) {
					continue;
				}
				if (team1Score > team2Score) {
					team1Points++;
				} else if (team2Score > team1Score) {
					team2Points++;
				} else {
					ties++;
				}
			}

			var minSetsToWin = (options.maxSets / 2);
			if (team1Points === 0 && team2Points === 0 && ties > 0) {
				winner = 3;
			} else if (team1Points < minSetsToWin && team2Points < minSetsToWin) {
				winner = 0;
			} else if (team1Points === 0 && team2Points === 0 && ties === 0) {
				winner = 0;
			} else if (team1Points === team2Points) {
				winner = 3;
			} else if (team1Points > team2Points) {
				winner = 1;
			} else {
				winner = 2;
			}
		}

		var winnerBasedOnScoreStatus = scoreStatusToWinner(scoreStatus, winner);
		if (winnerBasedOnScoreStatus !== winner) {
			return winnerBasedOnScoreStatus;
		}

		return winner;
	}

	// Public functions
	plugin.onScoreChanged = function (team1Element, team2Element, tiebreakElement, isValidScore) {
		var winnerID = calculateWinner();
		options.winnerElement.val(winnerID);
		options.winnerElement.trigger('chosen:updated');
		options.onScoreChanged(team1Element, team2Element, tiebreakElement, isValidScore, winnerID);
	}

	plugin.validateSets = function () {
		$.each(setCalcItems,
			function (index, itm) { // (re) validate all sets
				itm.validateSet();
			});
	};

	init();
};

$.fn.ScoreCalculator = function (options) {
	return this.each(function () {
		if (undefined == $(this).data('ScoreCalculator')) {
			var plugin = new $.ScoreCalculator(this, options);
			$(this).data('ScoreCalculator', plugin);
		}
	});
}

var scoreStatusToWinner = function (scoreStatusID, defaultWinnerID) {
	switch (parseInt(scoreStatusID, 10)) {
		case 101:
		case 103:
		case 111:
			return 2;
		case 102:
		case 104:
		case 110:
			return 1;
		case 105:
			return 3;
		case 0:
		case 106:
		case 107:
		default:
			return defaultWinnerID;
	}
};
function setMatchStatus(row, status) {
    var text = '';
	var title = '';
	var className = '';

	if (status === 3) {
		text = 'T';
	title = 'Tie';
	className = 'has-tie';
    } else if (status === 1) {
		text = 'W';
	title = 'Won';
	className = 'has-won';
    }

	row.removeClass('has-won has-tie').addClass(className).find('.match__status').text(text).prop('title', title);
  }

	var matchEditElement = $('#js-match-edit-form-3');
	var scoreOptions = {
		sportId: 0,
	setOptions: {
		maxSetPoints: 6,
	maxSetPointsWithSetting: 7,
	scoreDiffAfterSetting: 0,
	settingAt: 0
    },
	setOptionsLastSet: {
		maxSetPoints: 10,
	maxSetPointsWithSetting: 0,
	scoreDiffAfterSetting: 2,
	settingAt: 0
    },
	scorestatusElement: matchEditElement.find('#ScoreStatus'),
	winnerElement: matchEditElement.find('#MatchWinner'),
	maxSets: 3,
	onScoreChanged: function(team1Element, team2Element, tiebreakElement, isValidScore, winnerID) {
		$(team1Element).removeClass('points__cell-input--won');
	$(team2Element).removeClass('points__cell-input--won');
	if (tiebreakElement) {
		$(tiebreakElement).removeClass('points__cell-input--won');
      }

		var $matchRows = $('#js-edit-match-555ec343-26bd-4df0-bd4b-d0020cc2fb74').find('.match__row');

	var $team1 = $matchRows.first();
	var $team2 = $matchRows.last();

	if (isValidScore) {
        var scoreTeam1 = parseInt($(team1Element).val());
	var scoreTeam2 = parseInt($(team2Element).val());
	if (!isNaN(scoreTeam1) && !isNaN(scoreTeam2)) {
          if (scoreTeam1 > scoreTeam2) {
		$(team1Element).addClass('points__cell-input--won');
          } else if (scoreTeam2 > scoreTeam1) {
		$(team2Element).addClass('points__cell-input--won');
          }
        }
      }

      if ($matchRows.find('.input-validation-error').length > 0 || !isValidScore) {
		winnerID = 0;
      }

	switch (winnerID) {
        case 1:
	setMatchStatus($team1, 1);
	setMatchStatus($team2, 0);
	break;
	case 2:
	setMatchStatus($team1, 0);
	setMatchStatus($team2, 1);
	break;
	case 3:
	setMatchStatus($team1, 3);
	setMatchStatus($team2, 3);
	break;
	default:
	setMatchStatus($team1, 0);
	setMatchStatus($team2, 0);
	break;
      }
    }
  };

	var setBoxCollection = [];

	setBoxCollection.push({
		t1s: $('#Team1Sets_0_3'),
	t2s: $('#Team2Sets_0_3')
      });


	setBoxCollection.push({
		t1s: $('#Team1Sets_1_3'),
	t2s: $('#Team2Sets_1_3')
      });


	setBoxCollection.push({
		t1s: $('#Team1Sets_2_3'),
	t2s: $('#Team2Sets_2_3')
      });

	scoreOptions.setBoxCollection = setBoxCollection;

$('#js-edit-match-555ec343-26bd-4df0-bd4b-d0020cc2fb74').ScoreCalculator(scoreOptions);
