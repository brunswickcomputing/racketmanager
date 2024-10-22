<?php
/**
 *
 * Template page to display single club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *  $club_players: club Players object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

if ( empty( $standalone ) ) {
	$standalone   = false;
	$heading_type = 'h2';
} else {
	$heading_type = 'h1';
}
?>
<div class="page-subhead">
	<div class="media">
		<div class="media__wrapper">
			<div class="media__img">
				<span class="profile-icon">
					<span class="profile-icon__abbr">
						<?php
						$words    = explode( ' ', $club->shortcode );
						$initials = null;
						foreach ( $words as $w ) {
							$initials .= $w[0];
						}
						echo esc_html( $initials );
						?>
					</span>
				</span>
			</div>
			<div class="media__content">
				<<?php echo esc_html( $heading_type ); ?> class="media__title">
					<?php
					if ( ! $standalone ) {
						?>
						<a href="/clubs/<?php echo esc_html( sanitize_title( $club->shortcode ) ); ?>/">
						<?php
					}
					?>
						<?php echo esc_html( $club->name ); ?>
					<?php
					if ( ! $standalone ) {
						?>
						</a>
						<?php
					}
					?>
				</<?php echo esc_html( $heading_type ); ?>>
			</div>
			<ul class="media__icons">
				<li class="media__icons-item">
					<?php
					$favourite_type = 'club';
					$favourite_id   = $club->id;
					require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
					?>
				</li>
			</ul>
		</div>
	</div>
</div>
