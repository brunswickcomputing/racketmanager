<?php
/**
 * Club player options administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="form-control">
    <div class="form-control mb-3">
        <div class="form-check form-check">
            <input type="checkbox" class="form-check-input" name="clubPlayerAgeLimitCheck" id="clubPlayerAgeLimitCheck" value="true" <?php checked( 1, empty( $options['rosters']['ageLimitCheck'] ) ? null : 1 ); ?> />
            <label class="form-check-label" for="clubPlayerAgeLimitCheck"><?php esc_html_e( 'Age Limit Check', 'racketmanager' ); ?></label>
        </div>
        <div class="form-check form-check">
            <input class="form-check-input" type="checkbox" id="btmRequired" name="btmRequired" value="1" <?php checked( 1, empty( $options['rosters']['btm'] ) ? null : 1 ); ?> />
            <label for='btmRequired'><?php esc_html_e( 'LTA Tennis Number required', 'racketmanager' ); ?></label>
        </div>
    </div>
    <div class="form-floating mb-3">
        <select class="form-select" id="clubPlayerEntry" name="clubPlayerEntry">
            <option value="secretary" <?php selected( 'secretary', empty( $options['rosters']['rosterEntry'] ) ? null : $options['rosters']['rosterEntry'] ); ?>>
                <?php esc_html_e( 'Match Secretary', 'racketmanager' ); ?>
            </option>
            <option value="captain" <?php selected( 'captain', empty( $options['rosters']['rosterEntry'] ) ? null : $options['rosters']['rosterEntry'] ); ?>>
                <?php esc_html_e( 'Captain', 'racketmanager' ); ?>
            </option>
        </select>
        <label for='clubPlayerEntry'><?php esc_html_e( 'Entry', 'racketmanager' ); ?></label>
    </div>
    <div class="form-floating mb-3">
        <select class="form-select" id="confirmation" name="confirmation">
            <option value="auto"
            <?php
            if ( isset( $options['rosters']['rosterConfirmation'] ) ) {
                echo ( 'admin' === $options['rosters']['rosterConfirmation'] ) ? ' selected' : '';
            }
            ?>
            >
                <?php esc_html_e( 'Automatic', 'racketmanager' ); ?>
            </option>
            <option value="none"
            <?php
            if ( isset( $options['rosters']['rosterConfirmation'] ) ) {
                echo ( 'none' === $options['rosters']['rosterConfirmation'] ) ? ' selected' : '';
            }
            ?>
            >
                <?php esc_html_e( 'None', 'racketmanager' ); ?>
            </option>
        </select>
        <label for='confirmation'><?php esc_html_e( 'Confirmation', 'racketmanager' ); ?></label>
    </div>
    <div class="form-floating mb-3">
        <input type="email" class="form-control" name='confirmationEmail' id='confirmationEmail' value='<?php echo isset( $options['rosters']['rosterConfirmationEmail'] ) ? esc_html( $options['rosters']['rosterConfirmationEmail'] ) : ''; ?>' />
        <label for='confirmationEmail'><?php esc_html_e( 'Notification Email Address', 'racketmanager' ); ?></label>
    </div>
</div>
