<?php
/**
 * Competition Settings display administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var boolean $is_invalid */
/** @var string $msg */
$tab_name = 'display';
?>
<div class="form-control">
    <div class="row mb-3">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="mb-3">
                <?php
                $point_formats = Util_lookup::get_point_formats();
                ?>
                <legend class=""><?php esc_html_e( 'Point format', 'racketmanager' ); ?></legend>
                <div class="row gx-3">
                    <div class="col-6">
                        <div class="form-floating">
                            <?php
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'point_format', $validator->err_flds, true ) ) ) {
                                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                                $is_invalid = true;
                                $msg_id     = array_search( 'point_format', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="point_format" id="point_format" >
                                <option disabled <?php selected( null, empty( $competition->config->point_format ) ? null : $competition->config->point_format ); ?>><?php esc_html_e( 'Select point format', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $point_formats as $point_format => $desc ) {
                                    ?>
                                    <option value="<?php echo esc_html( $point_format ); ?>" <?php selected( $point_format, empty( $competition->config->point_format ) ? null : $competition->config->point_format ); ?>><?php echo esc_html( $desc ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <label for="point_format" class="form-label"><?php esc_html_e( 'Point format', 'racketmanager' ); ?></label>
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
                    <div class="col-6">
                        <div class="form-floating">
                            <?php
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'point_2_format', $validator->err_flds, true ) ) ) {
                                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                                $is_invalid = true;
                                $msg_id     = array_search( 'point_2_format', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="point_2_format" id="point_2_format" >
                                <option disabled <?php selected( null, empty( $competition->config->point_2_format ) ? null : $competition->config->point_2_format ); ?>><?php esc_html_e( 'Select point 2 format', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $point_formats as $point_format => $desc ) {
                                    ?>
                                    <option value="<?php echo esc_html( $point_format ); ?>" <?php selected( $point_format, empty( $competition->config->point_2_format ) ? null : $competition->config->point_2_format ); ?>><?php echo esc_html( $desc ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <label for="point_2_format" class="form-label"><?php esc_html_e( 'Point 2 format', 'racketmanager' ); ?></label>
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
            <div class="form-floating mb-3">
                <?php
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_matches_per_page', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'num_matches_per_page', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_matches_per_page" id="num_matches_per_page" value="<?php echo isset( $competition->config->num_matches_per_page ) ? esc_html( $competition->config->num_matches_per_page ) : null; ?>" />
                <label for="num_matches_per_page"><?php esc_html_e( 'Matches per page', 'racketmanager' ); ?></label>
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
        <div class="col-md-6">
            <?php
            $standings_options = Util_lookup::get_standings_display_options();
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'standings_option', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'standings_option', $validator->err_flds, true );
                $msg        = $validator->err_msgs[$msg_id] ?? null;
            }
            ?>
            <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></legend>
            <div>
                <?php
                foreach ( $standings_options as $standings_option => $type_desc ) {
                    ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="standings[<?php echo esc_html( $standings_option ); ?>]" id="standings-<?php echo esc_html( $standings_option ); ?>" value="1" <?php checked( 1, empty( $competition->config->standings[ $standings_option ] ) ? null : $competition->config->standings[ $standings_option ] ); ?> />
                        <label class="form-check-label" for="standings-<?php echo esc_html( $standings_option ); ?>"><?php echo esc_html( ucfirst( $type_desc ) ); ?></label>
                    </div>
                    <?php
                }
                ?>
            </div>
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
