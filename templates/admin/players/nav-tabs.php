<?php
/**
 * Players tabs administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $racketmanager_tab */
?>
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/admin.php?page=racketmanager-players"><?php esc_html_e( 'RacketManager Players', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( ucwords( un_seo_url( $racketmanager_tab ) ) ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( ucwords( un_seo_url( $racketmanager_tab )) ); ?></h1>
    <div class="row">
        <div class="container">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'errors' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="errors-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Errors', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-players&amp;view=errors" class="nav-link" id="errors-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Errors', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'requests' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="requests-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Requests', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-players&amp;view=requests" class="nav-link" id="requests-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Requests', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
                <li class="nav-item" role="presentation">
                    <?php
                    if ( 'players' === $racketmanager_tab ) {
                        ?>
                        <button class="nav-link active" id="players-tab" type="button" role="tab" aria-selected="true"><?php esc_html_e( 'Players', 'racketmanager' ); ?></button>
                        <?php
                    } else {
                        ?>
                        <a href="/admin.php?page=racketmanager-players&amp;view=players" class="nav-link" id="players-tab" type="button" role="tab" aria-selected="false"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a>
                        <?php
                    }
                    ?>
                </li>
            </ul>
        </div>
    </div>
