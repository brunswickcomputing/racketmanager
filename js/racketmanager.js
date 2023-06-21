let $mathCardWindow;
jQuery(document).ready(function ($) {
	jQuery("tr.match-rubber-row").slideToggle('fast', 'linear');
	jQuery("i", "td.angle-dir", "tr.match-row").toggleClass("angle-right angle-down");

	jQuery("tr.match-row").click(function (e) {
		jQuery(this).next("tr.match-rubber-row").slideToggle('0', 'linear');
		jQuery(this).find("i.angledir").toggleClass("angle-right angle-down");
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_archive').submit(function () {
		let league = jQuery('#league_id').val(); //
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/leagues/' + league.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_competititon_archive').submit(function () {
		let pagename = jQuery('#pagename').val();
		let season = jQuery('#season').val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + pagename.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').submit(function () {
		let league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		league = league.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery('#season').val();
		let matchday = jQuery('#match_day').val();
		if (matchday == -1) matchday = 0;
		let team = jQuery('#team_id').val();
		team = team.replace(/\s/g, "-"); // Replace space with a '-' symbol */

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/leagues/' + league.toLowerCase() + '/' + season + '/day' + matchday + '/' + team + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_winners').submit(function () {
		let selection = jQuery(`#selection`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		selection = selection.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		selection = selection.replace(/\s/g, "_"); // Replace space with a '-' symbol */
		let competitionSeason = jQuery(`#competitionSeason`).val();
		let competitionType = jQuery(`#competitionType`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/' + competitionType + 's/' + competitionSeason + '/winners/' + selection.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_orderofplay').submit(function () {
		let tournament = jQuery(`#tournament`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace(/\s/g, "_"); // Replace space with a '-' symbol */
		let season = jQuery(`#season`).val();

		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/tournaments/' + season + '/' + season + '-order-of-play/' + tournament.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_daily_matches').submit(function () {
		let matchDate = jQuery(`#match_date`).val();
		let cleanUrl = encodeURI(window.location.protocol) + '//' + encodeURIComponent(window.location.host) + '/leagues/daily-matches/' + encodeURIComponent(matchDate) + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	jQuery('.teamcaptain').autocomplete({
		minLength: 2,
		source: function (name, response) {
			let affiliatedClub = jQuery("#affiliatedClub").val();

			jQuery.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {
					"name": name,
					"affiliatedClub": affiliatedClub,
					"action": "racketmanager_getCaptainName"
				},
				success: function (data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function (event, ui) {
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let captain = "#".concat(captaininput);
			let captainId = "#captainId".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			jQuery(captain).val(ui.item.value);
			jQuery(captainId).val(ui.item.id);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let captaininput = this.id;
			let ref = captaininput.substr(7);
			let captain = "#".concat(captaininput);
			let captainId = "#captainid".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(captain).val('');
				jQuery(captainId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(captain).val(ui.item.value);
				jQuery(captainId).val(ui.item.id);
				jQuery(contactno).val(ui.item.contactno);
				jQuery(contactemail).val(ui.item.user_email);
			}
		}
	});

	jQuery('#matchSecretaryName').autocomplete({
		minLength: 2,
		source: function (name, response) {
			let affiliatedClub = jQuery("#clubId").val();

			jQuery.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {
					"name": name,
					"affiliatedClub": affiliatedClub,
					"action": "racketmanager_getCaptainName"
				},
				success: function (data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function (event, ui) {
			let captain = "#matchSecretaryName";
			let captainId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			jQuery(captain).val(ui.item.value);
			jQuery(captainId).val(ui.item.id);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let captain = "#matchSecretaryName";
			let captainId = "#matchSecretaryId";
			let contactno = "#matchSecretaryContactNo";
			let contactemail = "#matchSecretaryEmail";
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(captain).val('');
				jQuery(captainId).val('');
				jQuery(contactno).val('');
				jQuery(contactemail).val('');
			} else {
				jQuery(captain).val(ui.item.value);
				jQuery(captainId).val(ui.item.id);
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

	jQuery(":checkbox").click(function (event) {
		let $target = event.target;

		// If a checkbox with aria-controls, handle click
		let isCheckbox = $target.getAttribute('type') === 'checkbox';
		let hasAriaControls = $target.getAttribute('aria-controls');
		if (isCheckbox && hasAriaControls) {
			let $target2 = this.parentNode.parentNode.querySelector('#' + $target.getAttribute('aria-controls'));

			if ($target2 && $target2.classList.contains('form-checkboxes__conditional')) {
				let inputIsChecked = $target.checked;

				$target2.setAttribute('aria-expanded', inputIsChecked);
				$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			}
		}
	});

	jQuery('select.cupteam').on('change', function (e) {
		let team = this.value;
		let competition = this.name;
		competition = competition.substring(5, competition.length - 1);

		jQuery.ajax({
			type: 'POST',
			datatype: 'json',
			url: RacketManagerAjaxL10n.requestUrl,
			data: {
				"team": team,
				"competition": competition,
				"action": "racketmanager_get_team_info"
			},
			success: function (data) {
				let response = jQuery.parseJSON(data);
				let captaininput = "captain-".concat(competition);
				let ref = captaininput.substr(7);
				let captain = "#".concat(captaininput);
				let captainId = "#captainId".concat(ref);
				let contactno = "#contactno".concat(ref);
				let contactemail = "#contactemail".concat(ref);
				let matchday = "#matchday".concat(ref);
				let matchtime = "#matchtime".concat(ref);
				jQuery(captain).val(response.captain);
				jQuery(captainId).val(response.captainid);
				jQuery(contactno).val(response.contactno);
				jQuery(contactemail).val(response.user_email);
				jQuery(matchday).val(response.match_day);
				jQuery(matchtime).val(response.match_time);

			}
		});
	});
	jQuery('[data-js=add-favourite]').click(function (e) {
		e.preventDefault();
		let favouriteid = $(this).data('favourite');
		let favouritetype = $(this).data('type');
		let favourite_field = "#".concat(e.currentTarget.id);
		let message_field = "#fav-msg-".concat(favouriteid);

		jQuery.ajax({
			url: RacketManagerAjaxL10n.requestUrl,
			type: "POST",
			data: {
				"type": favouritetype,
				"id": favouriteid,
				"action": "racketmanager_add_favourite"
			},
			success: function (response) {
				let $response = jQuery.parseJSON(response);
				let $message = $response[1];
				let $action = $response[0];
				if ($action == 'del') {
					jQuery(favourite_field).find('i').removeClass('fav-icon-svg-selected');
				} else if ($action == 'add') {
					jQuery(favourite_field).find('i').addClass('fav-icon-svg-selected');
				}
				jQuery(message_field).show();
				jQuery(message_field).addClass('message-success');
				jQuery(message_field).html($message);
				jQuery(message_field).delay(10000).fadeOut('slow');
			},
			error: function () {
				alert("Ajax error on adding favourite");
			}
		});
	});
});

let Racketmanager = new Object();

Racketmanager.printScoreCard = function (e, link) {

	e.preventDefault();
	let matchId = jQuery(link).attr('id');
	let matchtype = jQuery(link).attr('type');
	let ajaxAction = '';
	if (matchtype == 'player') {
		ajaxAction = 'racketmanager_matchcard_player';
	} else {
		ajaxAction = 'racketmanager_matchcard_team';
	}
	let styleSheetList = document.styleSheets;
	let $head = '<html><head><title>Match Card</title>';
	for (let item of styleSheetList) {
		if (item.url != 'null') $head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
	};
	$head += '</head>';
	let $foot = '</body></html>';
	let $content = '';

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": ajaxAction
		},
		success: function ($response) {
			if (!$mathCardWindow || $mathCardWindow.closed) {
				$mathCardWindow = window.open("about:blank", "_blank", "width=800,height=660");
				if (!$mathCardWindow) {
					alert("Match Card not available - turn off pop blocker and retry");
				} else {
					$mathCardWindow.document.write($head + $response + $foot);
				}
			} else {
				// window still exists from last time and has not been closed.
				$mathCardWindow.document.body.innerHTML = $response;
				$mathCardWindow.focus()
			}
		},
		error: function () {
			alert("Ajax error on getting rubbers");
		}
	});
};
Racketmanager.closeMatchModal = function (link) {
	jQuery("#modalMatch").hide();
};
Racketmanager.showRubbers = function (e, matchId) {

	e.preventDefault();
	jQuery("#showMatchRubbers").empty();
	let myModal = new bootstrap.Modal(document.getElementById('modalMatch'), {
		keyboard: true, backdrop: true, focus: true
	})
	myModal.show()
	jQuery("#viewMatchRubbers").show();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_show_rubbers"
		},
		success: function (response) {
			jQuery("#showMatchRubbers").empty();
			jQuery("#showMatchRubbers").html(response);
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
		},
		error: function () {
			alert("Ajax error on getting rubbers");
		}
	});
};
Racketmanager.showMatch = function (matchId) {

	jQuery("#showMatchRubbers").empty();
	let myModal = new bootstrap.Modal(document.getElementById('modalMatch'), {
		keyboard: true, backdrop: false, focus: true
	})
	myModal.show()
	jQuery("#viewMatchRubbers").show();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_show_match"
		},
		success: function (response) {
			jQuery("#showMatchRubbers").empty();
			jQuery("#showMatchRubbers").html(response);
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
		},
		error: function () {
			alert("Ajax error on getting match");
		}
	});
};
Racketmanager.updateMatchResults = function (link) {

	let $form = jQuery('#match-view').serialize();
	$form += "&action=racketmanager_update_match";
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[3];
			jQuery("#updateResponse").show();
			if ($error === true) {
				jQuery("#updateResponse").addClass('message-error');
				jQuery("#updateResponse").html($message);
				let $errField = $response[4];
				for (let i = 0; i < $errField.length; i++) {
					$formfield = "#" + $errField[i];
					jQuery($formfield).addClass('is-invalid');
				}
			} else {
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").addClass('message-success');
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
		error: function () {
			alert("Ajax error on updating match");
		}
	});
	jQuery("#updateRubberResults").removeProp("disabled");
	jQuery("#updateRubberResults").removeClass("disabled");
};
Racketmanager.disableRubberUpdate = function () {

	jQuery("#match-rubbers select").prop("disabled", "true");
	jQuery("#match-rubbers input").prop("readonly", "true");
	jQuery("#updateRubber").val("confirm");
};
Racketmanager.updateResults = function (link) {

	let $form = jQuery('#match-rubbers').serialize();
	$form += "&action=racketmanager_update_rubbers";
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			jQuery("#updateResponse").show();
			if ($error === true) {
				jQuery("#updateResponse").addClass('message-error');
				jQuery("#updateResponse").html($message);
				let $errField = $response[4];
				for (let x = 0; x < $errField.length; x++) {
					$formfield = "#" + $errField[x];
					jQuery($formfield).addClass('is-invalid');
				}
			} else {
				jQuery("#updateResponse").addClass('message-success');
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").delay(10000).fadeOut('slow');
				let $homepoints = $response[2];
				let $matchhome = 0;
				let $matchaway = 0;
				for (let i in $homepoints) {
					let $formfield = "#home_points\\[" + i + "\\]";
					let $fieldval = $homepoints[i];
					jQuery($formfield).val($fieldval);
					$matchhome = +$matchhome + +$homepoints[i];
				}
				let $awaypoints = $response[3];
				for (let j in $awaypoints) {
					let $awayformfield = "#away_points\\[" + j + "\\]";
					let $awayfieldval = $awaypoints[j];
					jQuery($awayformfield).val($awayfieldval);
					$matchaway = +$matchaway + +$awaypoints[j];
				}
				let $updatedRubbers = $response[5];
				rubberNo = 1;
				for (let r in $updatedRubbers) {
					$rubber = $updatedRubbers[r];
					for (let t in $rubber['players']) {
						$team = $rubber['players'][t];
						for (let p = 0; p < $team.length; p++) {
							$player = $team[p];
							let id = p + 1;
							formfield = '#' + t + 'player' + id + '_' + rubberNo;
							fieldval = $player;
							jQuery(formfield).val(fieldval);
						}
					}
					for (let s in $rubber['sets']) {
						team = $rubber['sets'][s];
						for (let p in team) {
							score = team[p];
							formfield = '#' + 'set_' + rubberNo + '_' + s + '_' + p;
							fieldval = score;
							jQuery(formfield).val(fieldval);
						}
					}
					rubberNo++;
				}
			}
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
			jQuery("#updateRubberResults").removeAttr("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		},
		error: function () {
			alert("Ajax error on updating rubbers");
		}
	});
};
Racketmanager.playerRequest = function (link) {

	let $form = jQuery('#playerRequestFrm').serialize();
	$form += "&action=racketmanager_club_player_request";
	jQuery("#updateResponse").val("");
	jQuery("#clubPlayerUpdateSubmit").hide();
	jQuery("#clubPlayerUpdateSubmit").addClass("disabled");
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalidFeedback").val("");

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			if ($error === true) {
				let $errorField = $response[2];
				let $errorMsg = $response[3];
				for (let $i = 0; $i < $errorField.length; $i++) {
					let $id = '#'.concat($errorField[$i]);
					jQuery($id).addClass("is-invalid");
					let $id2 = '#'.concat($errorField[$i], 'Feedback');
					jQuery($id2).html($errorMsg[$i]);
				}
				jQuery("#updateResponse").addClass("message-error");
				jQuery("#updateResponse").show();
				jQuery("#updateResponse").html($message);
			} else {
				jQuery("#firstname").val("");
				jQuery("#surname").val("");
				jQuery("#genderMale").prop('checked', false);
				jQuery("#genderFemale").prop('checked', false);
				jQuery("#btm").val("");
				jQuery("#email").val("");
				jQuery("#updateResponse").addClass("message-success");
				jQuery("#updateResponse").show();
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").delay(10000).fadeOut('slow');
			}
			jQuery("#clubPlayerUpdateSubmit").removeClass("disabled");
		},
		error: function () {
			alert("Ajax error on player add");
		}
	});
	jQuery("#clubPlayerUpdateSubmit").show();
};
Racketmanager.clubPlayerRemove = function (link) {

	let $form = jQuery(link).serialize();
	$form += "&action=racketmanager_club_players_remove";

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function () {
			jQuery(link).find('tr').each(function () {
				let row = jQuery(this);
				if (row.find('input[type="checkbox"]').is(':checked')) {
					let rowId = "#" + row.attr('id');
					jQuery(rowId).remove();
				}
			});
		},
		error: function () {
			alert("Ajax error on player removal");
		}
	});
};
Racketmanager.teamUpdate = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let competition = link.form[3].value;
	let team = link.form[2].value;
	let updateResponse = "#updateTeamResponse-".concat(competition, "-", team);
	let submitButton = "#teamUpdateSubmit-".concat(competition, "-", team);
	$form += "&action=racketmanager_team_update";
	jQuery(updateResponse).val("");
	jQuery(updateResponse).hide();
	jQuery(submitButton).hide();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		async: false,
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			jQuery(updateResponse).show();
			jQuery(updateResponse).addClass("message-success");
			jQuery(updateResponse).html($message);
			jQuery(updateResponse).delay(10000).fadeOut('slow');
		},
		error: function () {
			alert("Ajax error on team update");
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.updateClub = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let updateResponse = "#updateClub";
	let submitButton = "#updateClubSubmit";
	$form += "&action=racketmanager_update_club";
	jQuery(updateResponse).html("");
	jQuery(submitButton).hide();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			jQuery(updateResponse).show();
			jQuery(updateResponse).addClass("message-success");
			jQuery(updateResponse).html($message);
			jQuery(updateResponse).delay(10000).fadeOut('slow');
			jQuery(submitButton).show();
		},
		error: function () {
			alert("Ajax error on club update");
			jQuery(submitButton).show();
		}
	});
};
Racketmanager.updatePlayer = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let updateResponse = "#updatePlayer";
	let submitButton = "#updatePlayerSubmit";
	$form += "&action=racketmanager_update_player";
	jQuery(updateResponse).html("");
	jQuery(submitButton).hide();

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		async: false,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			if ($error === true) {
				let $errorField = $response[2];
				let $errorMsg = $response[3];
				jQuery(updateResponse).addClass('message-error');
				for (let $i = 0; $i < $errorField.length; $i++) {
					$formfield = "#" + $errorField[$i];
					jQuery($formfield).addClass('is-invalid');
					$formfield = $formfield + 'Feedback';
					jQuery($formfield).html($errorMsg[$i]);
				}
				jQuery(submitButton).removeClass("disabled");
				jQuery(updateResponse).html($message);
			} else {
				jQuery(updateResponse).show();
				jQuery(updateResponse).addClass("message-success");
				jQuery(updateResponse).html($message);
				jQuery(updateResponse).delay(10000).fadeOut('slow');
			}
			jQuery(submitButton).show();
		},
		error: function () {
			alert("Ajax error on player update");
			jQuery(submitButton).show();
		}
	});
};
Racketmanager.tournamentEntryRequest = function (link) {

	let $form = jQuery('#form-tournamententry').serialize();
	$form += "&action=racketmanager_tournament_entry";
	jQuery("#tournamEntentryResponse").val("");
	jQuery("#tournamentEntrySubmit").hide();
	jQuery("#tournamentEntrySubmit").addClass("disabled");
	jQuery("#tournamentEntryResponse").removeClass('message-error');
	jQuery("#tournamentEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			if ($error === true) {
				jQuery("#tournamentEntryResponse").addClass('message-error');
				for (let errorMsg of $response[2]) {
					$message += '<br />' + errorMsg;
				}
				for (let errorField of $response[3]) {
					let $id = '#'.concat(errorField);
					jQuery($id).parents('.form-group').addClass('field-error');
				}
				jQuery("#tournamentEntrySubmit").removeClass("disabled");
				jQuery("#tournamentEntryResponse").html($message);
			} else {
				jQuery("#tournamentEntryResponse").show();
				jQuery("#tournamentEntryResponse").addClass('message-success');
				jQuery("#tournamentEntrySubmit").removeClass("disabled");
				jQuery("#tournamentEntryResponse").html($message);
				jQuery("#tournamentEntryResponse").delay(10000).fadeOut('slow');
			}
		},
		error: function () {
			alert("Ajax error on tournament entry");
		}
	});
	jQuery("#tournamentEntrySubmit").show();
};
Racketmanager.cupEntryRequest = function (link) {

	let $form = jQuery('#form-cupentry').serialize();
	$form += "&action=racketmanager_cup_entry";
	jQuery("#cupentryResponse").val("");
	jQuery("#cupEntrySubmit").hide();
	jQuery("#cupEntrySubmit").addClass("disabled");
	jQuery("#cupEntryResponse").removeClass('message-error');
	jQuery("#cupEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			if ($error === true) {
				jQuery("#cupEntryResponse").addClass('message-error');
				for (let errorMsg of $response[2]) {
					$message += '<br />' + errorMsg;
				}
				for (let errorField of $response[3]) {
					let $id = '#'.concat(errorField);
					jQuery($id).parents('.form-group').addClass('field-error');
				}
				jQuery("#cupEntryResponse").html($message);
			} else {
				jQuery("#cupEntryResponse").show();
				jQuery("#cupEntryResponse").addClass('message-success');
				jQuery("#cupEntryResponse").html($message);
				jQuery("#cupEntryResponse").delay(10000).fadeOut('slow');
			}
		},
		error: function () {
			alert("Ajax error on cup entry");
		}
	});
	jQuery("#cupEntrySubmit").show();
};
Racketmanager.leagueEntryRequest = function (link) {

	let $form = jQuery('#form-leagueentry').serialize();
	$form += "&action=racketmanager_league_entry";
	jQuery("#leagueentryResponse").val("");
	jQuery("#leagueEntrySubmit").hide();
	jQuery("#leagueEntrySubmit").addClass("disabled");
	jQuery("#leagueEntryResponse").removeClass('message-error');
	jQuery("#leagueEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url: RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function (response) {
			let $response = jQuery.parseJSON(response);
			let $message = $response[0];
			let $error = $response[1];
			jQuery("#acceptance").prop("checked", false);
			jQuery("#leagueEntryResponse").show();
			if ($error === true) {
				jQuery("#leagueEntryResponse").addClass('message-error');
				for (let errorMsg of $response[2]) {
					$message += '<br />' + errorMsg;
				}
				for (let errorField of $response[3]) {
					let $id = '#'.concat(errorField);
					jQuery($id).parents('.form-group').addClass('field-error');
				}
				jQuery("#leagueEntryResponse").html($message);
				jQuery("#leagueEntrySubmit").removeClass("disabled");
			} else {
				jQuery("#leagueEntryResponse").addClass('message-success');
				jQuery("#leagueEntryResponse").html($message);
				jQuery("#leagueEntryResponse").delay(10000).fadeOut('slow');
			}
		},
		error: function () {
			alert("Ajax error on league entry");
		}
	});
	jQuery("#leagueEntrySubmit").show();
};
function activaTab(tab) {
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
