<?php
/**
 * Event leagues administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

$tab = 'leagues';
?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="racketmanager-leagues" role="tabpanel">
                <h2><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></h2>
                <?php require 'leagues.php'; ?>
            </div>
        </div>
    </div>
</div>
