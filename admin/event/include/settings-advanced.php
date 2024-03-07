<?php
/**
 * Template for event advanced settings
 *
 * @package Racketmanager/Templats/Admin
 */

namespace Racketmanager;

?>
<div>
	<?php
	if ( $event->is_championship ) {
		if ( ! isset( $event->settings['primary_league'] ) ) {
			$event->settings['primary_league'] = '';
		}
		$leagues = $event->get_leagues();
		?>
		<div class="form-floating mb-3">
			<select class="form-select" size="1" name="settings[primary_league] ?>" id="primary_league">;
				<option value=""><?php esc_html_e( 'Select', 'racketmanager' ); ?></option>
					<?php
					foreach ( $leagues as $league ) {
						?>
						<option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $event->settings['primary_league'], $league->id ); ?> ><?php echo esc_html( $league->title ); ?></option>
						<?php
					}
					?>
			</select>
			<label for='primary_league'><?php esc_html_e( 'Primary League', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
				<?php
				if ( isset( $event->groups ) && is_array( $event->groups ) ) {
					$groups_input = ( implode( ';', $event->groups ) );
				} else {
					$groups_input = '';
				}
				?>
			<input class="form-control" type="text" name="settings[groups]" id="groups" size="20" value="<?php esc_html( $groups_input ); ?>" />
			<label for="groups"><?php esc_html_e( 'Groups', 'racketmanager' ); ?></label>
			<div class="form-hint">
				<?php esc_html_e( 'Separate Groups by semicolon ;', 'racketmanager' ); ?>
			</div>
		</div>
		<div class="form-floating mb-3 col-2">
			<input class="form-control" type="text" name="settings[teams_per_group]" id="teams_per_group" size="3" value="<?php echo isset( $event->teams_per_group ) ? esc_html( $event->teams_per_group ) : ''; ?>" />
			<label for="teams_per_group"><?php esc_html_e( 'Teams per group', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3 col-2">
			<input class="form-control" type="text" size="3" id="num_advance" name="settings[num_advance]" value="<?php echo isset( $event->num_advance ) ? esc_html( $event->num_advance ) : ''; ?>" />
			<label for="num_advance"><?php esc_html_e( 'Teams Advance', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check">
			<input type="checkbox" class="form-check-input" id="match_place3" name="settings[match_place3]" value="1" <?php isset( $event->match_place3 ) ? checked( $event->match_place3, 1 ) : ''; ?> />
			<label for="match_place3" class="form-check-label"><?php esc_html_e( 'Include 3rd place match', 'racketmanager' ); ?></label>
		</div>
		<div class="form-check">
			<input type="checkbox" class="form-check-input" id="non_group" name="settings[non_group]" value="1" <?php isset( $event->non_group ) ? checked( $event->non_group, 1 ) : ''; ?> />
			<label for="non_group" class="form-check-label"><?php esc_html_e( 'Allow Non-Group Games', 'racketmanager' ); ?></label>
		</div>
		<?php
	}
	?>
</div>
