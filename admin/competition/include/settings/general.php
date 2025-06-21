<?php
/**
 * Competition Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var bool   $is_invalid */
/** @var string $msg */
$tab_name = 'general';
?>
<div class="form-control">
    <div class="row gx-3 mb-3">
        <div class="form-floating">
            <?php
            if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'name', $racketmanager->error_fields, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'name', $racketmanager->error_fields, true );
                $msg        = $racketmanager->error_messages[$msg_id] ?? null;
            }
            ?>
            <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_title" id="competition_title" value="<?php echo esc_html( $competition->name ); ?>" placeholder="<?php esc_html_e( 'Competition name', 'racketmanager' ); ?>" />
            <label for="competition_title"><?php esc_html_e( 'Competition name', 'racketmanager' ); ?></label>
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
    <div class="row gx-3 mb-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $sports = Racketmanager_Util::get_sports();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'sport', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'sport', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="sport" id="sport" >
                    <option disabled <?php selected( null, empty( $competition->config->sport ) ? null : $competition->config->sport ); ?>><?php esc_html_e( 'Select sport', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $sports as $sport => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $sport ); ?>" <?php selected( $sport, empty( $competition->config->sport ) ? null : $competition->config->sport ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
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
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $types = Racketmanager_Util::get_competition_types();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'type', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'type', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="type" id="type" >
                    <option disabled <?php selected( null, empty( $competition->config->type ) ? null : $competition->config->type ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $types as $competition_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $competition_type ); ?>" <?php selected( $competition_type, empty( $competition->config->type ) ? null : $competition->config->type ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
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
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <?php
                $entry_types = Racketmanager_Util::get_entry_types();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'entry_type', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'entry_type', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="entry_type" id="entry_type" >
                    <option disabled <?php selected( null, empty( $competition->config->type ) ? null : $competition->config->type ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $entry_types as $entry_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $entry_type ); ?>" <?php selected( $entry_type, empty( $competition->config->entry_type ) ? null : $competition->config->entry_type ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
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
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
    </div>
    <div class="row gx-3">
        <div class="col-md-4">
            <div class="form-floating">
                <?php
                $age_groups = Racketmanager_Util::get_age_groups();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'age_group', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'age_group', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="age_group" id="age_group" >
                    <option disabled <?php selected( null, empty( $competition->config->age_group ) ? null : $competition->config->age_group ); ?>><?php esc_html_e( 'Select age group', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $age_groups as $age_group => $age_group_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $age_group ); ?>" <?php selected( $age_group, empty( $competition->config->age_group ) ? null : $competition->config->age_group ); ?>><?php echo esc_html( $age_group_desc ); ?></option>
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
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <?php
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'competition_code', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'competition_code', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" value="<?php echo isset( $competition->config->competition_code ) ? esc_html( $competition->config->competition_code ) : null; ?>" />
                <label for="competition_code"><?php esc_html_e( 'Competition code', 'racketmanager' ); ?></label>
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
            <div class="form-floating">
                <?php
                $grades = Racketmanager_Util::get_event_grades();
                if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'grade', $racketmanager->error_fields, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'grade', $racketmanager->error_fields, true );
                    $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="grade" id="grade" >
                    <option disabled <?php selected( null, empty( $competition->config->grade ) ? null : $competition->config->grade ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $grades as $grade => $grade_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, empty( $competition->config->grade ) ? null : $competition->config->grade ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
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
                    $is_invalid = false;
                    $msg        = null;
                }
                ?>
            </div>
        </div>
    </div>
</div>
