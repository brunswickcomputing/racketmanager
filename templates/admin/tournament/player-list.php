<?php
/**
 * Admin screen for tournament entries player lists.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;

// Preferred input: $vm from the overview page.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Overview_Page_View_Model ) ? $vm : null;

// BC fallback: allow legacy locals if $vm isn't provided.
if ( $vm ) {
    $tournament = $vm->tournament;
}

/** @var array  $player_list */
/** @var object $tournament */
foreach ( $player_list as $players ) {
    foreach ( $players as $player ) {
        ?>
        <tr>
            <td><a href="/tournament/entry-form/<?php echo esc_attr( seo_url( $tournament->name ) ); ?>/player/<?php echo esc_attr( seo_url( $player->display_name ) ); ?>/"><?php echo esc_html( $player->display_name ); ?></a></td>
        </tr>
        <?php
    }
}
