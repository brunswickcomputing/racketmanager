<?php
/**
 * Competition Settings matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Util\Util_Lookup;

/** @var Tournament_Competition_Config_Page_View_Model $vm */
$tab_name   = 'matches';
$is_invalid = false;
$msg        = null;
?>
<div class="form-control">
    <div class="row gx-3 mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $scoring_types = Util_Lookup::get_scoring_types();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'scoring', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'scoring', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['scoring'] ?? $vm->competition->config->scoring ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='scoring' id='scoring'>
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select scoring type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $scoring_types as $key => $label ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html( $label ); ?></option>
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
                }
                ?>
            </div>
        </div>
    </div>
    <div class="row gx-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_sets', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'num_sets', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['num_sets'] ?? $vm->competition->config->num_sets ?? null;
                ?>
                <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_sets" id="num_sets" value="<?php echo isset( $current_value ) ? esc_html( $current_value ) : null; ?>" placeholder="<?php esc_html_e( 'Number of sets', 'racketmanager' ); ?>" />
                <label for="num_sets"><?php esc_html_e( 'Number of sets', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
        $current_value = $_POST['num_rubbers'] ?? $vm->competition->config->num_rubbers ?? null;
        if ( $vm->competition->is_player_entry ) {
            ?>
            <input type="hidden" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $current_value ) ? esc_html( $current_value ) : null; ?>" />
            <?php
        } else {
            ?>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_rubbers', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'num_rubbers', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $current_value ) ? esc_html( $current_value ) : null; ?>"  placeholder="<?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?>" />
                    <label for="num_rubbers"><?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'reverse_rubbers', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'reverse_rubbers', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    $current_value = ( isset( $_POST['reverse_rubbers'] ) ? 1 : empty( $vm->competition->config->reverse_rubbers ) ) ? null : 1;
                    ?>
                    <input type="checkbox" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="reverse_rubbers" id="reverse_rubbers" <?php checked( 1, empty( $current_value ) ? null : 1 ); ?>value="true" />
                    <label for="reverse_rubbers"><?php esc_html_e( 'Reverse rubbers', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>
