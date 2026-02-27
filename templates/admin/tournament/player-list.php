<?php
/**
 * Admin screen for tournament entries player lists.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

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
