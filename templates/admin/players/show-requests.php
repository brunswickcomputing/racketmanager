<?php
/**
 * Players club player requests administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;

/** @var Player_Requests_Page_View_Model $vm */
$racketmanager_tab = $vm->racketmanager_tab;
?>
<div class="container">
    <?php require 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="<?php echo esc_attr( $racketmanager_tab ); ?>" role="tabpanel">
                <?php require 'requests.php'; ?>
            </div>
        </div>
    </div>
</div>
