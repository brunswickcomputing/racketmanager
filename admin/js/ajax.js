let Racketmanager = new Object();
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

Racketmanager.saveAddPoints = function(points, team_id, league_id, season) {
	let notifyField = "#feedback_" + team_id;
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).css("display", "none");
	jQuery(notifyField).text('');
	jQuery('#loading_' + team_id).css("display", "inline");
	jQuery('#loading_' + team_id).html("<img src='" + RacketManagerAjaxL10n.pluginUrl + "/images/loading.gif' />");
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"action": "racketmanager_save_add_points",
			"security": ajax_var.ajax_nonce,
			"team_id": team_id,
			"league_id": league_id,
			"season": season,
			"points": points,
		},
		success: function () {
			jQuery('#loading_' + team_id).fadeOut('fast');
			window.location.reload(true);
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
			jQuery('#loading_' + team_id).css("display", "none");
		}
	})
};

Racketmanager.isLoading = function(id) {
  document.getElementById(id).style.display = 'inline';
  document.getElementById(id).innerHTML="<img src='"+RacketManagerAjaxL10n.pluginUrl+"/images/loading.gif' />";
};

Racketmanager.doneLoading = function(id) {
  document.getElementById(id).style.display = 'none';
};

Racketmanager.setMatchDayPopUp = function (match_day, i, max_matches, mode) {
	if (i == 0 && mode == 'add') {
		for (let xx = 1; xx < max_matches; xx++) {
			let formfield = "#match_day_" + xx;
			jQuery(formfield).val(match_day);
		}
	}
};

Racketmanager.setMatchDate = function (match_date, i, max_matches, mode) {
	if (i == 0 && mode == 'add') {
		for (let xx = 1; xx < max_matches; xx++) {
			let formfield = "#mydatepicker\\[" + xx + "\\]";
			jQuery(formfield).val(match_date);
		}
	}
};

Racketmanager.setMatchDays = function (match_date, i, max_rounds, mode) {
	if (mode == 'add') {
		for (let xx = i; xx < max_rounds; xx++) {
			let formfield = "#matchDate-" + xx;
			jQuery(formfield).val(match_date);
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
			if (jQuery(notifyField).val() == '') {
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
Racketmanager.showRubbers = function(link) {
	let matchId = jQuery(link).attr('id');
	let myModal = new bootstrap.Modal(document.getElementById('modalMatch'), {
		keyboard: true, backdrop: true, focus: true
	})
	Racketmanager.adminMatchHeader(matchId);
	let notifyField = "#showMatchRubbers";
	jQuery(notifyField).removeClass("message-error");
	jQuery(notifyField).empty();
	myModal.show()
	jQuery("#viewMatchRubbers").show();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();
	jQuery.ajax({
		url: ajaxurl,
		type: "GET",
		data: {
			"matchId": matchId,
			"action": "racketmanager_show_rubbers"
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
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
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
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
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
			let $matchhome = 0;
			let $matchaway = 0;
			let $formfield = '';
			for (let i in $homepoints) {
				$formfield = "#home_points-" + i;
				let $fieldval = $homepoints[i];
				jQuery($formfield).val($fieldval);
				$matchhome = +$matchhome + +$homepoints[i];
			}
			for (let i in $awaypoints) {
				$formfield = "#away_points-" + i;
				let $fieldval = $awaypoints[i];
				jQuery($formfield).val($fieldval);
				$matchaway = +$matchaway + +$awaypoints[i];
			}
			let $updatedRubbers = $response[3];
			let rubberNo = 1;
			for (let r in $updatedRubbers) {
				let $rubber = $updatedRubbers[r];
				for (let t in $rubber['players']) {
					let $team = $rubber['players'][t];
					for (let p = 0; p < $team.length; p++) {
						let $player = $team[p];
						let id = p + 1;
						let formfield = '#' + t + 'player' + id + '_' + rubberNo;
						let fieldval = $player;
						jQuery(formfield).val(fieldval);
					}
				}
				for (let s in $rubber['sets']) {
					let team = $rubber['sets'][s];
					for (let p in team) {
						let score = team[p];
						let formfield = '#' + 'set_' + rubberNo + '_' + s + '_' + p;
						let fieldval = score;
						jQuery(formfield).val(fieldval);
					}
				}
				rubberNo++;
			}
			$formfield = "#home_points-" + $matchId;
			jQuery($formfield).val($matchhome);
			$formfield = "#away_points-" + $matchId;
			jQuery($formfield).val($matchaway);
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
					let $formfield = "#" + $errField[i];
          			jQuery($formfield).addClass('is-invalid');
				});
    		} else {
				let $homepoints = $response[1];
				let $formfield = "#home_points";
				let $fieldval = $homepoints;
  				jQuery($formfield).val($fieldval);
				let $awaypoints = $response[2];
				$formfield = "#away_points";
				$fieldval = $awaypoints;
  				jQuery($formfield).val($fieldval);
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
Racketmanager.chaseMatchResult = function(matchId) {
	let notifyField = "#notifyMessage-" + matchId;

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_chase_match_result",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = response.data.msg;
			jQuery(notifyField).text(message);
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
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
Racketmanager.chaseMatchApproval = function(matchId) {
	let notifyField = "#notifyMessage-" + matchId;

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_chase_match_approval",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let message = response.data.msg;
			jQuery(notifyField).text(message);
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
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
Racketmanager.getImportOption = function(option) {
	let selectedOption = option;
	if (selectedOption == 'table' || selectedOption == 'fixtures') {
		jQuery("#competitions").show();
		jQuery("#events").show();
		jQuery("#leagues").show();
		jQuery("#seasons").show();
		jQuery("#clubs").hide();
	} else if (selectedOption == 'clubplayers') {
		jQuery("#clubs").show();
		jQuery("#competitions").hide();
		jQuery("#events").hide();
		jQuery("#leagues").hide();
		jQuery("#seasons").hide();
	} else if (selectedOption == 'players') {
		jQuery("#clubs").hide();
		jQuery("#competitions").hide();
		jQuery("#events").hide();
		jQuery("#leagues").hide();
		jQuery("#seasons").hide();
	}
};
Racketmanager.checkAll = function(form) {
	for (let i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
			if (form.elements[i].checked && form.elements[i].checked != "0")
			form.elements[i].checked = false;
			else
			form.elements[i].checked = true;
		}
	}
};
//Racketmanager.checkPointRule = function( forwin, forwin_overtime, fordraw, forloss, forloss_overtime ) {
Racketmanager.checkPointRule = function (rule) {

	// manual rule selected
	if ( rule == 'user' ) {
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
	let player = document.getElementById(id).value;
	document.getElementById(target).value = player;
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
	let notifyField = "#notifyMessage-" + eventId;
	jQuery(notifyField).removeClass();
	jQuery(notifyField).text('');
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
			jQuery(notifyField).text(message);
			jQuery(notifyField).show();
			jQuery(notifyField).addClass('message-success');
			jQuery(notifyField).delay(10000).fadeOut('slow');
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
Racketmanager.setTournamentOpenDate = function (e) {
	let dateOpenField = "#date_open";
	let dateCloseField = "#date_close";
	let dateWithdrawfield = "#date_withdraw";
	let gradeField = '#grade';
	let grade = jQuery(gradeField).val();
	let dateStartVal = jQuery('#date_start').val();
	if (dateStartVal) {
		let dateOpen = Racketmanager.amendDate(dateStartVal, -46);
		jQuery(dateOpenField).val(dateOpen);
		if (grade) {
			let closingAdjust = -7;
			let withdrawAdjust = -5;
			if (grade === '1') {
				closingAdjust = -21;
				withdrawAdjust = -14;
			} else if (grade === '2') {
				closingAdjust = -21;
				withdrawAdjust = -14;
			} else if (grade === '3') {
				closingAdjust = -14;
				withdrawAdjust = -12;
			} else if (grade === '4') {
				closingAdjust = -10;
				withdrawAdjust = -8;
			} else if (grade === '5') {
				closingAdjust = -7;
				withdrawAdjust = -5;
			}
			if (closingAdjust) {
				let dateClose = Racketmanager.amendDate(dateStartVal, closingAdjust);
				jQuery(dateCloseField).val(dateClose);
			}
			if (withdrawAdjust) {
				let dateWithdraw = Racketmanager.amendDate(dateStartVal, withdrawAdjust);
				jQuery(dateWithdrawfield).val(dateWithdraw);
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
	let returnDate = year + "-" + month + "-" + day;
	return returnDate;
};