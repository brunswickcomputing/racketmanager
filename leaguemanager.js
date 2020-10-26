jQuery(document).ready(function($) {
	/* jQuery UI accordion list */	
	jQuery( ".jquery-ui-accordion" ).accordion({
		header: "h3.header",
		collapsible: true,
		heightStyle: "content",
        active: "false"
	});
	
	/*
	 * Make sure that jQuery UI Tab content containers have correct IDs based on tablist links
	 */
	var i = 0;
	// get all tablist a elements
	jQuery('.jquery-ui-tabs>.tablist a').each(function() {
		// get href attribute of current link and remove leading #
		var tab_id = jQuery(this).attr('href');
		tab_id = tab_id.substring(1, tab_id.length);
		// get corresponding tab container
		var tab = jQuery('.jquery-ui-tabs .tab-content').eq(i);
		// set ID of tab container
		tab.attr('id', tab_id);
		
		// increment item count
		i = i + 1;
	});
	
	/*
	 * Acivate Tabs
	 */
	jQuery('.jquery-ui-tabs').tabs({
		collapsible: true,
	});
	jQuery(".jquery-ui-tabs>.tablist").css("display", "block");
	jQuery(".jquery-ui-tabs .tab-header").css("display", "none");
	jQuery("tr.match-rubber-row").slideToggle('fast','linear');
	jQuery ("i", "tr.match-row").toggleClass("fa-angle-right fa-angle-down");

	jQuery("tr.match-row").click(function(e){
								jQuery(this).next("tr.match-rubber-row").slideToggle('0','linear');
								jQuery(this).find("i").toggleClass("fa-angle-right fa-angle-down");
								});
/* Friendly URL rewrite */
	jQuery('#leaguemanager_archive').submit(function() {
								var league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 ]/g,''); // Remove unwanted characters, only accept alphanumeric and space */
								var season = jQuery('#season').val();
								league = league.replace(/\s{2,}/g,' '); // Replace multi spaces with a single space */
								league = league.replace(/\s/g, "-"); // Replace space with a '-' symbol */

								var cleanUrl = window.location.protocol + '//' + window.location.host + '/leagues/' + league.toLowerCase() + '/' + season;
								window.location = cleanUrl;

								return false;  // Prevent default button behaviour
								});

/* Friendly URL rewrite */
	jQuery('#leaguemanager_match_day_selection').submit(function() {
								var league = jQuery('#league_id').val().replace(/[^A-Za-z0-9 ]/g,''); // Remove unwanted characters, only accept alphanumeric and space */
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

});

var Leaguemanager = new Object();

Leaguemanager.setMatchBox = function( requestURL, curr_index, operation, element, league_id, match_limit, widget_number, season, group, home_only, date_format ) {
	var ajax = new sack(requestURL);
	ajax.execute = 1;
	ajax.method = 'POST';
	ajax.setVar( "action", "leaguemanager_get_match_box" );
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

Leaguemanager.printScoreCard = function(e, link) {
	
	e.preventDefault();
	var matchId = jQuery(link).attr('id');
	var $hreflink = jQuery(link).attr('href');
	var $title = jQuery(link).attr('name');
	
	jQuery.ajax({
				url:LeagueManagerAjaxL10n.requestUrl,
				type: "POST",
				data: {"matchId": matchId,
					"action": "leaguemanager_view_rubbers"},
				success: function(response) {
					var printOne = response;
					var styleSheetList = document.styleSheets;
					var w = window.open("","","width=800,height=660");
					w.document.write('<html><head><title>Match Card</title>');
					for (var item of styleSheetList) {
						if (item.url != 'null') w.document.write('<link rel="stylesheet" type="text/css" href="' + item.href + '" media="all">');
					};
					w.document.write('</head>');
					w.document.write('<body>' + printOne  + '</body></html>');
					w.document.close();
				},
				error: function() {
					alert("Ajax error on getting rubbers");
				}
				}) ;
};
Leaguemanager.showRubbers = function(matchId) {
    
    jQuery("#showMatchRubbers").empty();
    jQuery("#showMatchRubbers").addClass("spinnerMatch");

    jQuery.ajax({
                url:LeagueManagerAjaxL10n.requestUrl,
                type: "POST",
                data: {"matchId": matchId,
                "action": "leaguemanager_show_rubbers"},
                success: function(response) {
                jQuery("#showMatchRubbers").empty();
                jQuery("#showMatchRubbers").removeClass("spinnerMatch");
                jQuery("#showMatchRubbers").html(response);
                },
                error: function() {
                alert("Ajax error on getting rubbers");
                }
                }) ;
};
Leaguemanager.disableRubberUpdate = function() {
    
    jQuery("select").prop("disabled", "true");
    jQuery("input").prop("readonly", "true");
    jQuery("#updateRubber").val("confirm");
};
Leaguemanager.updateRubbers = function(link) {
    
    var selects = document.getElementById('match-rubbers').getElementsByTagName('select');
    var values = [];
    for(i=0;i<selects.length;i++) {
        var select = selects[i];
        
    }
    var $match = document.getElementById('current_match_id');
    var $matchId = $match.value;
    var $form = jQuery('#match-rubbers').serialize();
    $form += "&action=leaguemanager_update_rubbers";
    jQuery("#updateRubberResults").prop("disabled", "true");
    jQuery("#updateRubberResults").addClass("disabled");

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
                },
                error: function() {
                    alert("Ajax error on updating rubbers");
                }
                }) ;
    jQuery("#updateRubberResults").removeProp("disabled");
    jQuery("#updateRubberResults").removeClass("disabled");
};
Leaguemanager.rosterRequest = function(link) {
    
    var $affiliatedClub = document.getElementById('affiliatedClub').value;
    var $form = jQuery('#rosterRequestFrm').serialize();
    $form += "&action=leaguemanager_roster_request";
    jQuery("#updateResponse").val("");
    jQuery("#rosterUpdateSubmit").prop("disabled", "true");
    jQuery("#rosterUpdateSubmit").addClass("disabled");

    jQuery.ajax({
                url:LeagueManagerAjaxL10n.requestUrl,
                type: "POST",
                data: $form,
                success: function(response) {
                    var $response = jQuery.parseJSON(response);
                    var $message = $response[0];
                    var $error = $response[1];
                    if ($error === true) {
                        for ( var errorField of $response[2] ) {
                            $message += '<br />' + errorField;
                        }
                        jQuery("#updateResponse").html($message);
                    } else {
                        jQuery("#firstName").val("");
                        jQuery("#surname").val("");
                        jQuery("#genderMale").prop('checked', false);
                        jQuery("#genderFemale").prop('checked', false);
                        jQuery("#btm").val("");
                        jQuery("#updateResponse").html($message);
                    }
                },
                error: function() {
                    alert("Ajax error on roster request");
                }
                }) ;
    jQuery("#rosterUpdateSubmit").removeProp("disabled");
    jQuery("#rosterUpdateSubmit").removeClass("disabled");
};
