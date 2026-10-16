<?php
/**
 *
 * Template page for a player statistics
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Player_View_Model;

/** @var Player_View_Model|null $vm */
/** @var object|null $player */
$stats = $vm->stats ?? ( $stats ?? ( $player->stats ?? [] ) );
?>
<div class="module module--card">
    <div class="module__content">
        <div class="module__banner">
            <h4 class="module__title"><?php esc_html_e( 'Statistics', 'racketmanager' ); ?></h4>
        </div>
        <div class="module-container">
            <div class="module player-stats">
                <div class="row stats-header">
                    <div class="col-2"></div>
                    <div class="col-1 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Played', 'racketmanager' ); ?>"><?php esc_html_e( 'P', 'racketmanager' ); ?></div>
                    <div class="col-4 text-center" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Win-loss-tie', 'racketmanager' ); ?>"><?php esc_html_e( 'W', 'racketmanager' ); ?></div>
                    <div class="col-4"></div>
                    <div class="col-1"></div>
                </div>
                <?php
                if ( ! empty( $stats ) && isset( $stats['totals'] ) ) {
                    $stats_type   = 'totals';
                    $player_stats = $stats[ $stats_type ];
                    $statistics   = $player_stats['total'] ?? [];
                    $stat_title   = null;
                    require 'statistics-group.php';
                    if ( ! empty( $stats['seasons'] ) && is_array( $stats['seasons'] ) ) {
                        foreach ( $stats['seasons'] as $stats_type => $player_stats ) {
                            $statistics = $player_stats['total'] ?? [];
                            $stat_title = $stats_type;
                            require 'statistics-group.php';
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
