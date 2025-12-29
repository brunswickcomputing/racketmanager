<?php
/**
 * Competition Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

/** @var object $competition */
/** @var bool   $is_invalid */
/** @var string $msg */
$tab_name = 'general';
?>
<div class="form-control">
    <div class="row gx-3 mb-3">
        <div class="form-floating">
            <?php
            $is_invalid = false;
            $msg        = null;
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'name', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'name', $validator->err_flds, true );
                $msg        = $validator->err_msgs[$msg_id] ?? null;
            }
            $current_value = $_POST['competition_title'] ?? $competition->name ?? null;
            ?>
            <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_title" id="competition_title" value="<?php echo esc_html( $current_value ); ?>" placeholder="<?php esc_html_e( 'Competition name', 'racketmanager' ); ?>" />
            <label for="competition_title"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
            <?php
            if ( $is_invalid ) {
                ?>
                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="row gx-3 mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                $sports     = Util::get_sports();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'sport', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'sport', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['sport'] ?? $competition->config->sport ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="sport" id="sport" >
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select sport', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $sports as $sport => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $sport ); ?>" <?php selected( $sport, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="sport" class="form-label"><?php esc_html_e( 'Sport', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                $types      = Util_Lookup::get_competition_types();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'type', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'type', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['type'] ?? $competition->config->type ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="type" id="type" >
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $types as $competition_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $competition_type ); ?>" <?php selected( $competition_type, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="type" class="form-label"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
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
            <div class="form-floating">
                <?php
                $is_invalid  = false;
                $msg        = null;
                $entry_types = Util_Lookup::get_entry_types();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'entry_type', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'entry_type', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['entry_type'] ?? $competition->config->entry_type ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="entry_type" id="entry_type" >
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $entry_types as $entry_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $entry_type ); ?>" <?php selected( $entry_type, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="entry_type" class="form-label"><?php esc_html_e( 'Entry type', 'racketmanager' ); ?></label>
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
        <div class="col-md-4">
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                $age_groups = Util_Lookup::get_age_groups();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'age_group', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'age_group', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['age_group'] ?? $competition->config->age_group ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="age_group" id="age_group" >
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select age group', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $age_groups as $age_group => $age_group_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $age_group ); ?>" <?php selected( $age_group, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html( $age_group_desc ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="age_group"><?php esc_html_e( 'Age Group', 'racketmanager' ); ?></label>
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
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'competition_code', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'competition_code', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['competition_code'] ?? $competition->config->competition_code ?? null;
                ?>
                <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" value="<?php echo isset( $current_value ) ? esc_html( $current_value ) : null; ?>" />
                <label for="competition_code"><?php esc_html_e( 'Competition code', 'racketmanager' ); ?></label>
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
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                $grades     = Util_Lookup::get_event_grades();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'grade', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'grade', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                $current_value = $_POST['grade'] ?? $competition->config->grade ?? null;
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="grade" id="grade" >
                    <option disabled <?php selected( null, empty( $current_value ) ? null : $current_value ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $grades as $grade => $grade_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, empty( $current_value ) ? null : $current_value ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="grade" class="form-label"><?php esc_html_e( 'Grade', 'racketmanager' ); ?></label>
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
</div>
