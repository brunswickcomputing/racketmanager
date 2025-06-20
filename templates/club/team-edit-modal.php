<?php
/**
 * Template for team edit modal
 *
 * @package Racketmanager/Templates/Club
 */

namespace Racketmanager;

/** @var object $event */
/** @var object $team */
/** @var string $modal */
/** @var array  $match_days */
/** @var object $event_team */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="team-update" class="" action="#" method="post">
            <?php wp_nonce_field( 'team-update', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="team_id" value="<?php echo esc_attr( $team->id ); ?>" />
            <input type="hidden" name="event_id" value="<?php echo esc_attr( $event->id ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <input type="hidden" name="clubId" id="clubId" value="<?php echo esc_attr( $team->club_id ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Edit team', 'racketmanager' ) ; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ui-front">
                <div class="container-fluid">
                    <div id="teamUpdateResponse-<?php echo esc_attr( $event->id ); ?>-<?php echo esc_attr( $team->id ); ?>" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="teamUpdateResponseText-<?php echo esc_attr( $event->id ); ?>-<?php echo esc_attr( $team->id ); ?>"></span>
                            </div>
                        </div>
                    </div>
                    <fieldset class="form-control mb-3">
                        <legend><?php esc_html_e( 'Captain', 'racketmanager' ); ?></legend>
                        <div class="row">
                            <div class="form-floating mb-3">
                                <input type="text" class="teamcaptain form-control" id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->captain ); ?>" />
                                <input type="hidden" id="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="captainId-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->captain_id ); ?>" />
                                <label for="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
                                <div id="captain-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->contactno ); ?>" />
                                    <label for="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
                                    <div id="contactno-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->contactemail ); ?>" size="30" />
                                    <label for="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
                                    <div id="contactemail-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="form-control mb-3">
                        <legend><?php esc_html_e( 'Match times', 'racketmanager' ); ?></legend>
                        <div class="row g-3">
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <select class="form-select" size="1" name="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" >
                                        <option disabled <?php selected( null, empty( $event_team->match_day ) ? null : 1 ); ?>><?php esc_html_e( 'Select match day', 'racketmanager' ); ?></option>
                                        <?php
                                        foreach ( $match_days as $key => $matchday ) {
                                            ?>
                                            <option value="<?php echo esc_html( $key ); ?>" <?php selected( $matchday, empty( $event_team->match_day ) ? null : $event_team->match_day ); ?>><?php echo esc_html( $matchday ); ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <div id="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                    <label for="matchday-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" value="<?php echo esc_html( $event_team->match_time ); ?>" size="30" />
                                    <label for="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
                                    <div id="matchtime-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>Feedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                    <button class="btn btn-primary" type="button" id="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" name="teamUpdateSubmit-<?php echo esc_html( $event->id ); ?>-<?php echo esc_html( $team->id ); ?>" onclick="Racketmanager.updateTeam(this)">
                        <?php esc_html_e( 'Update details', 'racketmanager' ); ?>
                    </button>
                </div>
        </form>
    </div>
</div>
