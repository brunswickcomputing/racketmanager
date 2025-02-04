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
	MatchDayChange();
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
	TournamentDateChange();
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
	jQuery(".hasModal:checkbox").click(function (event) {
		jQuery('#liEventDetails').addClass('is-loading');
		eventRef = '#' + this.id;
		let $target = event.target;
		checkToggle($target, event);
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
	PartnerLookup();
	TournamentDateChange();
	PopstateHandler();
	MatchDayChange();
});
function PopstateHandler() {
	// Handle forward/back buttons
	window.addEventListener("popstate", (event) => {
		// If a state has been provided, we have a "simulated" page
		// and we update the current page.
		if (event.state) {
			// Simulate the loading of the previous page
			jQuery('#pageContentTab').html(event.state);
		}
	});
}
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
function checkToggle($target, event) {
	jQuery('#liEventDetails').addClass('is-loading');
	// If a checkbox with aria-controls, handle click
	let isCheckbox = $target.getAttribute('type') === 'checkbox';
	let hasAriaControls = $target.getAttribute('aria-controls');
	let inputIsChecked = $target.checked;
	let eventId = $target.id.substring(6);
	if (isCheckbox && hasAriaControls) {
		let $target2 = jQuery('#' + hasAriaControls)[0];
		if ($target2.classList.contains('is-doubles')) {
			$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			if (inputIsChecked) {
				Racketmanager.partnerModal(event, eventId);
			} else {
				let partnerIdLink = "#partnerId-" + eventId;
				jQuery(partnerIdLink).val('');
				let partnerNameLink = "#partnerName-" + eventId;
				jQuery(partnerNameLink).html('');
				Racketmanager.clearPrice(eventId);
				jQuery('#liEventDetails').removeClass('is-loading');
			}
		} else {
			if (inputIsChecked) {
				Racketmanager.setEventPrice(eventId);
			} else {
				Racketmanager.clearPrice(eventId);
			}
			jQuery('#liEventDetails').removeClass('is-loading');
		}
	} else {
		jQuery('#liEventDetails').removeClass('is-loading');
	}
};
function PartnerLookup() {
	jQuery('.partner-name').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let partnerGender = jQuery("#partnerGender").val();
			let club = null;
			let notifyField = '#partner-feedback';
			response(get_player_details(request.term, club, notifyField, partnerGender));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let player = "#partnerName";
			let playerId = "#partnerId";
			let playerBTM = "#partnerBTM";
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(playerBTM).val(ui.item.btm);
		},
		change: function (event, ui) {
			let player = "#partnerName";
			let playerId = "#partnerId";
			let playerBTM = "#partnerBTM";
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(playerBTM).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(playerBTM).val(ui.item.btm);
			}
		}
	});
	jQuery('.partner-btm').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = null;
			let notifyField = '#partnerBTM-feedback';
			response(get_player_details(request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value == 'null') {
				ui.item.value = '';
			}
			let player = "#partnerName";
			let playerId = "#partnerId";
			let playerBTM = "#partnerBTM";
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(playerBTM).val(ui.item.btm);
		},
		change: function (event, ui) {
			let player = "#partnerName";
			let playerId = "#partnerId";
			let playerBTM = "#partnerBTM";
			if (ui.item === null) {
				jQuery(this).val('');
				jQuery(player).val('');
				jQuery(playerId).val('');
				jQuery(playerBTM).val('');
			} else {
				jQuery(player).val(ui.item.value);
				jQuery(playerId).val(ui.item.playerId);
				jQuery(playerBTM).val(ui.item.btm);
			}
		}
	});
	jQuery('#partnerModal').on('hidden.bs.modal', function (e) {
		let eventId = jQuery(this).attr('data-event');
		if (eventId) {
			let partnerRef = '#partnerId-' + eventId;
			let partnerId = jQuery(partnerRef).val();
			if (!partnerId) {
				eventRef = '#event-' + eventId;
				jQuery(eventRef).prop('checked', false);
				let target = jQuery(eventRef)[0];
				checkToggle(target, e);
			}
		}
	});
}
function MatchDayChange() {
	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').on('change', function (e) {
		let league = jQuery('#league_id').val();
		league = league.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery('#season').val();
		let matchday = jQuery('#match_day').val();
		if (matchday == -1) matchday = 0;
		let leagueLink = '/league/' + league.toLowerCase() + '/' + season + '/matches/day' + matchday + '/'
		let leagueId = jQuery('#leagueId').val();
		Racketmanager.tabDataLink(e, 'league', leagueId, season, leagueLink, matchday, 'matches');
		return false;  // Prevent default button behaviour
	});
}
function TournamentDateChange() {
	jQuery('#tournament-match-date-form #match_date').on('change', function (e) {
		let match_date = jQuery(`#match_date`).val().replace(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		let tournament = jQuery(`#tournament_id`).val();
		tournament = tournament.replace(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replace("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replace(/\s/g, "-"); // Replace space with a '_' symbol */
		let tournamentLink = '/tournament/' + tournament.toLowerCase() + '/matches/' + match_date + '/';
		let linkId = match_date;
		let linkType = 'matches';
		let tournamentId = jQuery('#tournamentId').val();
		Racketmanager.tabDataLink(e, 'tournament', tournamentId, null, tournamentLink, linkId, linkType)
		return false;  // Prevent default button behaviour
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
			let playerWarnings = $response[5];
			if (playerWarnings) {
				for (let w in playerWarnings) {
					let playerRef = '#' + w;
					jQuery(playerRef).addClass('is-invalid');
					let playerRefFeedback = playerRef + 'Feedback';
					jQuery(playerRefFeedback).html(playerWarnings[w])
				}
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
	jQuery(notifyField).removeClass('alert--success alert--warning alert--info alert--danger');
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
			if (Array.isArray(response.data)) {
				msg = response.data[0];
				msgType = response.data[1];
				if (response.data[2]) {
					link = response.data[3];
					if (link) {
						window.location = link;
					}
				}
			} else {
				msg = response.data;
				msgType = 'success';
			}
			let msgClass = 'alert--' + msgType;
			jQuery(notifyField).addClass(msgClass);
			jQuery(alertTextField).html(msg);
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
Racketmanager.matchMode = function (e, match_id, mode, message) {
	e.preventDefault();
	let notifyField;
	let tournament;
	if (mode === 'tournament') {
		notifyField = ".elementor-shortcode";
		tournament = jQuery('#tournamentName').val();
	} else {
		notifyField = "#showMatchRubbers";
	}
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").removeClass("d-none");
	jQuery("#splash").show();
	jQuery(".match-print").hide();
	jQuery(".match-mode").hide();
	jQuery(".match-mode").removeClass("d-none");
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"match_id": match_id,
			"mode": mode,
			"tournament": tournament,
			"action": "racketmanager_match_mode",
			"security": ajax_var.ajax_nonce,
			"message": message,
		},
		function () {
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
	);
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
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"match_id": match_id,
			"modal": modal,
			"match_status": matchStatus,
			"action": "racketmanager_match_status",
			"security": ajax_var.ajax_nonce,
		},
		function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	);
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
				let errorMsg = data[1];
				let errorField = data[2];
				for (let i = 0; i < errorField.length; i++) {
					let formfield = "#" + errorField[i];
					jQuery(formfield).addClass('is-invalid');
					formfield = formfield + 'Feedback';
					jQuery($ = formfield).html(errorMsg[i]);
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
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"rubber_id": rubber_id,
			"score_status": scoreStatus,
			"modal": modal,
			"action": "racketmanager_match_rubber_status",
			"security": ajax_var.ajax_nonce,
		},
		function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	);
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
				let errorMsg = data[1];
				let errorField = data[2];
				for (let i = 0; i < errorField.length; i++) {
					let formfield = "#" + errorField[i];
					jQuery(formfield).addClass('is-invalid');
					formfield = formfield + 'Feedback';
					jQuery($=formfield).html(errorMsg[i]);
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
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"match_id": match_id,
			"modal": modal,
			"match_status": matchStatus,
			"action": "racketmanager_match_status",
			"security": ajax_var.ajax_nonce,
		},
		function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	);
};
Racketmanager.matchOptions = function (event, match_id, option) {
	event.preventDefault();
	let notifyField = "#matchModal";
	let modal = 'matchModal';
	jQuery(notifyField).val("");
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"match_id": match_id,
			"modal": modal,
			"option": option,
			"action": 'racketmanager_match_option',
			"security": ajax_var.ajax_nonce,
		},
		function () {
			jQuery(notifyField).show();
			jQuery(notifyField).modal('show');
		}
	);
};
Racketmanager.setMatchDate = function (e, link, is_tournament) {
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
Racketmanager.resetMatchResult = function (e, link, is_tournament) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_reset_match_result";
	let notifyField = '#updateStatusResponse';
	let alert_id_1;
	let alert_response_1 = '';
	let mode;
	if (is_tournament) {
		alert_id_1 = jQuery('#matchAlert');
		alert_response_1 = '#alertResponse';
		mode = 'tournament';
	} else {
		alert_id_1 = jQuery('#matchOptionsAlert');
		alert_response_1 = '#alertMatchOptionsResponse';
		mode = 'view';
	}
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_id_2 = jQuery('#resetMatchAlert');
	jQuery(alert_id_2).hide();
	jQuery(alert_id_2).removeClass('alert--success alert--warning alert--danger');
	let alert_response_2 = '#alertresetMatchResponse';
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
			Racketmanager.matchMode(e, match_id, mode, message);
			if (!is_tournament) {
				Racketmanager.matchHeader(match_id);
			}
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
}
Racketmanager.switchHomeAway = function (e, link) {
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
	jQuery(notifyBlock).empty();
	let url = new URL(window.location.href);
	let newURL = url.protocol + '//' + url.hostname + url.pathname;
	let search_string = jQuery('#search_string').val();
	if (search_string !== "") {
		var newUri = newURL + '?q=' + search_string;
		let formId = '#'.concat(event.currentTarget.id);
		let form = jQuery(formId).serialize();
		form += "&action=racketmanager_search_players";
		let loadingArea = '#playerSearchContent';
		jQuery(loadingArea).addClass('is-loading');
		let ajaxURL = ajax_var.url + '?search_string=' + search_string + '&action=racketmanager_search_players&security=' + ajax_var.ajax_nonce;
		jQuery(notifyBlock).load(
			ajaxURL,
			function () {
				jQuery(notifyBlock).show();
				jQuery(loadingArea).removeClass('is-loading');
				history.pushState(jQuery('#pageContentTab').html(), '', newUri.toString());
			}
		);
	}
};
Racketmanager.partnerModal = function (event, event_id) {
	jQuery('#liEventDetails').addClass('is-loading');
	event.preventDefault();
	let partnerRef = '#partnerId-' + event_id;
	let partnerId = jQuery(partnerRef).val();
	let eventRef = '#event-' + event_id;
	jQuery(eventRef).prop('checked', true);
	let genderRef = "#playerGender";
	let gender = jQuery(genderRef).val();
	let seasonRef = "#season";
	let season = jQuery(seasonRef).val();
	let dateEndRef = "#tournamentDateEnd";
	let dateEnd = jQuery(dateEndRef).val();
	let notifyField = "#partnerModal";
	let modal = 'partnerModal';
	jQuery(notifyField).val("");
	let action = 'racketmanager_team_partner';

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"eventId": event_id,
			"modal": modal,
			"gender": gender,
			"season": season,
			"partnerId": partnerId,
			"dateEnd": dateEnd,
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
			jQuery(eventRef).prop('checked', true);
			jQuery('#liEventDetails').removeClass('is-loading');
			jQuery(notifyField).show();
			jQuery(notifyField).attr('data-event', event_id);
			jQuery(notifyField).modal('show');
		}
	});
};
Racketmanager.partnerSave = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_validate_partner";
	let alert_id;
	let alert_response = '';
	alert_id = jQuery('#partnerResponse');
	alert_response = '#partnerResponseText';
	jQuery(alert_id).hide();
	jQuery(alert_id).removeClass('alert--success alert--warning alert--danger');
	jQuery(".is-invalid").removeClass("is-invalid");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let modal = '#' + response.data[0];
			let partnerId = response.data[1];
			let partnerName = response.data[2];
			let eventId = response.data[3];
			let partnerIdLink = "#partnerId-" + eventId;
			jQuery(partnerIdLink).val(partnerId);
			let partnerNameLink = "#partnerName-" + eventId;
			jQuery(partnerNameLink).html(partnerName);
			jQuery(modal).modal('hide')
			Racketmanager.setEventPrice(eventId);
		},
		error: function (response) {
			if (response.responseJSON) {
				let message = response.responseJSON.data[0];
				if (response.responseJSON.data[1]) {
					let errorMsg = response.responseJSON.data[1];
					let errorField = response.responseJSON.data[2];
					for (let $i = 0; $i < errorField.length; $i++) {
						let formfield = "#" + errorField[$i];
						jQuery(formfield).addClass('is-invalid');
						formfield = formfield + 'Feedback';
						jQuery(formfield).html(errorMsg[$i]);
					}
				}
				jQuery(alert_response).html(message);
			} else {
				jQuery(alert_response).text(response.statusText);
			}
			jQuery(alert_id).addClass('alert--danger');
		},
		complete: function () {
		}
	});
};
Racketmanager.updateTournamentEvent = function (event, type) {
	jQuery('#liEventDetails').addClass('is-loading');
	let $target = event.target;
	jQuery('#liEventDetails').addClass('is-loading');
	// If a checkbox with aria-controls, handle click
	let isCheckbox = $target.getAttribute('type') === 'checkbox';
	let hasAriaControls = $target.getAttribute('aria-controls');
	if (isCheckbox && hasAriaControls) {
		let $target2 = $target.parentNode.parentNode.querySelector('#' + $target.getAttribute('aria-controls'));
		if ($target2?.classList.contains('form-checkboxes__conditional')) {
			let inputIsChecked = $target.checked;
			$target2.setAttribute('aria-expanded', inputIsChecked);
			$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			let event_id = $target.id.substring(6);
			if (inputIsChecked) {
				Racketmanager.partnerModal(event, event_id);
			} else {
				jQuery('#liEventDetails').removeClass('is-loading');
			}
		}
	}
	jQuery('#liEventDetails').removeClass('is-loading');
};
Racketmanager.tabData = function (e, target, id, season, name, competitionType) {
	e.preventDefault();
	let tabContent = '#' + target + 'TabContent';
	jQuery(tabContent).addClass('is-loading');
	let $target = e.target;
	let tab = $target.getAttribute('aria-controls');
	let newPath = '';
	if (target == 'tournament') {
		newPath = '/tournament/' + name + '/';
	} else if (target == 'event') {
		newPath = '/' + competitionType + 's/' + name + '/' + season + '/';
	} else if (target == 'competition') {
		newPath = '/' + name + '/' + season + '/';
	} else if (target == 'league') {
		newPath = '/' + competitionType + '/' + name + '/' + season + '/';
	}
	if (newPath !== "") {
		let tabDataRef = '#' + tab;
		let url = new URL(window.location.href);
		let newURL = url.protocol + '//' + url.hostname + newPath + tab + '/';
		if (newURL !== url.toString()) {
			jQuery(tabDataRef).html('');
			jQuery(tabDataRef).load(
				ajax_var.url,
				{
					"action": 'racketmanager_get_tab_data',
					"tab": tab,
					"id": id,
					"season": season,
					"security": ajax_var.ajax_nonce,
					"target": target,
				},
				function () {
					jQuery(tabContent).removeClass('is-loading');
					history.pushState(jQuery('#pageContentTab').html(), '', newURL.toString());
				}
			);
		} else {
			jQuery(tabContent).removeClass('is-loading');
		}
	}
};
Racketmanager.tabDataLink = function (e, target, id, season = null, link = null, linkId = null, linkType = null) {
	e.preventDefault();
	let tabContent = '#' + target + 'TabContent';
	jQuery(tabContent).addClass('is-loading');
	let tab = linkType;
	let tabDataRef = '#' + tab;
	let tabRef = tabDataRef + '-tab';
	let activeTab = jQuery(".tab-pane.active");
	let activeTabName = activeTab[0].id;
	if (activeTabName !== tab) {
		jQuery("#myTab li > button").removeClass("active");
		jQuery(tabRef).addClass("active");
		jQuery(".tab-pane").removeClass("active show").addClass("fade");
		jQuery(tabDataRef).removeClass("fade").addClass("active").show();
	}
	let url = new URL(window.location.href);
	let newURL = url.protocol + '//' + url.hostname + link;
	jQuery(tabDataRef).html('');
	jQuery(tabDataRef).load(
		ajax_var.url,
		{
			"action": 'racketmanager_get_tab_data',
			"tab": tab,
			"id": id,
			"season": season,
			"security": ajax_var.ajax_nonce,
			"link_id": linkId,
			"target": target,
		},
		function () {
			jQuery(tabContent).removeClass('is-loading');
			history.pushState(jQuery('#pageContentTab').html(), '', newURL.toString());
		}
	);
};
Racketmanager.setEventPrice = function (eventId) {
	let eventFeeFld = '#eventFee-' + eventId;
	let eventPrice = jQuery(eventFeeFld).val();
	if (eventPrice > 0) {
		let eventPriceFmt = currencyFormat(eventPrice);
		let eventPriceId = '#event-price-' + eventId;
		jQuery(eventPriceId).val(eventPrice);
		let eventPriceIdFmt = '#event-price-fmt-' + eventId;
		jQuery(eventPriceIdFmt).html(eventPriceFmt);
	}
	Racketmanager.setTotalPrice();
};
Racketmanager.clearPrice = function (eventId) {
	let eventPrice = '';
	let eventPriceId = '#event-price-' + eventId;
	jQuery(eventPriceId).val(eventPrice);
	let eventPriceIdFmt = '#event-price-fmt-' + eventId;
	jQuery(eventPriceIdFmt).html(eventPrice);
	Racketmanager.setTotalPrice();
};
Racketmanager.setTotalPrice = function () {
	let competitionFeeFld = '#competitionFee';
	let competitionFee = jQuery(competitionFeeFld).val();
	let eventPriceFld = '.event-price-amt';
	let eventPrices = jQuery(eventPriceFld);
	let totalPrice = 0 + +competitionFee;
	for (i = 0; i < eventPrices.length; i++) {
		eventPrice = eventPrices[i];
		eventPrice = eventPrice.value;
		totalPrice = +totalPrice + +eventPrice;
	}
	let totalPriceFmt = '';
	if (totalPrice > 0) {
		totalPriceFmt = 'Total: ' + currencyFormat(totalPrice);
	}
	let totalPriceFmtId = '#priceCostTotalFmt';
	jQuery(totalPriceFmtId).html(totalPriceFmt);
	let totalPriceId = '#priceCostTotal';
	jQuery(totalPriceId).val(totalPrice);
};
Racketmanager.setPaymentStatus = function (payRef) {
	let action = 'racketmanager_update_payment';
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"paymentReference": payRef,
			"action": action,
			"security": ajax_var.ajax_nonce,
		}
	});
};
Racketmanager.withdrawTournament = function (e) {
	e.preventDefault();
	jQuery('#liEventDetails').addClass('is-loading');
	eventsEnteredRef = '#eventsEntered';
	eventsEntered = jQuery(eventsEnteredRef).val();
	tournamentRef = '#tournamentId';
	tournamentId = jQuery(tournamentRef).val();
	let notifyField = "#partnerModal";
	let modal = 'partnerModal';
	jQuery(notifyField).val("");
	let action = 'racketmanager_tournament_withdrawal';
	jQuery(notifyField).val("");
	jQuery(notifyField).load(
							 ajax_var.url,
							 {
								 "tournamentId": tournamentId,
								 "eventsEntered": eventsEntered,
								 "modal": modal,
								 "action": action,
								 "security": ajax_var.ajax_nonce,
							 },
							 function () {
								 jQuery('#liEventDetails').removeClass('is-loading');
								 jQuery(notifyField).show();
								 jQuery(notifyField).modal('show');
							 }
							 );
};
	
Racketmanager.confirmTournamentWithdraw = function () {
	let modal = '#partnerModal';
	let tournamentRef = '#tournamentId';
	let tournamentId = jQuery(tournamentRef).val();
	let playerRef = '#playerId';
	let playerId = jQuery(playerRef).val();
	let eventsEnteredRef = 'input:checked.form-check--event';
	let eventsEntered = jQuery(eventsEnteredRef);
	let alertField = "#entryAlert";
	let alertResponseField = "#entryAlertResponse";
	jQuery(alertField).hide();
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertResponseField).val("");
	let action = 'racketmanager_confirm_tournament_withdrawal';

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"tournamentId": tournamentId,
			"playerId": playerId,
			"action": action,
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			for ( let event of eventsEntered) {
				event.checked = false;
				checkToggle(event, null);
			}
			jQuery(alertField).addClass('alert--success');
			jQuery(alertResponseField).html(response.data);
		},
		error: function (response) {
			if (response.responseJSON) {
				if (response.status == '401') {
					let output = response.responseJSON.data[1];
					jQuery(alertResponseField).html(output);
				} else {
					let message = response.responseJSON.data;
					jQuery(alertResponseField).html(message);
				}
			} else {
				jQuery(alertResponseField).text(response.statusText);
			}
			jQuery(alertField).addClass('alert--danger');
		},
		complete: function () {
			jQuery(modal).modal('hide');
			jQuery(alertField).show();
		}
	});

}
function activaTab(tab) {
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function get_player_details(name, club = null, notifyField = null, partnerGender = null) {
	let response = '';
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: {
			"name": name,
			"club": club,
			"partnerGender": partnerGender,
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
function currencyFormat(amount) {
	return totalPrice = new Intl.NumberFormat(locale_var.locale, { style: 'currency', currency: locale_var.currency }).format(amount);
}
function createPaymentRequest(tournamentEntry,invoiceId, callback) {
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"tournament_entry": tournamentEntry,
			"invoiceId" : invoiceId,
			"action": "racketmanager_tournament_payment_create",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			output = response.data;
		},
		error: function (response) {
			if (response.responseJSON) {
				output = response.responseJSON.data;
			} else {
				output = response.statusText;
			}
		},
		complete: function () {
			callback(output);
		}
	});
}
