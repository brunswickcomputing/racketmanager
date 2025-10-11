<?php
/**
 *
 * Template page to team for a club
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\util\Util_Lookup;

/** @var object $club */
/** @var bool   $user_can_update */
$match_days   = Util_Lookup::get_match_days();
$header_level = 1;
require_once RACKETMANAGER_PATH . 'templates/includes/club-header.php';
$team  = $club->team;
$event = $club->event;
?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="media__title">
                <span><?php echo esc_html( $team->title ); ?> - <?php echo esc_html( $event->name ); ?></span>
            </h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <form id="team-update-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>-Frm" action="" method="post" class="">
                    <?php wp_nonce_field( 'team-update', 'racketmanager_nonce' ); ?>
                    <input type="hidden" id="team_id" name="team_id" value="<?php echo esc_html( $team->id ); ?>" />
                    <input type="hidden" id="event_id" name="event_id" value="<?php echo esc_html( $event->id ); ?>" />
                    <div class="form-control mb-3">
                        <legend><?php esc_html_e( 'Captain', 'racketmanager' ); ?></legend>
                        <?php
                        if ( ! empty( $team->captain ) || $user_can_update ) {
                            ?>
                            <div class="row">
                                <div class="form-floating mb-3">
                                    <input type="text" class="teamcaptain form-control" id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->captain ); ?>" <?php disabled( $user_can_update, false ); ?> />
                                    <input type="hidden" id="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->captain_id ); ?>" />
                                    <label for="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
                                    <div id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="row g-3">
                            <?php
                            if ( is_user_logged_in() ) {
                                if ( ! empty( $team->contactno ) || $user_can_update ) {
                                    ?>
                                    <div class="col-sm-6 mb-3">
                                        <div class="form-floating">
                                            <input type="tel" class="form-control" id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->contactno ); ?>" <?php disabled( $user_can_update, false ); ?> />
                                            <label for="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
                                            <div id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                                if ( ! empty( $team->contactemail ) || $user_can_update ) {
                                    ?>
                                    <div class="col-sm-6 mb-3">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->contactemail ); ?>" size="30" <?php disabled( $user_can_update, false ); ?> />
                                            <label for="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
                                            <div id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <fieldset class="form-control mb-3">
                        <legend><?php esc_html_e( 'Match times', 'racketmanager' ); ?></legend>
                        <div class="row g-3">
                            <div class="col-sm-6 mb-3">
                                <?php
                                if ( ! empty( $team->match_day ) ) {
                                    ?>
                                    <div class="form-floating">
                                        <?php
                                        if ( $user_can_update ) {
                                            ?>
                                            <select class="form-select" size="1" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" >
                                                <option><?php esc_html_e( 'Select match day', 'racketmanager' ); ?></option>
                                                <?php
                                                foreach ( $match_days as $key => $matchday ) {
                                                    ?>
                                                    <option value="<?php echo esc_html( $key ); ?>" <?php selected( $matchday, $team->match_day ); ?> <?php disabled( $user_can_update, false ); ?>><?php echo esc_html( $matchday ); ?></option>
                                                    <?php
                                                }
                                                ?>
                                            </select>
                                            <div id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                            <?php
                                        } else {
                                            ?>
                                            <input type="text" class="form-control" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->match_day ); ?>" <?php disabled( $user_can_update, false ); ?> />
                                            <?php
                                        }
                                        ?>
                                        <label for="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <?php
                                if ( ! empty( $team->match_time ) || $user_can_update ) {
                                    ?>
                                    <div class="form-floating">
                                        <input type="time" class="form-control" id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $team->match_time ); ?>" size="30" <?php disabled( $user_can_update, false ); ?> />
                                        <label for="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
                                        <div id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </fieldset>
                    <div class="row mb-3">
                        <div class="match__buttons">
                            <a href="/clubs/<?php echo esc_html( sanitize_title( $club->shortcode ) ); ?>/competitions/" class="btn btn-secondary text-uppercase" type="button"><?php esc_html_e( 'Return to competitions', 'racketmanager' ); ?></a>
                            <?php
                            if ( $user_can_update ) {
                                ?>
                                <button class="btn btn-primary" type="button" id="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" onclick="Racketmanager.updateTeam(this)">
                                        <?php esc_html_e( 'Update details', 'racketmanager' ); ?>
                                </button>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div id="teamUpdateResponse-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" class="alert_rm" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="teamUpdateResponseText-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"></span>
                            </div>
                        </div>
                    </div>
                    <div class="updateResponse" id="updateTeamResponse-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="updateTeamResponse-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"></div>
                </form>
            </div>
        </div>
    </div>
