<?php
/**
 * Championship settings
 */
namespace Racketmanager;

use Racketmanager\util\Util_Lookup;

$grades = Util_Lookup::get_event_grades();
?>
<div class="form-control">
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Rounds', 'racketmanager' ); ?></legend>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="number" class="form-control" name='numRounds' id='numRounds' value='<?php echo $options['championship']['numRounds'] ?? ''; ?>' />
                <label for='numRounds'><?php _e( 'Default number of rounds', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php echo esc_html__( 'Tournament Timescales', 'racketmanager' ) . ' (' . esc_html__( 'days before the start of the competition', 'racketmanager' ) . ')'; ?></legend>
        <div class="col-md-4 mb-3">
            <div class="form-floating">
                <input type="number" class="form-control" name='openLeadtime' id='openLeadtime' value='<?php echo $options['championship']['open_lead_time'] ?? ''; ?>' />
                <label for='openLeadtime'><?php _e( 'Online entries accept date', 'racketmanager' ) ?></label>
            </div>
        </div>
        <fieldset class="row gx-3 mb-3">
            <legend><?php esc_html_e( 'Closing dates', 'racketmanager' ); ?></legend>
            <?php
            foreach ( $grades as $grade => $grade_desc ) {
                ?>
                <div class="col-3 col-md-1 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="number" class="form-control" name='<?php echo esc_attr( $grade ); ?>[dateClose]' id='dateClose-<?php echo esc_attr( $grade ); ?>' value="<?php echo isset( $options['championship']['date_closing'][ $grade ] ) ? $options['championship']['date_closing'][ esc_attr( $grade ) ] : ''; ?>" />
                        <label for='dateClose-<?php echo esc_attr( $grade ); ?>'><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></label>
                    </div>
                </div>
                <?php
            }
            ?>
        </fieldset>
        <fieldset class="row gx-3 mb-3">
            <legend><?php esc_html_e( 'Withdrawal deadline', 'racketmanager' ); ?></legend>
            <?php
            foreach ( $grades as $grade => $grade_desc ) {
                ?>
                <div class="col-3 col-md-1 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="number" class="form-control" name='<?php echo esc_attr( $grade ); ?>[dateWithdraw]' id='dateWithdraw-<?php echo esc_attr( $grade ); ?>' value="<?php echo isset( $options['championship']['date_withdrawal'][ $grade ] ) ? $options['championship']['date_withdrawal'][ esc_attr( $grade ) ] : ''; ?>" />
                        <label for='dateWithdraw-<?php echo esc_attr( $grade ); ?>'><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></label>
                    </div>
                </div>
                <?php
            }
            ?>
        </fieldset>
    </fieldset>
</div>
