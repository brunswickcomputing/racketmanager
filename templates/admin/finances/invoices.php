<?php
/**
 * RacketManager Admin invoices page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $racketmanager_tab */
/** @var array  $finance_invoices */
/** @var int    $club_id */
/** @var int    $charge_id */
/** @var string $status */
/** @var array  $clubs */
/** @var array  $charges */
$invoices = $finance_invoices;
if ( 'club-invoices' === $racketmanager_tab ) {
    $billable_type = __( 'Club', 'racketmanager' );
} else {
    $billable_type = __( 'Player', 'racketmanager' );
}
?>
<div class="container">
    <div class="row gx-3 align-items-center mb-3">
        <form id="invoices-filter" method="get" action="" class="form-control">
            <input type="hidden" name="page" value="racketmanager-finances" />
            <input type="hidden" name="view" value="<?php echo esc_attr( $racketmanager_tab ) ; ?>" />
            <input type="hidden" name="tab" value="<?php echo esc_attr( $racketmanager_tab ) ; ?>" />
            <div class="row gx-3 align-items-center">
                <div class="col-12 col-md-4 col-lg-auto mb-3 mb-md-0">
                    <label>
                        <select class="form-select" name="charge" id="charge">
                            <option value="" <?php selected( '', $club_id ); ?>><?php esc_html_e( 'All charges', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $charges as $charge ) {
                                ?>
                                <option value="<?php echo esc_html( $charge->id ); ?>" <?php selected( $charge->id, $charge_id ); ?>><?php echo esc_html( $charge->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </label>
                </div>
                <?php
                if ( 'club-invoices' === $racketmanager_tab ) {
                    ?>
                    <div class="col-12 col-md-4 col-lg-auto mb-3 mb-md-0">
                        <label>
                            <select class="form-select" name="club" id="club">
                                <option value="" <?php selected( '', $club_id ); ?>><?php esc_html_e( 'All clubs', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $clubs as $club ) {
                                    ?>
                                    <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, $club_id ); ?>><?php echo esc_html( $club->shortcode ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </label>
                    </div>
                    <?php
                }
                ?>
                <div class="col-6 col-md-2 col-lg-auto">
                    <label>
                        <select class="form-select" size="1" name="status" id="status">
                            <option value="" <?php echo esc_html( '' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
                            <option value="open" <?php echo esc_html( 'open' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Open', 'racketmanager' ); ?></option>
                            <option value="overdue" <?php echo esc_html( 'overdue' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Overdue', 'racketmanager' ); ?></option>
                            <option value="paid" <?php echo esc_html( 'paid' === $status ? 'selected' : '' ); ?>><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
                </div>
            </div>
        </form>
    </div>
    <div class="row gx-3 mb-3">
        <?php
        if ( $invoices ) {
            $invoices_total = 0;
            ?>
            <form id="invoices-action" method="post" action="" class="form-control">
                <?php wp_nonce_field( 'racketmanager_invoices-bulk', 'racketmanager_nonce' ); ?>
                <div class="row gx-3 mb-3 align-items-center">
                    <!-- Bulk Actions -->
                    <div class="col-auto">
                        <label>
                            <select class="form-select" name="action">
                                <option selected disabled><?php esc_html_e( 'Choose', 'racketmanager' ); ?></option>
                                <optgroup label="<?php esc_html_e( 'Status', 'racketmanager' ); ?>">
                                    <option value="draft"><?php esc_html_e( 'Draft', 'racketmanager' ); ?></option>
                                    <option value="final"><?php esc_html_e( 'Final', 'racketmanager' ); ?></option>
                                    <option value="paid"><?php esc_html_e( 'Paid', 'racketmanager' ); ?></option>
                                    <option value="cancelled"><?php esc_html_e( 'Cancelled', 'racketmanager' ); ?></option>
                                </optgroup>
                                <optgroup label="<?php esc_html_e( 'Action', 'racketmanager' ); ?>">
                                    <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                                </optgroup>
                            </select>
                        </label>
                    </div>
                    <div class="col-auto">
                        <button name="doActionInvoices" id="doActionInvoices" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                    </div>
                </div>
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th class="check-column">
                                <label for="checkAllInvoices" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAllInvoices" onclick="Racketmanager.checkAll(document.getElementById('invoices-action'));" />
                            </th>
                            <th class="d-none d-lg-table-cell text-center"><?php esc_html_e( 'Invoice', 'racketmanager' ); ?></th>
                            <th class="d-table-cell d-lg-none text-center"><?php esc_html_e( 'Inv', 'racketmanager' ); ?></th>
                            <th class=""><?php esc_html_e( 'Charge', 'racketmanager' ); ?></th>
                            <th class=""><?php echo esc_html( $billable_type ); ?></th>
                            <th class="text-end"><?php esc_html_e( 'Amount', 'racketmanager' ); ?></th>
                            <th class="d-none d-lg-table-cell text-center"><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
                            <th class="d-none d-lg-table-cell text-center"><?php esc_html_e( 'Date Due', 'racketmanager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ( $invoices as $invoice ) {
                            $invoices_total += $invoice->amount;
                            ?>
                            <tr>
                                <td class="check-column"><label for="invoice-<?php echo esc_html( $invoice->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $invoice->id ); ?>" name="invoice[<?php echo esc_html( $invoice->id ); ?>]" id="invoice-<?php echo esc_html( $invoice->id ); ?>" /></td>
                                <td class="text-center"><a href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=invoice&amp;invoice=<?php echo esc_html( $invoice->id ); ?>"><?php echo esc_html( $invoice->invoice_number ); ?></a></td>
                                <td class=""><?php echo esc_html( ucfirst( $invoice->charge_name ) ); ?></td>
                                <td class=""><?php echo esc_html( $invoice->billable_name ); ?></td>
                                <td class="text-end"><?php the_currency_amount( $invoice->amount ); ?></td>
                                <td class="d-none d-lg-table-cell text-center"><?php echo esc_html( $invoice->status ); ?></td>
                                <td class="d-none d-lg-table-cell text-center"><?php echo esc_html( $invoice->date_due ); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot class="table-footer">
                        <tr>
                            <td colspan="4"></td>
                            <td class="text-end"><?php the_currency_amount( $invoices_total ); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </form>
            <?php
        } else {
            ?>
            <div class="error">
                <?php esc_html_e( 'No invoices found for search criteria', 'racketmanager' ); ?>
            </div>
            <?php
        }
        ?>
    </div>
</div>
