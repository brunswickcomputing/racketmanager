<?php
/**
 * Template for match options modal
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $match */
/** @var string $modal */
/** @var string $title */
/** @var string $option */
/** @var string $action */
/** @var string $button */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="match-option" class="" action="#" method="post">
            <?php wp_nonce_field( 'match-option', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="match_id" value="<?php echo esc_attr( $match->id ); ?>" />
            <input type="hidden" name="home_team" value="<?php echo esc_attr( $match->home_team ); ?>" />
            <input type="hidden" name="away_team" value="<?php echo esc_attr( $match->away_team ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php echo esc_html( $title ); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <?php
                    require_once RACKETMANAGER_PATH . 'templates/includes/matches-teams-match.php';
                    if ( 'schedule_match' === $option ) {
                        if ( empty( $match->start_time ) ) {
                            $date_input_type = 'date';
                            $date_input      = substr( $match->date, 0, 10 );
                        } else {
                            $date_input_type = 'datetime-local';
                            $date_input      = $match->date;
                        }
                        ?>
                        <div class="strike mb-3">
                            <span><?php esc_html_e( '(Re)schedule match', 'racketmanager' ); ?></span>
                        </div>
                        <div class="mb-3">
                            <label for="schedule-date" class="visually-hidden"><?php esc_html_e( 'New date', 'racketmanager' ); ?></label>
                            <input type="<?php echo esc_attr( $date_input_type ); ?>" class="form-control" id="schedule-date" name="schedule-date" value="<?php echo esc_html( $date_input ); ?>" />
                        </div>
                        <div class="alert_rm" id="matchDateAlert" style="display:none;">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="alertMatchDateResponse">
                                </div>
                            </div>
                        </div>
                        <?php
                    } elseif ( 'switch_home' === $option ) {
                        ?>
                        <div class="mb-3">
                            <span><?php esc_html_e( 'Switch home and away?', 'racketmanager' ); ?></span>
                        </div>
                        <?php
                    } elseif ( 'reset_match_result' === $option ) {
                        ?>
                        <div class="strike mb-3">
                            <span><?php esc_html_e( 'Reset match result', 'racketmanager' ); ?></span>
                        </div>
                        <div class="mb-3">
                            <p class="text-center"><?php esc_html_e( 'This will remove scores and winner/loser', 'racketmanager' ); ?>.</p>
                        </div>
                        <div class="alert_rm" id="resetMatchAlert" style="display:none;">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="alertResetMatchResponse">
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                <?php
                if ( ! empty( $button ) ) {
                    ?>
                    <button type="button" class="btn btn-primary" id="actionButton" data-action="<?php echo esc_attr( $action ); ?>" data-is-tournament="<?php echo esc_attr( $match->league->event->competition->is_tournament ); ?>"><?php echo esc_html( $button ); ?></button>
                    <?php
                }
                ?>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    document.getElementById('actionButton').addEventListener('click', function (e) {
        let action = this.dataset.action;
        let isTournament = this.dataset.isTournament;
        if (action === 'setMatchDate') {
            Racketmanager.setMatchDate(e, this, isTournament);
        } else if (action === 'switchHomeAway' ) {
            Racketmanager.switchHomeAway(e, this, isTournament);
        } else if (action === 'resetMatchResult' ) {
            Racketmanager.resetMatchResult(e, this, isTournament);
        }
    });
</script>

