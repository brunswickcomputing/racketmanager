<?php
/**
 * Template for rubber status modal
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $rubber */
/** @var object $match */
/** @var string $modal */
/** @var array  $select */
/** @var string $status */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="match-rubber-status" class="" action="#" method="post">
            <?php wp_nonce_field( 'match-rubber-status', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="rubber_id" value="<?php echo esc_attr( $rubber->id ); ?>" />
            <input type="hidden" name="rubber_number" value="<?php echo esc_attr( $rubber->rubber_number ); ?>" />
            <input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
            <input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Score status', 'racketmanager' ); ?> - <?php echo esc_html( $rubber->title ); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div id="scoreStatusResponse" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="scoreStatusResponseText"></span>
                            </div>
                        </div>
                    </div>
                    <div id="splashBlockRubber">
                        <div class="row" id="splashHide">
                        <div class="col-sm-6">
                            <label>
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
                            </label>
                            <div id="score_statusFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-6">
                            <ul class="list list--naked">
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'The match has not started and at least one team cannot play.', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Retired', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'A player retired from a match in progress.', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php esc_html_e( 'Abandoned', 'racketmanager' ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'The match is partially played (and will not be finished)', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                                <li class="list__item">
                                    <dl>
                                        <dt class=""><?php echo esc_html( $this->not_played ); ?></dt>
                                        <dd class=""><?php esc_html_e( 'Not played (and will not be played)', 'racketmanager' ); ?></dd>
                                    </dl>
                                </li>
                            </ul>
                        </div>
                    </div>
                        <?php require_once RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                <button type="button" class="btn btn-primary" onclick="Racketmanager.setMatchRubberStatus(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
            </div>
        </form>
    </div>
</div>

