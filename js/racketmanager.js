jQuery(document).ready(function ($) {
	jQuery('[data-bs-toggle="tooltip"]').tooltip();
	jQuery("#acceptance").prop("checked", false)
	jQuery("#entrySubmit").hide();
	jQuery('#acceptance').change(function (e) {
		if (this.checked) {
			jQuery("#entrySubmit").show();
		} else {
			jQuery("#entrySubmit").hide();
		}
	});

	jQuery("tr.match-rubber-row").slideToggle('fast', 'linear');
	jQuery("i", "td.angle-dir", "tr.match-row").toggleClass("angle-right angle-down");

	jQuery("tr.match-row").click(function (e) {
		jQuery(this).next("tr.match-rubber-row").slideToggle('0', 'linear');
		jQuery(this).find("i.angledir").toggleClass("angle-right angle-down");
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_archive').on('change', function () {
		let league = jQuery('#league_id').val(); //
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/league/' + league.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_competititon_archive #season').on('change', function () {
		let pagename = jQuery('#pagename').val();
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + pagename.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').on('change', function () {
		let league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		league = league.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery('#season').val();
		let matchday = jQuery('#match_day').val();
		if (matchday == -1) matchday = 0;

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/league/' + league.toLowerCase() + '/' + season + '/day' + matchday + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_winners #selection').on('change', function () {
		let selection = jQuery(`#selection`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		selection = selection.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		selection = selection.replace("-", "_"); // Replace '-' with a '-' symbol */
		selection = selection.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let competitionSeason = jQuery(`#competitionSeason`).val();
		let competitionType = jQuery(`#competitionType`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + competitionType + 's/' + competitionSeason + '/winners/' + selection.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_orderofplay #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace '-' with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery(`#season`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournaments/' + season + '/order-of-play/' + tournament.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_tournament #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '_' symbol */
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournament/' + tournament.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_daily_matches #match_date').on('change', function () {
		let matchDate = jQuery(`#match_date`).val();
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/leagues/daily-matches/' + encodeURIComponent(matchDate) + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#match_date').on('change', function () {
		let match_date = jQuery(`#match_date`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		let tournament = jQuery(`#tournament_id`).val();
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '_' symbol */
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournament/' + tournament.toLowerCase() + '/matches/' + match_date + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#tournament-match-date-form').submit(function () {
	});
	jQuery('.teamcaptain').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#club").val();
			let fieldref = this.element[0].id;
			let ref = fieldref.substr(7);
			let notifyField = '#updateTeamResponse'.concat(ref);
			response(get_player_details(request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let player = "#".concat(captaininput);
			let playerId = "#captainId".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let player = "#".concat(captaininput);
			let playerId = "#captainid".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(contactno).val(ui.item.contactno);
				jQuery(contactemail).val(ui.item.user_email);
			}
		}
	});

	jQuery('#matchSecretaryName').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#club_id").val();
			let notifyField = '#match-secretary-feedback';
			response(get_player_details(request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let player = "#matchSecretaryName";
			let playerId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let player = "#matchSecretaryName";
			let playerId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(contactno).val(ui.item.contactno);
				jQuery(contactemail).val(ui.item.user_email);
			}
		}
	});

	jQuery('.passwordShow').hover(function () {
		let input = jQuery(this).parent().find('.password');
		input.attr('type', 'text');
	}, function () {
		jQuery('.password').attr('type', 'password');
		let input = jQuery(this).parent().find('.password');
		input.attr('type', 'password');
	});

	jQuery(".noModal:checkbox").click(function (event) {
		let $target = event.target;

		// If a checkbox with aria-controls, handle click
		let isCheckbox = $target.getAttribute('type') === 'checkbox';
		let hasAriaControls = $target.getAttribute('aria-controls');
		if (isCheckbox && hasAriaControls) {
			let $target2 = $target.parentNode.parentNode.querySelector('#' + $target.getAttribute('aria-controls'));
			if ($target2?.classList.contains('form-checkboxes__conditional')) {
				let inputIsChecked = $target.checked;
				$target2.setAttribute('aria-expanded', inputIsChecked);
				$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			}
		}
	});

	jQuery('select.cupteam').on('change', function (e) {
		let team = this.value;
		let event = this.name;
		event = event.substring(5, event.length - 1);
		let notifyField = "#notify-" + event;
		let responseField = "#team-dtls-" + event;
		let splash = '#splash-' + event;
		jQuery(splash).removeClass("d-none");
		jQuery(splash).css('opacity', 1);
		jQuery(splash).show();
		jQuery(responseField).hide();
		jQuery(notifyField).hide();

		jQuery.ajax({
			type: 'POST',
			datatype: 'json',
			url: ajax_var.url,
			data: {
				"team": team,
				"event": event,
				"action": "racketmanager_get_team_info",
				"security": ajax_var.ajax_nonce,
			},
			success: function (response) {
				let team_info = response.data;
				let captaininput = "captain-".concat(event);
				let ref = captaininput.substring(7);
				let captain = "#".concat(captaininput);
				let captainId = "#captainId".concat(ref);
				let contactno = "#contactno".concat(ref);
				let contactemail = "#contactemail".concat(ref);
				let matchday = "#matchday".concat(ref);
				let matchtime = "#matchtime".concat(ref);
				jQuery(captain).val(team_info.captain);
				jQuery(captainId).val(team_info.captainid);
				jQuery(contactno).val(team_info.contactno);
				jQuery(contactemail).val(team_info.user_email);
				jQuery(matchday).val(team_info.match_day);
				jQuery(matchtime).val(team_info.match_time);
			},
			error: function (response) {
				if (response.responseJSON) {
					jQuery(notifyField).text(response.responseJSON.data);
				} else {
					jQuery(notifyField).text(response.statusText);
				}
				jQuery(notifyField).show();
			},
			complete: function () {
				jQuery(splash).css('opacity', 0);
				jQuery(splash).hide();
				jQuery(responseField).show();
			}
		});
	});
	FavouriteInit();
});
jQuery(document).ajaxComplete(function () {
	FavouriteInit();
});
function FavouriteInit() {
	jQuery('[data-js=add-favourite]').click(function (e) {
		e.preventDefault();
		let favouriteid = jQuery(this).data('favourite');
		let favouritetype = jQuery(this).data('type');
		let favouriteStatus = jQuery(this).data('status');
		let favourite_field = "#".concat(e.currentTarget.id);
		let notifyField = "#fav-msg-".concat(favouriteid);
		if (favouriteStatus === 1) {
			jQuery(favourite_field).attr("data-status", 0);
			jQuery(favourite_field).attr("data-bs-original-title", "Add favourite");
			jQuery(favourite_field).removeClass('is-favourite');
			jQuery(favourite_field).find('i').removeClass('fav-icon-svg-selected');
		} else {
			jQuery(favourite_field).attr("data-status", 1);
			jQuery(favourite_field).attr("data-bs-original-title", "Remove favourite");
			jQuery(favourite_field).addClass('is-favourite');
			jQuery(favourite_field).find('i').addClass('fav-icon-svg-selected');
		}

		jQuery.ajax({
			url: ajax_var.url,
			type: "POST",
			data: {
				"type": favouritetype,
				"id": favouriteid,
				"action": "racketmanager_add_favourite",
				"security": ajax_var.ajax_nonce,
			},
			success: function () {
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
	});
}
let Racketmanager = new Object();

Racketmanager.printScoreCard = function (e, link) {
	e.preventDefault();
	let $matchCardWindow;
	let matchId = jQuery(link).attr('id');
	let notifyField = '#feedback-' + matchId;
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass('message-success message-error');
	let styleSheetList = document.styleSheets;
	let head = '<html><head><title>Match Card</title>';
	for (let item of styleSheetList) {
		if (item.url != 'null') head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
	};
	head += '</head>';
	let foot = '</body></html>';

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_matchcard",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			if (!$matchCardWindow || $matchCardWindow.closed) {
				let $matchCardWindow = window.open("about:blank", "_blank", "width=800,height=775");
				if (!$matchCardWindow) {
					jQuery(notifyField).text("Match Card not available - turn off pop blocker and retry");
					jQuery(notifyField).show();
					jQuery(notifyField).addClass('message-error');
				} else {
					$matchCardWindow.document.write(head + response.data + foot);
				}
			} else {
				// window still exists from last time and has not been closed.
				$matchCardWindow.document.body.innerHTML = response.data;
				$matchCardWindow.focus()
			}
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
Racketmanager.updateMatchResults = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_update_match";
	let notifyField = '#updateResponse';
	let alert_response = '#alertResponse';
	let splash = '#splash';
	let alert_id = jQuery('#matchAlert');
	let use_alert = false;
	if (alert_id.length == 0) {
		use_alert = false;
	} else {
		use_alert = true;
	}
	if (use_alert) {
		jQuery(alert_id).hide();
		jQuery(alert_id).removeClass('alert--success alert--warning alert--danger');
	} else {
		notifyField = '#updateResponse';
		jQuery(notifyField).removeClass("message-success");
		jQuery(notifyField).removeClass("message-error");
		jQuery(notifyField).val("");
		jQuery(notifyField).hide();
	}
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".winner").val("");
	jQuery(".winner").removeClass("winner");
	jQuery(notifyField).removeClass("message-success");
	jQuery(notifyField).removeClass("message-error");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery(splash).removeClass("d-none");
	jQuery(splash).css('opacity', 1);
	jQuery(splash).show();
	jQuery(".match__body").hide();
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = response.data;
			let $message = $response[0];
			if (use_alert) {
				jQuery(alert_id).show();
				jQuery(alert_id).addClass('alert--success');
				jQuery(alert_response).html($message);
			} else {
				jQuery("#updateResponse").show();
				jQuery("#updateResponse").addClass('message-success');
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").delay(10000).fadeOut('slow');
			}
			let homepoints = $response[1];
			let formfield = "#home_points";
			let fieldval = homepoints;
			jQuery(formfield).val(fieldval);
			let awaypoints = $response[2];
			formfield = "#away_points";
			fieldval = awaypoints;
			jQuery(formfield).val(fieldval);
			let winner = $response[3];
			formfield = '#match-status-' + winner;
			jQuery(formfield).addClass('winner');
			jQuery(formfield).val('W');
			let sets = Object.entries($response[4]);
			for (let set of sets) {
				let setno = set[0];
				let teams = Object.entries(set[1]);
				for (let team of teams) {
					formfield = '#set_' + setno + '_' + team[0];
					fieldval = team[1];
					jQuery(formfield).val(fieldval);
				}
			}
		},
		error: function (response) {
			let feedback = '';
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
				feedback = $message;
			} else {
				feedback = response.statusText;
			}
			if (use_alert) {
				jQuery(alert_id).show();
				jQuery(alert_id).addClass('alert--danger');
				jQuery(alert_response).html(feedback);
			} else {
				jQuery(notifyField).html(feedback);
				jQuery(notifyField).show();
				jQuery(notifyField).addClass('message-error');
			}
		},
		complete: function () {
			jQuery(splash).css('opacity', 0);
			jQuery(splash).hide();
			jQuery(".match__body").show();
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
	});
};
Racketmanager.updateResults = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_update_rubbers";
	let match_id = jQuery('#current_match_id').val();
	let match_status_link_id = jQuery('#matchStatusButton');
	let match_edit = false;
	if (match_status_link_id.length === 0) {
		match_edit = false;
	} else {
		match_edit = true;
	}
	let alert_id = jQuery('#matchAlert');
	let use_alert = false;
	let notifyField = '';
	let alert_response = '';
	if (alert_id.length == 0) {
		use_alert = false;
	} else {
		use_alert = true;
	}
	if (use_alert) {
		jQuery(alert_id).hide();
		jQuery(alert_id).removeClass('alert--success alert--warning alert--danger');
		alert_response = '#alertResponse';
	} else {
		notifyField = '#updateResponse';
		jQuery(notifyField).removeClass("message-success message-error message-warning");
		jQuery(notifyField).val("");
		jQuery(notifyField).hide();
	}
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = response.data;
			let $message = $response[0];
			if (use_alert) {
				jQuery(alert_id).show();
				let alertClass = 'alert--' + $response[4];
				jQuery(alert_id).addClass(alertClass);
				jQuery(alert_response).html($message);
			} else {
				let alertClass = 'message-' + $response[4];
				jQuery("#updateResponse").show();
				jQuery("#updateResponse").addClass(alertClass);
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").delay(10000).fadeOut('slow');
			}
			let $homepoints = $response[1];
			let $matchhome = 0;
			let $matchaway = 0;
			for (let i in $homepoints) {
				let $formfield = "#home_points-" + i;
				let $fieldval = $homepoints[i];
				jQuery($formfield).val($fieldval);
				$matchhome = +$matchhome + +$homepoints[i];
			}
			let $awaypoints = $response[2];
			for (let j in $awaypoints) {
				let $awayformfield = "#away_points-" + j;
				let $awayfieldval = $awaypoints[j];
				jQuery($awayformfield).val($awayfieldval);
				$matchaway = +$matchaway + +$awaypoints[j];
			}
			let $updatedRubbers = $response[3];
			let rubberNo = 1;
			for (let r in $updatedRubbers) {
				let $rubber = $updatedRubbers[r];
				let winner = $rubber['winner'];
				let formfield = '#match-status-' + rubberNo + '-' + winner;
				jQuery(formfield).addClass('winner');
				jQuery(formfield).val('W');
				for (let t in $rubber['players']) { // home or away
					let $team = $rubber['players'][t];
					for (let p = 0; p < $team.length; p++) {
						let $player = $team[p];
						let id = p + 1;
						let formfield = '#' + t + 'player' + id + '_' + rubberNo;
						let fieldval = $player;
						jQuery(formfield).val(fieldval);
						formfield = '#' + 'players_' + rubberNo + '_' + t + '_' + id;
						fieldval = $player;
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
			Racketmanager.matchHeader(match_id, match_edit);
		},
		error: function (response) {
			let feedback = '';
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
				feedback = $message;
			} else {
				feedback = response.statusText;
			}
			if (use_alert) {
				jQuery(alert_id).show();
				jQuery(alert_id).addClass('alert--danger');
				jQuery(alert_response).html(feedback);
			} else {
				jQuery(notifyField).html(feedback);
				jQuery(notifyField).show();
				jQuery(notifyField).addClass('message-error');
			}
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
			jQuery("#updateRubberResults").removeProp("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		}
	});
};
Racketmanager.club_player_request = function (link) {

	let $form = jQuery('#playerRequestFrm').serialize();
	$form += "&action=racketmanager_club_player_request";
	jQuery("#clubPlayerUpdateSubmit").hide();
	jQuery("#clubPlayerUpdateSubmit").addClass("disabled");
	let notifyField = '#playerAddResponse';
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#playerAddResponseText';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalid-feedback").val("");

	jQuery.ajax({
		url: ajax_var.url,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery("#firstname").val("");
			jQuery("#surname").val("");
			jQuery("#genderMale").prop('checked', false);
			jQuery("#genderFemale").prop('checked', false);
			jQuery("#btm").val("");
			jQuery("#year_of_birth").val("");
			jQuery("#email").val("");
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				let $errorField = data[2];
				let $errorMsg = data[3];
				for (let $i = 0; $i < $errorField.length; $i++) {
					let $id = '#'.concat($errorField[$i]);
					jQuery($id).addClass("is-invalid");
					let $id2 = '#'.concat($errorField[$i], 'Feedback');
					jQuery($id2).html($errorMsg[$i]);
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery("#clubPlayerUpdateSubmit").removeClass("disabled");
			jQuery("#clubPlayerUpdateSubmit").show();
			jQuery(notifyField).show();
		}
	});
};
Racketmanager.clubPlayerRemove = function (link, gender) {
	let $form = jQuery(link).serialize();
	$form += "&action=racketmanager_club_players_remove";
	let notifyField = '#playerDel' + gender + 'Response';
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#playerDel' + gender + 'ResponseText';
	jQuery(alertTextField).html("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery(link).find('tr').each(function () {
				let row = jQuery(this);
				if (row.find('input[type="checkbox"]').is(':checked')) {
					let rowId = "#" + row.attr('id');
					jQuery(rowId).remove();
				}
			});
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				for (let errorMsg of response.responseJSON.data[1]) {
					$message += '<br />' + errorMsg;
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
};
Racketmanager.updateTeam = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let event = link.form[3].value;
	let team = link.form[2].value;
	let submitButton = "#teamUpdateSubmit-".concat(event, "-", team);
	$form += "&action=racketmanager_update_team";
	jQuery(submitButton).hide();
	let notifyField = "#teamUpdateResponse-".concat(event, "-", team);
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#teamUpdateResponseText-'.concat(event, "-", team);
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalid-feedback").val("");
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		async: false,
		data: $form,
		success: function (response) {
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					jQuery(notifyField).addClass('message-error');
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + '-feedback';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.updateClub = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let submitButton = "#updateClubSubmit";
	$form += "&action=racketmanager_update_club";
	jQuery(submitButton).hide();
	let notifyField = "#clubUpdateResponse";
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#clubUpdateResponseText';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalid-feedback").val("");
	jQuery(".invalid-tooltip").val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + '-tooltip';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.updatePlayer = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_update_player";
	let submitButton = "#updatePlayerSubmit";
	jQuery(submitButton).hide();
	let notifyField = '#playerUpdateResponse';
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#playerUpdateResponseText';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalid-feedback").val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let $message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let $errorMsg = response.responseJSON.data[1];
					let $errorField = response.responseJSON.data[2];
					for (let $i = 0; $i < $errorField.length; $i++) {
						let $formfield = "#" + $errorField[$i];
						jQuery($formfield).addClass('is-invalid');
						$formfield = $formfield + 'Feedback';
						jQuery($formfield).html($errorMsg[$i]);
					}
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.entryRequest = function (event, type) {
	event.preventDefault();
	let notifyField = '#entryAlert';
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).hide();
	let alertTextField = '#entryAlertResponse';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalid-feedback").val("");
	jQuery(".invalid-tooltip").val("");
	jQuery("#entrySubmit").hide();
	let $form = jQuery('#form-entry').serialize();
	let action = "&action=racketmanager_" + type + "_entry";
	$form += action;

	jQuery.ajax({
		url: ajax_var.url,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			jQuery(notifyField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data[0];
				let errorMsg = response.responseJSON.data[1];
				let errorField = response.responseJSON.data[2];
				for (let i = 0; i < errorField.length; i++) {
					let $formfield = '#' + errorField[i];
					jQuery($formfield).addClass('is-invalid');
					$formfield = $formfield + '-feedback';
					jQuery($formfield).html(errorMsg[i]);
				}
				jQuery(alertTextField).html(message);
			} else {
				jQuery(alertTextField).text(response.statusText);
			}
			jQuery(notifyField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(notifyField).show();
			jQuery("#acceptance").prop("checked", false);
		}
	});
};
Racketmanager.resetMatchScores = function (e, formRef) {
	e.preventDefault();
	let formId = '#'.concat(formRef);
	jQuery(formId).find(':input').each(function () {
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
		}
	});
	let selector = formId + ' .match__message';
	jQuery(selector)
		.removeClass('match-warning')
		.addClass('d-none')
		.html('');
	selector = formId + ' .winner';
	jQuery(selector)
		.removeClass('winner');
	selector = formId + ' .loser';
	jQuery(selector)
		.removeClass('loser');
	selector = formId + ' .tie';
	jQuery(selector)
		.removeClass('tie');
};
Racketmanager.matchMode = function (e, match_id, mode) {
	e.preventDefault();
	let notifyField = "#showMatchRubbers";
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery(".match-print").hide();
	jQuery(".match-mode").hide();
	jQuery(".match-mode").removeClass("d-none");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"mode": mode,
			"action": "racketmanager_match_mode",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
			Racketmanager.matchHeader(match_id);
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
			if ('view' === mode) {
				jQuery(".match-print").show();
			}
			jQuery(".match-mode").show();
			let hidefield = '#' + mode + 'MatchMode';
			jQuery(hidefield).hide();
			jQuery(notifyField).show();
		}
	});

};
Racketmanager.matchHeader = function (match_id, edit_mode = false) {
	let notifyField = "#match-header";
	jQuery(notifyField).val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"edit_mode": edit_mode,
			"action": "racketmanager_update_match_header",
			"security": ajax_var.ajax_nonce,
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
			jQuery(notifyField).show();
		}
	});

};
Racketmanager.viewMatch = function (e) {
	let link = jQuery(e.currentTarget).find("a.score-row__anchor").attr('href');
	if (link) {
		e.preventDefault();
		window.location = link;
	}
};
Racketmanager.matchStatusModal = function (event, match_id) {
	event.preventDefault();
	let notifyField = "#scoreStatusModal";
	let matchStatus = jQuery('#match_status').val();
	let modal = 'scoreStatusModal';
	jQuery(notifyField).val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"modal": modal,
			"match_status": matchStatus,
			"action": "racketmanager_match_status",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(notifyField).html(message);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
		},
		complete: function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	});
};
Racketmanager.setMatchStatus = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_set_match_status";
	let notifyField = '#matchStatusResponse';
	jQuery(notifyField).hide();
	let alertTextField = '#matchStatusResponseText';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let scoreStatus = response.data[2];
			let statusMessages = Object.entries(response.data[3]);
			let statusClasses = Object.entries(response.data[4]);
			let numRubbers = response.data[6];
			if (numRubbers) {
				for (let x = 1; x <= numRubbers; x++) {
					let rubberNumber = x;
					for (let i in statusMessages) {
						let statusMessage = statusMessages[i];
						let teamRef = statusMessage[0];
						let teamMessage = statusMessage[1];
						let messageRef = '#match-message-' + rubberNumber + '-' + teamRef;
						if (teamMessage) {
							jQuery(messageRef).html(teamMessage);
							jQuery(messageRef).removeClass('d-none');
							jQuery(messageRef).addClass('match-warning');
						} else {
							jQuery(messageRef).addClass('d-none');
							jQuery(messageRef).removeClass('match-warning');
							jQuery(messageRef).html('');
						}
					}
					for (let i in statusClasses) {
						let statusClass = statusClasses[i];
						let teamRef = statusClass[0];
						let teamClass = statusClass[1];
						let statusRef = '#match-status-' + rubberNumber + '-' + teamRef;
						jQuery(statusRef).removeClass('winner loser tie');
						if (teamClass) {
							jQuery(statusRef).addClass(teamClass);
						}
					}
					let matchStatusRef = '#match_status_' + rubberNumber;
					jQuery(matchStatusRef).attr('value', scoreStatus);
				}
			} else {
				for (let i in statusMessages) {
					let statusMessage = statusMessages[i];
					let teamRef = statusMessage[0];
					let teamMessage = statusMessage[1];
					let messageRef = '#match-message-' + teamRef;
					if (teamMessage) {
						jQuery(messageRef).html(teamMessage);
						jQuery(messageRef).removeClass('d-none');
						jQuery(messageRef).addClass('match-warning');
					} else {
						jQuery(messageRef).addClass('d-none');
						jQuery(messageRef).removeClass('match-warning');
						jQuery(messageRef).html('');
					}
				}
				for (let i in statusClasses) {
					let statusClass = statusClasses[i];
					let teamRef = statusClass[0];
					let teamClass = statusClass[1];
					let statusRef = '#match-status-' + teamRef;
					jQuery(statusRef).removeClass('winner loser tie');
					if (teamClass) {
						jQuery(statusRef).addClass(teamClass);
					}
				}
			}
			let matchStatusRef = '#match_status';
			jQuery(matchStatusRef).attr('value', scoreStatus);
			let modal = '#' + response.data[5];
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				for (let errorMsg of data[1]) {
					$message += '<br />' + errorMsg;
				}
				let errorFields = data[2];
				for (let errorField of errorFields) {
					let $id = '#'.concat(errorField);
					jQuery($id).addClass("is-invalid");
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).html(response.statusText);
			}
			jQuery(notifyField).show();
		},
		complete: function () {
		}
	});
}
Racketmanager.scoreStatusModal = function (event, rubber_id, rubber_number) {
	event.preventDefault();
	let notifyField = "#scoreStatusModal";
	let modal = 'scoreStatusModal';
	let scoreStatus = jQuery('#match_status_' + rubber_number).val();
	jQuery(notifyField).val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"rubber_id": rubber_id,
			"score_status": scoreStatus,
			"modal": modal,
			"action": "racketmanager_match_rubber_status",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let output = response.responseJSON.data[1];
				jQuery(notifyField).html(output);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
		},
		complete: function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	});
};
Racketmanager.setMatchRubberStatus = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_set_match_rubber_status";
	let notifyField = '#scoreStatusResponse';
	jQuery(notifyField).hide();
	let alertTextField = '#scoreStatusResponseText';
	jQuery(alertTextField).html("");
	jQuery(".is-invalid").removeClass("is-invalid");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let rubberNumber = response.data[1];
			let scoreStatus = response.data[2];
			let statusMessages = Object.entries(response.data[3]);
			for (let i in statusMessages) {
				let statusMessage = statusMessages[i];
				let teamRef = statusMessage[0];
				let teamMessage = statusMessage[1];
				let messageRef = '#match-message-' + rubberNumber + '-' + teamRef;
				if (teamMessage) {
					jQuery(messageRef).html(teamMessage);
					jQuery(messageRef).removeClass('d-none');
					jQuery(messageRef).addClass('match-warning');
				} else {
					jQuery(messageRef).addClass('d-none');
					jQuery(messageRef).removeClass('match-warning');
					jQuery(messageRef).html('');
				}
			}
			let statusClasses = Object.entries(response.data[4]);
			for (let i in statusClasses) {
				let statusClass = statusClasses[i];
				let teamRef = statusClass[0];
				let teamClass = statusClass[1];
				let statusRef = '#match-status-' + rubberNumber + '-' + teamRef;
				jQuery(statusRef).removeClass('winner loser tie');
				if (teamClass) {
					jQuery(statusRef).addClass(teamClass);
				}
			}
			let modal = '#' + response.data[5];
			let matchStatusRef = '#' + 'match_status_' + rubberNumber;
			jQuery(matchStatusRef).val(scoreStatus);
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let $message = data[0];
				for (let errorMsg of data[1]) {
					$message += '<br />' + errorMsg;
				}
				let errorFields = data[2];
				for (let errorField of errorFields) {
					let $id = '#'.concat(errorField);
					jQuery($id).addClass("is-invalid");
				}
				jQuery(alertTextField).html($message);
			} else {
				jQuery(alertTextField).html(response.statusText);
			}
			jQuery(notifyField).show();
		},
		complete: function () {
		}
	});
}
Racketmanager.statusModal = function (event, match_id) {
	event.preventDefault();
	let notifyField = "#scoreStatusModal";
	let matchStatus = jQuery('#match_status').val();
	let modal = 'scoreStatusModal';
	jQuery(notifyField).val("");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"modal": modal,
			"match_status": matchStatus,
			"action": "racketmanager_match_status",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let output = response.responseJSON.data[1];
				jQuery(notifyField).html(output);
			} else {
				jQuery(notifyField).text(response.statusText);
			}
		},
		complete: function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	});
};
Racketmanager.matchOptions = function (event, match_id, option) {
	event.preventDefault();
	let notifyField = "#matchModal";
	let modal = 'matchModal';
	jQuery(notifyField).val("");
	let action = 'racketmanager_match_option';

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"match_id": match_id,
			"modal": modal,
			"option": option,
			"action": action,
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				if (response.status == '401') {
					let output = response.responseJSON.data[1];
					jQuery(notifyField).html(output);
				} else {
					let message = response.responseJSON.data;
					jQuery(notifyField).html(message);
				}
				jQuery(notifyField).addClass('message-error');
			} else {
				jQuery(notifyField).text(response.statusText);
			}
		},
		complete: function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	});
};
Racketmanager.setMatchDate = function (link, is_tournament) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_set_match_date";
	let notifyField = '#updateStatusResponse';
	let alert_id_1;
	let alert_response_1 = '';
	if (is_tournament) {
		alert_id_1 = jQuery('#matchAlert');
		alert_response_1 = '#alertResponse';
	} else {
		alert_id_1 = jQuery('#matchOptionsAlert');
		alert_response_1 = '#alertMatchOptionsResponse';
	}
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_id_2 = jQuery('#matchDateAlert');
	jQuery(alert_id_2).hide();
	jQuery(alert_id_2).removeClass('alert--success alert--warning alert--danger');
	let alert_response_2 = '#alertMatchDateResponse';
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let message = response.data[0];
			let modal = '#' + response.data[1];
			let match_id = response.data[2];
			let matchDate = response.data[4];
			jQuery(alert_id_1).show();
			jQuery(alert_id_1).addClass('alert--success');
			jQuery(alert_response_1).html(message);
			jQuery(modal).modal('hide')
			if (matchDate) {
				if (is_tournament) {
					jQuery('#match-tournament-date-header').html(matchDate);
				} else {
					Racketmanager.matchHeader(match_id);
				}
			}
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let message = '';
				for (let errorMsg of data[1]) {
					message += errorMsg + '<br />';
				}
				let errorFields = data[2];
				for (let errorField of errorFields) {
					let id = '#'.concat(errorField);
					jQuery(id).addClass("is-invalid");
				}
				jQuery(alert_response_2).html(message);
			} else {
				jQuery(alert_response_2).text(response.statusText);
			}
			jQuery(alert_id_2).show();
			jQuery(alert_id_2).addClass('alert--danger');
		},
		complete: function () {
		}
	});
}
Racketmanager.switchHomeAway = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_switch_home_away";
	let notifyField = '#updateStatusResponse';
	let alert_id_1 = jQuery('#matchOptionsAlert');
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_response_1 = '#alertMatchOptionsResponse';
	let alert_id_2 = jQuery('#matchDateAlert');
	jQuery(alert_id_2).hide();
	jQuery(alert_id_2).removeClass('alert--success alert--warning alert--danger');
	let alert_response_2 = '#alertMatchDateResponse';
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let message = response.data[0];
			let modal = '#' + response.data[1];
			let match_id = response.data[2];
			jQuery(alert_id_1).show();
			jQuery(alert_id_1).addClass('alert--success');
			jQuery(alert_response_1).html(message);
			jQuery(modal).modal('hide')
			Racketmanager.matchHeader(match_id);
			let newPath = response.data[3];
			let url = new URL(window.location.href);
			let newURL = url.protocol + '//' + url.hostname + newPath;
			if (newPath !== "") {
				var newUri = newURL;
				if (history.replaceState) {
					history.replaceState('', document.title, newUri.toString());
				}
			}
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let message = '';
				for (let errorMsg of data[1]) {
					message += errorMsg + '<br />';
				}
				let errorFields = data[2];
				for (let errorField of errorFields) {
					let id = '#'.concat(errorField);
					jQuery(id).addClass("is-invalid");
				}
				jQuery(alert_response_2).html(message);
			} else {
				jQuery(alert_response_2).text(response.statusText);
			}
			jQuery(alert_id_2).show();
			jQuery(alert_id_2).addClass('alert--danger');
		},
		complete: function () {
		}
	});
}
Racketmanager.switchTab = function (elem) {
	let selectedTab = jQuery(elem).data('tabid').toLowerCase();
	switch (selectedTab) {
		case 'tab-grid':
			jQuery('.match-group').addClass('match-group--grid');
			jQuery('.match').removeClass('match--list');
			jQuery('.match .match--list').removeClass('match--list');
			jQuery('#tab-list').removeClass('active');
			jQuery('#tab-grid').addClass('active');
			break;
		case 'tab-list':
			jQuery('.match-group').removeClass('match-group--grid');
			jQuery('.match').addClass("match--list");
			jQuery('#tab-list').addClass('active');
			jQuery('#tab-grid').removeClass('active');
			break;
	}
};
Racketmanager.getMessage = function (event, message_id) {
	event.preventDefault();
	jQuery('.selected').removeClass('selected');
	let message_ref = '#message-summary-' + message_id;
	jQuery(message_ref).addClass('selected read');
	jQuery(message_ref).removeClass('unread');
	let notifyField = "#message_detail";
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	let errorField = '#messages-alert';
	let errorResponse = '#messages-alert-response';
	jQuery(errorResponse).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"message_id": message_id,
			"action": "racketmanager_get_message",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			jQuery(notifyField).html(response.data.output);
			if (response.data.status == '1') {
				let readMessagesRef = '#read-messages';
				let readMessages = jQuery(readMessagesRef).html();
				readMessages++;
				jQuery(readMessagesRef).html(readMessages);
				let unreadMessagesRef = '#unread-messages';
				let unreadMessages = jQuery(unreadMessagesRef).html();
				unreadMessages--;
				jQuery(unreadMessagesRef).html(unreadMessages);
			}
			jQuery(notifyField).show();
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(errorField).html(message);
			} else {
				jQuery(errorField).text(response.statusText);
			}
			jQuery(errorResponse).show();
		},
		complete: function () {
		}
	});
};
Racketmanager.deleteMessage = function (event, message_id) {
	event.preventDefault();
	if (confirm('Are you sure you want to delete this message?') !== true) {
		return;
	};
	jQuery('.selected').removeClass('selected');
	let message_ref = '#message-summary-' + message_id;
	jQuery(message_ref).addClass('deleted');
	let notifyField = "#message_detail";
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	let errorField = '#messages-alert';
	let errorResponse = '#messages-alert-response';
	jQuery(errorResponse).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"message_id": message_id,
			"action": "racketmanager_delete_message",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			jQuery(notifyField).empty();
			if (response.data.success !== false) {
				jQuery(message_ref).hide();
			}
			jQuery(notifyField).html(response.data.output);
			jQuery(notifyField).show();
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(errorField).html(message);
			} else {
				jQuery(errorField).text(response.statusText);
			}
			jQuery(errorResponse).show();
		},
		complete: function () {
		}
	});
};
Racketmanager.deleteMessages = function (event, link) {
	event.preventDefault();
	if (confirm('Are you sure you want to delete these messages?') !== true) {
		return;
	};
	let formId = '#'.concat(link.form.id);
	let form = jQuery(formId).serialize();
	form += "&action=racketmanager_delete_messages";
	jQuery('.selected').removeClass('selected');
	let notifyField = "#message_detail";
	let errorField = '#messages-alert';
	let errorResponse = '#messages-alert-response';
	jQuery(errorResponse).hide();
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: form,
		success: function (response) {
			jQuery(notifyField).empty();
			if (response.data.success !== false) {
				let messagesRef = '.' + response.data.type;
				jQuery(messagesRef).hide();
				let messageCountRef = '#' + response.data.type + '-messages';
				let messageCount = 0;
				jQuery(messageCountRef).html(messageCount);
			}
			jQuery(notifyField).html(response.data.output);
			jQuery(notifyField).show();
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(errorField).html(message);
			} else {
				jQuery(errorField).text(response.statusText);
			}
			jQuery(errorResponse).show();
		},
		complete: function () {
		}
	});
};
Racketmanager.resetPassword = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_reset_password";
	let alert_id_1;
	let alert_response_1 = '';
	alert_id_1 = jQuery('#loginAlert');
	alert_response_1 = '#loginAlertResponse';
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_id_2 = jQuery('#resetAlert');
	jQuery(alert_id_2).hide();
	jQuery(alert_id_2).removeClass('alert--success alert--warning alert--danger');
	let alert_response_2 = '#resetAlertResponse';
	jQuery(".is-invalid").removeClass("is-invalid");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let message = response.data[0];
			let modal = '#resetPasswordModal';
			jQuery(alert_id_1).show();
			jQuery(alert_id_1).addClass('alert--success');
			jQuery(alert_response_1).html(message);
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let message = '';
				for (let errorMsg of data[1]) {
					message += errorMsg + '<br />';
				}
				let errorFields = data[2];
				for (let errorField of errorFields) {
					let id = '#'.concat(errorField);
					jQuery(id).addClass("is-invalid");
				}
				jQuery(alert_response_2).html(message);
			} else {
				jQuery(alert_response_2).text(response.statusText);
			}
			jQuery(alert_id_2).show();
			jQuery(alert_id_2).addClass('alert--danger');
		},
		complete: function () {
		}
	});
};
Racketmanager.playerSearch = function (event, link) {
	event.preventDefault();
	let notifyBlock = "#searchResultsContainer";
	jQuery(notifyBlock).hide();
	let errorField = '#search-alert';
	let errorResponse = '#search-alert-response';
	jQuery(errorResponse).hide();
	jQuery(notifyBlock).empty();
	let url = new URL(window.location.href);
	let newURL = url.protocol + '//' + url.hostname + url.pathname;
	let search_string = jQuery('#search_string').val();
	if (search_string !== "") {
		var newUri = newURL + '?q=' + search_string;
		if (history.replaceState) {
			history.replaceState('', document.title, newUri.toString());
		}
	} else {
		return;
	}
	let formId = '#'.concat(event.currentTarget.id);
	let form = jQuery(formId).serialize();
	form += "&action=racketmanager_search_players";
	let splash = '#splash';
	jQuery(splash).removeClass("d-none");
	jQuery(splash).css('opacity', 1);
	jQuery(splash).show();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: form,
		success: function (response) {
			jQuery(notifyBlock).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data;
				jQuery(errorField).html(message);
			} else {
				jQuery(errorField).text(response.statusText);
			}
			jQuery(errorResponse).show();
		},
		complete: function () {
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery(notifyBlock).show();
		}
	});
};

function activaTab(tab) {
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function get_player_details(name, club = null, notifyField = null) {
	let response = '';
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: {
			"name": name,
			"club": club,
			"action": "racketmanager_get_player_details",
			"security": ajax_var.ajax_nonce,
		},
		success: function (data) {
			response = JSON.parse(data.data);
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
	return response;
}