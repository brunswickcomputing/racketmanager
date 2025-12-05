<?php
/**
 *
 * Template page to show invoices for a club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 */

namespace Racketmanager;

/** @var object $club */
/** @var object $invoice */
$header_level = 1;
require_once RACKETMANAGER_PATH . 'templates/includes/club-header.php';
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php echo esc_html( __( 'Invoice', 'racketmanager' ) . ' ' . $invoice->invoice_number . ' (' . $invoice->status . ')' ); ?></h3>
        <?php
        if ( ! empty( $user_can_manage->club ) ) {
            ?>
            <div class="module__aside">
                <button class="btn btn--link" href="" id="POModalLink" data-invoice-id="<?php echo esc_attr( $invoice->id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Manage Purchase Order', 'racketmanager' ); ?>">
                    <span class=""><?php esc_html_e( 'Purchase Order', 'racketmanager' ); ?></span>
                </button>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div class="module">
                <div class="alert_rm" id="invoiceAlert" style="display:none;">
                    <div class="alert__body">
                        <div class="alert__body-inner" id="invoiceResponse">
                        </div>
                    </div>
                </div>
                <div class="row mb-3" id="invoiceDetails">
                    <?php echo $invoice->details; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </div>
                <div class="row">
                    <div class="match__buttons">
                        <a href="/clubs/<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/invoices/" class="btn btn-secondary text-uppercase" type="button"><?php esc_html_e( 'Return', 'racketmanager' ); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="POModal"></div>
