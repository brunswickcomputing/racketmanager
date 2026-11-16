<?php
/**
 * Template for list of teams matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;

/** @var object|null $vm */
/** @var array       $matches_list */
$matches_list = $matches_list ?? $vm->matches_list ?? [];
foreach ( $matches_list as $key => $group ) {
    if ( $group instanceof Team_Matches_Group_View_Model ) {
        $header_text = $group->header;
        $matches     = $group->matches;
    } else {
        continue;
    }
    ?>
    <h4 class="module-divider">
        <span class="module-divider__body">
            <?php echo esc_html( $header_text ); ?>
        </span>
    </h4>
    <?php
    require 'matches-team-list.php';
}
