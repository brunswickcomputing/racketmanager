<?php
/**
 * Event matches administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\League_Matches_Page_View_Model;

/** @var League_Matches_Page_View_Model $vm */
$event   = $vm->event;
$season  = $vm->season;
$matches = $vm->matches;
$tab     = $vm->tab;
?>
<div class="container">
    <?php require_once 'nav-tabs.php'; ?>
    <div class="row">
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane active" id="racketmanager-matches" role="tabpanel">
                <h2><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h2>
                <?php require_once 'matches.php'; ?>
            </div>
        </div>
    </div>
</div>
