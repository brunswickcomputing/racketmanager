<?php
/**
 * Event Settings general administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

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
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'name', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'name', $validator->err_flds, true );
                $msg        = $validator->err_msgs[$msg_id] ?? null;
            }
            ?>
            <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="event_name" id="event_name" value="<?php echo isset( $event->name ) ? esc_html( $event->name ) : null; ?>"  placeholder="<?php esc_html_e( 'Event name', 'racketmanager' ); ?>" />
            <label for="event_name"><?php esc_html_e( 'Event name', 'racketmanager' ); ?></label>
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
                $types      = Util_Lookup::get_event_types();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'type', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'type', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="type" id="type" onchange="Racketmanager.setEventName()">
                    <option disabled <?php selected( null, empty( $event->type ) ? null : $event->type ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $types as $event_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $event_type ); ?>" <?php selected( $event_type, empty( $event->type ) ? null : $event->type ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
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
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $age_limits = Util_Lookup::get_age_limits( $competition->age_group );
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'age_limit', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'age_limit', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='age_limit' id='age_limit' onchange="Racketmanager.setEventName()">
                    <option disabled <?php selected( null, empty( $event->config->age_limit ) ? null : $event->config->age_limit ); ?>><?php esc_html_e( 'Select age limit', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $age_limits as $key => $label ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $event->config->age_limit ) ? null : $event->config->age_limit ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for='age_limit'><?php esc_html_e( 'Age limit', 'racketmanager' ); ?></label>
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
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'age_offset', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'age_offset', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="age_offset" id="age_offset" >
                    <option value='0' <?php selected( '0', empty( $event->config->age_offset ) ? null : $event->config->age_offset ); ?>><?php esc_html_e( 'None', 'racketmanager' ); ?></option>
                    <option value='5' <?php selected( '5', empty( $event->config->age_offset ) ? null : $event->config->age_offset ); ?>><?php esc_html_e( '5 years for ladies', 'racketmanager' ); ?></option>
                </select>
                <label for="age_offset" class="form-label"><?php esc_html_e( 'Mixed age offset', 'racketmanager' ); ?></label>
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
    <div class="row gx-3 mb-0 mb-md-3">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $is_invalid    = false;
                $scoring_types = Util_Lookup::get_scoring_types();
                $scoring       = empty( $event->config->scoring ) ? null : $event->config->scoring;
                if ( empty( $scoring ) ) {
                    $scoring = $competition->scoring ?? null;
                }
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'scoring', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'scoring', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name='scoring' id='scoring'>
                    <option disabled <?php selected( null, empty( $scoring ) ? null : $scoring ); ?>><?php esc_html_e( 'Select scoring type', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $scoring_types as $key => $label ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $key, empty( $scoring ) ? null : $scoring ); ?>><?php echo esc_html( $label ); ?></option>
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
                $num_sets   = empty( $event->config->num_sets ) ? null : $event->config->num_sets;
                if ( empty( $num_sets ) ) {
                    $num_sets = $competition->num_sets ?? null;
                }
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_sets', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'num_sets', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_sets" id="num_sets" value="<?php echo isset( $num_sets ) ? esc_html( $num_sets ) : null; ?>" placeholder="<?php esc_html_e( 'Number of sets', 'racketmanager' ); ?>" />
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
        if ( $competition->is_player_entry ) {
            ?>
            <input type="hidden" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $event->num_rubbers ) ? esc_html( $event->num_rubbers ) : null; ?>" />
            <?php
        } else {
            ?>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $num_rubbers = empty( $event->config->num_rubbers ) ? null : $event->config->num_rubbers;
                    if ( empty( $num_rubbers ) ) {
                        $num_rubbers = $competition->num_rubbers ?? null;
                    }
                    $is_invalid = false;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_rubbers', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'num_rubbers', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_rubbers" id="num_rubbers" value="<?php echo isset( $num_rubbers ) ? esc_html( $num_rubbers ) : null; ?>" placeholder="<?php esc_html_e( 'Number of rubbers', 'racketmanager' ); ?>" />
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
                    $reverse_rubbers = empty( $event->config->reverse_rubbers ) ? null : $event->config->reverse_rubbers;
                    if ( empty( $reverse_rubbers ) ) {
                        $reverse_rubbers = $competition->reverse_rubbers ?? null;
                    }
                    $is_invalid = false;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'reverse_rubbers', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'reverse_rubbers', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="checkbox" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="reverse_rubbers" id="reverse_rubbers" <?php checked( $reverse_rubbers, 1 ); ?> value="1" />
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
