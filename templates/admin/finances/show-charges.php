<?php
/**
 * Finances charges administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;

/** @var Finances_Charges_Page_View_Model $vm */

?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="racketmanager-charges" role="tabpanel">
                <?php require 'charges.php'; ?>
            </div>
        </div>
    </div>
</div>
