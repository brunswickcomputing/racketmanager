<?php
/**
 * Finances tabs administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $racketmanager_tab */
?>
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/admin.php?page=racketmanager-finances"><?php esc_html_e( 'RacketManager Finances', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( ucwords( un_seo_url( $racketmanager_tab ) ) ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( ucwords( un_seo_url( $racketmanager_tab )) ); ?></h1>
    <div class="row">
        <div class="container">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'charges' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="charges-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Charges', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-finances&amp;view=charges" class="nav-link" id="charges-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Charges', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'club-invoices' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="invoices-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Club Invoices', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-finances&amp;view=club-invoices" class="nav-link" id="invoices-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Club Invoices', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'player-invoices' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="payments-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Tournament Invoices', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-finances&amp;view=player-invoices" class="nav-link" id="payments-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Tournament Invoices', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
            </ul>
        </div>
    </div>
