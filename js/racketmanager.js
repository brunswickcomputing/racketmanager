jQuery(function () {
	jQuery('[data-bs-toggle="tooltip"]').tooltip();
	jQuery("#acceptance").prop("checked", false)
	jQuery("#entrySubmit").hide();
	jQuery('#acceptance').on( "change",function () {
		if (this.checked) {
			jQuery("#entrySubmit").show();
		} else {
			jQuery("#entrySubmit").hide();
		}
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_archive').on('change', function () {
		let league = jQuery('#league_id').val(); //
		let season = jQuery('#season').val();

		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/league/' + league.toLowerCase() + '/' + season + '/';

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_competition_archive #season').on('change', function () {
		let pagename = jQuery('#pagename').val();
		let season = jQuery('#season').val();

		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/' + pagename.toLowerCase() + '/' + season + '/';

		return false;  // Prevent default button behaviour
	});
	MatchDayChange();
	/* Friendly URL rewrite */
	jQuery('#racketmanager_winners #selection').on('change', function () {
		let selection = jQuery(`#selection`).val().replaceAll(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		selection = selection.replaceAll(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		selection = selection.replaceAll("-", "_"); // Replace '-' with a '-' symbol */
		selection = selection.replaceAll(/\s/g, "-"); // Replace space with a '-' symbol */
		let competitionSeason = jQuery(`#competitionSeason`).val();
		let competitionType = jQuery(`#competitionType`).val();

		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/' + competitionType + 's/' + competitionSeason + '/winners/' + selection.toLowerCase() + '/';

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_orderofplay #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replaceAll(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replaceAll(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replaceAll("-", "_"); // Replace '-' with a '_' symbol */
		tournament = tournament.replaceAll(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery(`#season`).val();

		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/tournaments/' + season + '/order-of-play/' + tournament.toLowerCase() + '/';

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_tournament #tournament_id').on('change', function () {
		let tournament = jQuery(`#tournament_id`).val().replaceAll(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replaceAll(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replaceAll("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replaceAll(/\s/g, "-"); // Replace space with a '_' symbol */
		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/tournament/' + tournament.toLowerCase() + '/';

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_daily_matches #match_date').on('change', function () {
		let matchDate = jQuery(`#match_date`).val();
		globalThis.location = encodeURI(globalThis.location.protocol) + '//' + encodeURIComponent(globalThis.location.host) + '/leagues/daily-matches/' + encodeURIComponent(matchDate) + '/';

		return false;  // Prevent default button behaviour
	});
	TournamentDateChange();
	CaptainLookup();

	jQuery('#matchSecretaryName').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#club_id").val();
			let notifyField = '#match-secretary-feedback';
			response(get_player_details('name', request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value === 'null') {
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
			setPlayerDetails(ui,player,playerId,contactno,contactemail);
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
		let target = event.target;
		checkToggle(target, event);
	});

	jQuery('select.cupteam').on('change', function () {
		let team = this.value;
		let event = this.name;
		event = event.substring(5, event.length - 1);
		let notifyField = "#team-" + event;
		jQuery(notifyField).removeClass('is-invalid');
		let responseField = "#team-dtls-" + event;
		let splash = '#splash-' + event;
		jQuery(splash).removeClass("d-none");
		jQuery(splash).css('opacity', 1);
		jQuery(splash).show();
		jQuery(responseField).hide();

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
				let captainInput = "captain-".concat(event);
				let ref = captainInput.substring(7);
				let captain = "#".concat(captainInput);
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
				let feedback = notifyField + 'Feedback';
				if (response.responseJSON) {
					jQuery(feedback).text(response.responseJSON.data);
				} else {
					jQuery(feedback).text(response.statusText);
				}
				jQuery(notifyField).addClass('is-invalid');
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
function setPlayerDetails(ui,player,playerId,contactno,contactemail) {
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
jQuery(document).ajaxComplete(function () {
	FavouriteInit();
	PartnerLookup();
	TournamentDateChange();
	PopstateHandler();
	MatchDayChange();
	CaptainLookup();
    UserLookup();
});
function UserLookup() {
   jQuery('#userName').autocomplete({
        minLength: 2,
        source: function (request, response) {
            let club = jQuery("#clubId").val();
            let notifyField = '#user-feedback';
            response(get_player_details('name', request.term, club, notifyField));
        },
        select: function (event, ui) {
            if (ui.item.value === 'null') {
                ui.item.value = '';
            }
            let player = "#userName";
            let playerId = "#userId";
            let contactno = "#contactno";
            let contactemail = "#contactemail";
            jQuery(player).val(ui.item.value);
            jQuery(playerId).val(ui.item.playerId);
            jQuery(contactno).val(ui.item.contactno);
            jQuery(contactemail).val(ui.item.user_email);
        },
        change: function (event, ui) {
            let player = "#userName";
            let playerId = "#userId";
            let contactno = "#contactno";
            let contactemail = "#contactemail";
            setPlayerDetails(ui,player,playerId,contactno,contactemail);
        }
    });
}
function CaptainLookup() {
	jQuery('.teamcaptain').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = jQuery("#clubId").val();
			let fieldRef = this.element[0].id;
			let ref = fieldRef.substr(7);
			let notifyField = '#updateTeamResponse'.concat(ref);
			response(get_player_details('name', request.term, club, notifyField));
		},
		select: function (event, ui) {
			if (ui.item.value === 'null') {
				ui.item.value = '';
			}
			let captainInput = this.id;
			let ref = captainInput.substr(7);
			let player = "#".concat(captainInput);
			let playerId = "#captainId".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			jQuery(player).val(ui.item.value);
			jQuery(playerId).val(ui.item.playerId);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function (event, ui) {
			let captainInput = this.id;
			let ref = captainInput.substr(7);
			let player = "#".concat(captainInput);
			let playerId = "#captainId".concat(ref);
			let contactno = "#contactno".concat(ref);
			let contactemail = "#contactemail".concat(ref);
			setPlayerDetails(ui,player,playerId,contactno,contactemail);
		}
	});
}
function PopstateHandler() {
	// Handle forward/back buttons
	globalThis.addEventListener("popstate", (event) => {
		// If a state has been provided, we have a "simulated" page,
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
		let favouriteId = jQuery(this).data('favourite');
		let favouriteType = jQuery(this).data('type');
		let favouriteStatus = jQuery(this).data('status');
		let favourite_field = "#".concat(e.currentTarget.id);
		let notifyField = "#fav-msg-".concat(favouriteId);
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
				"type": favouriteType,
				"id": favouriteId,
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
	let liEventDetails = jQuery('#liEventDetails');
	liEventDetails.addClass('is-loading');
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
				liEventDetails.removeClass('is-loading');
			}
		} else {
			if (inputIsChecked) {
				Racketmanager.setEventPrice(eventId);
			} else {
				Racketmanager.clearPrice(eventId);
			}
			liEventDetails.removeClass('is-loading');
		}
	} else {
		liEventDetails.removeClass('is-loading');
	}
}
function PartnerLookup() {
	jQuery('.partner-name').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let partnerGender = jQuery("#partnerGender").val();
			let club = null;
			let notifyField = '#partner-feedback';
			response(get_player_details('name', request.term, club, notifyField, partnerGender));
		},
		select: function (event, ui) {
			selectPartnerName(ui);
		},
		change: function (event, ui) {
			changePartnerName(ui);
		}
	});
	jQuery('.partner-btm').autocomplete({
		minLength: 2,
		source: function (request, response) {
			let club = null;
			let partnerGender = jQuery("#partnerGender").val();
			let notifyField = '#partnerBTM-feedback';
			response(get_player_details('btm', request.term, club, notifyField, partnerGender));
		},
		select: function (event, ui) {
			selectPartnerName(ui);
		},
		change: function (event, ui) {
			changePartnerName(ui);
		}
	});
	jQuery('#partnerModal').on('hidden.bs.modal', function (e) {
		let eventId = jQuery(this).attr('data-event');
		if (eventId) {
			let partnerRef = '#partnerId-' + eventId;
			let partnerId = jQuery(partnerRef).val();
			if (!partnerId) {
				let eventRef = '#event-' + eventId;
				jQuery(eventRef).prop('checked', false);
				let target = jQuery(eventRef)[0];
				checkToggle(target, e);
			}
		}
	});
}
function selectPartnerName(ui) {
	let player = "#partner";
	let playerId = "#partnerId";
	let playerBTM = "#partnerBTM";
	if (ui.item.value === 'null') {
		ui.item.value = '';
	}
	jQuery(player).val(ui.item.name);
	jQuery(playerId).val(ui.item.playerId);
	jQuery(playerBTM).val(ui.item.btm);
}
function changePartnerName(ui) {
	let player = "#partner";
	let playerId = "#partnerId";
	let playerBTM = "#partnerBTM";
	if (ui.item === null) {
		jQuery(this).val('');
		jQuery(player).val('');
		jQuery(playerId).val('');
		jQuery(playerBTM).val('');
	} else {
		jQuery(player).val(ui.item.name);
		jQuery(playerId).val(ui.item.playerId);
		jQuery(playerBTM).val(ui.item.btm);
	}
}
function MatchDayChange() {
	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').on('change', function (e) {
		let league = jQuery('#league_id').val();
		league = league.replaceAll(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		league = league.replaceAll(/\s/g, "-"); // Replace space with a '-' symbol */
		let season = jQuery('#season').val();
		let matchday = jQuery('#match_day').val();
		if (matchday === -1) matchday = 0;
		let leagueLink = '/league/' + league.toLowerCase() + '/' + season + '/matches/day' + matchday + '/'
		let leagueId = jQuery('#leagueId').val();
		Racketmanager.tabDataLink(e, 'league', leagueId, season, leagueLink, matchday, 'matches');
		return false;  // Prevent default button behaviour
	});
}
function TournamentDateChange() {
	jQuery('#tournament-match-date-form #match_date').on('change', function (e) {
		let match_date = jQuery(`#match_date`).val().replaceAll(/[^A-Za-z0-9 -]/g, ''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		let tournament = jQuery(`#tournament_id`).val();
		tournament = tournament.replaceAll(/\s{2,}/g, ' '); // Replace multi spaces with a single space */
		tournament = tournament.replaceAll("-", "_"); // Replace space with a '_' symbol */
		tournament = tournament.replaceAll(/\s/g, "-"); // Replace space with a '_' symbol */
		let tournamentLink = '/tournament/' + tournament.toLowerCase() + '/matches/' + match_date + '/';
		let linkId = match_date;
		let linkType = 'matches';
		let tournamentId = jQuery('#tournamentId').val();
		Racketmanager.tabDataLink(e, 'tournament', tournamentId, null, tournamentLink, linkId, linkType)
		return false;  // Prevent default button behaviour
	});
}
let Racketmanager = {
    loadingModal: '#loadingModal',
};

Racketmanager.printScoreCard = function (e, matchId) {
	e.preventDefault();
	let matchCardWindow;
	let notifyField = '#feedback-' + matchId;
	jQuery(notifyField).hide();
	jQuery(notifyField).removeClass('message-success message-error');
	let styleSheetList = document.styleSheets;
	let head = '<html lang=""><head><title>Match Card</title>';
	for (let item of styleSheetList) {
		if (item.url !== null) head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
	}
	head += '</head>';
	let foot = '</body></html>';

    jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"matchId": matchId,
			"action": "racketmanager_match_card",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			matchCardWindow = globalThis.open("about:blank", "match_card", "popup, width=800,height=775");
			if (matchCardWindow) {
                matchCardWindow.document.head.innerHTML = head;
                matchCardWindow.document.body.innerHTML = response.data + foot;
			} else {
                jQuery(notifyField).text("Match Card not available - turn off pop blocker and retry");
                jQuery(notifyField).show();
                jQuery(notifyField).addClass('message-error');
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
	let splash = '#splash';
	let alert_id = jQuery('#matchAlert');
    let alert_response = '#matchAlertResponse';
	let use_alert = false;
	use_alert = alert_id.length !== 0;
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
	let winner = jQuery(".winner");
	winner.val("");
	winner.removeClass("winner");
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
				let updateResponse = jQuery("#updateResponse");
				updateResponse.show();
				updateResponse.addClass('message-success');
				updateResponse.html($message);
				updateResponse.delay(10000).fadeOut('slow');
			}
			let homepoints = $response[1];
			let formField = "#home_points";
			let fieldVal = homepoints;
			jQuery(formField).val(fieldVal);
			let awaypoints = $response[2];
			formField = "#away_points";
			fieldVal = awaypoints;
			jQuery(formField).val(fieldVal);
			let winner = $response[3];
			formField = '#match-status-' + winner;
			jQuery(formField).addClass('winner');
			jQuery(formField).val('W');
			let sets = Object.entries($response[4]);
			for (let set of sets) {
				let setNo = set[0];
				let teams = Object.entries(set[1]);
				for (let team of teams) {
					formField = '#set_' + setNo + '_' + team[0];
					fieldVal = team[1];
					jQuery(formField).val(fieldVal);
				}
			}
		},
		error: function (response) {
			let feedback;
			if (response.responseJSON) {
				let data = response.responseJSON.data;
				let message = data[0];
				if (message) {
					for (let errorMsg of data[1]) {
						message += '<br />' + errorMsg;
					}
					let errorFields = data[2];
					for (let errorField of errorFields) {
						let id = '#'.concat(errorField);
						jQuery(id).addClass("is-invalid");
					}
					feedback = message;
				} else {
					feedback = data.message + ' ' + data.file + ' ' + data.line;
				}
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
			let updateRubberResults = jQuery("#updateRubberResults");
			updateRubberResults.removeProp("disabled");
			updateRubberResults.removeClass("disabled");
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
	match_edit = match_status_link_id.length !== 0;
	let alert_id = jQuery('#matchAlert');
	let alert_response = '#matchAlertResponse';
	jQuery(alert_id).hide();
	jQuery(alert_id).removeClass('alert--success alert--warning alert--danger');
	jQuery(".is-invalid").removeClass("is-invalid");
	let splash = jQuery("#splash");
	splash.css('opacity', 1);
	splash.removeClass("d-none");
	splash.show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let data = response.data;
			let message = data.msg;
			let status = data.status;
			let rubbers = data.rubbers;
			let warnings = data.warnings;
			let alertClass = 'alert--' + status;
			jQuery(alert_id).addClass(alertClass);
			jQuery(alert_response).html(message);
			let rubberNo = 1;
			for (let r in rubbers) {
				let rubber = rubbers[r];
				let winner = rubber['winner'];
				let formField = '#match-status-' + rubberNo + '-' + winner;
				jQuery(formField).addClass('winner');
				jQuery(formField).val('W');
				for (let t in rubber['players']) { // home or away
					let team = rubber['players'][t];
					for (let p = 0; p < team.length; p++) {
						let player = team[p];
						let id = p + 1;
						let formField = '#' + t + 'player' + id + '_' + rubberNo;
						let fieldVal = player;
						jQuery(formField).val(fieldVal);
						formField = '#' + 'players_' + rubberNo + '_' + t + '_' + id;
						fieldVal = player;
						jQuery(formField).val(fieldVal);
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
			if (warnings) {
				for (let w in warnings) {
					let playerRef = '#' + w;
					jQuery(playerRef).addClass('is-invalid');
					let playerRefFeedback = playerRef + 'Feedback';
					jQuery(playerRefFeedback).html(warnings[w])
				}
			}
			Racketmanager.matchHeader(match_id, match_edit);
		},
		error: function (response) {
			let message;
			let data;
			if (response.status === 500) {
				message = Racketmanager.getMessageFromResponse(response);
			} else if ( response.responseJSON) {
				data = response.responseJSON.data;
				message = data.msg;
				if (data.err_msgs) {
					for (let errorMsg of data.err_msgs) {
						message += '<br />' + errorMsg;
					}
				}
				if (data.err_flds) {
					for (let errorField of data.err_flds) {
						let id = '#'.concat(errorField);
						jQuery(id).addClass("is-invalid");
					}
				}
			} else {
				message = response.statusText;
			}
			jQuery(alert_id).addClass('alert--danger');
			jQuery(alert_response).html(message);

		},
		complete: function () {
			jQuery(alert_id).show();
			let splash = jQuery('#splash');
			splash.css('opacity', 0);
			splash.hide();
			jQuery("#showMatchRubbers").show();
			let updateRubberResults = jQuery('#updateRubberResults');
			updateRubberResults.removeProp("disabled");
			updateRubberResults.removeClass("disabled");
		}
	});
};
Racketmanager.club_player_request = function () {
	let $form = jQuery('#playerRequestFrm').serialize();
	$form += "&action=racketmanager_club_player_request";
	let clubPlayerUpdateSubmit = jQuery("#clubPlayerUpdateSubmit");
	clubPlayerUpdateSubmit.hide();
	clubPlayerUpdateSubmit.addClass("disabled");
	let alertField = '#playerAddResponse';
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertField).hide();
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
			jQuery(alertField).addClass('alert--success');
			jQuery(alertTextField).html(response.data.msg);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			let clubPlayerUpdateSubmit = jQuery("#clubPlayerUpdateSubmit");
			clubPlayerUpdateSubmit.removeClass("disabled");
			clubPlayerUpdateSubmit.show();
			jQuery(alertField).show();
		}
	});
};
Racketmanager.clubPlayerRemove = function (link, gender) {
	let $form = jQuery(link).serialize();
	$form += "&action=racketmanager_club_players_remove";
	let alertField = '#playerDel' + gender + 'Response';
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertField).hide();
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
			jQuery(alertField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
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
	let alertField = "#teamUpdateResponse-".concat(event, "-", team);
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertField).hide();
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
			let captainNameField = '#captain-'.concat(event, "-", team);
			let captainName = jQuery(captainNameField).val();
			if (captainName) {
				let teamCaptainNameField = '#captain-name-'.concat(event, "-", team);
				jQuery(teamCaptainNameField).html(captainName);
			}
			let captainContactNoField = '#contactno-'.concat(event, "-", team);
			let captainContactNo = jQuery(captainContactNoField).val();
			if (captainContactNo) {
				let teamContactNoField = '#captain-contact-no-'.concat(event, "-", team);
				jQuery(teamContactNoField).html(captainContactNo);
			}
			let captainContactEmailField = '#contactemail-'.concat(event, "-", team);
			let captainContactEmail = jQuery(captainContactEmailField).val();
			if (captainContactEmail) {
				let teamContactEmailField = '#captain-contact-email-'.concat(event, "-", team);
				jQuery(teamContactEmailField).html(captainContactEmail);
			}
			jQuery(alertField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.handleAjaxError = function(response, alertTextField, alertField) {
	let message;
	let data;
	if (response.status === 500) {
		message = Racketmanager.getMessageFromResponse(response);
	} else if ( response.responseJSON) {
		data = response.responseJSON.data;
		message = data.msg;
		if (data.err_msgs) {
			for (let i = 0; i < data.err_flds.length; i++) {
				let formField = "#" + data.err_flds[i];
				jQuery(formField).addClass('is-invalid');
				formField = formField + 'Feedback';
				jQuery(formField).html(data.err_msgs[i]);
			}
		}
	} else {
		message = response.statusText;
	}
	jQuery(alertTextField).html(message);
	jQuery(alertField).addClass('alert--danger');

}
Racketmanager.updateClub = function (link) {

	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let submitButton = "#updateClubSubmit";
	$form += "&action=racketmanager_update_club";
	jQuery(submitButton).hide();
	let alertField = "#clubUpdateResponse";
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertField).hide();
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
			jQuery(alertField).addClass('alert--success');
			jQuery(alertTextField).html(response.data);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
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
	let alertField = '#playerUpdateResponse';
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertField).hide();
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
			let data = response.data;
			let alertClass;
			if (data.state) {
				alertClass = 'alert--' + data.state;
			} else {
				alertClass = 'alert--success';}
			jQuery(alertField).addClass(alertClass);
			jQuery(alertTextField).html(data.msg);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
		}
	});
	jQuery(submitButton).show();
};
Racketmanager.entryRequest = function (event, type) {
	event.preventDefault();
	let entryDetails = jQuery('#entry-details');
	entryDetails.addClass('is-loading');
	let alertField = '#entryAlert';
	jQuery(alertField).removeClass('alert--success alert--warning alert--info alert--danger');
	jQuery(alertField).hide();
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
			let msg;
			let msgType;
			if (Array.isArray(response.data)) {
				msg = response.data[0];
				msgType = response.data[1];
				if (response.data[2]) {
					let link = response.data[3];
					if (link) {
						globalThis.location = link;
					}
				}
			} else {
				msg = response.data;
				msgType = 'success';
			}
			let msgClass = 'alert--' + msgType;
			jQuery(alertField).addClass(msgClass);
			jQuery(alertTextField).html(msg);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
			jQuery("#acceptance").prop("checked", false);
			entryDetails.removeClass('is-loading');
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
		globalThis.location = link;
	}
};
Racketmanager.setMatchStatus = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let splashBock = '#splashBlockMatch';
	jQuery(splashBock).addClass('is-loading');
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
			let data = response.data;
			let scoreStatus = data.match_status;
			let statusMessages = Object.entries(data.status_message);
			let statusClasses = Object.entries(data.status_class);
			let numRubbers = data.num_rubbers;
			if (numRubbers) {
				for (let x = 1; x <= numRubbers; x++) {
					let rubberNumber = x;
					Racketmanager.setRubberStatusMessages(rubberNumber,statusMessages);
					Racketmanager.setRubberStatusClasses(rubberNumber,statusClasses);
					let matchStatusRef = '#match_status_' + rubberNumber;
					jQuery(matchStatusRef).attr('value', scoreStatus);
				}
			} else {
				for (let i in statusMessages) {
					let statusMessage = statusMessages[i];
					let teamRef = statusMessage[0];
					let teamMessage = statusMessage[1];
					let messageRef = '#match-message-' + teamRef;
					Racketmanager.setTeamMessage(messageRef, teamMessage);
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
			let modal = '#' + data.modal;
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, notifyField);
			jQuery(notifyField).show();
		},
		complete: function () {
			jQuery(splashBock).removeClass('is-loading');
		}
	});
}
Racketmanager.scoreStatusModal = function (event, rubber_id, rubber_number) {
	event.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let errorField = "#matchAlert";
    let errorResponseField = errorField + 'Response';
    jQuery(errorField).hide();
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
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
        }
	);
};
Racketmanager.setMatchRubberStatus = function (link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	let splashBock = '#splashBlockRubber';
	jQuery(splashBock).addClass('is-loading');
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
			let data = response.data;
			let rubberNumber = data.rubber_number;
			let scoreStatus = data.score_status;
			let statusMessages = Object.entries(data.status_message);
			let statusClasses = Object.entries(data.status_class);
			Racketmanager.setRubberStatusMessages(rubberNumber,statusMessages);
			Racketmanager.setRubberStatusClasses(rubberNumber,statusClasses);
			let modal = '#' + data.modal;
			let matchStatusRef = '#' + 'match_status_' + rubberNumber;
			jQuery(matchStatusRef).val(scoreStatus);
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, notifyField);
			jQuery(notifyField).show();
		},
		complete: function () {
			jQuery(splashBock).removeClass('is-loading');
		}
	});
}
Racketmanager.statusModal = function (event, match_id) {
	event.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let errorField = "#headerResponse";
    let errorResponseField = errorField + 'Response';
    jQuery(errorField).hide();
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
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
        }
	);
};
Racketmanager.matchOptions = function (event, match_id, option) {
	event.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let errorField = "#headerResponse";
    let errorResponseField = errorField + 'Response';
    jQuery(errorField).hide();
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
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
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
		alert_id_1 = '#matchAlert';
		alert_response_1 = '#matchAlertResponse';
	} else {
		alert_id_1 = '#matchOptionsAlert';
		alert_response_1 = '#alertMatchOptionsResponse';
	}
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_id_2 = '#matchDateAlert';
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
			let data = response.data;
			let message = data.msg;
			let modal = '#' + data.modal;
			let match_id = data.match_id;
			let matchDate = data.match_date;
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
			Racketmanager.handleAjaxError(response, alert_response_2, alert_id_2);
			jQuery(alert_id_2).show();
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
	alert_id_1 = jQuery('#matchAlert');
	alert_response_1 = '#matchAlertResponse';
	jQuery(alert_id_1).hide();
	jQuery(alert_id_1).removeClass('alert--success alert--warning alert--danger');
	let alert_id_2 = jQuery('#resetMatchAlert');
	jQuery(alert_id_2).hide();
	jQuery(alert_id_2).removeClass('alert--success alert--warning alert--danger');
	let alert_response_2 = '#alertResetMatchResponse';
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let data = response.data;
			let message = data.msg;
			let modal = '#' + data.modal;
			let match_id = data.match_id;
			let matchForm = 'form-match-' + match_id;
			Racketmanager.resetMatchScores(e, matchForm);
			if (!is_tournament) {
				Racketmanager.matchHeader(match_id);
			}
			jQuery(alert_id_1).show();
			jQuery(alert_id_1).addClass('alert--success');
			jQuery(alert_response_1).html(message);
			jQuery(modal).modal('hide')
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alert_response_2, alert_id_2);
			jQuery(alert_id_2).show();
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
			let data = response.data;
			let message = data.msg;
			let modal = '#' + data.modal;
			let match_id = data.matach_id;
			jQuery(alert_id_1).show();
			jQuery(alert_id_1).addClass('alert--success');
			jQuery(alert_response_1).html(message);
			jQuery(modal).modal('hide')
			Racketmanager.matchHeader(match_id);
			let newPath = data.link;
			let url = new URL(globalThis.location.href);
			let newURL = url.protocol + '//' + url.hostname + newPath;
			if (newPath !== "") {
				if (history.replaceState) {
					history.replaceState('', document.title, newURL.toString());
				}
			}
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alert_response_2, alert_id_2);
			jQuery(alert_id_2).show();
		},
		complete: function () {
		}
	});
}
Racketmanager.switchTab = function (elem) {
	let selectedTab = jQuery(elem).data('tabid').toLowerCase();
	let matches = jQuery('.match');
	let matchGroup = jQuery('.match-group');
	let matchList = jQuery('.match--list');
	let tabList = jQuery('#tab-list');
	let tabGrid = jQuery('#tab-grid');
	switch (selectedTab) {
		case 'tab-grid':
			matchGroup.addClass('match-group--grid');
			matches.removeClass('match--list');
			tabList.removeClass('active');
			matchList.removeClass('match--list');
			tabGrid.addClass('active');
			break;
		case 'tab-list':
			matchGroup.removeClass('match-group--grid');
			tabGrid.removeClass('active');
			matches.addClass("match--list");
			tabList.addClass('active');
			break;
	}
};
Racketmanager.getMessage = function (event, message_id) {
	event.preventDefault();
	let messageDisplay = '#messageDetailDisplay';
	jQuery(messageDisplay).addClass('is-loading');
	jQuery('.selected').removeClass('selected');
	let message_ref = '#message-summary-' + message_id;
	jQuery(message_ref).addClass('selected read');
	jQuery(message_ref).removeClass('unread');
	let notifyField = "#message_detail";
	jQuery(notifyField).removeClass('alert--success alert--warning alert--danger');
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	let errorField = '#messagesAlert';
	let errorResponse = '#messagesAlertResponse';
	jQuery(errorField).hide();

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"message_id": message_id,
			"action": "racketmanager_get_message",
			"security": ajax_var.ajax_nonce,
		},
		success: function (response) {
			let data = response.data;
			jQuery(notifyField).empty();
			jQuery(notifyField).html(data.output);
			if (data.status === '1') {
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
			Racketmanager.handleAjaxError(response, errorResponse, errorField);
			jQuery(errorField).show();
		},
		complete: function () {
			jQuery(messageDisplay).removeClass('is-loading');
		}
	});
};
Racketmanager.deleteMessage = function (event, message_id) {
	event.preventDefault();
	if (confirm('Are you sure you want to delete this message?') !== true) {
		return;
	}
	jQuery('.selected').removeClass('selected');
	let message_ref = '#message-summary-' + message_id;
	jQuery(message_ref).addClass('deleted');
	let notifyField = "#message_detail";
	jQuery(notifyField).removeClass('message-error');
	jQuery(notifyField).val("");
	jQuery(notifyField).hide();
	let errorField = '#messagesAlert';
	let errorResponse = '#messagesAlertResponse';
	jQuery(errorField).hide();

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
			Racketmanager.handleAjaxError(response, errorResponse, errorField);
			jQuery(errorField).show();
		},
		complete: function () {
		}
	});
};
Racketmanager.deleteMessages = function (event, link) {
	event.preventDefault();
	if (confirm('Are you sure you want to delete these messages?') !== true) {
		return;
	}
	let formId = '#'.concat(link.form.id);
	let form = jQuery(formId).serialize();
	form += "&action=racketmanager_delete_messages";
	jQuery('.selected').removeClass('selected');
	let notifyField = "#message_detail";
	let errorField = '#messagesAlert';
	let errorResponse = '#messagesAlertResponse';
	jQuery(errorField).hide();
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
			Racketmanager.handleAjaxError(response, errorResponse, errorField);
			jQuery(errorField).show();
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
Racketmanager.playerSearch = function (event) {
	event.preventDefault();
	let notifyBlock = "#searchResultsContainer";
	jQuery(notifyBlock).empty();
	let url = new URL(globalThis.location.href);
	let newURL = url.protocol + '//' + url.hostname + url.pathname;
	let search_string = jQuery('#search_string').val();
	if (search_string !== "") {
		search_string = encodeURI( search_string );
		let newUri = newURL + '?q=' + search_string;
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
				if (response.status === 401) {
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
						let formField = "#" + errorField[$i];
						jQuery(formField).addClass('is-invalid');
						formField = formField + 'Feedback';
						jQuery(formField).html(errorMsg[$i]);
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
Racketmanager.tabData = function (e, target, id, season, name, competitionType) {
	e.preventDefault();
	let tabContent = '#' + target + 'TabContent';
	jQuery(tabContent).addClass('is-loading');
	let $target = e.target;
	let tab = $target.getAttribute('aria-controls');
	let newPath = '';
	if (target === 'tournament') {
		newPath = '/tournament/' + name + '/';
	} else if (target === 'event') {
		newPath = '/' + competitionType + 's/' + name + '/' + season + '/';
	} else if (target === 'competition') {
		newPath = '/' + name + '/' + season + '/';
	} else if (target === 'league') {
		newPath = '/' + competitionType + '/' + name + '/' + season + '/';
	}
	if (newPath !== "") {
		let tabDataRef = '#' + tab;
		let url = new URL(globalThis.location.href);
		let newURL = url.protocol + '//' + url.hostname + newPath + tab + '/';
		if (newURL === url.toString()) {
            jQuery(tabContent).removeClass('is-loading');
		} else {
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
	let url = new URL(globalThis.location.href);
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
	let totalPrice = +competitionFee;
	for (const element of eventPrices) {
		let eventPrice = element;
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
	let eventsEnteredRef = '#eventsEntered';
	let eventsEntered = jQuery(eventsEnteredRef).val();
	let tournamentRef = '#tournamentId';
	let tournamentId = jQuery(tournamentRef).val();
	let playerRef = '#playerId';
	let playerId = jQuery(playerRef).val();
	let notifyField = "#partnerModal";
	let modal = 'partnerModal';
	jQuery(notifyField).val("");
	let action = 'racketmanager_tournament_withdrawal';
	jQuery(notifyField).val("");
	jQuery(notifyField).load(
							 ajax_var.url,
							 {
								 "tournamentId": tournamentId,
								 "playerId": playerId,
								 "eventsEntered": eventsEntered,
								 "modal": modal,
								 "action": action,
								 "security": ajax_var.ajax_nonce,
							 },
							 function ( response, status) {
								 if ( 'error' !== status ) {
									 jQuery('#liEventDetails').removeClass('is-loading');
									 jQuery(notifyField).show();
									 jQuery(notifyField).modal('show');
								 }
							 }
							 );
};
Racketmanager.login = function (e) {
	e.preventDefault();
	let	notifyField = "#login";
	jQuery(notifyField).css('opacity', 0.25);
	let alertTextField = '#loginAlertResponse';
	let alertField = '#loginAlert';
	jQuery(alertField).hide();
	jQuery(alertTextField).html();
	let userLogin = jQuery('#user_login').val();
	let userPass = jQuery('#user_pass').val();
	let redirectURL = jQuery('#redirect_to').val();
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: {
			"action": "racketmanager_login",
			"security": ajax_var.ajax_nonce,
			"log": userLogin,
			"pwd": userPass,
			"redirect_to": redirectURL,
		},
		success: function (response) {
			document.location.href = response.data;
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertTextField, alertField);
			jQuery(alertField).show();
		},
		complete: function () {
			jQuery(notifyField).css('opacity', 1);
		}
	});
};
Racketmanager.confirmTournamentWithdraw = function () {
	let modal = '#partnerModal';
	let tournamentRef = '#tournamentId';
	let tournamentId = jQuery(tournamentRef).val();
	let playerRef = '#playerId';
	let playerId = jQuery(playerRef).val();
	let eventsEnteredRef = 'input:checked.form-check--event';
	let eventsEntered = jQuery(eventsEnteredRef);
	let successField = "#entryAlert";
	let successFieldResponse = "#entryAlertResponse";
	let alertField = '#withdrawResponse';
	let alertResponseField = "#withdrawResponseText";
	jQuery(alertField).hide();
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertResponseField).val("");
	jQuery(successField).hide();
	jQuery(successField).removeClass('alert--success alert--warning alert--danger');
	jQuery(successFieldResponse).val("");
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
			jQuery(successField).addClass('alert--success');
			jQuery(successFieldResponse).html(response.data);
			Racketmanager.setTotalPrice();
			jQuery(modal).modal('hide');
			jQuery(successField).show();
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertResponseField, alertField);
			jQuery(alertField).show();
		},
		complete: function () {
		}
	});
};
Racketmanager.showTeamOrderPlayers = function (e) {
	e.preventDefault();
	let alertField = "#teamOrderAlert";
	jQuery(alertField).hide();
	let eventId = jQuery('#event_id').val();
	let clubId = jQuery('#club_id').val();
	if (clubId && eventId) {
		let notifyField = '#team-order-rubbers';
		jQuery(notifyField).hide();
		let loadingField = '#team-order-details';
		jQuery(loadingField).addClass('is-loading');
		let action = 'racketmanager_show_team_order_players';
		jQuery(notifyField).val("");
		jQuery(notifyField).load(
			ajax_var.url,
			{
				"eventId": eventId,
				"clubId": clubId,
				"action": action,
				"security": ajax_var.ajax_nonce,
			},
			function () {
				jQuery(notifyField).show();
				jQuery(loadingField).removeClass('is-loading');
				jQuery('#resetMatchScore').on('click', function (e) {
					Racketmanager.resetMatchScores(e, 'match');
				});
				jQuery('#setTeamButton').on('click', function (e) {
					let setTeam = this.dataset.setTeam;
					Racketmanager.validateTeamOrder(e, this, setTeam);
				});
				jQuery('#validateTeamButton').on('click', function (e) {
					Racketmanager.validateTeamOrder(e, this);
				});
			}
			);
	}
}
Racketmanager.validateTeamOrder = function( e, link, setTeam='' ) {
	e.preventDefault();
	let loadingField = '#team-order-details';
	jQuery(loadingField).addClass('is-loading');
	let notifyField = '#team-order-rubbers';
	jQuery(notifyField).hide();
	jQuery('.winner').removeClass('winner');
	jQuery('.loser').removeClass('loser');
	jQuery(".is-invalid").removeClass("is-invalid");
	let alertField = "#teamOrderAlert";
	let alertResponseField = "#teamOrderAlertResponse";
	jQuery(alertField).hide();
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertResponseField).val("");
	let formId = '#'.concat(link.form.id);
	let form = jQuery(formId).serialize();
	form += "&action=racketmanager_validate_team_order";
	form += "&security=";
	form += ajax_var.ajax_nonce;
	form += "&setTeam=";
	form += setTeam;
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: form,
		success: function (response) {
			let data = response.data;
			let updatedRubbers = data.rubbers;
			let rubberNo = 1;
			for (let r in updatedRubbers) {
				let rubber = updatedRubbers[r];
				let status = rubber['status'];
				let statusClass = rubber['status_class'];
				let formField = '#match-status-' + rubberNo;
				jQuery(formField).addClass(statusClass);
				jQuery(formField).val(status);
				formField = '#wtn_' + rubberNo;
				jQuery(formField).addClass(statusClass);
				jQuery(formField).val(rubber['wtn']);
				rubberNo++;
			}
			let msg = data.msg;
			jQuery(alertResponseField).html(msg);
			let valid = data.valid;
			let alertClass;
			if (valid) {
				alertClass = 'alert--success';
			} else {
				alertClass = 'alert--danger';
			}
			jQuery(alertField).addClass(alertClass);
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, alertResponseField, alertField);
		},
		complete: function () {
			jQuery(alertField).show();
			jQuery(notifyField).show();
			jQuery(loadingField).removeClass('is-loading');
		}
	});
}
Racketmanager.get_event_team_match_dropdown = function (teamId) {
	let eventId = jQuery('#event_id').val();
	if (eventId) {
		let notifyField = '#matches';
		jQuery(notifyField).hide();
		jQuery("#setTeamButton").hide();
		let action = 'racketmanager_get_event_team_match_dropdown';
		jQuery(notifyField).html("");
		jQuery(notifyField).load(
			ajax_var.url,
			{
				"eventId": eventId,
				"teamId": teamId,
				"action": action,
				"security": ajax_var.ajax_nonce,
			},
			function () {
				jQuery(notifyField).show();
			}
		);
	}
}
Racketmanager.teamEditModal = function (event, teamId, eventId) {
	event.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let modal = 'teamModal';
    let notifyField = "#" + modal;
    let errorField = "#rolesResponse";
    let errorResponseField = errorField + 'Text';
    jQuery(errorField).hide();
	jQuery(notifyField).val("");
	jQuery(notifyField).load(
		ajax_var.url,
		{
			"teamId": teamId,
			"eventId": eventId,
			"modal": modal,
			"action": "racketmanager_team_edit_modal",
			"security": ajax_var.ajax_nonce,
		},
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
        }
	);
};
Racketmanager.show_set_team_button = function () {
	jQuery("#setTeamButton").show();
};
Racketmanager.accountUpdate = function (e, link) {
	e.preventDefault();
	let alertField = "#userAlert";
	let alertResponseField = "#userAlertResponse";
	jQuery(alertField).hide();
	jQuery(alertField).removeClass('alert--success alert--warning alert--danger');
	jQuery(alertResponseField).val("");
	jQuery(".is-invalid").removeClass("is-invalid");
	let loadingField = '#accountUpdateModule';
	jQuery(loadingField).addClass('is-loading');
	let notifyField = '#memberAccountForm';
	jQuery(notifyField).hide();
	let formId = '#'.concat(link.form.id);
	let form = jQuery(formId).serialize();
	form += "&action=racketmanager_update_account";
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: form,
		success: function (response) {
			let data = response.data;
			let msg = data.msg;
			jQuery(alertResponseField).html(msg);
			let alertClass = 'alert--' + data.class;
			jQuery(alertField).addClass(alertClass);
		},
		error: function (response) {
			if (response.responseJSON) {
				if (response.status === 401) {
					let data = response.responseJSON.data;
					let message = data.msg;
					if (data.error) {
						let errorMsgs = data.err_msgs;
						let errorFields = data.err_flds;
						for (let $i = 0; $i < errorFields.length; $i++) {
							let formField = "#" + errorFields[$i];
							jQuery(formField).addClass('is-invalid');
							formField = formField + 'Feedback';
							jQuery(formField).html(errorMsgs[$i]);
						}
					}
					jQuery(alertResponseField).html(message);
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
			jQuery(alertField).show();
			jQuery(notifyField).show();
			jQuery(loadingField).removeClass('is-loading');
		}
	});
};
Racketmanager.setRubberStatusMessages = function(rubberNumber,statusMessages) {
	for (let i in statusMessages) {
		let statusMessage = statusMessages[i];
		let teamRef = statusMessage[0];
		let teamMessage = statusMessage[1];
		let messageRef = '#match-message-' + rubberNumber + '-' + teamRef;
		Racketmanager.setTeamMessage(messageRef, teamMessage);
	}
}
Racketmanager.setRubberStatusClasses = function(rubberNumber,statusClasses) {
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
}
Racketmanager.setTeamMessage = function(messageRef, teamMessage) {
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
Racketmanager.getMessageFromResponse = function(response) {
	let message;
	let data;
	if ( response.responseJSON) {
		data = response.responseJSON.data;
		message = data.message;
		if (data.file) {
			message = message.concat(' ' + data.file + ' ' + data.line);
		}
	} else {
		message = response.statusText;
	}
	return message;
}
Racketmanager.clubRoleModal = function (e, clubRoleId) {
    e.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let modal = 'clubRoleModal';
    let notifyField = "#" + modal;
    let errorField = "#rolesResponse";
    let errorResponseField = errorField + 'Text';
    jQuery(errorField).hide();
    jQuery(notifyField).val("");
    jQuery(notifyField).load(
        ajax_var.url,
        {
            "clubRoleId": clubRoleId,
            "modal": modal,
            "action": "racketmanager_club_role_modal",
            "security": ajax_var.ajax_nonce,
        },
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
        }
    );
};
Racketmanager.setClubRole = function (e, link) {
    let formId = '#'.concat(link.form.id);
    let $form = jQuery(formId).serialize();
    $form += "&action=racketmanager_set_club_role";
    let clubRoleAlert = '#clubRoleResponse';
    let clubRoleResponse = '#clubRoleResponseText';
    jQuery(clubRoleAlert).hide();
    jQuery(clubRoleResponse).removeClass('alert--success alert--warning alert--danger');
    jQuery(".is-invalid").removeClass("is-invalid");

    jQuery.ajax({
        url: ajax_var.url,
        type: "POST",
        data: $form,
        success: function (response) {
            let data = response.data;
            let message = data.msg;
            let msgStatus = data.status;
            let alertClass = 'alert--' + msgStatus;
            jQuery(clubRoleAlert).show();
            jQuery(clubRoleAlert).addClass(alertClass);
            jQuery(clubRoleResponse).html(message);
        },
        error: function (response) {
            Racketmanager.handleAjaxError(response, clubRoleResponse, clubRoleAlert);
            jQuery(clubRoleAlert).show();
        },
        complete: function () {
        }
    });
}
Racketmanager.POModal = function (e, invoiceId) {
	e.preventDefault();
    let loadingModal = this.loadingModal;
    jQuery(loadingModal).modal('show');
    let errorField = "#POUpdateResponse";
    let errorResponseField = errorField + 'Text';
    jQuery(errorField).hide();
	let modal = 'POModal';
    let notifyField = "#" + modal;
    jQuery(notifyField).val("");
    jQuery(notifyField).load(
		ajax_var.url,
		{
			"invoiceId": invoiceId,
			"modal": modal,
			"action": "racketmanager_purchase_order_modal",
			"security": ajax_var.ajax_nonce,
		},
        function (response, status) {
            jQuery(loadingModal).modal('hide');
            if ( 'error' === status ) {
                let data = JSON.parse(response);
                jQuery(errorResponseField).html(data.message);
                jQuery(errorField).show();
            } else {
                jQuery(notifyField).show();
                jQuery(notifyField).modal('show');
            }
        }
	);
};
Racketmanager.setPurchaseOrder = function (e, link) {
	let formId = '#'.concat(link.form.id);
	let $form = jQuery(formId).serialize();
	$form += "&action=racketmanager_set_purchase_order";
	let invoiceAlertId = '#invoiceAlert';
	let invoiceResponse = '#invoiceResponse';
	jQuery(invoiceAlertId).hide();
	jQuery(invoiceAlertId).removeClass('alert--success alert--warning alert--danger');
	let POAlertId = '#POUpdateResponse';
	jQuery(POAlertId).hide();
	jQuery(POAlertId).removeClass('alert--success alert--warning alert--danger');
	let POResponse = '#POUpdateResponseText';
	jQuery(".is-invalid").removeClass("is-invalid");

	jQuery.ajax({
		url: ajax_var.url,
		type: "POST",
		data: $form,
		success: function (response) {
			let data = response.data;
			let message = data.msg;
			let modal = '#' + data.modal;
			let invoice = data.invoice;
			jQuery(invoiceAlertId).show();
			jQuery(invoiceAlertId).addClass('alert--success');
			jQuery(invoiceResponse).html(message);
			jQuery(modal).modal('hide')
			if (invoice) {
				jQuery('#invoiceDetails').html(invoice);
			}
		},
		error: function (response) {
			Racketmanager.handleAjaxError(response, POResponse, POAlertId);
			jQuery(POAlertId).show();
		},
		complete: function () {
		}
	});
}

function activaTab(tab) {
	jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
	jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
function get_player_details(type, name, club = null, notifyField = null, partnerGender = null) {
	let response = '';
	jQuery.ajax({
		type: 'POST',
		datatype: 'json',
		url: ajax_var.url,
		async: false,
		data: {
			"name": name,
			"type": type,
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
	return new Intl.NumberFormat(locale_var.locale, {
		style: 'currency',
		currency: locale_var.currency
	}).format(amount);
}
function createPaymentRequest(tournamentEntry,invoiceId, callback) {
	let output;
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
