<?php
/**
 * Finances club invoices administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;

/** @var Finances_Invoices_Page_View_Model $vm */

?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="racketmanager-club-invoices" role="tabpanel">
                <?php require 'invoices.php'; ?>
            </div>
        </div>
    </div>
</div>
