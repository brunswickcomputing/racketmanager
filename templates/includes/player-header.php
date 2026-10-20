<?php
/**
 * Template for individual player header
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Club_Link_View_Model;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Player_View_Model;

/** @var Player_View_Model|null $vm */
/** @var Player_View_Model|null $player_vm */
/** @var object|null            $player */
/** @var array|null             $clubs */
/** @var bool|null              $is_page_header */
/** @var int|null               $header_level */
/** @var Player_Presenter|null  $player_presenter */

// Modern path: consume precomputed Player_View_Model.
$active_vm = $player_vm ?? ( $vm instanceof Player_View_Model ? $vm : null );

// Theme/direct-include compatibility only: live handlers always supply a Player_View_Model.
// Raw-player includes retain their historical WTN, stats and club links via this isolated fallback.
if ( null === $active_vm && isset( $player ) && is_object( $player ) ) {
    $presenter = $player_presenter ?? new Player_Presenter();
    $active_vm = $presenter->present_player(
        player: $player,
        clubs: $clubs ?? [],
        is_page_header: ! empty( $is_page_header ),
        header_level: $header_level ?? null
    );
}

$is_page_header   = $active_vm?->is_page_header ?? ( ! empty( $is_page_header ) );
$header_level     = (int) ( $active_vm?->header_level ?? ( $header_level ?? ( $is_page_header ? 1 : 3 ) ) );
$player_initials  = $active_vm?->initials ?? '';
$wtn_badges       = $active_vm?->wtn_badges ?? [];
$win_loss_summary = $active_vm?->win_loss_summary ?? null;
$player_name      = $active_vm?->display_name ?? '';
$player_link      = $active_vm?->link ?? '';
$player_btm       = $active_vm?->btm ?? null;
$header_clubs     = $active_vm?->clubs ?? [];
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
                    <h<?php echo esc_attr( (string) $header_level ); ?> class="media__title">
                        <?php if ( ! $is_page_header ) : ?>
                            <a href="<?php echo esc_url( $player_link ); ?>">
                        <?php endif; ?>
                        <span class="nav--link">
                            <span class="nav-link__value"><?php echo esc_html( $player_name ); ?></span>
                        </span>
                        <?php if ( ! $is_page_header ) : ?>
                            </a>
                        <?php endif; ?>
                        <?php if ( ! empty( $player_btm ) ) : ?>
                            <span class="media__title-aside"><?php echo esc_html( $player_btm ); ?></span>
                        <?php endif; ?>
                    </h<?php echo esc_attr( (string) $header_level ); ?>>
                    <div class="media__content-subinfo">
                        <span class="media__subheading">
                            <?php if ( ! empty( $header_clubs ) ) : ?>
                                <ul class="media__subheading-info">
                                    <?php
                                    foreach ( $header_clubs as $club_item ) {
                                        $club_url  = $club_item->club_url;
                                        $club_name = $club_item->club_name;
                                        ?>
                                        <li class="media__subheading-info-item">
                                            <?php if ( '' !== $club_url ) : ?>
                                                <a href="<?php echo esc_url( $club_url ); ?>">
                                                    <span class="nav--link">
                                                        <span class="nav-link__value"><?php echo esc_html( $club_name ); ?></span>
                                                    </span>
                                                </a>
                                            <?php else : ?>
                                                <span class="nav-link__value"><?php echo esc_html( $club_name ); ?></span>
                                            <?php endif; ?>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ( ! empty( $wtn_badges ) ) : ?>
                        <div class="media__content-subinfo">
                            <ul class="list list--inline">
                                <?php foreach ( $wtn_badges as $badge ) : ?>
                                    <li class="list__item" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-title="<?php echo esc_attr( $badge['tooltip'] ?? '' ); ?>">
                                        <span class="tag tag-pair">
                                            <span class="tag-pair__title"><?php echo esc_html( $badge['description'] ?? '' ); ?></span>
                                            <span class="tag-pair__value"><?php echo esc_html( $badge['value'] ?? '' ); ?></span>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="media__aside">
                    <div class="progress-bar-container">
                        <?php if ( ! empty( $win_loss_summary ) ) : ?>
                            <div class="clearfix">
                                <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                <span class="pull-right"><?php echo esc_html( $win_loss_summary['record_text'] ?? '' ); ?></span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( (string) ( $win_loss_summary['win_pct'] ?? 0 ) ); ?>%" aria-valuenow="<?php echo esc_html( (string) ( $win_loss_summary['win_pct'] ?? 0 ) ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $win_loss_summary['progress_title'] ?? '' ); ?>"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
