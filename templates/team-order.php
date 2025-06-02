<?php
/**
 * Template for team order
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $clubs */
/** @var array $events */
?>
<div class="container">
	<div class="module module--card">
		<div class="module__banner">
			<h3 class="module__title"><?php esc_html_e( 'Team order', 'racketmanager' ); ?></h3>
		</div>
		<div class="module__content">
			<div class="module-container">
				<div id="team_order_selections" class="mb-3">
					<form method="get" action="" id="team_selection_club">
						<div class="row gx-3 mb-3 align-items-center">
							<div class="form-floating col-auto">
								<select class="form-select" size="1" name="club_id" id="club_id">
									<option value="" disabled selected><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
									<?php
									foreach ( $clubs as $club ) {
										?>
										<option value="<?php echo esc_attr( $club->id ); ?>"><?php echo esc_html( $club->shortcode ); ?></option>
										<?php
									}
									?>
								</select>
								<label for="club_id"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
							</div>
							<div class="form-floating col-auto">
								<select class="form-select" size="1" name="event_id" id="event_id">
									<option value="" disabled selected><?php esc_html_e( 'Select event', 'racketmanager' ); ?></option>
									<?php
									foreach ( $events as $event ) {
										?>
										<option value="<?php echo esc_attr( $event->id ); ?>"><?php echo esc_html( $event->name ); ?></option>
										<?php
									}
									?>
								</select>
								<label for="event_id"><?php esc_html_e( 'Event', 'racketmanager' ); ?></label>
							</div>
						</div>
					</form>
				</div>
				<div id="team-order-details">
					<?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
					<div class="" id="team-order-rubbers"></div>
					<div class="alert_rm mt-3" id="teamOrderAlert" style="display:none;">
						<div class="alert__body">
							<div class="alert__body-inner" id="teamOrderAlertResponse"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        showTeamPlayers();
    });
    jQuery(document).ajaxComplete(function () {
        showTeamPlayers();
    });
    function showTeamPlayers () {
        document.getElementById('club_id').addEventListener('change', function (e) {
            Racketmanager.showTeamOrderPlayers(e);
        });
        document.getElementById('event_id').addEventListener('change', function (e) {
            Racketmanager.showTeamOrderPlayers(e);
        });
    }
</script>
