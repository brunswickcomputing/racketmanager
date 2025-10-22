<?php
/**
 * Template for tournament withdrawal modal
 *
 * @package Racketmanager/Templates/Tournament
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var string $modal */
/** @var object $player */
/** @var string $msg */
/** @var int    $events_entered */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="tournament-withdrawal" class="" action="#" method="post">
            <?php wp_nonce_field( 'team-partner', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="tournamentId" value="<?php echo esc_attr( $tournament->id ); ?>" />
            <input type="hidden" name="playerId" value="<?php echo esc_attr( $player->id ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Withdraw', 'racketmanager' ) ; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ui-front">
                <div class="container-fluid">
                    <div id="withdrawResponse" class="alert_rm alert--danger" <?php echo $events_entered ? 'style="display: none;"' : null; ?>>
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="withdrawResponseText"><?php echo esc_html( $msg ); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ( $events_entered ) {
                        ?>
                        <div class="row">
                            <div class="">
                                <p><?php esc_html_e( 'You will be withdrawn from all events if you proceed.', 'racketmanager' ); ?></p>
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
                if ( $events_entered ) {
                    ?>
                    <button type="button" class="btn btn-primary" data-action="confirm-tournament-withdrawal"><?php esc_html_e( 'Withdraw', 'racketmanager' ); ?></button>
                    <?php
                }
                ?>
            </div>
        </form>
    </div>
</div>

