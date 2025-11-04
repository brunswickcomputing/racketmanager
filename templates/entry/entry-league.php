<?php
/**
 * Template page to display a league entry form
 *
 * @author Paul Moffat
 * @package Racketmanager
 *
 * The following variables are usable:
 * $competition: competition object
 * $events: events array of objects
 * $season: season name
 * $club: club object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $competition */
/** @var string $season */
/** @var array  $events */
/** @var object $club */
$match_days = Util_Lookup::get_match_days();
if ( $competition->is_open ) {
    $changes_allowed = true;
} else {
    $changes_allowed = false;
}
if ( ! empty( $club->entry ) ) {
    $entered    = true;
    $form_title = __( 'Entry details', 'racketmanager' );
} else {
    $entered    = false;
    $form_title = __( 'Enter online', 'racketmanager' );
}
$additional_information = __( 'Additional information', 'racketmanager' );
?>
<div class="container">
    <?php
    require_once RACKETMANAGER_PATH . 'templates/includes/competition-header.php';
    ?>
    <form id="form-entry" action="" method="post">
        <?php wp_nonce_field( 'league-entry' ); ?>
        <input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
        <input type="hidden" name="competitionId" value="<?php echo esc_html( $competition->id ); ?>" />
        <input type="hidden" name="competitionType" value="<?php echo esc_html( $competition->type ); ?>" />
        <div class="module module--card">
            <div class="module__content">
                <div class="module-container">
                    <div class="entry-subhead">
                        <div class="hgroup">
                            <h3 class="hgroup__heading">
                                <?php echo esc_html( $form_title ); ?>
                            </h3>
                            <?php
                            if ( ! empty( $competition->date_closing ) ) {
                                ?>
                                <span class="hgroup__subheading">
                                    <?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
                                    <time datetime="<?php echo esc_attr( $competition->date_closing ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $competition->date_closing ) ); ?></time>
                                </span>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="entry-subhead__aside">
                            <a role="button" href="/rules/<?php echo esc_attr( seo_url( $competition->name ) ); ?>-rules/" target="_blank" class="btn btn-primary">
                                <?php esc_html_e( 'League Rules', 'racketmanager' ); ?>
                            </a>
                        </div>
                    </div>
                    <?php
                    if ( ! $changes_allowed ) {
                        if ( $entered ) {
                            $alert_class = 'info';
                            $alert_msg[] = __( 'League entries are now closed.', 'racketmanager' );
                            $alert_msg[] = __( 'These are the latest entry details.', 'racketmanager' );
                        } else {
                            $alert_class = 'warning';
                            $alert_msg[] = __( 'League not currently open for entries', 'racketmanager' );
                        }
                        ?>
                        <div class="alert_rm mt-3 alert--<?php echo esc_attr( $alert_class ); ?>">
                            <div class="alert__body">
                                <?php
                                foreach ( $alert_msg as $msg ) {
                                    ?>
                                    <div class="alert__body-inner">
                                        <?php echo esc_html( $msg ); ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="club-entry__body">
                        <div id="club-details">
                            <div class="media">
                                <div class="media__wrapper">
                                    <div class="media__img">
                                        <span class="profile-icon">
                                            <span class="profile-icon__abbr">
                                                <?php
                                                $words    = explode( ' ', $club->shortcode );
                                                $initials = null;
                                                foreach ( $words as $w ) {
                                                    $initials .= $w[0];
                                                }
                                                echo esc_html( $initials );
                                                ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="media__content">
                                        <h4 class="media__title"><?php echo esc_html( $club->name ); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ol class="list list--naked" id="entry-details">
                            <li id="liClubDetails" class="club-entry__panel">
                                <div id="clubDetails">
                                    <input type="hidden" name="clubId" id="clubId" value="<?php echo esc_html( $club->id ); ?>" />
                                    <div class="hgroup">
                                        <h4 class="hgroup__heading"><?php esc_html_e( 'Club details', 'racketmanager' ); ?></h4>
                                        <p class="hgroup__subheading"><?php esc_html_e( 'Check if your details are correct, and change them if necessary', 'racketmanager' ); ?></p>
                                    </div>
                                    <div class="row">
                                        <div id="contactDetails" class="col-12 col-md-6">
                                            <div class="border p-3">
                                                <h5 class="subheading"><?php esc_html_e( 'Courts', 'racketmanager' ); ?></h5>
                                                <dl class="list list--flex">
                                                    <div class="list__item">
                                                        <dt class="list__label"><?php esc_html_e( 'Available', 'racketmanager' ); ?></dt>
                                                        <dd class="list__value">
                                                            <label for="numCourtsAvailable" class="visually-hidden"><?php esc_html_e( 'Court to use', 'racketmanager' ); ?></label><input type="number" class="form-control" id="numCourtsAvailable" name="numCourtsAvailable" value="<?php echo empty( $competition->num_courts_available[ $club->id ] ) ? null : esc_html( $competition->num_courts_available[ $club->id ] ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                                            <div id="numCourtsAvailableFeedback" class="invalid-feedback"></div>
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li id="liEventDetails" class="club-entry__panel">
                                <div id="entryDetails">
                                    <div class="hgroup" id="event">
                                        <h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
                                        <p class="hgroup__subheading">
                                            <?php
                                            echo esc_html__( 'Select all events your wish to enter', 'racketmanager' );
                                            ?>
                                        </p>
                                    </div>
                                    <div id="eventFeedback" class="invalid-feedback"></div>
                                    <div class="form-checkboxes">
                                        <?php
                                        $competition_events = array();
                                        foreach ( $events as $event ) {
                                            $competition_events[] = $event->id;
                                            ?>
                                            <div class="form-check form-check-lg eventList">
                                                <input class="form-check-input eventId noModal" id="event-<?php echo esc_html( $event->id ); ?>" name="event[<?php echo esc_html( $event->id ); ?>]" type="checkbox" value=<?php echo esc_html( $event->id ); ?> aria-controls="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo esc_attr( $event->status ); ?> <?php echo $changes_allowed ? null : 'disabled'; ?>>
                                                <label class="form-check-label" for="event-<?php echo esc_html( $event->id ); ?>">
                                                    <?php echo esc_html( $event->name ); ?>
                                                </label>
                                                <div id="event-<?php echo esc_html( $event->id ); ?>Feedback" class="invalid-feedback"></div>
                                            </div>

                                            <div class="form-checkboxes__conditional <?php echo $event->status ? '' : 'form-checkboxes__conditional--hidden'; ?>" id="conditional-event-<?php echo esc_html( $event->id ); ?>" <?php echo $event->status ? 'aria-expanded="true"' : ''; ?>>
                                                <div id="event-hint" class="hint">
                                                    <?php esc_html_e( 'Select all teams that you would like to enter', 'racketmanager' ); ?>
                                                </div>
                                                <?php
                                                $event_teams = array();
                                                foreach ( $event->event_teams as $event_team ) {
                                                    $event_teams[] = $event_team->team_id;
                                                    ?>
                                                    <div class="form-check form-check-lg teamEventList">
                                                        <input class="form-check-input teamEventId noModal" id="teamEvent-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" name="teamEvent[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" type="checkbox" value=<?php echo esc_html( $event_team->team_id ); ?> aria-controls="conditional-team-event-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" <?php echo esc_attr( $event_team->status ); ?> <?php echo $changes_allowed ? null : 'disabled'; ?>>

                                                        <label class="form-check-label" for="teamEvent-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>">
                                                            <?php echo esc_html( $event_team->name ); ?>
                                                        </label>
                                                        <input type="hidden" value="<?php echo esc_html( $event_team->name ); ?>" name="teamEventTitle[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" />
                                                        <?php
                                                        if ( isset( $event_team->league_id ) ) {
                                                            ?>
                                                            <input type="hidden" value="<?php echo esc_html( $event_team->league_id ); ?>" name="teamEventLeague[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" />
                                                        <?php } ?>
                                                    </div>
                                                    <div class="form-checkboxes__conditional <?php echo $event_team->status ? '' : 'form-checkboxes__conditional--hidden'; ?>" id="conditional-team-event-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" <?php echo $event_team->status ? 'aria-expanded="true"' : ''; ?>>
                                                        <div class="row">
                                                            <fieldset class="col-md-6">
                                                                <legend><?php esc_html_e( 'Captain', 'racketmanager' ); ?></legend>
                                                                <div class="row">
                                                                    <div class="form-floating mb-3">
                                                                        <?php
                                                                        $captain = $event_team->team_info->captain ?? '';
                                                                        ?>
                                                                        <input type="text" class="form-control teamcaptain" name="captain[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" value="<?php echo esc_html( $captain ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                                                        <label class="form-label" for="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
                                                                        <div id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>Feedback" class="invalid-feedback"></div>
                                                                        <?php
                                                                        $captain_id = $event_team->team_info->captain_id ?? '';
                                                                        ?>
                                                                        <input type="hidden" name="captainId[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" value="<?php echo esc_html( $captain_id ); ?>" />
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6 form-floating mb-3">
                                                                        <?php
                                                                        $contact_no = $event_team->team_info->contactno ?? '';
                                                                        ?>
                                                                        <input type="tel" class="form-control" name="contactno[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" value="<?php echo esc_html( $contact_no ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                                                        <label class="form-label" for="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>"><?php esc_html_e( 'Telephone', 'racketmanager' ); ?></label>
                                                                        <div id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>Feedback" class="invalid-feedback"></div>
                                                                    </div>
                                                                    <div class="col-md-6 form-floating mb-3">
                                                                        <?php
                                                                        $contact_email = $event_team->team_info->contactemail ?? '';
                                                                        ?>
                                                                        <input type="email" class="form-control" name="contactemail[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" value="<?php echo esc_html( $contact_email ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                                                        <label class="form-label" for="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label>
                                                                        <div id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>Feedback" class="invalid-feedback"></div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                            <fieldset class="col-md-6">
                                                                <legend><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
                                                                <div class="row">
                                                                    <div class="col-md-6 form-floating mb-3">
                                                                        <select class="form-select" name="matchday[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" size="1" <?php echo $changes_allowed ? null : 'disabled'; ?>>
                                                                            <?php
                                                                            foreach ( $match_days as $key => $match_day ) {
                                                                                ?>
                                                                                <option value="<?php echo esc_html( $key ); ?>" <?php selected( $match_day, empty( $event_team->team_info->match_day ) ? null : $event_team->team_info->match_day ); ?>><?php echo esc_html( $match_day ); ?></option>
                                                                                <?php
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                        <label class="form-label" for="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
                                                                        <div id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>Feedback" class="invalid-feedback"></div>
                                                                    </div>
                                                                    <div class="col-md-6 form-floating mb-3 match-time">
                                                                        <?php
                                                                        $match_time = $event_team->team_info->match_time ?? '';
                                                                        ?>
                                                                        <input type="time" class="form-control" name="matchtime[<?php echo esc_html( $event->id ); ?>][<?php echo esc_html( $event_team->team_id ); ?>]" id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>" value="<?php echo esc_html( $match_time ); ?>" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                                                        <label class="form-label" for="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
                                                                        <div id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $event_team->team_id ); ?>Feedback" class="invalid-feedback"></div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <input type="hidden" name="event_teams[<?php echo esc_html( $event->id ); ?>]" id="event_teams-<?php echo esc_html( $event->id ); ?>" value="<?php echo esc_html( implode( ',', $event_teams ) ); ?>" />
                                            </div>
                                        <?php } ?>
                                    </div>

                                </div>
                            </li>
                            <li id="liCommentDetails" class="club-entry__panel">
                                <div id="comment_Details">
                                    <div class="hgroup">
                                        <h4 class="hgroup__heading"><?php esc_html( $additional_information ); ?></h4>
                                        <p class="hgroup__subheading">
                                            <?php esc_html_e( 'Please leave any additional information for the League Organiser here', 'racketmanager' ); ?>
                                        </p>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <div class="form-floating">
                                            <textarea class="form-control" placeholder="<?php esc_attr( $additional_information ); ?>" id="commentDetails" name="commentDetails" <?php echo $changes_allowed ? null : 'disabled'; ?>></textarea>
                                            <label for="commentDetails"><?php esc_attr( $additional_information ); ?></label>
                                            <div id="commentDetailsFeedback" class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php require_once RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                        </ol>
                        <div id="entry-acceptance" class="col-12 col-md-8">
                            <div class="form-check form-switch form-check-reverse mb-3">
                                <input class="form-check-input" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false" <?php echo $changes_allowed ? null : 'disabled'; ?> />
                                <label class="form-check-label" for="acceptance">
                                    <?php
                                    $rules_link = '<a href="/rules/' . seo_url( $competition->name ) . '-rules" target="_blank">' . __( 'the rules', 'racketmanager' ) . '</a>';
                                    /* Translators: %s: link to tournament rules */
                                    printf( __( 'I agree to abide by %s.', 'racketmanager' ), $rules_link ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                </label>
                                <div id="acceptanceFeedback" class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="club-entry__footer">
                        <div class="alert_rm" id="entryAlert" style="display:none;">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="entryAlertResponse">
                                </div>
                            </div>
                        </div>
                        <div class="btn__group">
                            <div class="club-entry__submit">
                                <input type="hidden" name="competition_events" id="competition_events" value="<?php echo esc_html( implode( ',', $competition_events ) ); ?>" />
                                <button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" data-type="league" data-action="entry-submit"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></button>
                            </div>
                            <a role="button" href="/clubs/<?php echo esc_html( seo_url( $club->shortcode ) ); ?>/" class="btn btn--cancel"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
