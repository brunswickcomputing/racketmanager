<?php
/*
//  tournament-payment-complete.php
//  Racketmanager
//
//  Created by Paul Moffat on 27/01/2025.
//
 */

namespace Racketmanager;

/** @var object $tournament_entry */
/** @var float  $total_due */
/** @var object $tournament */
/** @var object $player */
/** @var object $stripe */
$msgs = array();
$payment_required = false;
if ( $tournament_entry ) {
    $msgs[] = __( 'You have been entered into the tournament.', 'racketmanager' );
    if ( $total_due ) {
        if ( $total_due > 0 ) {
            $msgs[] = __( 'You now need to pay your entry fee.', 'racketmanager' );
            $alert_type = 'success';
            $payment_required = true;
            $payment_complete_url = RACKETMANAGER_SITE . 'entry-form/' . seo_url( $tournament->name ) . '-tournament/payment-complete/';
        } else {
            $msgs[] = __( 'You are due a refund which will be processed when entries close.', 'racketmanager' );
            $alert_type = 'warning';
        }
    } else {
        $msgs[] = __( 'There are no outstanding entry fees.', 'racketmanager' );
        $alert_type = 'warning';
    }
} else {
    $msgs[] = __( 'You have not yet entered the tournament.', 'racketmanager' );
    $alert_type = 'danger';
}
if ( $payment_required ) {
    ?>
    <input type="hidden" name="playerName" id="playerName" value="<?php echo esc_attr( $player->display_name ); ?>" />
    <input type="hidden" name="playerEmail" id="playerEmail" value="<?php echo esc_attr( $player->email ); ?>" />
    <input type="hidden" name="playerContactNo" id="playerContactNo" value="<?php echo esc_attr( $player->contactno ); ?>" />
    <input type="hidden" name="paymentCompleteUrl" id="paymentCompleteUrl" value="<?php echo isset( $payment_complete_url) ? esc_url( $payment_complete_url ) : null; ?>" />
    <input type="hidden" name="tournamentEntryId" id="tournamentEntryId" value="<?php echo esc_attr( $tournament_entry->id ); ?>" />
    <input type="hidden" name="api_publishable_key" id="api_publishable_key" value="<?php echo esc_attr( $stripe->api_publishable_key ); ?>" />
    <script src="https://js.stripe.com/v3/"></script>
    <script type="module" src="<?php echo esc_url( RACKETMANAGER_URL );  ?>js/stripe-complete.js" defer></script>
    <?php
}
?>
<div class="container">
    <?php require RACKETMANAGER_PATH . 'templates/includes/tournament-header.php'; ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Payment status', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="row individual-entry__footer">
                    <div class="col-md-<?php echo empty( $total_due ) ? '12' : '8'; ?>">
                        <div class="alert_rm alert--<?php echo esc_attr( $alert_type ); ?>" id="paymentAlert">
                            <div class="alert__body">
                                <div class="alert__body-inner" id="paymentAlertResponse">
                                    <?php
                                    foreach ( $msgs as $msg ) {
                                        ?>
                                        <p><?php echo esc_html( $msg ); ?></p>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    if ( $total_due ) {
                        ?>
                        <div class="col-md-4">
                            <div class="price-row">
                                <div class="price-cost"<?php echo empty( $total_due ) ? null : esc_html__( 'Total:', 'racketmanager' ) . ' '; ?><?php the_currency_amount( $total_due ); ?></div>
                                <input type="hidden" name="priceCostTotal" id="priceCostTotal" value=<?php echo esc_attr( $total_due ); ?> />
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

