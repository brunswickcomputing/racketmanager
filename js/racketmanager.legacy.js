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
