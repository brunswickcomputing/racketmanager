<?php
/**
 * Template page to display a tournament entry form
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $tournament: tournament object
 *  $events: events object
 *  $player: player object
 *  $club_memberships: club memberships array
 *  $season: season name
 *  $type: event type
 *  $malePartners: male partners object
 *  $femalePartners: female partners object
 *  $player_entries: array of existing entries for the player
 *  $tournament_entry: object containing entry metadata
 *  $changes_allowed: whether the current user may change the entry (supplied by the handler)
 *  $withdrawal_allowed: whether the current user may withdraw from events (supplied by the handler)
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $player */
/** @var object $tournament */
/** @var array $club_memberships */
/** @var array $player_entries */
/** @var object $tournament_entry */
/** @var bool|null $changes_allowed */
/** @var bool|null $withdrawal_allowed */

// Permissions are decided by the producer; without them the form is read-only.
$changes_allowed    = (bool) ( $changes_allowed ?? false );
$withdrawal_allowed = (bool) ( $withdrawal_allowed ?? false );
// Consumed by templates/includes/tournament-header.php (hides header "Enter" button).
$entry_option       = false;

$entered        = ! empty( $player_entries );
$form_title     = $entered ? __( 'Entry details', 'racketmanager' ) : __( 'Enter online', 'racketmanager' );
$readonly_attr  = $changes_allowed ? '' : 'readonly';
$disabled_attr  = ( $changes_allowed || $withdrawal_allowed ) ? '' : 'disabled';
$player_initial = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
$has_age        = ! empty( $player->age );
$player_age     = $has_age ? $player->age : __( 'Unknown', 'racketmanager' );
$filter_age     = $has_age ? $player->age : 'unknown';

$gender = match ( $player->gender ) {
    'M'     => __( 'Male', 'racketmanager' ),
    'F'     => __( 'Female', 'racketmanager' ),
    default => '',
};

// Determine warning/info alerts when changes are not allowed.
$alert_class = '';
$alert_msg   = [];

if ( ! $changes_allowed ) {
    if ( $tournament->is_closed ) {
        $alert_class = 'warning';
        $alert_msg   = [
            __( 'Tournament entries are now closed.', 'racketmanager' ),
            __( 'You can still withdraw from events.', 'racketmanager' ),
        ];
    } elseif ( ! $tournament->is_open ) {
        if ( $entered ) {
            $alert_class = 'info';
            $alert_msg   = [
                __( 'Tournament entries are now closed.', 'racketmanager' ),
                __( 'These are the latest entry details.', 'racketmanager' ),
            ];
        } else {
            $alert_class = 'warning';
            $alert_msg   = [
                __( 'Tournament not currently open for entries', 'racketmanager' ),
            ];
        }
    } else {
        $alert_class = 'info';
        $alert_msg   = [
            __( 'You can not make changes to a entry form for someone else.', 'racketmanager' ),
        ];
    }
}
?>
<div class="container">
    <?php require RACKETMANAGER_PATH . 'templates/includes/tournament-header.php'; ?>
    <form id="form-entry" action="" method="post">
        <?php wp_nonce_field( 'racketmanager_tournament-entry', 'racketmanager_nonce' ); ?>
        <input type="hidden" name="tournamentId" id="tournamentId" value="<?php echo esc_attr( $tournament->id ); ?>" />
        <input type="hidden" name="tournamentDateEnd" id="tournamentDateEnd" value="<?php echo esc_attr( $tournament->date_end ); ?>" />
        <input type="hidden" name="season" id="season" value="<?php echo esc_attr( $tournament->season ); ?>" />
        <input type="hidden" name="playerId" id="playerId" value="<?php echo esc_attr( $player->id ); ?>" />
        <input type="hidden" id="playerGender" value="<?php echo esc_attr( $player->gender ); ?>" />
        <input type="hidden" id="competitionFee" name="competitionFee" value="<?php echo esc_attr( $tournament->fees->competition ); ?>" />
        <div class="module module--card">
            <div class="module__content">
                <div class="module-container">
                    <div class="entry-subhead">
                        <div class="hgroup">
                            <h3 class="hgroup__heading">
                                <?php echo esc_html( $form_title ); ?>
                            </h3>
                            <?php
                            $closing_display = $tournament_dates->date_closing_display ?? ( $date_closing_display ?? '' );
                            $closing_date    = $tournament->date_closing ?? '';
                            if ( ! empty( $closing_date ) && ! empty( $closing_display ) ) :
                                ?>
                                <span class="hgroup__subheading">
                                    <?php esc_html_e( 'Entry deadline', 'racketmanager' ); ?>
                                    <time datetime="<?php echo esc_attr( $closing_date ); ?>"><?php echo esc_html( $closing_display ); ?></time>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="entry-subhead__aside">
                            <a href="/rules/tournament-rules/" target="_blank" class="btn btn-primary">
                                <?php esc_html_e( 'Tournament Rules', 'racketmanager' ); ?>
                            </a>
                        </div>
                    </div>
                    <?php if ( ! $changes_allowed && ! empty( $alert_msg ) ) : ?>
                        <div class="alert_rm mt-3 alert--<?php echo esc_attr( $alert_class ); ?>">
                            <div class="alert__body">
                                <?php foreach ( $alert_msg as $msg ) : ?>
                                    <div class="alert__body-inner">
                                        <?php echo esc_html( $msg ); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="individual-entry__body">
                        <div id="personal-details">
                            <div class="media">
                                <div class="media__wrapper">
                                    <div class="media__img">
                                        <span class="profile-icon">
                                            <span class="profile-icon__abbr">
                                                <?php echo esc_html( $player_initial ); ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="media__content">
                                        <h4 class="media__title"><?php echo esc_html( $player->display_name ); ?></h4>
                                        <div class="media__content-subinfo">
                                            <div class="media__subheading">
                                                <ul class="list list--inline player-atts">
                                                    <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_attr_e( 'Gender', 'racketmanager' ); ?>">
                                                        <span><?php echo esc_html( $gender ); ?></span>
                                                    </li>
                                                    <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?php esc_attr_e( 'Playing age for tournament', 'racketmanager' ); ?>">
                                                        <span><?php echo esc_html( $player_age ); ?></span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="media__content-subinfo">
                                            <ul class="list list--inline">
                                                <?php
                                                $rating      = $player->wtn;
                                                $help_text   = __( 'World Tennis Number for', 'racketmanager' );
                                                $match_types = Util_Lookup::get_match_types();
                                                foreach ( $match_types as $match_type => $description ) :
                                                    $rating_value = $rating[ $match_type ] ?? '';
                                                    $tooltip_text = sprintf( '%s %s', $help_text, $description );
                                                    ?>
                                                    <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo esc_attr( $tooltip_text ); ?>">
                                                        <span class="tag tag-pair">
                                                            <span class="tag-pair__title"><?php echo esc_html( $description ); ?></span>
                                                            <span class="tag-pair__value"><?php echo esc_html( $rating_value ); ?></span>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ol class="list list--naked" id="entry-details">
                            <li id="liPlayerDetails" class="individual-entry__panel">
                                <div id="playerDetails">
                                    <div class="hgroup">
                                        <h4 class="hgroup__heading"><?php esc_html_e( 'My details', 'racketmanager' ); ?></h4>
                                        <?php if ( $changes_allowed ) : ?>
                                            <p class="hgroup__subheading"><?php esc_html_e( 'Check if your details are correct, and change them if necessary', 'racketmanager' ); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="row">
                                        <div id="contactDetails" class="col-12 col-md-6">
                                            <div class="border p-3">
                                                <h5 class="subheading"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></h5>
                                                <dl class="list list--flex">
                                                    <div class="list__item">
                                                        <dt class="list__label"><label for="contactno"><?php esc_html_e( 'Phone', 'racketmanager' ); ?></label></dt>
                                                        <dd class="list__value">
                                                            <input type="tel" class="form-control" id="contactno" name="contactno" value="<?php echo esc_attr( $player->contactno ); ?>" <?php echo $readonly_attr; ?> />
                                                            <div id="contactnoFeedback" class="invalid-feedback"></div>
                                                        </dd>
                                                    </div>
                                                    <div class="list__item">
                                                        <dt class="list__label"><label for="contactemail"><?php esc_html_e( 'Email', 'racketmanager' ); ?></label></dt>
                                                        <dd class="list__value">
                                                            <input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo esc_attr( $player->user_email ); ?>" <?php echo $readonly_attr; ?> />
                                                            <div id="contactemailFeedback" class="invalid-feedback"></div>
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>
                                        <div id="membershipDetails" class="col-12 col-md-6">
                                            <div class="border p-3">
                                                <h5 class="subheading"><?php esc_html_e( 'Membership', 'racketmanager' ); ?></h5>
                                                <dl class="list list--flex">
                                                    <div class="list__item">
                                                        <dt class="list__label"><?php esc_html_e( 'Club', 'racketmanager' ); ?></dt>
                                                        <dd class="list__value">
                                                            <?php
                                                            $membership_count = count( $club_memberships );
                                                            if ( 1 === $membership_count ) :
                                                                $single_club_name = $club_memberships[0]->club_display_name ?? '';
                                                                ?>
                                                                <span class="form-control"><?php echo esc_html( $single_club_name ); ?></span>
                                                                <input type="hidden" id="clubId" name="clubId" value="<?php echo esc_attr( $club_memberships[0]->club_id ); ?>" />
                                                            <?php elseif ( 0 === $membership_count ) : ?>
                                                                <span class="form-control"><?php esc_html_e( 'No club', 'racketmanager' ); ?></span>
                                                                <input type="hidden" id="clubId" name="clubId" value="" />
                                                            <?php else :
                                                                $selected_club_id = $tournament_entry->club_id ?? null;
                                                                ?>
                                                                <label for="clubId" class="visually-hidden"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
                                                                <select class="form-select" size="1" name="clubId" id="clubId" <?php echo $readonly_attr; ?>>
                                                                    <option value="" disabled <?php selected( empty( $selected_club_id ) ); ?>><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
                                                                    <?php foreach ( $club_memberships as $club_player ) :
                                                                        $membership_club_id   = $club_player->club_id ?? ( $club_player->id ?? 0 );
                                                                        $membership_club_name = $club_player->club_display_name ?? '';
                                                                        ?>
                                                                        <option value="<?php echo esc_attr( $membership_club_id ); ?>" <?php selected( (int) $membership_club_id, (int) $selected_club_id ); ?>>
                                                                            <?php echo esc_html( $membership_club_name ); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div id="clubIdFeedback" class="invalid-feedback"></div>
                                                            <?php endif; ?>
                                                        </dd>
                                                    </div>
                                                    <div class="list__item">
                                                        <dt class="list__label"><label for="btm"><?php esc_html_e( 'LTA Number', 'racketmanager' ); ?></label></dt>
                                                        <dd class="list__value">
                                                            <input type="number" class="form-control" id="btm" name="btm" value="<?php echo esc_attr( $player->btm ); ?>" <?php echo $readonly_attr; ?> />
                                                            <div id="btmFeedback" class="invalid-feedback"></div>
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li id="liEventDetails" class="individual-entry__panel">
                                <div id="entryDetails">
                                    <div class="hgroup" id="event">
                                        <h4 class="hgroup__heading"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h4>
                                        <?php if ( ! empty( $tournament->num_entries ) ) : ?>
                                            <p class="hgroup__subheading">
                                                <?php
                                                /* translators: %d: maximum number of events. */
                                                echo esc_html( sprintf( __( 'You may enter a maximum of %d events.', 'racketmanager' ), $tournament->num_entries ) );
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="hgroup__subheading">
                                            <?php
                                            /* Translators: %1$s: gender %2$s age. */
                                            echo esc_html( sprintf( __( 'Events are filtered by your gender (%1$s) and playing age (%2$s)', 'racketmanager' ), $gender, $filter_age ) );
                                            ?>
                                        </p>
                                    </div>
                                    <div id="eventFeedback" class="invalid-feedback"></div>
                                </div>
                                <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                                <div class="container form-checkboxes">
                                    <?php
                                    $fee_total         = 0;
                                    $tournament_events = [];
                                    $events_entered    = 0;

                                    if ( empty( $events ) ) :
                                        ?>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert_rm mt-3 alert--warning">
                                                    <div class="alert__body">
                                                        <div class="alert__body-inner">
                                                            <?php esc_html_e( 'There are no events that you are eligible to enter.', 'racketmanager' ); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    else :
                                        foreach ( $events as $event ) :
                                            $is_doubles     = ( substr( $event->type, 1, 1 ) === 'D' );
                                            $event_entered  = isset( $player_entries[ $event->id ] );
                                            $event_disabled = ! $changes_allowed;

                                            $partner_id   = null;
                                            $partner_name = null;

                                            if ( $event_entered ) {
                                                if ( $withdrawal_allowed ) {
                                                    $event_disabled = false;
                                                }
                                                ++$events_entered;
                                                $fee_total += $tournament->fees->event;

                                                $player_entry = $player_entries[ $event->id ];
                                                $partner_id   = ! empty( $player_entry->partner_id ) ? $player_entry->partner_id : null;
                                                $partner_name = ! empty( $player_entry->partner_name ) ? $player_entry->partner_name : null;
                                            }
                                            ?>
                                            <div class="row">
                                                <div class="col-8 col-lg-6 tournament-entry--row">
                                                    <div class="form-check form-check-lg">
                                                        <input type="hidden" name="eventFee[<?php echo esc_attr( $event->id ); ?>]" id="eventFee-<?php echo esc_attr( $event->id ); ?>" value="<?php echo esc_attr( $tournament->fees->event ); ?>" />
                                                        <input class="form-check-input form-check--event hasModal" id="event-<?php echo esc_attr( $event->id ); ?>" name="event[<?php echo esc_attr( $event->id ); ?>]" type="checkbox" value="<?php echo esc_attr( $event->id ); ?>" aria-controls="conditional-event-<?php echo esc_attr( $event->id ); ?>" <?php checked( $event_entered ); ?> <?php disabled( $event_disabled ); ?> />
                                                        <label class="form-check-label" for="event-<?php echo esc_attr( $event->id ); ?>"><?php echo esc_html( $event->name ); ?></label>
                                                        <div id="event-<?php echo esc_attr( $event->id ); ?>Feedback" class="invalid-feedback"></div>
                                                    </div>
                                                </div>
                                                <div class="col-4 col-lg-6">
                                                    <div class="container">
                                                        <div class="row tournament-entry--row">
                                                            <div class="col-6 <?php echo $is_doubles ? 'is-doubles' : ''; ?>" id="conditional-event-<?php echo esc_attr( $event->id ); ?>">
                                                                <?php if ( $is_doubles ) : ?>
                                                                    <input type="hidden" name="partner[<?php echo esc_attr( $event->id ); ?>]" id="partner-<?php echo esc_attr( $event->id ); ?>" value="<?php echo esc_attr( $partner_id ); ?>" />
                                                                    <input type="hidden" name="partnerId[<?php echo esc_attr( $event->id ); ?>]" id="partnerId-<?php echo esc_attr( $event->id ); ?>" value="<?php echo esc_attr( $partner_id ); ?>" />
                                                                    <?php if ( $changes_allowed ) : ?>
                                                                        <a href="#" class="tournamentEventEntry" data-event-id="<?php echo esc_attr( $event->id ); ?>" data-action="open-partner-modal">
                                                                            <span id="partnerName-<?php echo esc_attr( $event->id ); ?>"><?php echo esc_html( $partner_name ); ?></span>
                                                                        </a>
                                                                    <?php else : ?>
                                                                        <span id="partnerName-<?php echo esc_attr( $event->id ); ?>"><?php echo esc_html( $partner_name ); ?></span>
                                                                    <?php endif; ?>
                                                                    <div id="partner-<?php echo esc_attr( $event->id ); ?>Feedback" class="invalid-feedback"></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <?php if ( $tournament->fees->event > '' ) : ?>
                                                                    <span class="event-price" id="event-price-fmt-<?php echo esc_attr( $event->id ); ?>">
                                                                        <?php
                                                                        if ( $event_entered ) {
                                                                            the_currency_amount( $tournament->fees->event );
                                                                        }
                                                                        ?>
                                                                    </span>
                                                                    <input type="hidden" class="event-price-amt" name="event-price[<?php echo esc_attr( $event->id ); ?>]" id="event-price-<?php echo esc_attr( $event->id ); ?>" value="<?php echo $event_entered ? esc_attr( $tournament->fees->event ) : ''; ?>" />
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                            $tournament_events[] = $event->id;
                                        endforeach;
                                    endif;
                                    ?>
                                    <input type="hidden" name="eventsEntered" id="eventsEntered" value="<?php echo esc_attr( $events_entered ); ?>" />
                                    <input type="hidden" name="tournamentEvents" value="<?php echo esc_attr( implode( ',', $tournament_events ) ); ?>" />
                                </div>
                            </li>
                            <li id="liCommentDetails" class="individual-entry__panel">
                                <div id="comment_Details">
                                    <div class="hgroup">
                                        <h4 class="hgroup__heading"><?php esc_html_e( 'Additional information', 'racketmanager' ); ?></h4>
                                        <p class="hgroup__subheading">
                                            <?php esc_html_e( 'Please leave any additional information for the Tournament Organiser including medical conditions here', 'racketmanager' ); ?>
                                        </p>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <div class="form-floating">
                                            <textarea class="form-control" placeholder="<?php esc_attr_e( 'Information', 'racketmanager' ); ?>" id="commentDetails" name="commentDetails" <?php echo $readonly_attr; ?>></textarea>
                                            <label for="commentDetails"><?php esc_html_e( 'Additional information', 'racketmanager' ); ?></label>
                                            <div id="commentDetailsFeedback" class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                        </ol>
                        <div id="entry-acceptance" class="col-12 col-md-8">
                            <div class="form-check form-switch form-check-reverse mb-3">
                                <input class="form-check-input" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false" <?php echo $disabled_attr; ?> />
                                <label class="form-check-label" for="acceptance">
                                    <?php
                                    $rules_link = '<a href="/rules/tournament-rules" target="_blank">' . esc_html__( 'the rules of the tournament', 'racketmanager' ) . '</a>';
                                    /* translators: %s: link to tournament rules */
                                    printf( esc_html__( 'I agree to abide by %s.', 'racketmanager' ), $rules_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                </label>
                                <div id="acceptanceFeedback" class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="individual-entry__footer">
                        <div class="alert_rm" id="entryAlert" style="display:none;">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="entryAlertResponse"></div>
                            </div>
                        </div>
                        <div class="price-row">
                            <?php $fee_total += $tournament->fees->competition; ?>
                            <div class="price-cost" id="priceCostTotalFmt">
                                <?php
                                if ( ! empty( $fee_total ) ) {
                                    echo esc_html__( 'Total:', 'racketmanager' ) . ' ';
                                }
                                the_currency_amount( $fee_total );
                                ?>
                            </div>
                            <input type="hidden" name="priceCostTotal" id="priceCostTotal" value="<?php echo esc_attr( $fee_total ); ?>" />
                        </div>
                        <?php if ( ! empty( $tournament->payments ) ) : ?>
                            <div class="price-row">
                                <div class="price-cost" id="pricePaidTotalFmt">
                                    <?php echo esc_html__( 'Paid:', 'racketmanager' ) . ' '; ?><?php the_currency_amount( $tournament->payments ); ?>
                                </div>
                                <input type="hidden" name="pricePaidTotal" id="pricePaidTotal" value="<?php echo esc_attr( $tournament->payments ); ?>" />
                            </div>
                        <?php endif; ?>
                        <div class="btn__group">
                            <?php if ( $changes_allowed || $withdrawal_allowed ) : ?>
                                <div class="individual-entry__submit">
                                    <button type="submit" class="btn btn-primary" id="entrySubmit" name="entrySubmit" data-type="tournament" data-action="entry-submit">
                                        <?php esc_html_e( 'Enter', 'racketmanager' ); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <a href="<?php echo esc_url( $back_url ); ?>" class="btn btn--cancel">
                                <?php esc_html_e( 'Back', 'racketmanager' ); ?>
                            </a>
                            <?php if ( $withdrawal_allowed ) : ?>
                                <a class="btn btn--withdraw" id="tournamentWithdraw" data-action="withdraw-tournament">
                                    <?php esc_html_e( 'Withdraw', 'racketmanager' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<div class="modal" id="partnerModal" tabindex="-1"></div>
