<?php
/**
 * Template for match options modal
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;

/** @var Fixture_Details_DTO $dto */
/** @var Fixture $match */
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
            <input type="hidden" name="match_id" value="<?php echo esc_attr( $match->get_id() ); ?>" />
            <input type="hidden" name="home_team" value="<?php echo esc_attr( $match->get_home_team() ); ?>" />
            <input type="hidden" name="away_team" value="<?php echo esc_attr( $match->get_away_team() ); ?>" />
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
                        if ( empty( $match->get_start_time() ) ) {
                            $date_input_type = 'date';
                            $date_input      = substr( $match->get_date(), 0, 10 );
                        } else {
                            $date_input_type = 'datetime-local';
                            $date_input      = $match->get_date();
                        }
                        ?>
                        <div class="strike mb-3">
                            <span><?php esc_html_e( '(Re)schedule fixture', 'racketmanager' ); ?></span>
                        </div>
                        <div class="mb-3">
                            <label for="schedule-date" class="visually-hidden"><?php esc_html_e( 'New date', 'racketmanager' ); ?></label>
                            <input type="<?php echo esc_attr( $date_input_type ); ?>" class="form-control" id="schedule-date" name="schedule-date" value="<?php echo esc_html( $date_input ); ?>" />
                            <div class="invalid-feedback" id="schedule-dateFeedback"></div>
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
                        <div class="text-center">
                            <span><?php esc_html_e( 'Switch home and away?', 'racketmanager' ); ?></span>
                        </div>
                        <div class="alert_rm" id="switchFixtureAlert" style="display:none;">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="alertSwitchFixtureResponse">
                                </div>
                            </div>
                        </div>
                        <?php
                    } elseif ( 'reset_fixture_result' === $option ) {
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
                    <?php
                        // Map legacy action identifiers to modular data-action values
                        $data_action = '';
                        if ( 'setMatchDate' === $action ) {
                            $data_action = 'set-match-date';
                        } elseif ( 'switchHomeAway' === $action ) {
                            $data_action = 'switch-home-away';
                        } elseif ( 'resetMatchResult' === $action ) {
                            $data_action = 'reset-match-result';
                        }
                    ?>
                    <button type="button" class="btn btn-primary" id="actionButton" data-action="<?php echo esc_attr( $data_action ); ?>" data-is-tournament="<?php echo empty( $dto->competition->is_tournament ) ? 'false' : 'true'; ?>"><?php echo esc_html( $button ); ?></button>
                    <?php
                }
                ?>
            </div>
        </form>
    </div>
</div>

