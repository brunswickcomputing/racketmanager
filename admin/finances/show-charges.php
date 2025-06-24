<?php
/**
 * Finances charges administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<div class="container">
    <?php require_once 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="racketmanager-charges" role="tabpanel">
                <?php require_once 'charges.php'; ?>
            </div>
        </div>
    </div>
</div>
