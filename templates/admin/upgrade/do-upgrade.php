<?php
/**
 * Run Upgrade page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Services\Upgrade;

?>
<div class="container">
    <h1><?php esc_html_e( 'Upgrade RacketManager', 'racketmanager' ); ?></h1>
    <p><?php esc_html_e( 'Upgrade database structure...', 'racketmanager' ); ?></p>
    <?php
    $upgrade = new Upgrade();
    $upgrade->run();
    ?>
    <p><?php esc_html_e( 'finished', 'racketmanager' ); ?></p>
    <p><?php _e( 'Upgrade successful', 'racketmanager' ); ?></p>
    <form method="post">
        <button class="btn btn-primary" name="upgrade"><?php _e( 'Continue', 'racketmanager' ); ?></button>
    </form>
</div>
