<?php
/**
 * Admin screen for tournament entries player lists.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var array  $player_list */
/** @var bool   $entered */
/** @var object $tournament */
foreach ( $player_list as $players ) {
    foreach ( $players as $player ) {
        ?>
        <tr>
            <td>
                <a href="/tournament/entry-form/<?php echo esc_attr( seo_url( $tournament->name ) ); ?>/player/<?php echo esc_attr( seo_url( $player->display_name ) ); ?>/">
                <?php echo esc_html( $player->display_name ); ?>
                <?php
                $rating         = $player->wtn;
                $match_types    = Util::get_match_types();
                $rating_display = '';
                foreach ( $match_types as $match_type => $description ) {
                    $rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
                }
                echo ' ' . esc_html( $rating_display );
                ?>
            </td>
        </tr>
        <?php
    }
}
