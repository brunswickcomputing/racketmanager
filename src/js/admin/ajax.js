// Provides global Racketmanager methods used by admin UI and post edit meta boxes
window.Racketmanager = window.Racketmanager || {};

Racketmanager.getEventDropdown = function (competition_id) {
    let notifyField = "#events";
    jQuery('#leagues').hide();
    jQuery('#seasons').hide();
    jQuery(notifyField).removeClass('message-error');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "competition_id": competition_id,
            "action": "racketmanager_get_event_dropdown",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).html(response.data);
        },
        error: function (response) {
            if (response.responseJSON) {
                jQuery(notifyField).text(response.responseJSON.data);
            } else {
                jQuery(notifyField).text(response.statusText);
            }
            jQuery(notifyField).addClass('message-error');
        },
        complete: function () {
            jQuery(notifyField).show();
        }
    });
};
Racketmanager.getLeagueDropdown = function (event_id) {
    let notifyField = "#leagues";
    jQuery('#seasons').hide();
    jQuery(notifyField).removeClass('message-error');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "event_id": event_id,
            "action": "racketmanager_get_league_dropdown",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).html(response.data);
        },
        error: function (response) {
            if (response.responseJSON) {
                jQuery(notifyField).text(response.responseJSON.data);
            } else {
                jQuery(notifyField).text(response.statusText);
            }
            jQuery(notifyField).addClass('message-error');
        },
        complete: function () {
            jQuery(notifyField).show();
        }
    });
};
Racketmanager.getSeasonDropdown = function (league_id) {
    let notifyField = "#seasons";
    jQuery(notifyField).removeClass('message-error');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "league_id": league_id,
            "action": "racketmanager_get_season_dropdown",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).html(response.data);
        },
        error: function (response) {
            if (response.responseJSON) {
                jQuery(notifyField).text(response.responseJSON.data);
            } else {
                jQuery(notifyField).text(response.statusText);
            }
            jQuery(notifyField).addClass('message-error');
        },
        complete: function () {
            jQuery(notifyField).show();
        }
    });
};

Racketmanager.getMatchDropdown = function (league_id, season) {
    let notifyField = "#matches";
    jQuery(notifyField).removeClass('message-error');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "league_id": league_id,
            "season": season,
            "action": "racketmanager_get_match_dropdown",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).html(response.data);
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
Racketmanager.getImportOption = function(option) {
    let selectedOption = option;
    if (selectedOption === 'table' || selectedOption === 'fixtures') {
        jQuery("#competitions").show();
        jQuery("#events").show();
        jQuery("#leagues").show();
        jQuery("#seasons").show();
        jQuery("#clubs").hide();
    } else if (selectedOption === 'clubplayers') {
        jQuery("#clubs").show();
        jQuery("#competitions").hide();
        jQuery("#events").hide();
        jQuery("#leagues").hide();
        jQuery("#seasons").hide();
    } else if (selectedOption === 'players') {
        jQuery("#clubs").hide();
        jQuery("#competitions").hide();
        jQuery("#events").hide();
        jQuery("#leagues").hide();
        jQuery("#seasons").hide();
    }
};
Racketmanager.setEventName = function () {
    let eventNameField = "#event_name";
    let eventName = jQuery(eventNameField).val();
    if (eventName) {
        return;
    }
    let typeField = "#type";
    let type = jQuery(typeField).val();
    if (type) {
        let typeTextField = typeField + " option:selected";
        let typeText = jQuery(typeTextField).text();
        if (typeText) {
            let ageLimitField = "#age_limit";
            let ageLimit = jQuery(ageLimitField).val();
            if (ageLimit) {
                if (ageLimit === 'open') {
                    eventName = '';
                } else if ( ageLimit <= 21) {
                    eventName = 'U' + ageLimit + ' ';
                } else if ( ageLimit >= 30) {
                    eventName = 'O' + ageLimit + ' ';
                }
                eventName = eventName + typeText;
                jQuery(eventNameField).val(eventName);
            }
        }
    }
};
Racketmanager.setEndDate = function () {
    let competitionTypeField = "#competitionType";
    let competitionType = jQuery(competitionTypeField).val();
    if (competitionType === 'league') {
        let dateStartField = "#dateStart";
        let dateStart = jQuery(dateStartField).val();
        let dateEndField = "#dateEnd";
        let roundLengthField = "#round_length";
        let fillerWeeksField = "#filler_weeks";
        let homeAwayDiffField = "#home_away_diff";
        let numMatchDaysField = '#num_match_days';
        let roundLength = jQuery(roundLengthField).val();
        let roundLengthWeeks = roundLength / 7;
        let fillerWeeks = jQuery(fillerWeeksField).val();
        if (fillerWeeks === '') {
            fillerWeeks = 0;
        }
        let homeAwayDiff = jQuery(homeAwayDiffField).val();
        if (homeAwayDiff === '') {
            homeAwayDiff = 0;
        }
        let numMatchDays = jQuery(numMatchDaysField).val();
        if (dateStart) {
            if (roundLengthWeeks && numMatchDays) {
                let endDateGapWeeks = ((+roundLengthWeeks * +numMatchDays) + +fillerWeeks + +homeAwayDiff);
                let endDateGap = (endDateGapWeeks * 7) - 1;
                let endDate = Racketmanager.amendDate(dateStart, endDateGap);
                jQuery(dateEndField).val(endDate);
            }
        }
    }
};
Racketmanager.amendDate = function (date, adjustment) {
	let newDate = new Date(date);
	newDate.setDate(newDate.getDate() + adjustment);
	let year = newDate.toLocaleString("default", { year: "numeric" });
	let month = newDate.toLocaleString("default", { month: "2-digit" });
	let day = newDate.toLocaleString("default", { day: "2-digit" });
	return year + "-" + month + "-" + day;
};
Racketmanager.setNumMatchDays = function () {
    let homeAwayTrueField = "#homeAwayTrue";
    let maxTeamsField = "#max_teams";
    let numMatchDaysField = '#num_match_days';
    let maxTeams = jQuery(maxTeamsField).val();
    let homeAway = jQuery(homeAwayTrueField).is(':checked');
    let numMatchDays = 0;
    if (maxTeams) {
        if (maxTeams % 2 !== 0) {
            maxTeams++;
        }
        numMatchDays = maxTeams - 1;
        if (homeAway) {
            numMatchDays = numMatchDays * 2;
        }
        jQuery(numMatchDaysField).val(numMatchDays);
    }
};
Racketmanager.setTournamentOpenDate = function () {
    let date_openField = "#dateOpen";
    let dateStartField = "#dateStart";
    let dateCloseField = "#dateClose";
    let dateWithdrawField = "#dateWithdraw";
    let gradeField = '#grade';
    let grade = jQuery(gradeField).val();
    let date_start = jQuery(dateStartField).val();
    let notifyField1 = "#alert-dates";
    jQuery(notifyField1).hide();
    jQuery(notifyField1).removeClass('alert--success alert--danger');
    let notifyField2 = "#alert-dates-response";
    jQuery(notifyField2).html('');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "dateStart": date_start,
            "grade": grade,
            "action": "racketmanager_set_tournament_dates",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            let message = response.data.msg;
            jQuery(notifyField2).text(message);
            let date_open = response.data.date_open;
            jQuery(date_openField).val(date_open);
            let dateClose = response.data.date_closing;
            jQuery(dateCloseField).val(dateClose);
            let dateWithdraw = response.data.date_withdraw;
            jQuery(dateWithdrawField).val(dateWithdraw);
            jQuery(notifyField1).addClass('alert--success');
            jQuery(notifyField1).delay(10000).fadeOut('slow');
        },
        error: function (response) {
            if (response.responseJSON) {
                let message = response.responseJSON.data;
                jQuery(notifyField2).html(message);
            } else {
                jQuery(notifyField2).text(response.statusText);
            }
            jQuery(notifyField1).addClass('alert--danger');
        },
        complete: function () {
            jQuery(notifyField1).show();
        }
    });
};
Racketmanager.sendFixtures = function(e,eventId) {
    e.preventDefault();
    let notifyField1 = "#alert-season";
    jQuery(notifyField1).hide();
    jQuery(notifyField1).removeClass('alert--success alert--danger');
    let notifyField2 = "#alert-season-response";
    jQuery(notifyField2).html('');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "security": ajax_var.ajax_nonce,
            "eventId": eventId,
            "action": "racketmanager_send_fixtures"
        },
        success: function (response) {
            let message = response.data.msg;
            jQuery(notifyField2).text(message);
            jQuery(notifyField1).addClass('alert--success');
        },
        error: function (response) {
            if (response.responseJSON) {
                let message = response.responseJSON.data;
                jQuery(notifyField2).html(message);
            } else {
                jQuery(notifyField2).text(response.statusText);
            }
            jQuery(notifyField1).addClass('alert--danger');
        },
        complete: function() {
            jQuery(notifyField1).show();
        }
    });
};
Racketmanager.notifyTournamentEntryOpen = function(e, tournamentId) {
    e.preventDefault();
    let notifyField1 = "#alert-tournaments";
    jQuery(notifyField1).hide();
    jQuery(notifyField1).removeClass('alert--success alert--danger');
    let notifyField2 = "#alert-tournaments-response";
    jQuery(notifyField2).html('');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "tournamentId": tournamentId,
            "action": "racketmanager_notify_tournament_entries_open",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            let message = response.data;
            jQuery(notifyField2).text(message);
            jQuery(notifyField1).addClass('alert--success');
        },
        error: function (response) {
            if (response.responseJSON) {
                let message = response.responseJSON.data;
                jQuery(notifyField2).html(message);
            } else {
                jQuery(notifyField2).text(response.statusText);
            }
            jQuery(notifyField1).addClass('alert--danger');
        },
        complete: function() {
            jQuery(notifyField1).show();
        }
    });
};
Racketmanager.notify_open = function(e, competitionId, season) {
    e.preventDefault();
    let notifyField1 = "#alert-season";
    jQuery(notifyField1).hide();
    jQuery(notifyField1).removeClass('alert--success alert--danger');
    let notifyField2 = "#alert-season-response";
    jQuery(notifyField2).html('');
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "competitionId": competitionId,
            "season": season,
            "action": "racketmanager_notify_competition_entries_open",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            let message = response.data;
            jQuery(notifyField2).text(message);
            jQuery(notifyField1).addClass('alert--success');
        },
        error: function (response) {
            if (response.responseJSON) {
                let message = response.responseJSON.data;
                jQuery(notifyField2).html(message);
            } else {
                jQuery(notifyField2).text(response.statusText);
            }
            jQuery(notifyField1).addClass('alert--danger');
        },
        complete: function() {
            jQuery(notifyField1).show();
        }
    });
};
Racketmanager.emailConstitution = function(e, eventId) {
    e.preventDefault();
    let notifyField = "#notifyMessage-constitution";
    jQuery(notifyField).hide();
    jQuery(notifyField).removeClass();
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "eventId": eventId,
            "action": "racketmanager_email_constitution",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).text(response.data);
            jQuery(notifyField).show();
            jQuery(notifyField).addClass('message-success');
            jQuery(notifyField).delay(10000).fadeOut('slow');
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
Racketmanager.notifyTeams = function(matchId) {

    let notifyField = "#notifyMessage-" + matchId;
    jQuery(notifyField).hide();
    jQuery(notifyField).removeClass();
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "matchId": matchId,
            "action": "racketmanager_notify_teams",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            jQuery(notifyField).text(response.data.message);
            jQuery(notifyField).show();
            jQuery(notifyField).addClass('message-success');
            jQuery(notifyField).delay(10000).fadeOut('slow');
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
Racketmanager.insertHomeStadium = function(team_id, i) {
    let notifyField = "#feedback";
    jQuery(notifyField).removeClass("message-error");
    jQuery(notifyField).empty();
    notifyField = "#location_" + i;

    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "team_id": team_id,
            "action": "racketmanager_insert_home_stadium",
            "security": ajax_var.ajax_nonce,
        },
        success: function (response) {
            let stadium = response.data;
            if (jQuery(notifyField).val() === '') {
                jQuery(notifyField).val(stadium);
            }
        },
        error: function (response) {
            notifyField = "#feedback";
            if (response.responseJSON) {
                let message = response.responseJSON.data;
                jQuery("#feedback").show();
                jQuery(notifyField).html(message);
            } else {
                jQuery(notifyField).text(response.statusText);
            }
            jQuery(notifyField).show();
            jQuery(notifyField).addClass('message-error');
        }
    });
};
Racketmanager.setMatchDate = function (match_date, i, max_matches, mode) {
    if (i === 0 && mode === 'add') {
        for (let xx = 1; xx < max_matches; xx++) {
            let formField = "#myDatePicker\\[" + xx + "\\]";
            jQuery(formField).val(match_date);
        }
    }
};
Racketmanager.setMatchDayPopUp = function (match_day, i, max_matches, mode) {
    if (i === 0 && mode === 'add') {
        for (let xx = 1; xx < max_matches; xx++) {
            let formField = "#match_day_" + xx;
            jQuery(formField).val(match_day);
        }
    }
};
Racketmanager.saveAddPoints = function(points, table_id) {
    let notifyField = "#feedback_" + table_id;
    jQuery(notifyField).removeClass('message-error');
    jQuery(notifyField).css("display", "none");
    jQuery(notifyField).text('');
    let loadingTeam = jQuery('#loading_' + table_id);
    loadingTeam.css("display", "inline");
    loadingTeam.html("<img alt=\"loading\" src='" + RacketManagerAjaxL10n.pluginUrl + "/images/loading.gif' />");
    jQuery.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            "action": "racketmanager_save_add_points",
            "security": ajax_var.ajax_nonce,
            "table_id": table_id,
            "points": points,
        },
        success: function () {
            jQuery('#loading_' + table_id).fadeOut('fast');
            window.location.reload();
            return true;
        },
        error: function (response) {
            if (response.responseJSON) {
                jQuery(notifyField).text(response.responseJSON.data);
            } else {
                jQuery(notifyField).text(response.statusText);
            }
            jQuery(notifyField).show();
            jQuery(notifyField).addClass('message-error');
            jQuery(notifyField).css("display", "inline-block");
        },
        complete: function () {
            jQuery('#loading_' + table_id).css("display", "none");
        }
    })
};
