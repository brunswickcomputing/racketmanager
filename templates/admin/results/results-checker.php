<?php
/**
 * Results checker administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
/** @var string $season_select */
/** @var string $competition_select */
/** @var string $event_select */
/** @var string $results_check_filter */
/** @var array  $results_checkers */
/** @var array  $competitions */
/** @var array  $events */
$seasons = $racketmanager->get_seasons( 'DESC' );
?>
<!-- Results Checker -->

<div class="container">
	<div class="row justify-content-between mb-3">
		<form id="results-checker-filter" method="get" action="" class="form-control">
			<input type="hidden" name="page" value="<?php echo esc_html( 'racketmanager-results' ); ?>" />
			<input type="hidden" name="tab" value="<?php echo esc_html( 'resultschecker' ); ?>" />
			<div class="col-auto">
                <label for="season" class="visually-hidden"><?php esc_html_e( 'Select season', 'racketmanager' ); ?></label><select class="form-select-1" size="1" name="season" id="season">
					<option value="all"><?php esc_html_e( 'All seasons', 'racketmanager' ); ?></option>
					<?php
					foreach ( $seasons as $season ) {
						?>
						<option value="<?php echo esc_html( $season->name ); ?>" <?php selected( $season->name, $season_select ); ?>><?php echo esc_html( $season->name ); ?></option>
						<?php
					}
					?>
				</select>
                <label for="competition" class="visually-hidden"><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></label><select class="form-select-1" size="1" name="competition" id="competition">
					<option value="all"><?php esc_html_e( 'All competitions', 'racketmanager' ); ?></option>
					<?php
					foreach ( $competitions as $competition ) {
						?>
						<option value="<?php echo esc_html( $competition->id ); ?>" <?php selected( $competition->id, $competition_select ); ?>><?php echo esc_html( $competition->name ); ?></option>
						<?php
					}
					?>
				</select>
                <label for="event" class="visually-hidden"><?php esc_html_e( 'Select event', 'racketmanager' ); ?></label><select class="form-select-1" size="1" name="event" id="event">
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
                        <option value="all" <?php selected( $results_check_filter, 'all' ); ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
                        <option value="outstanding" <?php selected( $results_check_filter, 'outstanding' ); ?>><?php esc_html_e( 'Outstanding', 'racketmanager' ); ?></option>
                        <option value="1" <?php selected( $results_check_filter, '1' ); ?>><?php esc_html_e( 'Approved', 'racketmanager' ); ?></option>
                        <option value="2" <?php selected( $results_check_filter, '2' ); ?>><?php esc_html_e( 'Handled', 'racketmanager' ); ?></option>
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
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="check-column">
                            <label class="visually-hidden" for="checkAll"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('results-checker-action'));" />
                        </th>
                        <th class=""><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                        <th class=""><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
                        <th class=""><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
                        <th class=""><?php esc_html_e( 'Player', 'racketmanager' ); ?></th>
                        <th class=""><?php esc_html_e( 'Description', 'racketmanager' ); ?></th>
	                    <?php
	                    if ( 'outstanding' !== $results_check_filter ) {
		                    ?>
                            <th class=""><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
		                    <?php
	                    }
	                    ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( $results_checkers ) {
                        $class = '';
                        foreach ( $results_checkers as $results_checker ) {
                            $class = ( 'alternate' === $class ) ? '' : 'alternate';
                            ?>
                            <tr>
                                <td class="check-column">
                                    <label class="visually-hidden" for="resultsChecker-<?php echo esc_html( $results_checker->id ); ?>"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $results_checker->id ); ?>" name="resultsChecker[<?php echo esc_html( $results_checker->id ); ?>]" id="resultsChecker-<?php echo esc_html( $results_checker->id ); ?>" />
                                </td>
                                <td class=""><?php echo esc_html( mysql2date( 'Y-m-d', $results_checker->match->date ) ); ?></td>
                                <td class="">
                                    <a href="<?php echo esc_html( $results_checker->match->link ); ?>result/">
                                        <?php echo esc_html( $results_checker->match->match_title ); ?>
                                    </a>
                                </td>
                                <td class=""><?php echo esc_html( $results_checker->team->title ); ?></td>
                                <td class="">
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
                                </td>
                                <td class=""><?php echo esc_html( $results_checker->description ); ?></td>
                                <?php
                                if ( 'outstanding' !== $results_check_filter ) {
	                                $tooltip = $results_checker->updated_user_name . ' ' . __( 'on', 'racketmanager' ) . ' ' . $results_checker->updated_date;
                                    ?>
                                    <td data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?php echo esc_html( $tooltip ); ?>"><?php echo esc_html( $results_checker->status_desc ); ?></td>
                                    <?php
                                }
                                ?>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No player checks found', 'racketmanager' ); ?></td></tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
		</form>
	</div>
</div>
