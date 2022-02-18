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
    jQuery("#modalMatch").show();
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
                    var $homepoints = $response[1];
                    var $awaypoints = $response[2];
                    jQuery("#UpdateResponse").show();
                    jQuery("#UpdateResponse").text($message);
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
                    jQuery("#splash").css('opacity', 0);
                    jQuery("#splash").hide();
                    jQuery("#showMatchRubbers").show();
                    },
                error: function() {
                    alert("Ajax error on updating rubbers");
                }
                }) ;
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

    jQuery.ajax({
                url:RacketManagerAjaxL10n.requestUrl,
                type: "POST",
                data: {"matchId": matchId,
                "action": "racketmanager_notify_teams"},
                success: function(response) {
                alert(response);
                },
                error: function() {
                alert("Ajax error on notifying teams");
                }
                }) ;
};
