<?php
/**
 * Event leagues administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;

/** @var League_Event_Page_View_Model $vm */
$event              = $vm->event;
$season             = $vm->season;
$leagues            = $vm->leagues;
$league_matches_map = $vm->league_matches_map;
$league_id          = $vm->league_id;
$league_title       = $vm->league_title;
$league_edit        = $vm->league_edit;
$tab                = $vm->tab;
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
