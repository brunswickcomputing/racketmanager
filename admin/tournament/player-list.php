<?php
/**
 * Admin screen for tournament entries player lists.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\util\Util_Lookup;

/** @var array  $player_list */
/** @var bool   $entered */
/** @var object $tournament */
foreach ( $player_list as $players ) {
    foreach ( $players as $player ) {
        ?>
        <tr>
            <td>
                <?php
                if ( $entered || ! empty( $player->user_email ) ) {
                    ?>
                <a href="/tournament/entry-form/<?php echo esc_attr( seo_url( $tournament->name ) ); ?>/player/<?php echo esc_attr( seo_url( $player->display_name ) ); ?>/">
                    <?php
                }
                ?>
                <?php echo esc_html( $player->display_name ); ?>
                <?php
                if ( $entered ) {
                    ?>
                    </a>
                    <?php
                }
                ?>
                <?php
                $rating         = $player->wtn;
                $match_types    = Util_Lookup::get_match_types();
                $rating_display = '';
                foreach ( $match_types as $match_type => $description ) {
                    if ( isset( $rating[ $match_type ] ) ) {
                        $rating_display .= '[' . $match_type . ' - ' . $rating[ $match_type ] . ']';
                    }
                }
                echo ' ' . esc_html( $rating_display );
                ?>
            </td>
        </tr>
        <?php
    }
}
