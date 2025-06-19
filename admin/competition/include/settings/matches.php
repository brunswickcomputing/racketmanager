<?php
/**
 * Competition Settings matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
$tab_name   = 'matches';
$is_invalid = false;
$msg        = null;
?>
<div class="form-control">
    <div class="row gx-3 mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $scoring_types = Racketmanager_Util::get_scoring_types();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'scoring', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'scoring', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='scoring' id='scoring'>
                    <option disabled <?php selected( null, empty( $competition->config->scoring ) ? null : $competition->config->scoring ); ?>><?php esc_html_e( 'Select scoring type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $scoring_types as $key => $label ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $competition->config->scoring ) ? null : $competition->config->scoring ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for='scoring'><?php esc_html_e( 'Scoring format', 'racketmanager' ); ?></label>
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
    </div>
    <div class="row gx-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'num_sets', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'num_sets', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_sets" id="num_sets" value="<?php echo isset( $competition->num_sets ) ? esc_html( $competition->num_sets ) : null; ?>" placeholder="<?php esc_html_e( 'Number of sets', 'racketmanager' ); ?>" />
                <label for="num_sets"><?php esc_html_e( 'Number of sets', 'racketmanager' ); ?></label>
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
        if ( $competition->is_player_entry ) {
            ?>
            <input type="hidden" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $competition->num_rubbers ) ? esc_html( $competition->num_rubbers ) : null; ?>" />
            <?php
        } else {
            ?>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'num_rubbers', $racketmanager->error_fields, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'num_rubbers', $racketmanager->error_fields, true );
                        $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $competition->num_rubbers ) ? esc_html( $competition->num_rubbers ) : null; ?>"  placeholder="<?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?>" />
                    <label for="num_rubbers"><?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?></label>
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
            <div class="col-md-4">
                <div class="form-check">
                    <?php
                    if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'reverse_rubbers', $racketmanager->error_fields, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'reverse_rubbers', $racketmanager->error_fields, true );
                        $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                    }
                    ?>
                    <input type="checkbox" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="reverse_rubbers" id="reverse_rubbers" <?php checked( $competition->reverse_rubbers ?? null, 1 ); ?>value="1" />
                    <label for="reverse_rubbers"><?php esc_html_e( 'Reverse rubbers', 'racketmanager' ); ?></label>
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
    </div>
</div>
