<?php
/**
 * Template for admin section
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var string $tab */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">

    <h1><?php esc_html_e( 'Racketmanager Administration', 'racketmanager' ); ?></h1>

    <div class="container">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="seasons-tab" data-bs-toggle="tab" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="true"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></button>
            </li>
        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane show fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
                <h2 class="header"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></h2>
                <?php require_once 'admin/seasons.php'; ?>
            </div>
        </div>
    </div>
</div>
