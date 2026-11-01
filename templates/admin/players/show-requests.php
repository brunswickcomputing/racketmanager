<?php
/**
 * Players club player requests administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;

/** @var Player_Requests_Page_View_Model $vm */

?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="requests" role="tabpanel">
                <?php require 'requests.php'; ?>
            </div>
        </div>
    </div>
</div>
