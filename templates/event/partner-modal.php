<?php
/**
 * Template for partner modal
 *
 * @package Racketmanager/Templates/Event
 */

namespace Racketmanager;

/** @var int    $player_id */
/** @var object $event */
/** @var string $modal */
/** @var string $date_end */
/** @var string $season */
/** @var string $partner_name */
/** @var int    $partner_id */
/** @var string $partner_btm */
/** @var string $partner_gender */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="team-partner" class="" action="#" method="post">
            <?php wp_nonce_field( 'team-partner', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="playerId" value="<?php echo esc_attr( $player_id ); ?>" />
            <input type="hidden" name="eventId" value="<?php echo esc_attr( $event->id ); ?>" />
            <input type="hidden" name="dateEnd" value="<?php echo esc_attr( $date_end ); ?>" />
            <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php echo esc_html__( 'Doubles partner', 'racketmanager' ) . ': ' . esc_html( $event->name ); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ui-front">
                <div class="container-fluid">
                    <div id="partnerResponse" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="partnerResponseText"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="">
                            <p><?php esc_html_e( 'Specify your partner.', 'racketmanager' ); ?></p>
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control partner-name" id="partner" name="partner" value="<?php echo esc_attr( $partner_name ); ?>" />
                            <label for="partner"><?php esc_html_e( 'Partner name', 'racketmanager' ); ?></label>
                            <div id="partnerFeedback" class="invalid-feedback"></div>
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control partner-btm" id="partnerBTM" name="partnerBTM" value="<?php echo esc_attr( $partner_btm ); ?>" />
                            <label for="partnerBTM"><?php esc_html_e( 'Partner LTA Number', 'racketmanager' ); ?></label>
                            <div id="partnerBTM-feedback" class="invalid-feedback"></div>
                        </div>
                        <input type="hidden" name="partnerId" id="partnerId" value="<?php echo esc_html( $partner_id ); ?>" />
                        <input type="hidden" id="partnerGender" value="<?php echo esc_html( $partner_gender ); ?>" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                <button type="button" class="btn btn-primary" onclick="Racketmanager.partnerSave(this)"><?php esc_html_e( 'Save', 'racketmanager' ); ?></button>
            </div>
        </form>
    </div>
</div>
