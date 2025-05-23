<?php
/**
 * Template for favourites
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $favourite_types */
?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h2 class="module__title"><?php esc_html_e( 'My favourites', 'racketmanager' ); ?></h2>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div class="">
					<nav class="navbar navbar-expand-lg">
						<div class="container-fluid">
							<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
								<span class="navbar-toggler-icon"></span>
							</button>
							<div class="collapse navbar-collapse mt-3 justify-content-center" id="navbarSupportedContent">
								<!-- Nav tabs -->
								<ul class="nav nav-pills frontend" id="myTab" role="tablist">
									<?php
									$i = 0;
									foreach ( $favourite_types as $favourite_type ) {
										if ( 'competition' === $favourite_type['name'] ) {
											$favourite_type_name = 'tournament';
										} else {
											$favourite_type_name = $favourite_type['name'];
										}
										?>
										<li class="nav-item" role="presentation">
											<button class="nav-link
												<?php
												if ( 0 === $i ) {
													echo ' active';
												}
												?>
												" id="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" type="button" role="tab" aria-controls="favourite_type-<?php echo esc_html( $favourite_type['name'] ); ?>" aria-selected="true"><?php echo esc_html( $favourite_type_name ); ?></button>
										</li>
										<?php
										++$i;
									}
									?>
								</ul>
							</div>
						</div>
					</nav>
				</div>
				<!-- Tab panes -->
				<div class="tab-content">
					<?php
					$i = 0;
					foreach ( $favourite_types as $favourite_type ) {
						$favourite_name = $favourite_type['name'];
						?>
						<div class="tab-pane fade <?php echo 0 === $i ? 'show active' : ''; ?>" id="favourite_type-<?php echo esc_html( $favourite_name ); ?>" role="tabpanel" aria-labelledby="favourite_type-<?php echo esc_html( $favourite_name ); ?>-tab">
							<div class="module module--card">
								<div class="module__content">
									<div class="module-container">
										<?php
										$favourites = $favourite_type['favourites'];
										require RACKETMANAGER_PATH . 'templates/includes/favourites.php';
										?>
									</div>
								</div>
							</div>
						</div>
						<?php
						++$i;
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
