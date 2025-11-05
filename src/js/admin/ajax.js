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
