<?php
/**
 * Template for favourites
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $type */
?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<?php // translators: %s: type. ?>
			<h2 class="module__title"><?php printf( esc_html__( 'My %s', 'racketmanager' ), esc_html( strtolower( $type ) ) ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<ul class="list list--grid list--bordered">
					<?php
					if ( empty( $user_competitions ) ) {
						?>
						<?php // translators: %s: type. ?>
						<li class="list__item col-12 text-center"><?php printf( esc_html__( 'This will show %s that you have played in.', 'racketmanager' ), esc_html( strtolower( $type ) ) ); ?></li>
						<?php
					} else {
						$competition_list = $user_competitions;
						require 'includes/competition-list.php';
						?>
						<?php
					}
					?>
				</ul>
			</div>
		</div>
	</div>
	<div class="module module--card">
		<div class="module__banner">
			<h2 class="module__title"><?php echo esc_html( $type ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<ul class="list list--grid list--bordered">
					<?php
					if ( empty( $competitions ) ) {
						?>
						<?php // translators: %s: type. ?>
						<li class="list__item col-12 text-center"><?php printf( esc_html__( 'No %s found', 'racketmanager' ), esc_html( strtolower( $type ) ) ); ?></li>
						<?php
					} else {
						$competition_list = $competitions;
						require 'includes/competition-list.php';
					}
					?>
				</ul>
			</div>
		</div>
	</div>
</div>
