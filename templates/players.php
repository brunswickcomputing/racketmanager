<?php
/**
 * Template page for players
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="page-subhead competition">
		<div class="media">
			<div class="media__wrapper">
				<div class="media__content">
					<h1 class="media__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h1>
					<div class="media__content-subinfo">
						<form id="playerSearch" action="" onsubmit="Racketmanager.playerSearch(event, this)">
							<?php wp_nonce_field( 'search_players', 'racketmanager_nonce' ); ?>
							<div class="search-box">
								<div class="input-group">
									<span class="input-group-text">
										<svg width="16" height="16" class="">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#search' ); ?>"></use>
										</svg>
									</span>
									<input type="search" name="search_string" id="search_string" class="form-control search-box__field" value="<?php echo esc_attr( $search_string ); ?>" />
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
	<div class="module module--card" id="searchResultsContainer" <?php echo empty( $search_results ) ? 'style="display: none;' : null; ?>>
		<?php
		if ( ! empty( $search_results ) ) {
			echo $search_results; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<div class="module module--card">
		<div class="module__banner">
			<h4 class="module__title"><?php esc_html_e( 'My favourites', 'racketmanager' ); ?></h4>
		</div>
		<div class="module__content">
			<div class="module-container">
				<?php
				$favourite_name = 'player';
				require RACKETMANAGER_PATH . 'templates/includes/favourites.php';
				?>
			</div>
		</div>
	</div>
</div>
