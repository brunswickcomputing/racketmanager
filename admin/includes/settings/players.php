<?php
/**
 * Player options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
      <div class="form-floating mb-3">
		<input type="number" class="form-control" name='playerLeadTime' id='playerLeadTime' value='<?php echo esc_html( isset( $options['checks']['rosterLeadTime'] ) ? $options['checks']['rosterLeadTime'] : '' ); ?>' />
		<label for='playerLeadTime'><?php esc_html_e( 'Player Registration Lead Time (hours)', 'racketmanager' ); ?></label>
      </div>
    <div class="form-floating mb-3">
		<input type="number" class="form-control" name='playedRounds' id='playedRounds' value='<?php echo esc_html( isset( $options['checks']['playedRounds'] ) ? $options['checks']['playedRounds'] : '' ); ?>' />
		<label for='playedRounds'><?php esc_html_e( 'End of season eligibility (Match Days)', 'racketmanager' ); ?></label>
    </div>
    <div class="form-floating mb-3">
		<input type="number" class="form-control" name='playerLocked' id='playerLocked' value='<?php echo esc_html( isset( $options['checks']['playerLocked'] ) ? $options['checks']['playerLocked'] : '' ); ?>' />
		<label for='playerLocked'><?php esc_html_e( 'How many matches lock a player', 'racketmanager' ); ?></label>
    </div>
</div>
