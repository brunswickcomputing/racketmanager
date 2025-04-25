<?php
/**
 * Template for tournament order of play for a court
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $court_times */
foreach ( $court_times as $matches ) {
    ?>
    <div class="match-group__item-wrapper<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>">
    <?php
	foreach ( $matches as $final_match ) {
		$match = get_match( $final_match->id );
		?>
			<div class="match-group__item">
				<div class="match__header-title">
					<span><?php echo esc_html( $match->league->title ); ?></span>
				</div>
				<?php
				if ( is_numeric( $match->home_team ) ) {
					$home_match_title = $match->teams['home']->title;
				} else {
					$home_match_title = $match->prev_home_match->match_title;
				}
				if ( is_numeric( $match->away_team ) ) {
					$away_match_title = $match->teams['away']->title;
				} else {
					$away_match_title = $match->prev_away_match->match_title;
				}
				?>
				<div class="match__body-title<?php echo is_numeric( $match->home_team ) ? null : ' is_pending'; ?>">
					<?php echo esc_html( $home_match_title ); ?>
				</div>
				<div class="team-separator"><?php esc_html_e( 'vs', 'racketmanager' ); ?></div>
				<div class="match__body-title<?php echo is_numeric( $match->away_team ) ? null : ' is_pending'; ?>">
					<?php echo esc_html( $away_match_title ); ?>
				</div>
			</div>
		<?php
	}
    ?>
    </div>
<?php
}

