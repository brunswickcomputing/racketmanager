jQuery(function () {
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
})
jQuery(document).ajaxComplete(function () {
	PartnerLookup();
	PopstateHandler();
});
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
var Racketmanager = (window.Racketmanager = window.Racketmanager || {});
Racketmanager.loadingModal = Racketmanager.loadingModal || '#loadingModal';

Racketmanager.updateMatchResults = Racketmanager.updateMatchResults || function (link) {
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


// ----- Stage C: Legacy Neutralizer (auto-generated) -----
// If not explicitly disabled, neutralize migrated legacy functions to avoid collisions with modular code.
(function(){
    try {
        var disable = !(globalThis && globalThis.RACKETMANAGER_DISABLE_LEGACY === false);
        if (!disable) return; // Legacy enabled explicitly (e.g., during rollback)
        globalThis.Racketmanager = globalThis.Racketmanager || {};
        var warn = function(name){
            return function(){
                try { console.warn('Racketmanager.' + name + ' is disabled; use modular delegated handlers instead.'); } catch(_){}
            };
        };
        var fns = [
            'printScoreCard', 'playerSearch', 'partnerModal', 'partnerSave',
            'setEventPrice', 'clearPrice', 'setTotalPrice',
            'setPaymentStatus', 'withdrawTournament', 'confirmTournamentWithdraw',
            'showTeamOrderPlayers', 'validateTeamOrder', 'get_event_team_match_dropdown', 'teamEditModal', 'show_set_team_button',
            'clubRoleModal', 'setClubRole', 'entryRequest',
            'updateMatchResults', 'setMatchDate', 'resetMatchResult', 'resetMatchScores', 'matchHeader', 'matchOptions', 'switchHomeAway',
            'viewMatch', 'switchTab', 'getMessageFromResponse'
        ];
        for (var i=0; i<fns.length; i++) {
            var n = fns[i];
            if (typeof globalThis.Racketmanager[n] === 'function') {
                globalThis.Racketmanager[n] = warn(n);
            }
        }
    } catch(_) { /* no-op */ }
})();
// ----- End Stage C Neutralizer -----
