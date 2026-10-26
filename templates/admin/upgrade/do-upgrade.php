<?php
/**
 * Run Upgrade page administration panel
 *
 * @package  Racketmanager/Templates
 *
 * @var Racketmanager\Services\Upgrade $upgrade Upgrade runner built by Admin_Upgrade.
 */

namespace Racketmanager;

?>
<div class="container">
    <h1><?php esc_html_e( 'Upgrade RacketManager', 'racketmanager' ); ?></h1>
    <p><?php esc_html_e( 'Upgrade database structure...', 'racketmanager' ); ?></p>
    <?php
    $upgrade->run();
    ?>
    <p><?php esc_html_e( 'finished', 'racketmanager' ); ?></p>
    <p><?php _e( 'Upgrade successful', 'racketmanager' ); ?></p>
    <form method="post">
        <button class="btn btn-primary" name="upgrade"><?php _e( 'Continue', 'racketmanager' ); ?></button>
    </form>
</div>
