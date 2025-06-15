<?php
/**
 * Template for match for teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $wp, $racketmanager;
/** @var object $is_update_allowed */
/** @var object $match */
$user_can_update = $is_update_allowed->user_can_update;
?>
	<div id="match-header" class="team-match-header module module--dark module--card">
		<?php echo match_header( $match->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<div class="page-content row">
		<div class="page-content__main col-12 col-lg-8">
			<div class="alert_rm" id="matchOptionsAlert" style="display:none;">
				<div class="alert__body">
					<div class="alert__body-inner" id="alertMatchOptionsResponse">
					</div>
				</div>
			</div>
			<div class="module module--card">
				<div class="module__banner">
					<h3 class="module__title">
						<?php esc_html_e( 'Rubbers', 'racketmanager' ); ?>
					</h3>
					<div class="module__aside">
						<?php
						if ( ! $match->winner_id ) {
							?>
							<a role="button" class="btn btn--link match-print" id="printMatchCard" data-match-id="<?php echo esc_attr( $match->id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Print match card', 'racketmanager' ); ?>">
								<svg width="16" height="16" class="icon ">
									<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#printer-fill' ); ?>"></use>
								</svg>
							</a>
							<?php
						}
						if ( $user_can_update ) {
							$target_url  = home_url( add_query_arg( array(), $wp->request ) );
							$target_url .= '/result/';
							?>
							<div class="match-mode" id="editMatchMode">
								<a role="button" class="btn btn--link" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Edit', 'racketmanager' ); ?>" href="<?php echo esc_url( $target_url ); ?>">
									<svg width="16" height="16" class="icon ">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
									</svg>
								</a>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div class="module__content">
					<div class="module-container">
						<div id="viewMatchRubbers">
							<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
							<div id="showMatchRubbers">
								<?php echo $racketmanager->show_match_screen( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
		<div class="page-content__sidebar col-12 col-lg-4">
			<div class="row">
				<div class="col-12 col-sm-6 col-lg-12">
					<div class="module module--card">
						<div class="module__banner">
							<h3 class="module__title">
								<?php esc_html_e( 'Location', 'racketmanager' ); ?>
							</h3>
						</div>
						<div class="module__content">
							<?php $opponent = empty( $match->host ) ? 'home' : $match->host; ?>
							<div class="module-container">
								<h5 class="subheading">
									<?php echo esc_html( $match->teams[ $opponent ]->club->name ); ?>
								</h5>
								<ul class="list list--naked">
									<li class="list__item">
										<svg width="16" height="16" class="icon icon-marker">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-marker' ); ?>"></use>
										</svg>
										<span class="nav-link__value">
											<?php echo esc_html( $match->teams[ $opponent ]->club->address ); ?>
										</span>
									</li>
									<li class="list__item">
										<svg width="16" height="16" class="icon icon-captain">
											<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-captain' ); ?>"></use>
										</svg>
										<span class="">
											<?php echo esc_html( $match->teams[ $opponent ]->club->match_secretary_name ); ?>
										</span>
									</li>
									<?php
									if ( is_user_logged_in() ) {
										if ( ! empty( $match->teams[ $opponent ]->club->match_secretary_contact_no ) ) {
											?>
											<li class="list__item">
												<a href="tel:<?php echo esc_html( $match->teams[ $opponent ]->club->match_secretary_contact_no ); ?>" class="nav--link" rel="nofollow">
													<svg width="16" height="16" class="icon ">
														<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
													</svg>
													<span class="nav--link">
														<span class="nav-link__value">
															<?php echo esc_html( $match->teams[ $opponent ]->club->match_secretary_contact_no ); ?>
														</span>
													</span>
												</a>
											</li>
											<?php
										}
										?>
										<?php
										if ( ! empty( $match->teams[ $opponent ]->club->match_secretary_email ) ) {
											?>
											<li class="list__item">
												<a href="mailto:<?php echo esc_html( $match->teams[ $opponent ]->club->match_secretary_email ); ?>" class="nav--link">
													<svg width="16" height="16" class="icon ">
														<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
													</svg>
													<span class="nav--link">
														<span class="nav-link__value">
															<?php echo esc_html( $match->teams[ $opponent ]->club->match_secretary_email ); ?>
														</span>
													</span>
												</a>
											</li>
											<?php
										}
									}
									?>
									<?php
									if ( ! empty( $match->teams[ $opponent ]->club->website ) ) {
										?>
										<li class="list__item">
											<a href="<?php echo esc_html( $match->teams[ $opponent ]->club->website ); ?>" class="nav--link" target="_blank" rel="noopener nofollow">
												<svg width="16" height="16" class="icon icon-globe">
													<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#globe' ); ?>"></use>
												</svg>
												<span class="">
													<?php echo esc_html( $match->teams[ $opponent ]->club->website ); ?>
												</span>
											</a>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div class="col-12 col-sm-6 col-lg-12">
					<div class="module module--card">
						<div class="module__banner">
							<h3 class="module__title">
								<?php esc_html_e( 'Team Captains', 'racketmanager' ); ?>
							</h3>
						</div>
						<div class="module__content">
							<div class="module-container">
								<?php
								$opponents = array( 'home', 'away' );
								foreach ( $opponents as $opponent ) {
									?>
									<h5 class="subheading">
										<?php echo esc_html( $match->teams[ $opponent ]->captain ); ?>
									</h5>
									<ul class="list list--naked">
										<li class="list__item">
											<svg width="16" height="16" class="icon-team">
												<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons-extra.svg#icon-team' ); ?>"></use>
											</svg>
											<span class="">
												<?php echo esc_html( $match->teams[ $opponent ]->title ); ?>
											</span>
										</li>
										<?php
										if ( is_user_logged_in() ) {
											if ( ! empty( $match->teams[ $opponent ]->contactno ) ) {
												?>
												<li class="list__item">
													<a href="tel:<?php echo esc_html( $match->teams[ $opponent ]->contactno ); ?>" class="nav--link" rel="nofollow">
														<svg width="16" height="16" class="icon ">
															<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
														</svg>
														<span class="nav--link">
															<span class="nav-link__value">
																<?php echo esc_html( $match->teams[ $opponent ]->contactno ); ?>
															</span>
														</span>
													</a>
												</li>
												<?php
											}
											?>
											<?php
											if ( ! empty( $match->teams[ $opponent ]->contactemail ) ) {
												?>
												<li class="list__item">
													<a href="mailto:<?php echo esc_html( $match->teams[ $opponent ]->contactemail ); ?>" class="nav--link">
														<svg width="16" height="16" class="icon ">
															<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
														</svg>
														<span class="nav--link">
															<span class="nav-link__value">
																<?php echo esc_html( $match->teams[ $opponent ]->contactemail ); ?>
															</span>
														</span>
													</a>
												</li>
												<?php
											}
										}
										?>
									</ul>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php require 'includes/match-modal.php'; ?>
<script type="text/javascript">
    document.getElementById('printMatchCard').addEventListener('click', function (e) {
        let matchId = this.dataset.matchId;
        Racketmanager.printScoreCard(e, matchId);
    });
</script>
