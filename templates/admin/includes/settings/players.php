<?php
/**
 * Player options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
    <div class="form-control mb-3">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="ageLimitCheck" id="ageLimitCheck" value="true" <?php echo empty( $options['checks']['ageLimitCheck'] ) ? null : 'checked'; ?> />
            <label class="form-check-label" for="ageLimitCheck"><?php esc_html_e( 'Age Limit Check', 'racketmanager' ); ?></label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="leadTimeCheck" id="leadTimeCheck" value="true" <?php echo empty( $options['checks']['leadTimeCheck'] ) ? null : 'checked'; ?> />
            <label class="form-check-label" for="leadTimeCheck"><?php esc_html_e( 'Lead Time Check', 'racketmanager' ); ?></label>
        </div>
    </div>
    <fieldset class="form-control mb-3">
        <legend class="form-check-label"><?php esc_html_e( 'Ability Checks', 'racketmanager' ); ?></legend>
        <div class="form-check form-check-inline">
            <input type="checkbox" class="form-check-input" name="ratingCheck" id="ratingCheckTrue" value="true" <?php checked( true, !empty($options['checks']['ratingCheck'])); ?> />
            <label class="form-check-label" for="ratingCheckTrue"><?php esc_html_e( 'Player Rating Check', 'racketmanager' ); ?></label>
        </div>
        <div class="form-check form-check-inline">
            <input type="checkbox" class="form-check-input" name="wtnCheck" id="wtnCheckTrue" value="true" <?php checked( true, !empty($options['checks']['wtn_check'])); ?> />
            <label class="form-check-label" for="wtnCheckTrue"><?php esc_html_e( 'Player WTN Check', 'racketmanager' ); ?></label>
        </div>
    </fieldset>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playerLeadTime' id='playerLeadTime' value='<?php echo esc_html($options['checks']['rosterLeadTime'] ?? ''); ?>' />
        <label for='playerLeadTime'><?php esc_html_e( 'Player Registration Lead Time (hours)', 'racketmanager' ); ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playedRounds' id='playedRounds' value='<?php echo esc_html($options['checks']['playedRounds'] ?? ''); ?>' />
        <label for='playedRounds'><?php esc_html_e( 'End of season eligibility (Match Days)', 'racketmanager' ); ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="number" class="form-control" name='playerLocked' id='playerLocked' value='<?php echo esc_html($options['checks']['playerLocked'] ?? ''); ?>' />
        <label for='playerLocked'><?php esc_html_e( 'How many matches lock a player', 'racketmanager' ); ?></label>
    </div>
</div>
