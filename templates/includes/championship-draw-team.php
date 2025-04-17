<?php
/**
 * Template for championship draw team output
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $team */
/** @var object $match */
if ( -1 === $team->id && $match->league->event->competition->is_player_entry ) {
	echo '&nbsp;<br/>' . esc_html( $team->title );
} elseif ( empty( $team->player ) ) {
	if ( ! empty( $tournament ) ) {
		?>
			<a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/players/<?php echo esc_html( seo_url( $team->title ) ); ?>">
			<?php
	}
	$team_name = match ( substr( $match->league->event->type, 0, 1 ) ) {
		'M' => str_replace('Mens ', '', $team->title),
		'W' => str_replace('Ladies ', '', $team->title),
		'X' => str_replace('Mixed ', '', $team->title),
		default => $team->title,
	};
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
