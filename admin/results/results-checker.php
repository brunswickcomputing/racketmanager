<?php
/**
 * Results checker administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $season_select */
/** @var string $competition_select */
/** @var string $event_select */
/** @var string $results_check_filter */
/** @var array  $results_checkers */
$seasons      = $this->get_seasons( 'DESC' );
$competitions = $this->get_competitions( array( 'type' => 'league' ) );
$events       = array();
foreach ( $competitions as $competition ) {
	$competition = get_competition( $competition );
	if ( $competition ) {
		$competition_events = $competition->get_events();
		foreach ( $competition_events as $event ) {
			$events[] = $event;
		}
	}
}
?>
<!-- Results Checker -->

<div class="container">
	<div class="row justify-content-between mb-3">
		<form id="results-checker-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo esc_html( 'racketmanager-results' ); ?>" />
			<input type="hidden" name="tab" value="<?php echo esc_html( 'resultschecker' ); ?>" />
			<div class="col-auto">
                <label for="season"></label><select class="form-select-1" size="1" name="season" id="season">
					<option value="all"><?php esc_html_e( 'All seasons', 'racketmanager' ); ?></option>
					<?php
					foreach ( $seasons as $season ) {
						?>
						<option value="<?php echo esc_html( $season->name ); ?>" <?php selected( $season->name, $season_select ); ?>><?php echo esc_html( $season->name ); ?></option>
						<?php
					}
					?>
				</select>
                <label for="competition"></label><select class="form-select-1" size="1" name="competition" id="competition">
					<option value="all"><?php esc_html_e( 'All competitions', 'racketmanager' ); ?></option>
					<?php
					foreach ( $competitions as $competition ) {
						?>
						<option value="<?php echo esc_html( $competition->id ); ?>" <?php selected( $competition->id, $competition_select ); ?>><?php echo esc_html( $competition->name ); ?></option>
						<?php
					}
					?>
				</select>
                <label for="event"></label><select class="form-select-1" size="1" name="event" id="event">
					<option value="all"><?php esc_html_e( 'All events', 'racketmanager' ); ?></option>
					<?php
					foreach ( $events as $event ) {
						?>
						<option value="<?php echo esc_html( $event->id ); ?>" <?php selected( $event->id, $event_select ); ?>><?php echo esc_html( $event->name ); ?></option>
						<?php
					}
					?>
				</select>
                <label>
                    <select name="filterResultsChecker" size="1">
                        <option value="-1" selected="selected"><?php esc_html_e( 'Filter results', 'racketmanager' ); ?></option>
                        <option value="all"
                        <?php
                        if ( 'all' === $results_check_filter ) {
                            echo esc_html( ' selected' );
                        }
                        ?>
                        ><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
                        <option value="outstanding"
                        <?php
                        if ( 'outstanding' === $results_check_filter ) {
                            echo esc_html( ' selected' );
                        }
                        ?>
                        ><?php esc_html_e( 'Outstanding', 'racketmanager' ); ?></option>
                        <option value="1"
                        <?php
                        if ( '1' === $results_check_filter ) {
                            echo esc_html( ' selected' );
                        }
                        ?>
                        ><?php esc_html_e( 'Approved', 'racketmanager' ); ?></option>
                        <option value="2"
                        <?php
                        if ( '2' === $results_check_filter ) {
                            echo esc_html( ' selected' );
                        }
                        ?>
                        ><?php esc_html_e( 'Handled', 'racketmanager' ); ?></option>
                    </select>
                </label>
                <button class="btn btn-primary"><?php esc_html_e( 'Filter', 'racketmanager' ); ?></button>
			</div>
		</form>
	</div>
	<div class="row">
		<form id="results-checker-action" method="post" action="" class="form-control">
			<?php wp_nonce_field( 'results-checker-bulk' ); ?>
            <div class="row gx-3 mb-3 align-items-center">
                <!-- Bulk Actions -->
                <div class="col-auto">
                    <label>
                        <select class="form-select" name="action">
                            <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                            <option value="approve"><?php esc_html_e( 'Approve', 'racketmanager' ); ?></option>
                            <option value="handle"><?php esc_html_e( 'Handle', 'racketmanager' ); ?></option>
                            <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button name="doResultsChecker" id="doResultsChecker" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
			<div class="container">
				<div class="row table-header">
					<div class="col-2 col-md-auto check-column">
                        <label class="visually-hidden" for="checkAll"></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('results-checker-action'));" />
                    </div>
					<div class="col-5 col-sm-2 col-lg-1"><?php esc_html_e( 'Date', 'racketmanager' ); ?></div>
					<div class="col-12 col-sm-2 col-lg-3"><?php esc_html_e( 'Match', 'racketmanager' ); ?></div>
					<div class="col-6 col-md-2"><?php esc_html_e( 'Team', 'racketmanager' ); ?></div>
					<div class="col-6 col-sm-2"><?php esc_html_e( 'Player', 'racketmanager' ); ?></div>
					<div class="col-12 col-md-2"><?php esc_html_e( 'Description', 'racketmanager' ); ?></div>
					<?php
					if ( 'outstanding' !== $results_check_filter ) {
						?>
						<div class="d-none d-md-block col-md-3 col-lg-6"></div>
						<div class="col-4 col-md-3 col-lg-2"><?php esc_html_e( 'Status', 'racketmanager' ); ?></div>
						<div class="col-4 col-md-3 col-lg-2"><?php esc_html_e( 'Updated Date', 'racketmanager' ); ?></div>
						<div class="col-4 col-md-3 col-lg-2"><?php esc_html_e( 'Updated User', 'racketmanager' ); ?></div>
						<?php
					}
					?>
				</div>

				<?php
				if ( $results_checkers ) {
					$class = '';
					foreach ( $results_checkers as $results_checker ) {
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						?>
						<div class="row table-row <?php echo esc_html( $class ); ?>">
							<div class="col-2 col-md-auto check-column">
                                <label class="visually-hidden" for="resultsChecker-<?php echo esc_html( $results_checker->id ); ?>"></label><input type="checkbox" value="<?php echo esc_html( $results_checker->id ); ?>" name="resultsChecker[<?php echo esc_html( $results_checker->id ); ?>]" id="resultsChecker-<?php echo esc_html( $results_checker->id ); ?>" />
                            </div>
							<div class="col-5 col-sm-2 col-lg-1"><?php echo esc_html( mysql2date( 'Y-m-d', $results_checker->match->date ) ); ?></div>
							<div class="col-12 col-md-2 col-lg-3">
								<a href="<?php echo esc_html( $results_checker->match->link ); ?>result/">
									<?php echo esc_html( $results_checker->match->match_title ); ?>
								</a>
							</div>
							<div class="col-auto col-md-2"><?php echo esc_html( $results_checker->team->title ); ?></div>
							<div class="col-auto col-sm-2">
								<?php
								if ( isset( $results_checker->player->display_name ) ) {
									$player_link = '/clubs/' . seo_url( $results_checker->team->club->shortcode ) . '/players/' . seo_url( $results_checker->player->display_name ) . '/';
									?>
									<a href="<?php echo esc_attr( $player_link ); ?>">
										<?php echo esc_html( $results_checker->player->display_name ); ?>
									</a>
									<?php
								}
								?>
							</div>
							<div class="col-12 col-md-3"><?php echo esc_html( $results_checker->description ); ?></div>
							<?php
							if ( 'outstanding' !== $results_check_filter ) {
								?>
								<div class="d-none d-md-block col-md-3 col-lg-6"></div>
								<div class="col-4 col-md-3 col-lg-2"><?php echo esc_html( $results_checker->status_desc ); ?></div>
								<div class="col-4 col-md-3 col-lg-2"><?php echo esc_html( $results_checker->updated_date ); ?></div>
								<div class="col-4 col-md-3 col-lg-2"><?php echo esc_html( $results_checker->updated_user_name ); ?></div>
								<?php
							}
							?>
						</div>
						<?php
					}
				} else {
					?>
					<div class="col-auto my-3"><?php esc_html_e( 'No player checks found', 'racketmanager' ); ?></div>
					<?php
				}
				?>
			</div>
		</form>
	</div>
</div>
