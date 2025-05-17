<?php
/**
 *
 * Template page for a single match
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $match: contains data of displayed match
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var object $match */
?>
<?php
if ( $match ) { ?>
	<div class="match" id="match-<?php echo esc_html( $match->id ); ?>">
		<h3 class="header"><?php esc_html_e( 'Match', 'racketmanager' ); ?></h3>
		<div class="match-content">
			<h4><?php echo esc_html( $match->match_title( $match->id, false ) ); ?></h4>
			<?php
			if ( '0:0' === $match->score ) {
				?>
				<p class="matchDate"><?php echo esc_html( $match->date ) . ' ' . esc_html( $match->start_time ) . ' ' . esc_html( $match->location ); ?></p>
			<?php } else { ?>
				<p class="score">
					<?php echo esc_html( $match->score ); ?>
				</p>
			<?php } ?>
			<?php if ( ! empty( $match->match_day ) ) { ?>
				<?php /* translators: %d: Match day */ ?>
				<p class='match_day'><?php echo esc_html( esc_html( sprintf( __( 'Match Day %d', 'racketmanager' ), $match->match_day ) ) ); ?></p>
			<?php } ?>
			<p class='date'><?php the_match_date(); ?>, <span class='time'><?php the_match_time(); ?></span></p>
			<p class='location'><?php echo esc_html( $match->location ); ?></p>
			<?php
			if ( 0 !== $match->post_id ) {
				?>
				<p class='report'><a href='<?php the_permalink( $match->post_id ); ?>'><?php esc_html_e( 'Report', 'racketmanager' ); ?></a></p>
			<?php } ?>
		</div>
	</div>
<?php } ?>
