<?php
/**
 * Finances club invoices administration panel
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
            <div class="tab-pane active" id="racketmanager-club-invoices" role="tabpanel">
                <?php require_once 'invoices.php'; ?>
            </div>
        </div>
    </div>
</div>
