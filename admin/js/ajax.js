let Racketmanager = {};
Racketmanager.getEventDropdown = function (competition_id) {
	let notifyField = "#events";
	jQuery('#leagues').hide();
	jQuery('#seasons').hide();
	jQuery(notifyField).removeClass('message-error');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"competition_id": competition_id,
			"action": "racketmanager_get_event_dropdown",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
};
Racketmanager.getLeagueDropdown = function (event_id) {
	let notifyField = "#leagues";
	jQuery('#seasons').hide();
	jQuery(notifyField).removeClass('message-error');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"event_id": event_id,
			"action": "racketmanager_get_league_dropdown",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
};
Racketmanager.getSeasonDropdown = function (league_id) {
	let notifyField = "#seasons";
	jQuery(notifyField).removeClass('message-error');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"league_id": league_id,
			"action": "racketmanager_get_season_dropdown",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
};

Racketmanager.getMatchDropdown = function(league_id, season) {
	let notifyField = "#matches";
	jQuery(notifyField).removeClass('message-error');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"league_id": league_id,
			"season": season,
			"action": "racketmanager_get_match_dropdown",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};

Racketmanager.saveAddPoints = function(points, table_id) {
	let notifyField = "#feedback_" + table_id;
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).css("display", "none");
	jQuery(notifyField).text('');
	let loadingTeam = jQuery('#loading_' + table_id);
	loadingTeam.css("display", "inline");
	loadingTeam.html("<img alt=\"loading\" src='" + RacketManagerAjaxL10n.pluginUrl + "/images/loading.gif' />");
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"action": "racketmanager_save_add_points",
			"security": ajax_var.ajax_nonce,
			"table_id": table_id,
			"points": points,
		},
		success: function () {
			jQuery('#loading_' + table_id).fadeOut('fast');
			window.location.reload();
			return true;
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
			jQuery(notifyField).css("display", "inline-block");
		},
		complete: function () {
			jQuery('#loading_' + table_id).css("display", "none");
		}
	})
};

Racketmanager.isLoading = function(id) {
  document.getElementById(id).style.display = 'inline';
  document.getElementById(id).innerHTML="<img alt=\"loading\" src='"+RacketManagerAjaxL10n.pluginUrl+"/images/loading.gif' />";
};

Racketmanager.doneLoading = function(id) {
  document.getElementById(id).style.display = 'none';
};

Racketmanager.setMatchDayPopUp = function (match_day, i, max_matches, mode) {
	if (i === 0 && mode === 'add') {
		for (let xx = 1; xx < max_matches; xx++) {
			let formField = "#match_day_" + xx;
			jQuery(formField).val(match_day);
		}
	}
};

Racketmanager.setMatchDate = function (match_date, i, max_matches, mode) {
	if (i === 0 && mode === 'add') {
		for (let xx = 1; xx < max_matches; xx++) {
			let formField = "#myDatePicker\\[" + xx + "\\]";
			jQuery(formField).val(match_date);
		}
	}
};

Racketmanager.setMatchDays = function (match_date, i, max_rounds, mode) {
	if (mode === 'add') {
		for (let xx = i; xx < max_rounds; xx++) {
			let formField = "#matchDate-" + xx;
			jQuery(formField).val(match_date);
		}
	}
};

Racketmanager.insertHomeStadium = function(team_id, i) {
	let notifyField = "#feedback";
	jQuery(notifyField).removeClass("message-error");
	jQuery(notifyField).empty();
	notifyField = "#location_" + i;

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"team_id": team_id,
			"action": "racketmanager_insert_home_stadium",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let stadium = response.data;
			if (jQuery(notifyField).val() === '') {
				jQuery(notifyField).val(stadium);
			}
		},
		error: function (response) {
			notifyField = "#feedback";
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery("#feedback").show();
				jQuery(notifyField).html(message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.adminMatchHeader = function (matchId) {
	let notifyField = "#matchHeader";
	jQuery.ajax({
		url: ajaxurl,
		type: "GET",
		data: {
			async: false,
			"matchId": matchId,
			"action": "racketmanager_show_match_header"
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField).show();
				jQuery(notifyField).html(message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.updateResults = function(link) {

	let $match = document.getElementById('current_match_id');
	let notifyField = ("#updateResponse");
	let $matchId = $match.value;
	let $form = jQuery('#match-rubbers').serialize();
	$form += "&action=racketmanager_update_rubbers";
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery("#updateResponse").removeClass("message-success message-error");
	jQuery("#showMatchRubbers").hide();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = response.data;
			let $message = $response[0];
			jQuery("#updateResponse").show();
			jQuery("#updateResponse").addClass('message-success');
			jQuery("#updateResponse").html($message);
			jQuery("#updateResponse").delay(10000).fadeOut('slow');
			let $homepoints = $response[1];
			let $awaypoints = $response[2];
			let matchHome = 0;
			let matchAway = 0;
			let $formField = '';
			for (let i in $homepoints) {
				$formField = "#home_points-" + i;
				let fieldVal = $homepoints[i];
				jQuery($formField).val(fieldVal);
				matchHome = +matchHome + +$homepoints[i];
			}
			for (let i in $awaypoints) {
				$formField = "#away_points-" + i;
				let fieldVal = $awaypoints[i];
				jQuery($formField).val(fieldVal);
				matchAway = +matchAway + +$awaypoints[i];
			}
			let $updatedRubbers = $response[3];
			let rubberNo = 1;
			for (let r in $updatedRubbers) {
				let rubber = $updatedRubbers[r];
				for (let t in rubber['players']) {
					let team = rubber['players'][t];
					for (let p = 0; p < team.length; p++) {
						let player = team[p];
						let id = p + 1;
						let formField = '#' + t + 'player' + id + '_' + rubberNo;
						jQuery(formField).val(player);
					}
				}
				for (let s in rubber['sets']) {
					let team = rubber['sets'][s];
					for (let p in team) {
						let score = team[p];
						let formField = '#' + 'set_' + rubberNo + '_' + s + '_' + p;
						jQuery(formField).val(score);
					}
				}
				rubberNo++;
			}
			$formField = "#home_points-" + $matchId;
			jQuery($formField).val(matchHome);
			$formField = "#away_points-" + $matchId;
			jQuery($formField).val(matchAway);
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				for (let errorMsg of data[1]) {
					$message += '<br />' + errorMsg;
				}
				let $errorFields = data[2];
				for (let $errorField of $errorFields) {
					let $id = '#'.concat($errorField);
					jQuery($id).addClass("is-invalid");
				}
				jQuery(notifyField).show();
				jQuery(notifyField).html($message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
  }) ;
};
Racketmanager.updateMatchResults = function(link) {

	let $form = jQuery('#match-view').serialize();
	$form += "&action=racketmanager_update_match";
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: $form,
		success: function(response) {
			let $response = response.data;
			let $message = $response[0];
			jQuery("#updateResponse").show();
			jQuery("#updateResponse").html($message);
			let $error = $response[3];
      		if ($error === true) {
				jQuery("#updateResponse").addClass('message-error');
				let $errFields = $response[4];
				$errFields.array.forEach($errField => {
					let $formField = "#" + $errField[i];
          			jQuery($formField).addClass('is-invalid');
				});
    		} else {
				let $homepoints = $response[1];
				let $formField = "#home_points";
				let fieldVal = $homepoints;
  				jQuery($formField).val(fieldVal);
				let $awaypoints = $response[2];
				$formField = "#away_points";
				fieldVal = $awaypoints;
  				jQuery($formField).val(fieldVal);
    		}
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
		},
		error: function() {
			alert("Ajax error on updating match");
		},
		complete: function () {
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
	});
};
Racketmanager.confirmResults = function() {

	let $form = jQuery('#match-results').serialize();
  $form += "&action=racketmanager_confirm_results";
  jQuery("#updates").css('opacity', 1);
  jQuery("#updateResults").hide();

  jQuery.ajax({
	  url: ajaxurl,
    type: "POST",
    data: $form,
    success: function(response) {
		let $response = jQuery.parseJSON(response);
      jQuery("#MatchUpdateResponse").text($response);
      jQuery("#message").addClass("updated");
      jQuery("#updateResults").show();
    },
    error: function() {
      alert("Ajax error on updating results");
    }
  }) ;
  return false;
};
Racketmanager.notifyTeams = function(matchId) {

	let notifyField = "#notifyMessage-" + matchId;
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass();
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_notify_teams",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).text(response.data.message);
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.emailConstitution = function(e, eventId) {
	e.preventDefault();
	let notifyField = "#notifyMessage-constitution";
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass();
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"eventId": eventId,
			"action": "racketmanager_email_constitution",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).text(response.data);
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				jQuery(notifyField).text(response.responseJSON.data);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-error');
		}
	});
};
Racketmanager.notify_open = function(e, competitionId, season) {
	e.preventDefault();
	let notifyField1 = "#alert-season";
	jQuery(notifyField1).hide();
	jQuery(notifyField1).removeClass('alert--success alert--danger');
	let notifyField2 = "#alert-season-response";
	jQuery(notifyField2).html('');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"competitionId": competitionId,
			"season": season,
			"action": "racketmanager_notify_competition_entries_open",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = response.data;
			jQuery(notifyField2).text(message);
			jQuery(notifyField1).addClass('alert--success');
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField2).html(message);
			} else {
				jQuery(notifyField2).text(response.statusText);
			}
			jQuery(notifyField1).addClass('alert--danger');
		},
		complete: function() {
			jQuery(notifyField1).show();
		}
	});
};
Racketmanager.notifyTournamentEntryOpen = function(e, tournamentId) {
	e.preventDefault();
	let notifyField1 = "#alert-tournaments";
	jQuery(notifyField1).hide();
	jQuery(notifyField1).removeClass('alert--success alert--danger');
	let notifyField2 = "#alert-tournaments-response";
	jQuery(notifyField2).html('');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"tournamentId": tournamentId,
			"action": "racketmanager_notify_tournament_entries_open",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = response.data;
			jQuery(notifyField2).text(message);
			jQuery(notifyField1).addClass('alert--success');
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField2).html(message);
			} else {
				jQuery(notifyField2).text(response.statusText);
			}
			jQuery(notifyField1).addClass('alert--danger');
		},
		complete: function() {
			jQuery(notifyField1).show();
		}
	});
};
Racketmanager.getImportOption = function(option) {
	let selectedOption = option;
	if (selectedOption === 'table' || selectedOption === 'fixtures') {
		jQuery("#competitions").show();
		jQuery("#events").show();
		jQuery("#leagues").show();
		jQuery("#seasons").show();
		jQuery("#clubs").hide();
	} else if (selectedOption === 'clubplayers') {
		jQuery("#clubs").show();
		jQuery("#competitions").hide();
		jQuery("#events").hide();
		jQuery("#leagues").hide();
		jQuery("#seasons").hide();
	} else if (selectedOption === 'players') {
		jQuery("#clubs").hide();
		jQuery("#competitions").hide();
		jQuery("#events").hide();
		jQuery("#leagues").hide();
		jQuery("#seasons").hide();
	}
};
Racketmanager.checkAll = function(form) {
	for (let i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type === "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
			form.elements[i].checked = !(form.elements[i].checked && form.elements[i].checked !== 0);
		}
	}
};
//Racketmanager.checkPointRule = function( forwin, forwin_overtime, fordraw, forloss, forloss_overtime ) {
Racketmanager.checkPointRule = function (rule) {

	// manual rule selected
	if ( rule === 'user' ) {
		let new_element_contents = "";
		new_element_contents += "<input type='text' name='forwin' id='forwin' value=" + forwin + " size='2' />";
		new_element_contents += "<input type='text' name='forwin_overtime' id='forwin_overtime' value=" + forwin_overtime + " size='2' />";
		new_element_contents += "<input type='text' name='fordraw' id='fordraw' value=" + fordraw + " size='2' />";
		new_element_contents += "<input type='text' name='forloss' id='forloss' value=" + forloss + " size='2' />";
		new_element_contents += "<input type='text' name='forloss_overtime' id='forloss_overtime' value=" + forloss_overtime + " size='2' />";
		new_element_contents += "&#160;<span class='setting-description'>" + RacketManagerAjaxL10n.manualPointRuleDescription + "</span>";
		let new_element_id = "point_rule_manual_content";
		let new_element = document.createElement('div');
		new_element.id = new_element_id;

		document.getElementById("point_rule_manual").appendChild(new_element);
		document.getElementById(new_element_id).innerHTML = new_element_contents;
	} else {
		let element_count = document.getElementById("point_rule_manual").childNodes.length;
		if(element_count > 0) {
			let target_element = document.getElementById("point_rule_manual_content");
			document.getElementById("point_rule_manual").removeChild(target_element);
		}

	}

	return false;
}

Racketmanager.insertPlayer = function(id, target) {
	tb_remove();
	document.getElementById(target).value = document.getElementById(id).value;
}

Racketmanager.removeField = function(id, parent_id) {
	let element_count = document.getElementById(parent_id).childNodes.length;
	if(element_count > 1) {
		let target_element = document.getElementById(id);
		document.getElementById(parent_id).removeChild(target_element);
	}
	return false;
}

Racketmanager.reInit = function() {
	tb_init('a.thickbox, area.thickbox, input.thickbox');
}
Racketmanager.sendFixtures = function(eventId) {
	let notifyField1 = "#alert-season";
	jQuery(notifyField1).hide();
	jQuery(notifyField1).removeClass('alert--success alert--danger');
	let notifyField2 = "#alert-season-response";
	jQuery(notifyField2).html('');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"security": ajax_var.ajax_nonce,
			"eventId": eventId,
			"action": "racketmanager_send_fixtures"
		},
		success: function (response) {
			let message = response.data.msg;
			jQuery(notifyField2).text(message);
			jQuery(notifyField1).addClass('alert--success');
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField2).html(message);
			} else {
				jQuery(notifyField2).text(response.statusText);
			}
			jQuery(notifyField1).addClass('alert--danger');
		},
		complete: function() {
			jQuery(notifyField1).show();
		}
	});
};
Racketmanager.resetMatchScores = function (e, formId) {
	e.preventDefault();
	formId = '#'.concat(formId);
	jQuery(':input', formId)
		.not(':button, :submit, :reset, :hidden, :radio')
		.val('')
	jQuery(':input', formId)
		.not(':button, :submit, :reset, :hidden')
		.prop('checked', false)
		.prop('selected', false);
};
Racketmanager.setTournamentOpenDate = function () {
	let date_openField = "#dateOpen";
	let dateStartField = "#dateStart";
	let dateCloseField = "#dateClose";
	let dateWithdrawField = "#dateWithdraw";
	let gradeField = '#grade';
	let grade = jQuery(gradeField).val();
	let date_start = jQuery(dateStartField).val();
	let notifyField1 = "#alert-dates";
	jQuery(notifyField1).hide();
	jQuery(notifyField1).removeClass('alert--success alert--danger');
	let notifyField2 = "#alert-dates-response";
	jQuery(notifyField2).html('');
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"dateStart": date_start,
			"grade": grade,
			"action": "racketmanager_set_tournament_dates",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = response.data.msg;
			jQuery(notifyField2).text(message);
			let date_open = response.data.date_open;
			jQuery(date_openField).val(date_open);
			let dateClose = response.data.date_closing;
			jQuery(dateCloseField).val(dateClose);
			let dateWithdraw = response.data.date_withdraw;
			jQuery(dateWithdrawField).val(dateWithdraw);
			jQuery(notifyField1).addClass('alert--success');
			jQuery(notifyField1).delay(10000).fadeOut('slow');
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField2).html(message);
			} else {
				jQuery(notifyField2).text(response.statusText);
			}
			jQuery(notifyField1).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField1).show();
		}
	});
};
Racketmanager.setNumMatchDays = function () {
	let homeAwayTrueField = "#homeAwayTrue";
	let maxTeamsField = "#max_teams";
	let numMatchDaysField = '#num_match_days';
	let maxTeams = jQuery(maxTeamsField).val();
	let homeAway = jQuery(homeAwayTrueField).is(':checked');
	let numMatchDays = 0;
	if (maxTeams) {
		if (maxTeams % 2 !== 0) {
			maxTeams++;
		}
		numMatchDays = maxTeams - 1;
		if (homeAway) {
			numMatchDays = numMatchDays * 2;
		}
		jQuery(numMatchDaysField).val(numMatchDays);
	}
};
Racketmanager.setEndDate = function () {
	let competitionTypeField = "#competitionType";
	let competitionType = jQuery(competitionTypeField).val();
	if (competitionType === 'league') {
		let dateStartField = "#dateStart";
		let dateStart = jQuery(dateStartField).val();
		let dateEndField = "#dateEnd";
		let roundLengthField = "#round_length";
		let fillerWeeksField = "#filler_weeks";
		let homeAwayDiffField = "#home_away_diff";
		let numMatchDaysField = '#num_match_days';
		let roundLength = jQuery(roundLengthField).val();
		let roundLengthWeeks = roundLength / 7;
		let fillerWeeks = jQuery(fillerWeeksField).val();
		if (fillerWeeks === '') {
			fillerWeeks = 0;
		}
		let homeAwayDiff = jQuery(homeAwayDiffField).val();
		if (homeAwayDiff === '') {
			homeAwayDiff = 0;
		}
		let numMatchDays = jQuery(numMatchDaysField).val();
		if (dateStart) {
			if (roundLengthWeeks && numMatchDays) {
				let endDateGapWeeks = ((+roundLengthWeeks * +numMatchDays) + +fillerWeeks + +homeAwayDiff);
				let endDateGap = (endDateGapWeeks * 7) - 1;
				let endDate = Racketmanager.amendDate(dateStart, endDateGap);
				jQuery(dateEndField).val(endDate);
			}
		}
	}
};
Racketmanager.amendDate = function (date, adjustment) {
	let newDate = new Date(date);
	newDate.setDate(newDate.getDate() + adjustment);
	let year = newDate.toLocaleString("default", { year: "numeric" });
	let month = newDate.toLocaleString("default", { month: "2-digit" });
	let day = newDate.toLocaleString("default", { day: "2-digit" });
	return year + "-" + month + "-" + day;
};
Racketmanager.setEventName = function () {
	let eventNameField = "#event_name";
	let eventName = jQuery(eventNameField).val();
	if (eventName) {
		return;
	}
	let typeField = "#type";
	let type = jQuery(typeField).val();
	if (type) {
		let typeTextField = typeField + " option:selected";
		let typeText = jQuery(typeTextField).text();
		if (typeText) {
			let ageLimitField = "#age_limit";
			let ageLimit = jQuery(ageLimitField).val();
			if (ageLimit) {
				if (ageLimit === 'open') {
					eventName = '';
				} else if ( ageLimit <= 21) {
					eventName = 'U' + ageLimit + ' ';
				} else if ( ageLimit >= 30) {
					eventName = 'O' + ageLimit + ' ';
				}
				eventName = eventName + typeText;
				jQuery(eventNameField).val(eventName);
			}
		}
	}
};
