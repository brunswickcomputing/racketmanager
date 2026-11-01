<?php
/**
 * Template for player statistics
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $stat_title */
/** @var string $played */
/** @var string $matches_won */
/** @var string $matches_lost */
/** @var string $sets_won */
/** @var string $sets_lost */
/** @var string $games_won */
/** @var string $games_lost */
/** @var string $win_pct */
/** @var string $win_pct_sets */
/** @var string $win_pct_games */
/** @var string $walkover */
$is_walkover   = $is_walkover ?? $vars['is_walkover'] ?? $vm->is_walkover ?? false;
$stat_title    = $stat_title ?? $vars['stat_title'] ?? $vm->stat_title ?? '';
$played        = $played ?? $vars['played'] ?? $vm->played ?? 0;
$matches_won   = $matches_won ?? $vars['matches_won'] ?? $vm->matches_won ?? 0;
$matches_lost  = $matches_lost ?? $vars['matches_lost'] ?? $vm->matches_lost ?? 0;
$sets_won      = $sets_won ?? $vars['sets_won'] ?? $vm->sets_won ?? 0;
$sets_lost     = $sets_lost ?? $vars['sets_lost'] ?? $vm->sets_lost ?? 0;
$games_won     = $games_won ?? $vars['games_won'] ?? $vm->games_won ?? 0;
$games_lost    = $games_lost ?? $vars['games_lost'] ?? $vm->games_lost ?? 0;
$win_pct       = $win_pct ?? $vars['win_pct'] ?? $vm->win_pct ?? null;
$win_pct_sets  = $win_pct_sets ?? $vars['win_pct_sets'] ?? $vm->win_pct_sets ?? 0;
$win_pct_games = $win_pct_games ?? $vars['win_pct_games'] ?? $vm->win_pct_games ?? 0;
$walkover      = $walkover ?? $vars['walkover'] ?? $vm->walkover ?? 0;
?>
<div class="col-<?php echo empty( $is_walkover ) ? 4 : 3; ?> stats-title">
    <?php
    if ( 's' === $stat_title ) {
        $stat_title = __( 'Singles', 'racketmanager' );
    } elseif ( 'd' === $stat_title ) {
        $stat_title = __( 'Doubles', 'racketmanager' );
    }
    ?>
    <?php echo esc_html( $stat_title ); ?>
</div>
<div class="col-1 text-center"><?php echo esc_html( $played ); ?></div>
<div class="col-2 text-center">
    <?php echo esc_html( $matches_won ) . '-' . esc_html( $matches_lost ); ?>
    <div class="progress">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
    </div>
</div>
<div class="col-2 text-center">
    <?php echo esc_html( $sets_won ) . '-' . esc_html( $sets_lost ); ?>
    <div class="progress">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct_sets ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct_sets ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
    </div>
</div>
<div class="col-2 text-center">
    <?php echo esc_html( $games_won ) . '-' . esc_html( $games_lost ); ?>
    <div class="progress">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $win_pct_games ); ?>%" aria-valuenow="<?php echo esc_html( $win_pct_games ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $win_pct ) . '% ' . esc_html__( 'won', 'racketmanager' ); ?>"></div>
    </div>
</div>
<?php
if ( ! empty( $is_walkover ) ) {
    ?>
    <div class="col-1 text-center"><?php echo esc_html( $walkover ); ?></div>
    <?php
}
?>
