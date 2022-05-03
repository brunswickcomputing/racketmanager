var $mathCardWindow;
jQuery(document).ready(function($) {
	jQuery("tr.match-rubber-row").slideToggle('fast','linear');
	jQuery ("i", "td.angle-dir", "tr.match-row").toggleClass("angle-right angle-down");

	jQuery("tr.match-row").click(function(e){
		jQuery(this).next("tr.match-rubber-row").slideToggle('0','linear');
		jQuery(this).find("i.angledir").toggleClass("angle-right angle-down");
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_archive').submit(function() {
		var league = jQuery('#league_id').val(); //
		var season = jQuery('#season').val();

		var cleanUrl = window.location.protocol + '//' + window.location.host + '/leagues/' + league.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_competititon_archive').submit(function() {
		var pagename = jQuery('#pagename').val();
		var season = jQuery('#season').val();

		var cleanUrl = window.location.protocol + '//' + window.location.host + '/' + pagename.toLowerCase() + '/' + season + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	/* Friendly URL rewrite */
	jQuery('#racketmanager_match_day_selection').submit(function() {
		var league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 -]/g,''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		league = league.replace(/\s{2,}/g,' '); // Replace multi spaces with a single space */
		league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */
		var season = jQuery('#season').val();
		var matchday = jQuery('#match_day').val();
		if (matchday == -1) matchday = 0;
		var team = jQuery('#team_id').val();
		team = team.replace(/\s/g, "-"); // Replace space with a '-' symbol */

		var cleanUrl = window.location.protocol + '//' + window.location.host + '/leagues/' + league.toLowerCase() + '/' + season + '/day' + matchday + '/' + team + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	/* Friendly URL rewrite */
	jQuery('#racketmanager_winners').submit(function() {
		var selection = jQuery(`#selection`).val().replace(/[^A-Za-z0-9 -]/g,''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		selection = selection.replace(/\s{2,}/g,' '); // Replace multi spaces with a single space */
		selection = selection.replace(/\s/g, "_"); // Replace space with a '-' symbol */
		var competitionSeason = jQuery(`#competitionSeason`).val();
		var competitionType = jQuery(`#competitionType`).val();

		var cleanUrl = window.location.protocol + '//' + window.location.host + '/' + competitionType + 's/' + competitionSeason + '/winners/' + selection.toLowerCase() + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_orderofplay').submit(function() {
		var tournament = jQuery(`#tournament`).val().replace(/[^A-Za-z0-9 -]/g,''); // Remove unwanted characters, only accept alphanumeric, '-' and space */
		tournament = tournament.replace(/\s{2,}/g,' '); // Replace multi spaces with a single space */
		tournament = tournament.replace(/\s/g, "_"); // Replace space with a '-' symbol */
		var season = jQuery(`#season`).val();

		var cleanUrl = window.location.protocol + '//' + window.location.host + '/tournaments/' + season + '/order-of-play/' + tournament.toLowerCase() + '/' ;
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});
	jQuery('#racketmanager_daily_matches').submit(function() {
		var matchDate = jQuery(`#match_date`).val();
		var cleanUrl = window.location.protocol + '//' + window.location.host + '/leagues/daily-matches/' + matchDate + '/';
		window.location = cleanUrl;

		return false;  // Prevent default button behaviour
	});

	jQuery('.teamcaptain').autocomplete({
		minLength: 2,
		source: function(name, response) {
			var affiliatedClub = jQuery("#affiliatedClub").val();

			jQuery.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"affiliatedClub": affiliatedClub,
				"action": "racketmanager_getCaptainName"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			var captaininput = this.id;
			var ref = captaininput.substr(7);
			var captain = "#".concat(captaininput);
			var captainId = "#captainId".concat(ref);
			var contactno = "#contactno".concat(ref);
			var contactemail = "#contactemail".concat(ref);
			jQuery(captain).val(ui.item.value);
			jQuery(captainId).val(ui.item.id);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function(event, ui) {
			var captaininput = this.id;
			var ref = captaininput.substr(7);
			var captain = "#".concat(captaininput);
			var captainId = "#captainid".concat(ref);
			var contactno = "#contactno".concat(ref);
			var contactemail = "#contactemail".concat(ref);
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
		source: function(name, response) {
			var affiliatedClub = jQuery("#clubId").val();

			jQuery.ajax({
				type: 'POST',
				datatype: 'json',
				url: RacketManagerAjaxL10n.requestUrl,
				data: {"name": name,
				"affiliatedClub": affiliatedClub,
				"action": "racketmanager_getCaptainName"},
				success: function(data) {
					response(JSON.parse(data));
				}
			});
		},
		select: function(event, ui) {
			var captain = "#matchSecretaryName";
			var captainId = "#matchSecretaryId";
			var contactno = "#matchSecretaryContactNo";
			var contactemail = "#matchSecretaryEmail";
			jQuery(captain).val(ui.item.value);
			jQuery(captainId).val(ui.item.id);
			jQuery(contactno).val(ui.item.contactno);
			jQuery(contactemail).val(ui.item.user_email);
		},
		change: function(event, ui) {
			var captain = "#matchSecretaryName";
			var captainId = "#matchSecretaryId";
			var contactno = "#matchSecretaryContactNo";
			var contactemail = "#matchSecretaryEmail";
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
		var input=jQuery(this).parent().find('.password');
		input.attr('type', 'text');
	}, function () {
		jQuery('.password').attr('type', 'password');
		var input=jQuery(this).parent().find('.password');
		input.attr('type', 'password');
	});

	jQuery(":checkbox").click(function (event) {
		var $target = event.target;

		// If a checkbox with aria-controls, handle click
		var isCheckbox = $target.getAttribute('type') === 'checkbox';
		var hasAriaControls = $target.getAttribute('aria-controls');
		if (isCheckbox && hasAriaControls) {
			var $target2 = this.parentNode.parentNode.querySelector('#' + $target.getAttribute('aria-controls'));

			if ($target2 && $target2.classList.contains('form-checkboxes__conditional')) {
				var inputIsChecked = $target.checked;

				$target2.setAttribute('aria-expanded', inputIsChecked);
				$target2.classList.toggle('form-checkboxes__conditional--hidden', !inputIsChecked);
			}
		}
	});

	jQuery('select.cupteam').on('change', function (e) {
		team = this.value;
		competition = this.name;
		competition = competition.substring(5,competition.length-1);

		jQuery.ajax({
			type: 'POST',
			datatype: 'json',
			url: RacketManagerAjaxL10n.requestUrl,
			data: {"team": team,
			"competition": competition,
			"action": "racketmanager_get_team_info"},
			success: function(data) {
				response = jQuery.parseJSON(data);
				var captaininput = "captain-".concat(competition);
				var ref = captaininput.substr(7);
				var captain = "#".concat(captaininput);
				var captainId = "#captainId".concat(ref);
				var contactno = "#contactno".concat(ref);
				var contactemail = "#contactemail".concat(ref);
				var matchday = "#matchday".concat(ref);
				var matchtime = "#matchtime".concat(ref);
				jQuery(captain).val(response.captain);
				jQuery(captainId).val(response.captainid);
				jQuery(contactno).val(response.contactno);
				jQuery(contactemail).val(response.user_email);
				jQuery(matchday).val(response.match_day);
				jQuery(matchtime).val(response.match_time);

			}
		});
	});
	jQuery('[data-js=add-favourite]').click(function(e){
		e.preventDefault();
		var favouriteid = $(this).data('favourite');
		var favouritetype = $(this).data('type');
		var favourite_field = "#".concat(e.currentTarget.id);
		var message_field = "#fav-msg-".concat(favouriteid);

		jQuery.ajax({
			url:RacketManagerAjaxL10n.requestUrl,
			type: "POST",
			data: {"type": favouritetype,
			"id": favouriteid,
			"action": "racketmanager_add_favourite"},
			success: function(response) {
				var $response = jQuery.parseJSON(response);
				var $message = $response[1];
				var $action = $response[0];
				if ( $action == 'del' ) {
					jQuery(favourite_field).find('i').removeClass('fav-icon-svg-selected');
				} else if ( $action == 'add' ) {
					jQuery(favourite_field).find('i').addClass('fav-icon-svg-selected');
				}
				jQuery(message_field).show();
				jQuery(message_field).addClass('message-success');
				jQuery(message_field).html($message);
				jQuery(message_field).delay(10000).fadeOut('slow');
			},
			error: function() {
				alert("Ajax error on adding favourite");
			}
		}) ;
	});
});

var Racketmanager = new Object();

Racketmanager.setMatchBox = function( requestURL, curr_index, operation, element, league_id, match_limit, widget_number, season, group, home_only, date_format ) {
	var ajax = new sack(requestURL);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "racketmanager_get_match_box" );
	ajax.setVar( "widget_number", widget_number );
	ajax.setVar( "current", curr_index );
	ajax.setVar( "season", season );
	ajax.setVar( "group", group );
	ajax.setVar( "operation", operation );
	ajax.setVar( "element", element );
	ajax.setVar( "league_id", league_id );
	ajax.setVar( "match_limit", match_limit );
	ajax.setVar( "home_only", home_only );
	ajax.setVar( "date_format", date_format );
	ajax.onError = function() { alert('Ajax error'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
}

Racketmanager.printScoreCard = function(e, link) {

	e.preventDefault();
	var matchId = jQuery(link).attr('id');
	var matchtype = jQuery(link).attr('type');
	if (matchtype == 'player') {
		var ajaxAction = 'racketmanager_matchcard_player';
	} else {
		var ajaxAction = 'racketmanager_view_rubbers';
	}
	var styleSheetList = document.styleSheets;
	var $head = '<html><head><title>Match Card</title>';
	for (var item of styleSheetList) {
		if (item.url != 'null') $head += '<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">';
	};
	$head += '</head>';
	var $foot = '</body></html>';
	var $content = '';

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {"matchId": matchId,
		"action": ajaxAction},
		success: function($response) {
			var printOne = $response;
			if (!$mathCardWindow || $mathCardWindow.closed) {
				$mathCardWindow = window.open("about:blank","_blank","width=800,height=660");
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
		error: function() {
			alert("Ajax error on getting rubbers");
		}
	}) ;
};
Racketmanager.closeMatchModal = function(link) {
	jQuery("#modalMatch").hide();
};
Racketmanager.showRubbers = function(e, matchId) {

	e.preventDefault();
	jQuery("#showMatchRubbers").empty();
	var myModal = new bootstrap.Modal(document.getElementById('modalMatch'), {
	  keyboard: true,	backdrop: true, focus: true
	})
	myModal.show()
	jQuery("#viewMatchRubbers").show();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {"matchId": matchId,
		"action": "racketmanager_show_rubbers"},
		success: function(response) {
			jQuery("#showMatchRubbers").empty();
			jQuery("#showMatchRubbers").html(response);
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
		},
		error: function() {
			alert("Ajax error on getting rubbers");
		}
	}) ;
};
Racketmanager.showMatch = function(matchId) {

	jQuery("#showMatchRubbers").empty();
	var myModal = new bootstrap.Modal(document.getElementById('modalMatch'), {
	  keyboard: true,	backdrop: false, focus: true
	})
	myModal.show()
	jQuery("#viewMatchRubbers").show();
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: {"matchId": matchId,
		"action": "racketmanager_show_match"},
		success: function(response) {
			jQuery("#showMatchRubbers").empty();
			jQuery("#showMatchRubbers").html(response);
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
		},
		error: function() {
			alert("Ajax error on getting match");
		}
	}) ;
};
Racketmanager.updateMatchResults = function(link) {

	var $match = document.getElementById('current_match_id');
	var $matchId = $match.value;
	var $form = jQuery('#match-view').serialize();
	$form += "&action=racketmanager_update_match";
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			jQuery("#UpdateResponse").show();
			jQuery("#UpdateResponse").text($message);
			var $homepoints = $response[1];
			var $formfield = "#home_points";
			var $fieldval = $homepoints;
			jQuery($formfield).val($fieldval);
			var $awaypoints = $response[2];
			var $formfield = "#away_points";
			var $fieldval = $awaypoints;
			jQuery($formfield).val($fieldval);
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
		},
		error: function() {
			alert("Ajax error on updating match");
		}
	}) ;
	jQuery("#updateRubberResults").removeProp("disabled");
	jQuery("#updateRubberResults").removeClass("disabled");
};
Racketmanager.disableRubberUpdate = function() {

	jQuery("#match-rubbers select").prop("disabled", "true");
	jQuery("#match-rubbers input").prop("readonly", "true");
	jQuery("#updateRubber").val("confirm");
};
Racketmanager.updateResults = function(link) {

	var selects = document.getElementById('match-rubbers').getElementsByTagName('select');
	var values = [];
	for(i=0;i<selects.length;i++) {
		var select = selects[i];

	}
	var $match = document.getElementById('current_match_id');
	var $matchId = $match.value;
	var $form = jQuery('#match-rubbers').serialize();
	$form += "&action=racketmanager_update_rubbers";
	jQuery("#updateRubberResults").prop("disabled", "true");
	jQuery("#updateRubberResults").addClass("disabled");
	jQuery("#splash").css('opacity', 1);
	jQuery("#splash").show();
	jQuery("#showMatchRubbers").hide();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			jQuery("#UpdateResponse").show();
			jQuery("#UpdateResponse").text($message);
			var $homepoints = $response[1];
			var $matchhome = 0;
			var $matchaway = 0;
			for ( var i in $homepoints) {
				var $formfield = "#home_points\\["+i+"\\]";
				var $fieldval = $homepoints[i];
				jQuery($formfield).val($fieldval);
				$matchhome  = +$matchhome + +$homepoints[i];
			}
			var $awaypoints = $response[2];
			for ( var i in $awaypoints) {
				var $formfield = "#away_points\\["+i+"\\]";
				var $fieldval = $awaypoints[i];
				jQuery($formfield).val($fieldval);
				$matchaway  = +$matchaway + +$awaypoints[i];
			}
			jQuery("#splash").css('opacity', 0);
			jQuery("#splash").hide();
			jQuery("#showMatchRubbers").show();
			jQuery("#updateRubberResults").removeAttr("disabled");
			jQuery("#updateRubberResults").removeClass("disabled");
		},
		error: function() {
			alert("Ajax error on updating rubbers");
		}
	}) ;
};
Racketmanager.rosterRequest = function(link) {

	var $form = jQuery('#rosterRequestFrm').serialize();
	$form += "&action=racketmanager_roster_request";
	jQuery("#updateResponse").val("");
	jQuery("#rosterUpdateSubmit").hide();
	jQuery("#rosterUpdateSubmit").addClass("disabled");
	jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
	jQuery(".is-invalid").removeClass("is-invalid");
	jQuery(".invalidFeedback").val("");

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			var $error = $response[1];
			if ($error === true) {
				var $errorField = $response[2];
				var $errorMsg = $response[3];
				for ( var $i=0; $i < $errorField.length; $i++) {
					var $id = '#'.concat($errorField[$i]);
					jQuery($id).addClass("is-invalid");
					var $id2 = '#'.concat($errorField[$i],'Feedback');
					jQuery($id2).html($errorMsg[$i]);
				}
				jQuery("#updateResponse").addClass("message-error");
				jQuery("#updateResponse").show();
				jQuery("#updateResponse").html($message);
			} else {
				jQuery("#firstName").val("");
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
			jQuery("#rosterUpdateSubmit").removeClass("disabled");
		},
		error: function() {
			alert("Ajax error on player add");
		}
	}) ;
	jQuery("#rosterUpdateSubmit").show();
};
Racketmanager.rosterRemove = function(link) {

	var $form = jQuery(link).serialize();
	$form += "&action=racketmanager_roster_remove";

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function() {
			jQuery(link).find('tr').each(function () {
				var row = jQuery(this);
				if (row.find('input[type="checkbox"]').is(':checked')) {
					var rowId = "#"+row.attr('id');
					jQuery(rowId).remove();
				}
			});
		},
		error: function() {
			alert("Ajax error on player removal");
		}
	}) ;
};
Racketmanager.teamUpdate = function(link) {

	var formId = '#'.concat(link.form.id);
	var $form = jQuery(formId).serialize();
	var competition = link.form[3].value;
	var team = link.form[2].value;
	var updateResponse = "#updateTeamResponse-".concat(competition,"-",team);
	var submitButton = "#teamUpdateSubmit-".concat(competition,"-",team);
	$form += "&action=racketmanager_team_update";
	jQuery(updateResponse).val("");
	jQuery(updateResponse).hide();
	jQuery(submitButton).hide();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		async: false,
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			jQuery(updateResponse).show();
			jQuery(updateResponse).addClass("message-success");
			jQuery(updateResponse).html($message);
			jQuery(updateResponse).delay(10000).fadeOut('slow');
		},
		error: function() {
			alert("Ajax error on team update");
		}
	}) ;
	jQuery(submitButton).show();
};
Racketmanager.updateClub = function(link) {

	var formId = '#'.concat(link.form.id);
	var $form = jQuery(formId).serialize();
	var updateResponse = "#updateClub";
	var submitButton = "#updateClubSubmit";
	$form += "&action=racketmanager_update_club";
	jQuery(updateResponse).val("");
	jQuery(submitButton).hide();

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		async: false,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			jQuery(updateResponse).addClass("message-success");
			jQuery(updateResponse).html($message);
			jQuery(updateResponse).delay(10000).fadeOut('slow');
			jQuery(submitButton).show();
		},
		error: function() {
			alert("Ajax error on club update");
			jQuery(submitButton).show();
		}
	}) ;
};
Racketmanager.tournamentEntryRequest = function(link) {

	var $form = jQuery('#form-tournamententry').serialize();
	$form += "&action=racketmanager_tournament_entry";
	jQuery("#tournamEntentryResponse").val("");
	jQuery("#tournamentEntrySubmit").hide();
	jQuery("#tournamentEntrySubmit").addClass("disabled");
	jQuery("#tournamentEntryResponse").removeClass('message-error');
	jQuery("#tournamentEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			var $error = $response[1];
			var $errorMsg = $response[2];
			var $errorField = $response[3];
			if ($error === true) {
				jQuery("#tournamentEntryResponse").addClass('message-error');
				for ( var errorMsg of $response[2] ) {
					$message += '<br />' + errorMsg;
				}
				for ( var errorField of $response[3] ) {
					var $id = '#'.concat(errorField);
					jQuery($id).parents('.form-group').addClass('field-error');
				}
				jQuery("#tournamentEntryResponse").html($message);
			} else {
				jQuery("#tournamentEntryResponse").show();
				jQuery("#tournamentEntryResponse").addClass('message-success');
				jQuery("#tournamentEntryResponse").html($message);
				jQuery("#tournamentEntryResponse").delay(10000).fadeOut('slow');
			}
		},
		error: function() {
			alert("Ajax error on tournament entry");
		}
	}) ;
	jQuery("#tournamentEntrySubmit").show();
};
Racketmanager.cupEntryRequest = function(link) {

	var $form = jQuery('#form-cupentry').serialize();
	$form += "&action=racketmanager_cup_entry";
	jQuery("#cupentryResponse").val("");
	jQuery("#cupEntrySubmit").hide();
	jQuery("#cupEntrySubmit").addClass("disabled");
	jQuery("#cupEntryResponse").removeClass('message-error');
	jQuery("#cupEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			var $error = $response[1];
			var $errorMsg = $response[2];
			var $errorField = $response[3];
			if ($error === true) {
				jQuery("#cupEntryResponse").addClass('message-error');
				for ( var errorMsg of $response[2] ) {
					$message += '<br />' + errorMsg;
				}
				for ( var errorField of $response[3] ) {
					var $id = '#'.concat(errorField);
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
		error: function() {
			alert("Ajax error on cup entry");
		}
	}) ;
	jQuery("#cupEntrySubmit").show();
};
Racketmanager.leagueEntryRequest = function(link) {

	var $form = jQuery('#form-leagueentry').serialize();
	$form += "&action=racketmanager_league_entry";
	jQuery("#leagueentryResponse").val("");
	jQuery("#leagueEntrySubmit").hide();
	jQuery("#leagueEntrySubmit").addClass("disabled");
	jQuery("#leagueEntryResponse").removeClass('message-error');
	jQuery("#leagueEntryResponse").removeClass('message-success');

	jQuery.ajax({
		url:RacketManagerAjaxL10n.requestUrl,
		async: false,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
			var $message = $response[0];
			var $error = $response[1];
			var $errorMsg = $response[2];
			var $errorField = $response[3];
			jQuery( "#acceptance" ).prop( "checked", false );
			if ($error === true) {
				jQuery("#leagueEntryResponse").addClass('message-error');
				for ( var errorMsg of $response[2] ) {
					$message += '<br />' + errorMsg;
				}
				for ( var errorField of $response[3] ) {
					var $id = '#'.concat(errorField);
					jQuery($id).parents('.form-group').addClass('field-error');
				}
				jQuery("#leagueEntryResponse").html($message);
			} else {
				jQuery("#leagueEntryResponse").show();
				jQuery("#leagueEntryResponse").addClass('message-success');
				jQuery("#leagueEntryResponse").html($message);
				jQuery("#leagueEntryResponse").delay(10000).fadeOut('slow');
			}
		},
		error: function() {
			alert("Ajax error on league entry");
		}
	}) ;
	jQuery("#leagueEntrySubmit").show();
};
function activaTab(tab) {
		jQuery('.nav-tabs button[data-bs-target="#' + tab + '"]').tab('show');
		jQuery('.nav-pills button[data-bs-target="#' + tab + '"]').tab('show');
}
