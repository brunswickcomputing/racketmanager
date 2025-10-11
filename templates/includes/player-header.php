<?php
/**
 * Template for individual player header
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

global $racketmanager;

use Racketmanager\util\Util_Lookup;

/** @var object $player */
if ( empty( $is_page_header ) ) {
    $is_page_header = false;
    $header_level   = 3;
} else {
    $is_page_header = true;
    $header_level   = 1;
}
?>
    <div class="page-subhead player-header">
        <div class="media">
            <div class="media__wrapper">
                <div class="media__img">
                    <span class="profile-icon">
                        <span class="profile-icon__abbr">
                            <?php
                            $player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
                            echo esc_html( $player_initials );
                            ?>
                        </span>
                    </span>
                </div>
                <div class="media__content">
                    <h<?php echo esc_attr( $header_level ); ?> class="media__title">
                        <?php
                        if ( ! $is_page_header ) {
                            ?>
                            <a href="<?php echo esc_html( $player->link ); ?>">
                            <?php
                        }
                        ?>
                        <span class="nav--link">
                            <span class="nav-link__value"><?php echo esc_html( $player->display_name ); ?></span>
                        </span>
                        <?php
                        if ( ! $is_page_header ) {
                            ?>
                            </a>
                            <?php
                        }
                        ?>
                        <?php
                        if ( ! empty( $player->btm ) ) {
                            ?>
                            <span class="media__title-aside"><?php echo esc_html( $player->btm ); ?></span>
                            <?php
                        }
                        ?>
                    </h<?php echo esc_attr( $header_level ); ?>>
                    <div class="media__content-subinfo">
                        <span class="media__subheading">
                            <?php
                            if ( isset( $player->club ) ) {
                                ?>
                                <span class="media__subheading-info-item">
                                    <a href="/clubs/<?php echo esc_attr( seo_url( $player->club->shortcode ) ); ?>/">
                                        <span class="nav--link">
                                            <span class="nav-link__value"><?php echo esc_html( $player->club->shortcode ); ?></span>
                                        </span>
                                    </a>
                                </span>
                                <?php
                            } elseif ( ! empty( $player->clubs ) ) {
                                ?>
                                <ul class="media__subheading-info">
                                    <?php
                                    foreach ( $player->clubs as $club ) {
                                        ?>
                                        <li class="media__subheading-info-item">
                                            <a href="<?php echo esc_html( $club->link ); ?>">
                                                <span class="nav--link">
                                                    <span class="nav-link__value"><?php echo esc_html( $club->shortcode ); ?></span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                                <?php
                            }
                            ?>
                        </span>
                    </div>
                    <div class="media__content-subinfo">
                        <ul class="list list--inline">
                            <?php
                            $rating      = $player->wtn;
                            $help_text   = __( 'World Tennis Number for', 'racketmanager');
                            $match_types = Util_Lookup::get_match_types();
                            foreach ( $match_types as $match_type => $description ) {
                                ?>
                                <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php printf( esc_html( $help_text . ' ' . $description ) ); ?>">
                                    <span class="tag tag-pair" >
                                        <span class="tag-pair__title"><?php echo esc_html( $description ); ?></span>
                                        <span class="tag-pair__value"><?php echo esc_html( $rating[ $match_type ] ); ?></span>
                                    </span>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                <div class="media__aside">
                    <div class="progress-bar-container">
                        <?php
                        if ( isset( $player->statistics['total'] ) && $player->statistics['total']->played ) {
                            ?>
                            <div class="clearfix">
                                <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                <span class="pull-right"><?php echo esc_html( $player->statistics['total']->matches_won ) . '-' . esc_html( $player->statistics['total']->matches_lost ) . ' (' . esc_html( $player->statistics['total']->played ) . ')'; ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $player->statistics['total']->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->statistics['total']->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->statistics['total']->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
