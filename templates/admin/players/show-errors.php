<?php
/**
 * Players errors administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;

/** @var Player_Errors_Page_View_Model $vm */

?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="errors" role="tabpanel">
                <?php require 'errors.php'; ?>
            </div>
        </div>
    </div>
</div>
