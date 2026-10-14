<?php
/**
 * Template for individual player header
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

use Racketmanager\Presenters\Player_Presenter;
use Racketmanager\Util\Util_Lookup;

/** @var object $player */
/** @var array  $clubs */
$player_presenter = $player_presenter ?? new Player_Presenter();
$is_page_header   = ! empty( $is_page_header );
$header_level     = $player_presenter->get_heading_level( $is_page_header, $header_level ?? null );
$player_initials  = $player_presenter->get_initials( $player->firstname ?? '', $player->surname ?? '' );
$wtn_badges       = $player_presenter->get_wtn_badges( $player->wtn ?? array() );
$win_loss_summary = $player_presenter->get_win_loss_summary( $player->statistics ?? null );
?>
    <div class="page-subhead player-header">
        <div class="media">
            <div class="media__wrapper">
                <div class="media__img">
                    <span class="profile-icon">
                        <span class="profile-icon__abbr">
                            <?php echo esc_html( $player_initials ); ?>
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
                            } elseif ( ! empty( $clubs ) ) {
                                ?>
                                <ul class="media__subheading-info">
                                    <?php
                                    foreach ( $clubs as $club ) {
                                        ?>
                                        <li class="media__subheading-info-item">
                                            <a href="/clubs/<?php echo esc_attr( seo_url( $club->club_name ) ); ?>/">
                                                <span class="nav--link">
                                                    <span class="nav-link__value"><?php echo esc_html( $club->club_name ); ?></span>
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
                    <?php if ( ! empty( $wtn_badges ) ) { ?>
                        <div class="media__content-subinfo">
                            <ul class="list list--inline">
                                <?php
                                foreach ( $wtn_badges as $badge ) {
                                    ?>
                                    <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo esc_attr( $badge['tooltip'] ); ?>">
                                        <span class="tag tag-pair">
                                            <span class="tag-pair__title"><?php echo esc_html( $badge['description'] ); ?></span>
                                            <span class="tag-pair__value"><?php echo esc_html( $badge['value'] ); ?></span>
                                        </span>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
                <div class="media__aside">
                    <div class="progress-bar-container">
                        <?php
                        if ( ! empty( $win_loss_summary ) ) {
                            ?>
                            <div class="clearfix">
                                <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                <span class="pull-right"><?php echo esc_html( $win_loss_summary['record_text'] ); ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_loss_summary['win_pct'] ); ?>%" aria-valuenow="<?php echo esc_html( $win_loss_summary['win_pct'] ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $win_loss_summary['progress_title'] ); ?>"></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
