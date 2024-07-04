<?php
/**
 * Template for championship draw team output
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( -1 === $team->id && $match->league->event->competition->is_player_entry ) {
	echo '&nbsp;<br/>' . esc_html( $team->title );
} elseif ( empty( $team->player ) ) {
	if ( ! empty( $tournament ) ) {
		?>
			<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( $team->title ) ); ?>">
			<?php
	}
	switch ( substr( $match->league->event->type, 0, 1 ) ) {
		case 'M':
			$team_name = str_replace( 'Mens ', '', $team->title );
			break;
		case 'W':
			$team_name = str_replace( 'Ladies ', '', $team->title );
			break;
		case 'X':
			$team_name = str_replace( 'Mixed ', '', $team->title );
			break;
		default:
			$team_name = $team->title;
			break;
	}
	echo esc_html( $team_name );
	if ( ! empty( $tournament ) && -1 !== $team->id ) {
		?>
			</a>
		<?php
	}
} else {
	foreach ( $team->player as $player ) {
		?>
			<div class="draw-player">
			<?php
			if ( ! empty( $tournament ) ) {
				?>
					<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( trim( $player ) ) ); ?>">
					<?php
			}
			?>
			<?php echo esc_html( trim( $player ) ); ?>
				<?php
				if ( ! empty( $tournament ) ) {
					?>
					</a>
					<?php
				}
				?>
			</div>
			<?php
	}
}
