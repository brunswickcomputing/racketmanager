<?php
/**
 * Template for player list of names
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( empty( $players ) ) {
	$result_count = 0;
} else {
	$result_count = count( $players );
}
?>
<div class="module__banner">
	<h4 class="module__title" id="searchTitle"><?php /* translators: %d: result count */ printf( esc_html__( '%d results', 'racketmanager' ), esc_html( $result_count ) ); ?></h4>
</div>
<div class="module__content">
	<div class="module-container">
		<?php
		if ( ! empty( $players ) ) {
			?>
			<ul class="list list--bordered" id="searchResults">
				<?php
				foreach ( $players as $player ) {
					?>
					<li class="col-12 list__item">
						<div class="media">
							<div class="media__wrapper">
								<div class="media__img">
									<div class="profile-icon">
										<span class="profile-icon__abbr">
											<?php
											$player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
											echo esc_html( $player_initials );
											?>
										</span>
									</div>
								</div>
								<div class="media__content">
									<h4 class="media__title">
										<a class="nav--link media__link" href="<?php echo esc_html( $player->link ); ?>">
											<span class="nav-link__value"><?php echo esc_html( $player->display_name ); ?></span>
										</a>
									</h4>
								</div>
							</div>
							<ul class="media__icons">
								<li class="media__icons-item">
									<?php
									$favourite_type = 'player';
									$favourite_id   = $player->ID;
									require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
									?>
								</li>
							</ul>
						</div>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		} else {
			esc_html_e( 'No players found', 'racketmanager' );
		}
		?>
	</div>
</div>
