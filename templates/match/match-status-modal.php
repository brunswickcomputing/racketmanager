<?php
/**
 * Template for match status modal
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $modal */
/** @var array  $select */
/** @var string $status */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="match-status" class="" action="#" method="post">
            <?php wp_nonce_field( 'match-status', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>" />
            <input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
            <input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Match status', 'racketmanager' ); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div id="matchStatusResponse" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="matchStatusResponseText"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="score_status" class="visually-hidden"><?php esc_html_e( 'Status', 'racketmanager' ); ?></label>
                            <select class="form-select" name="score_status" id="score_status">
                                <option value="" disabled selected><?php esc_html_e( 'Status', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $select as $option ) {
                                    ?>
                                    <option value="<?php echo esc_attr( $option->value ); ?>" <?php selected( $option->select, $status ); ?>><?php echo esc_html( $option->desc ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <div id="score_statusFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-6">
                            <ul class="list list--naked">
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Match not played and one team did not show', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'The match has not started and at least one team cannot play.', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <?php
                                if ( ! $match->league->num_rubbers ) {
                                    ?>
                                    <li class="list__item">
                                        <dl>
                                            <dt class=""><?php esc_html_e( 'Retired', 'racketmanager' ); ?></dt>
                                            <dd class=""><?php esc_html_e( 'A player retired from a match in progress.', 'racketmanager' ); ?></dd>
                                        </dl>
                                    </li>
                                    <?php
                                }
                                ?>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Cancelled', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'Not played (and will not be played - no points awarded)', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php echo esc_html( $this->not_played ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'Not played (and will not be played)', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <?php
                                if ( $match->league->event->competition->is_team_entry ) {
                                    ?>
                                    <li class="list__item">
                                        <dl>
                                            <dt class=""><?php esc_html_e( 'Abandoned', 'racketmanager' ); ?></dt>
                                            <dd class=""><?php esc_html_e( 'The match is partially played (and will not be finished)', 'racketmanager' ); ?></dd>
                                        </dl>
                                    </li>
                                    <?php
                                }
                                ?>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Reset', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'Clear match status', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                <button type="button" class="btn btn-primary" onclick="Racketmanager.setMatchStatus(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
            </div>
        </form>
    </div>
</div>
