<?php
/**
 * Template page for the order of play
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $winners: array of all winners
 *  $curr_season: current season
 *  $tournaments: array of all tournaments
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $racketmanager_shortcodes;
$post_id = isset( $wp_query->post->ID ) ? $wp_query->post->ID : ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( ! $tournament->num_courts ) {
	$num_courts = 1;
} else {
	$num_courts = $tournament->num_courts;
}
$column_width = floor( 12 / $num_courts );
?>
<div id="orderofplay">
	<h1><?php echo esc_html( $tournament->name ); ?> <?php esc_html_e( 'Finals Day Order of Play', 'racketmanager' ); ?></h1>
	<?php
	$selection_id = 'racketmanager_orderofplay';
	require 'tournament-selections.php';
	?>
	<h2><?php echo esc_html( $tournament->venue_name ); ?></h2>
	<?php
	if ( ! empty( $order_of_play ) ) {
		?>
		<?php require 'includes/order-of-play-body.php'; ?>
		<?php
	} else {
		?>
		<?php esc_html_e( 'No finals day order of play available', 'racketmanager' ); ?>
		<?php
	}
	?>
</div>
