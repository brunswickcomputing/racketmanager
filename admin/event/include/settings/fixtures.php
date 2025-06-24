<?php
/**
 * Event Settings fixtures administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var bool   $is_invalid */
/** @var string $msg */
/** @var object $competition */
$tab_name = 'fixtures';
?>
<div class="form-control">
    <?php
    if ( $competition->is_league ) {
        ?>
        <div class="row gx-3 mb-3">
            <div class="col-md-3 mb-3 mb-md-0">
                <?php
                $match_days = Racketmanager_Util::get_match_days();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'fixed_match_dates', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'fixed_match_dates', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Match days allowed', 'racketmanager' ); ?></legend>
                <?php
                foreach ( $match_days as $key => $label ) {
                    ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="match_days_allowed[<?php echo esc_html( $key ); ?>]" id="match_days_allowed_<?php echo esc_html( $key ); ?>" value="1" <?php checked( 1, $event->config->match_days_allowed[ $key ] ?? null ); ?> />
                        <label for="match_days_allowed_<?php echo esc_html( $key ); ?>" class="form-check-label"><?php echo esc_html( $label ); ?></label>
                    </div>
                    <?php
                }
                ?>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
        <?php
    }
    ?>
    <div class="row gx-3 mb-3">
        <fieldset class="col-md-3 mb-3 mb-md-0">
            <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Main fixture date offset', 'racketmanager' ); ?></legend>
            <div class="form-floating">
                <?php
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'offset', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'offset', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='offset' id='offset'>
                    <option value='0' <?php selected( $event->config->offset ?? null, '0' ); ?>><?php esc_html_e( 'Week 0', 'racketmanager' ); ?></option>
                    <option value='1' <?php selected( $event->config->offset ?? null, '1' ); ?>><?php esc_html_e( 'Week 1', 'racketmanager' ); ?></option>
                    <option value='2' <?php selected( $event->config->offset ?? null, '2' ); ?>><?php esc_html_e( 'Week 2', 'racketmanager' ); ?></option>
                </select>
                <label for='offset'><?php esc_html_e( 'Offset week', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </fieldset>
    </div>
</div>
