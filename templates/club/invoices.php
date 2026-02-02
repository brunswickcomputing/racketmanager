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
/** @var array $invoices */
$header_level = 1;
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Invoices', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div class="module">
                <div class="row mb-2 row-header">
                    <div class="col-2 col-md-1">
                        <?php esc_html_e( 'Invoice', 'racketmanager' ); ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <?php esc_html_e( 'Description', 'racketmanager' ); ?>
                    </div>
                    <div class="col-2 text-end">
                        <?php esc_html_e( 'Amount', 'racketmanager' ); ?>
                    </div>
                    <div class="col-2 col-md-1">
                        <?php esc_html_e( 'Status', 'racketmanager' ); ?>
                    </div>
                    <div class="d-none d-lg-block col-2">
                        <?php esc_html_e( 'Due date', 'racketmanager' ); ?>
                    </div>
                </div>
                <?php
                $total_amount = 0;
                foreach ( $invoices as $invoice ) {
                    $total_amount += $invoice->amount;
                    ?>
                    <div class="row mb-2 row-list">
                        <div class="col-2 col-md-1">
                            <a href="<?php echo esc_attr( $invoice->id ); ?>/"><?php echo esc_html( $invoice->invoice_number ); ?></a>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class=""><?php echo esc_html( ucfirst( $invoice->charge_name ) ); ?></span>
                        </div>
                        <div class="col-2 text-end">
                            <span class=""><?php the_currency_amount( $invoice->amount ); ?></span>
                        </div>
                        <div class="col-2 col-md-1">
                            <span class=""><?php echo esc_html( $invoice->status ); ?></span>
                        </div>
                        <div class="d-none d-lg-block col-2">
                            <span class=""><?php echo esc_html( $invoice->date_due ); ?></span>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="row mb-2 row-footer">
                    <div class="col-8 col-md-4 text-end"><?php esc_html_e( 'Total', 'racketmanager' ); ?></div>
                    <div class="col-2 text-end">
                        <span class=""><?php the_currency_amount( $total_amount ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
