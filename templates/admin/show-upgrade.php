<?php
/**
 * Upgrade main page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $installed */
?>
<div class="container">
    <h1><?php esc_html_e( 'Upgrade RacketManager', 'racketmanager' ); ?></h1>
    <p><?php _e( 'Your database for RacketManager is out-of-date, and must be upgraded before you can continue.', 'racketmanager' ); ?></p>
    <p><?php printf( __( 'Current version %s'), $installed ); ?></p>
    <p><?php printf( __( 'Latest version %s'), RACKETMANAGER_DBVERSION ); ?></p>
    <p><?php _e( 'The upgrade process may take a while, so please be patient.', 'racketmanager' ); ?></p>
    <form action="" method="post">
        <?php wp_nonce_field( 'racketmanager_upgrade', 'racketmanager_nonce' ); ?>
        <input type="hidden" name="doUpgrade" />
        <button class="btn btn-primary"><?php _e( 'Start upgrade now', 'racketmanager' ); ?></button>
    </form>
</div>

