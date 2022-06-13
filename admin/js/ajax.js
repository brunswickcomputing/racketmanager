var Racketmanager = new Object();

Racketmanager.getLeagueDropdown = function(competition_id) {

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"competition_id": competition_id,
    "action": "racketmanager_get_league_dropdown"},
    success: function(response) {
      jQuery("#leagues").empty();
      jQuery("#leagues").html(response);
    },
    error: function() {
      alert("Ajax error on getting leagues");
    }
  }) ;
};

Racketmanager.getSeasonDropdown = function(league_id) {

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"league_id": league_id,
    "action": "racketmanager_get_season_dropdown"},
    success: function(response) {
      jQuery("#seasons").empty();
      jQuery("#seasons").html(response);
    },
    error: function() {
      alert("Ajax error on getting seasons dropdown");
    }
  }) ;
};

Racketmanager.getMatchDropdown = function(league_id, season) {

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"league_id": league_id,
    "season": season,
    "action": "racketmanager_get_match_dropdown"},
    success: function(response) {
      jQuery("#matches").empty();
      jQuery("#matches").html(response);
    },
    error: function() {
      alert("Ajax error on getting matches dropdown");
    }
  }) ;
};

Racketmanager.saveAddPoints = function(points, team_id, league_id, season) {
  jQuery('#loading_' + team_id).css("display", "inline");
  jQuery('#loading_' + team_id).html("<img src='"+RacketManagerAjaxL10n.pluginUrl+"/images/loading.gif' />");

  var ajax = new sack(RacketManagerAjaxL10n.requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( "action", "racketmanager_save_add_points" );
  ajax.setVar( "team_id", team_id );
  ajax.setVar( "league_id", league_id );
  ajax.setVar( "points", points );
  ajax.onError = function() { alert('Ajax error on saving additional points'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();
};

Racketmanager.isLoading = function(id) {
  document.getElementById(id).style.display = 'inline';
  document.getElementById(id).innerHTML="<img src='"+RacketManagerAjaxL10n.pluginUrl+"/images/loading.gif' />";
};

Racketmanager.doneLoading = function(id) {
  document.getElementById(id).style.display = 'none';
};

Racketmanager.setMatchDayPopUp = function(match_day, i, max_matches, mode) {
  var ajax = new sack(RacketManagerAjaxL10n.requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( "action", "racketmanager_set_match_day_popup" );
  ajax.setVar( "match_day", match_day );
  ajax.setVar( "i", i);
  ajax.setVar( "mode", mode);
  ajax.setVar( "max_matches", max_matches );
  ajax.onError = function() { alert('Ajax error on setting popup'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();
};

Racketmanager.setMatchDate = function(match_date, i, max_matches, mode) {
  var ajax = new sack(RacketManagerAjaxL10n.requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( "action", "racketmanager_set_match_date" );
  ajax.setVar( "match_date", match_date );
  ajax.setVar( "i", i);
  ajax.setVar( "mode", mode);
  ajax.setVar( "max_matches", max_matches );
  ajax.onError = function() { alert('Ajax error on setting match date'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();
};

Racketmanager.insertHomeStadium = function(team_id, i) {

  var teamId = team_id;
  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"team_id": teamId,
    "action": "racketmanager_insert_home_stadium"},
    success: function(stadium) {
      if (jQuery('#location\\['+i+'\\]').val() == '' ) {
        jQuery('#location\\['+i+'\\]').val(stadium);
      }
    },
    error: function() {
      alert("Ajax error on getting home stadium");
    }
  }) ;
};

Racketmanager.closeMatchModal = function(link) {
  jQuery("#modalMatch").hide();
};

Racketmanager.showRubbers = function(link) {

  var matchId = jQuery(link).attr('id');
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
Racketmanager.disableRubberUpdate = function() {

};
Racketmanager.updateResults = function(link) {

  var $match = document.getElementById('current_match_id');
  var $matchId = $match.value;
  var $league = document.getElementById('current_league_id');
  var $leagueId = $league.value;
  var $form = jQuery('#match-rubbers').serialize();
  $form += "&action=racketmanager_update_rubbers";
  jQuery(".is-invalid").removeClass("is-invalid");
  jQuery("#updateResponse").removeClass("message-success");
	jQuery("#updateResponse").removeClass("message-error");
  jQuery("#showMatchRubbers").hide();
  jQuery("#splash").css('opacity', 1);
  jQuery("#splash").show();

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: $form,
    success: function(response) {
      var $response = jQuery.parseJSON(response);
      var $message = $response[0];
      var $error = $response[1];
			if ($error === true) {
				jQuery("#updateResponse").addClass('message-error');
				jQuery("#updateResponse").html($message);
        jQuery("#updateResponse").show();
        var $errField = $response[4];
        for (var i = 0; i < $errField.length; i++) {
          $formfield = "#"+$errField[i];
          jQuery($formfield).addClass('is-invalid');
        }
			} else {
        jQuery("#updateResponse").show();
				jQuery("#updateResponse").addClass('message-success');
				jQuery("#updateResponse").html($message);
				jQuery("#updateResponse").delay(10000).fadeOut('slow');
        var $homepoints = $response[2];
        var $awaypoints = $response[3];
        jQuery("#updateResponse").show();
        jQuery("#updateResponse").text($message);
        var $matchhome = 0;
        var $matchaway = 0;
        for ( i = 0; i < $homepoints.length; i++) {
          $formfield = "#home_points\\["+i+"\\]";
          $fieldval = $homepoints[i];
          jQuery($formfield).val($fieldval);
          $matchhome  = +$matchhome + +$homepoints[i];
        }
        for ( i = 0; i < $awaypoints.length; i++) {
          $formfield = "#away_points\\["+i+"\\]";
          $fieldval = $awaypoints[i];
          jQuery($formfield).val($fieldval);
          $matchaway  = +$matchaway + +$awaypoints[i];
        }
        $formfield = "#home_points\\["+$matchId+"\\]";
        $formfield1 = "#home_points\\["+$leagueId+"\\]\\["+$matchId+"\\]";
        jQuery($formfield).val($matchhome);
        jQuery($formfield1).val($matchhome);
        var $formfield = "#away_points\\["+$matchId+"\\]";
        var $formfield1 = "#away_points\\["+$leagueId+"\\]\\["+$matchId+"\\]";
        jQuery($formfield).val($matchaway);
        jQuery($formfield1).val($matchaway);
      }
      jQuery("#splash").css('opacity', 0);
      jQuery("#splash").hide();
      jQuery("#showMatchRubbers").show();
    },
    error: function() {
      alert("Ajax error on updating rubbers");
    }
  }) ;
};
Racketmanager.updateMatchResults = function(link) {

	var $match = document.getElementById('current_match_id');
	var $matchId = $match.value;
	var $form = jQuery('#match-view').serialize();
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
		url:RacketManagerAjaxL10n.requestUrl,
		type: "POST",
		data: $form,
		success: function(response) {
			var $response = jQuery.parseJSON(response);
      var $message = $response[0];
			jQuery("#updateResponse").show();
			jQuery("#updateResponse").html($message);
      var $error = $response[3];
      if ($error === true) {
				jQuery("#updateResponse").addClass('message-error');
        var $errField = $response[4];
        for (var i = 0; i < $errField.length; i++) {
          $formfield = "#"+$errField[i];
          jQuery($formfield).addClass('is-invalid');
        }
      } else {
        var $homepoints = $response[1];
  			var $formfield = "#home_points";
  			var $fieldval = $homepoints;
  			jQuery($formfield).val($fieldval);
  			var $awaypoints = $response[2];
  			var $formfield = "#away_points";
  			var $fieldval = $awaypoints;
  			jQuery($formfield).val($fieldval);
      }
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
Racketmanager.confirmResults = function() {

  var $form = jQuery('#match-results').serialize();
  $form += "&action=racketmanager_confirm_results";
  jQuery("#updates").css('opacity', 1);
  jQuery("#updateResults").hide();

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: $form,
    success: function(response) {
      var $response = jQuery.parseJSON(response);
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

  notifyField = "#notifyMessage-"+matchId;
  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"matchId": matchId,
    "action": "racketmanager_notify_teams"},
    success: function(response) {
      jQuery(notifyField).text(response);
      jQuery(notifyField).show();
      jQuery(notifyField).delay(10000).fadeOut('slow');
    },
    error: function() {
      alert("Ajax error on notifying teams");
    }
  }) ;
};
Racketmanager.notifyEntryOpen = function(competitionId) {
  var latestSeason = document.getElementById('latestSeason').value;

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"competitonId": competitionId,
    "latestSeason": latestSeason,
    "action": "racketmanager_notify_entries_open"},
    success: function(response) {
      var $response = jQuery.parseJSON(response);
      var $message = $response['msg'];
      var $error = $response['error'];

      jQuery("#notifyMessage").text($message);
      jQuery("#notifyMessage").show();
      if ( !$error ) {
        jQuery("#notifyMessage").delay(10000).fadeOut('slow');
      }
    },
    error: function() {
      alert("Ajax error on notifying secretaries of entries open");
    }
  }) ;
};
Racketmanager.notifyTournamentEntryOpen = function(tournamentId) {
  notifyField = "#notifyMessage-"+tournamentId;

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"tournamentId": tournamentId,
    "action": "racketmanager_notify_tournament_entries_open"},
    success: function(response) {
      var $response = jQuery.parseJSON(response);
      var $message = $response['msg'];
      var $error = $response['error'];

      jQuery(notifyField).text($message);
      jQuery(notifyField).show();
      if ( !$error ) {
        jQuery(notifyField).delay(10000).fadeOut('slow');
      }
    },
    error: function() {
      alert("Ajax error on notifying secretaries of entries open");
    }
  }) ;
};
Racketmanager.chaseMatchResult = function(matchId) {
  notifyField = "#notifyMessage-"+matchId;

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"matchId": matchId,
    "action": "racketmanager_chase_match_result"},
    success: function(response) {
      var $response = jQuery.parseJSON(response);
      var $message = $response['msg'];
      var $error = $response['error'];

      jQuery(notifyField).text($message);
      jQuery(notifyField).show();
      if ( !$error ) {
        jQuery(notifyField).delay(10000).fadeOut('slow');
      }
    },
    error: function() {
      alert("Ajax error on chasing for match result");
    }
  }) ;
};
Racketmanager.chaseMatchApproval = function(matchId) {
  notifyField = "#notifyMessage-"+matchId;

  jQuery.ajax({
    url:RacketManagerAjaxL10n.requestUrl,
    type: "POST",
    data: {"matchId": matchId,
    "action": "racketmanager_chase_match_approval"},
    success: function(response) {
      var $response = jQuery.parseJSON(response);
      var $message = $response['msg'];
      var $error = $response['error'];

      jQuery(notifyField).text($message);
      jQuery(notifyField).show();
      if ( !$error ) {
        jQuery(notifyField).addClass('message-success');
        jQuery(notifyField).delay(10000).fadeOut('slow');
      } else {
        jQuery(notifyField).addClass('message-error');
      }
    },
    error: function() {
      alert("Ajax error on chasing for match approval");
    }
  }) ;
};
Racketmanager.getImportOption = function(option) {

  var selectedOption = option;
  if ( selectedOption == 'table' || selectedOption == 'fixtures' ) {
    jQuery("#competitions").show();
    jQuery("#leagues").show();
    jQuery("#clubs").hide();
  } else if ( selectedOption == 'roster') {
    jQuery("#clubs").show();
    jQuery("#competitions").hide();
    jQuery("#leagues").hide();
  } else if ( selectedOption == 'players') {
    jQuery("#clubs").hide();
    jQuery("#competitions").hide();
    jQuery("#leagues").hide();
  }

};
Racketmanager.checkAll = function(form) {
	for (i = 0, n = form.elements.length; i < n; i++) {
		if(form.elements[i].type == "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
			if(form.elements[i].checked == true)
			form.elements[i].checked = false;
			else
			form.elements[i].checked = true;
		}
	}
};
//Racketmanager.checkPointRule = function( forwin, forwin_overtime, fordraw, forloss, forloss_overtime ) {
Racketmanager.checkPointRule = function( rule ) {
	//	var rule = document.getElementById('point_rule').value;

	// manual rule selected
	if ( rule == 'user' ) {
		new_element_contents = "";
		new_element_contents += "<input type='text' name='forwin' id='forwin' value=" + forwin + " size='2' />";
		new_element_contents += "<input type='text' name='forwin_overtime' id='forwin_overtime' value=" + forwin_overtime + " size='2' />";
		new_element_contents += "<input type='text' name='fordraw' id='fordraw' value=" + fordraw + " size='2' />";
		new_element_contents += "<input type='text' name='forloss' id='forloss' value=" + forloss + " size='2' />";
		new_element_contents += "<input type='text' name='forloss_overtime' id='forloss_overtime' value=" + forloss_overtime + " size='2' />";
		new_element_contents += "&#160;<span class='setting-description'>" + RacketManagerAjaxL10n.manualPointRuleDescription + "</span>";
		new_element_id = "point_rule_manual_content";
		new_element = document.createElement('div');
		new_element.id = new_element_id;

		document.getElementById("point_rule_manual").appendChild(new_element);
		document.getElementById(new_element_id).innerHTML = new_element_contents;
	} else {
		element_count = document.getElementById("point_rule_manual").childNodes.length;
		if(element_count > 0) {
			target_element = document.getElementById("point_rule_manual_content");
			document.getElementById("point_rule_manual").removeChild(target_element);
		}

	}

	return false;
}

Racketmanager.insertPlayer = function(id, target) {
	tb_remove();
	var player = document.getElementById(id).value;
	document.getElementById(target).value = player;
}

Racketmanager.removeField = function(id, parent_id) {
	element_count = document.getElementById(parent_id).childNodes.length;
	if(element_count > 1) {
		target_element = document.getElementById(id);
		document.getElementById(parent_id).removeChild(target_element);
	}
	return false;
}

Racketmanager.reInit = function() {
	tb_init('a.thickbox, area.thickbox, input.thickbox');
}
