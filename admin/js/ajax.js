//var Leaguemanager = new Object();
if(typeof Leaguemanager == "undefined") {
  var Leaguemanager = new Object();
}

Leaguemanager.reInit = function() {
	tb_init('a.thickbox, area.thickbox, input.thickbox');
};

Leaguemanager.insertLogoFromLibrary = function() {
  logo = document.getElementById('logo_library_url').value;

  var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( 'action', 'leaguemanager_insert_logo_from_library' );
  ajax.setVar( 'logo', logo );
  ajax.onError = function() { alert('Ajax error while getting inserting logo'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();

  tb_remove();
};


Leaguemanager.addStatsField = function(requestUrl) {
  time = new Date();

  var ajax = new sack(requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( 'action', 'leaguemanager_add_stats_field' );
  ajax.onError = function() { alert('Ajax error while adding stats field'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();
};

Leaguemanager.addStat = function(el_id, stat_id, match_id) {
  time = new Date();

  var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
  ajax.execute = 1;
  ajax.method = 'POST';
  ajax.setVar( 'action', 'leaguemanager_add_stat' );
  ajax.setVar( 'parent_id', el_id );
  ajax.setVar( 'stat_id', stat_id );
  ajax.setVar( 'match_id', match_id );
  ajax.onError = function() { alert('Ajax error while adding a stat'); };
  ajax.onCompletion = function() { return true; };
  ajax.runAJAX();
};

Leaguemanager.removeField = function(field_id, parent_id) {
	element_count = document.getElementById(parent_id).childNodes.length;
	if(element_count > 0) {
		target_element = document.getElementById(field_id);
		document.getElementById(parent_id).removeChild(target_element);
	}
	return false;
};

Leaguemanager.toggleTeamRosterGroups = function( roster ) {
	if ( '' === roster ) {
		jQuery('div#team_roster_groups').fadeOut('fast');
	} else {
		var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
		ajax.execute = 1;
		ajax.method = 'POST';
		ajax.setVar( 'action', 'leaguemanager_set_team_roster_groups' );
		ajax.setVar( 'roster', roster );
		ajax.onError = function() { alert('Ajax error while toggling team rosters'); };
		ajax.onCompletion = function() { return true; };
		ajax.runAJAX();
	}
};

Leaguemanager.getTeamFromDatabase = function() {
	var team_id = document.getElementById('team_db_select').value;

	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( 'action', 'leaguemanager_add_team_from_db' );
	ajax.setVar( 'team_id', team_id );
	ajax.onError = function() { alert('Ajax error while getting teams'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();

	tb_remove();
};

Leaguemanager.getTeamPlayerFromDatabase = function() {
    var team_id = document.getElementById('team_db_select').value;
    
    var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
    ajax.execute = 1;
    ajax.method = 'POST';
    ajax.setVar( 'action', 'leaguemanager_add_teamplayer_from_db' );
    ajax.setVar( 'team_id', team_id );
    ajax.onError = function() { alert('Ajax error while getting teams'); };
    ajax.onCompletion = function() { return true; };
    ajax.runAJAX();
    
    tb_remove();
};

Leaguemanager.getSeasonDropdown = function(league_id){
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( 'action', 'leaguemanager_get_season_dropdown' );
	ajax.setVar( 'league_id', league_id );
	ajax.onError = function() { alert('Ajax error while getting seasons'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.getMatchDropdown = function(league_id, season) {
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( 'action', 'leaguemanager_get_match_dropdown' );
	ajax.setVar( 'league_id', league_id );
	ajax.setVar( 'season', season );
	ajax.onError = function() { alert('Ajax error while getting matches'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.saveStandings = function(ranking) {
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_save_team_standings" );
	ajax.setVar( "ranking", ranking );
	ajax.onError = function() { alert('Ajax error on saving standings'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.saveAddPoints = function(team_id, league_id, season) {
	Leaguemanager.isLoading('loading_' + team_id);
	var points = document.getElementById('add_points_' + team_id).value;

	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_save_add_points" );
	ajax.setVar( "team_id", team_id );
	ajax.setVar( "league_id", league_id );
	ajax.setVar( "points", points );
	ajax.onError = function() { alert('Ajax error on saving additional points'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.isLoading = function(id) {
	document.getElementById(id).style.display = 'inline';
	document.getElementById(id).innerHTML="<img src='"+LeagueManagerAjaxL10n.pluginUrl+"/images/loading.gif' />";
};

Leaguemanager.doneLoading = function(id) {
	document.getElementById(id).style.display = 'none';
};

Leaguemanager.setMatchDayPopUp = function(match_day, i, max_matches, mode) {
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_set_match_day_popup" );
	ajax.setVar( "match_day", match_day );
	ajax.setVar( "i", i);
	ajax.setVar( "mode", mode);
	ajax.setVar( "max_matches", max_matches );
	ajax.onError = function() { alert('Ajax error on setting popup'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.setMatchDate = function(match_date, i, max_matches, mode) {
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_set_match_date" );
	ajax.setVar( "match_date", match_date );
	ajax.setVar( "i", i);
	ajax.setVar( "mode", mode);
	ajax.setVar( "max_matches", max_matches );
	ajax.onError = function() { alert('Ajax error on setting match date'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.insertHomeStadium = function(team_id, i) {
	var ajax = new sack(LeagueManagerAjaxL10n.requestUrl);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_insert_home_stadium" );
	ajax.setVar( "team_id", team_id );
	ajax.setVar( "i", i);
	ajax.onError = function() { alert('Ajax error on inserting home stadium'); };
	ajax.onCompletion = function() { return true; };
	ajax.runAJAX();
};

Leaguemanager.showRubbers = function(link) {
    
    var matchId = jQuery(link).attr('id');
    var $hreflink = jQuery(link).attr("href");
    var $title = jQuery(link).attr("name");
    jQuery("#showMatchRubbers").empty();
    jQuery("#showMatchRubbers").html('<div class="preloader"><div class="spinner"><div class="pre-bounce1"></div><div class="pre-bounce2"></div></div></div>');

    jQuery.ajax({
                url:LeagueManagerAjaxL10n.requestUrl,
                type: "POST",
                data: {"matchId": matchId,
                "action": "leaguemanager_show_rubbers"},
                success: function(response) {
                jQuery("#showMatchRubbers").empty();
                jQuery("#showMatchRubbers").html(response);
                tb_show($title,$hreflink,false);
                },
                error: function() {
                alert("Ajax error on getting rubbers");
                }
                }) ;
};
Leaguemanager.updateRubbers = function(link) {
    
	var selects = document.getElementById('match-rubbers').getElementsByTagName('select');
	var values = [];
	for(i=0;i<selects.length;i++) {
		var select = selects[i];
//		if(values.indexOf(select.value)>-1) {
//			jQuery("#UpdateResponse").text('Results not updated - duplicate player selected');
//			return false;
//		}
//		else
//			values.push(select.value);

	}
	var $match = document.getElementById('current_match_id');
	var $matchId = $match.value;
    var $league = document.getElementById('current_league_id');
    var $leagueId = $league.value;
    var $form = jQuery('#match-rubbers').serialize();
    $form += "&action=leaguemanager_update_rubbers";
    
    jQuery.ajax({
                url:LeagueManagerAjaxL10n.requestUrl,
                type: "POST",
                data: $form,
                success: function(response) {
                    var $response = jQuery.parseJSON(response);
                    var $message = $response[0];
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
                    var $formfield = "#home_points\\["+$matchId+"\\]";
                    var $formfield1 = "#home_points\\["+$leagueId+"\\]\\["+$matchId+"\\]";
                    jQuery($formfield).val($matchhome);
                    jQuery($formfield1).val($matchhome);
                    var $formfield = "#away_points\\["+$matchId+"\\]";
                    var $formfield1 = "#away_points\\["+$leagueId+"\\]\\["+$matchId+"\\]";
                    jQuery($formfield).val($matchaway);
                    jQuery($formfield1).val($matchaway);
                    },
                error: function() {
                    alert("Ajax error on updating rubbers");
                }
                }) ;
};
Leaguemanager.confirmResults = function() {
    
    var $form = jQuery('#match-results').serialize();
    $form += "&action=leaguemanager_confirm_results";
    
    jQuery.ajax({
                url:LeagueManagerAjaxL10n.requestUrl,
                type: "POST",
                data: $form,
                success: function(response) {
                    var $response = jQuery.parseJSON(response);
                    jQuery("#MatchUpdateResponse").text($response);
                jQuery("#message").addClass("updated")
                },
                error: function() {
                    alert("Ajax error on updating results");
                }
                }) ;
    return false;
};
