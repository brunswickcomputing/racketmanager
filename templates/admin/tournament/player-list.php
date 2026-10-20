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
    $tournament        = $vm->tournament;
    $player_entry_urls = $vm->player_entry_urls;
}

$player_entry_urls = $player_entry_urls ?? array();

/** @var array  $player_list */
/** @var object $tournament */
foreach ( $player_list as $players ) {
    foreach ( $players as $player ) {
        $display_name = is_object( $player ) ? (string) ( $player->display_name ?? '' ) : (string) ( $player['display_name'] ?? '' );
        $player_key   = $display_name ?: (string) ( is_object( $player ) ? ( $player->id ?? $player->ID ?? '' ) : ( $player['id'] ?? '' ) );
        $player_id    = (string) ( is_object( $player ) ? ( $player->id ?? $player->ID ?? '' ) : ( $player['id'] ?? '' ) );
        $entry_url    = $player_entry_urls[ $player_key ] ?? ( $player_entry_urls[ $player_id ] ?? '' );
        ?>
        <tr>
            <td>
                <?php if ( '' !== $entry_url ) : ?>
                    <a href="<?php echo esc_url( $entry_url ); ?>"><?php echo esc_html( $display_name ); ?></a>
                <?php else : ?>
                    <?php echo esc_html( $display_name ); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
