<?php
/**
 * Competition Settings fixtures administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var bool   $is_invalid */
/** @var string $msg */
/** @var object $competition */
$tab_name = 'fixtures';
?>
<div class="form-control">
    <div class="row gx-3 mb-3">
        <div class="col-md-3 mb-3 mb-md-0">
            <?php
            $is_invalid = false;
            $msg        = null;
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'fixed_match_dates', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'fixed_match_dates', $validator->err_flds, true );
                $msg        = $validator->err_msgs[$msg_id] ?? null;
            }
            ?>
            <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixed match dates', 'racketmanager' ); ?></legend>
            <div class="form-check">
                <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixed_match_dates" id="fixed_match_dates_true" value="true" <?php checked( 1, empty( $competition->config->fixed_match_dates ) ? null : 1 ); ?> />
                <label class="form-check-label" for="fixed_match_dates_true"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
            </div>
            <div class="form-check">
                <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixed_match_dates" id="fixed_match_dates_false" value="false" <?php checked( 1, empty( $competition->config->fixed_match_dates ) ? 1 : null ); ?> />
                <label class="form-check-label" for="fixed_match_dates_false"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
            </div>
            <?php
            if ( $is_invalid ) {
                ?>
                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                <?php
            }
            ?>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <?php
            if ( $competition->is_league ) {
                $home_away_desc_true  = __( 'Home and away', 'racketmanager' );
                $home_away_desc_false = __( 'Home only', 'racketmanager' );
            } else {
                $home_away_desc_true  = __( 'Two legs', 'racketmanager' );
                $home_away_desc_false = __( 'Single leg', 'racketmanager' );
            }
            $is_invalid = false;
            $msg        = null;
            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'home_away', $validator->err_flds, true ) ) ) {
                $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                $is_invalid = true;
                $msg_id     = array_search( 'home_away', $validator->err_flds, true );
                $msg        = $validator->err_msgs[$msg_id] ?? null;
            }
            ?>
            <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixture type', 'racketmanager' ); ?></legend>
            <div class="form-check">
                <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="home_away" id="home_away_true" value="true" <?php checked( 1, empty( $competition->config->home_away ) ? null : 1 ); ?> />
                <label class="form-check-label" for="home_away_true"><?php echo esc_html( $home_away_desc_true ); ?></label>
            </div>
            <div class="form-check">
                <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="home_away" id="home_away_false" value="false" <?php checked( 1, empty( $competition->config->home_away ) ? 1 : null ); ?> />
                <label class="form-check-label" for="home_away_false"><?php echo esc_html( $home_away_desc_false ); ?></label>
            </div>
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
        <fieldset class="col-md-3 mb-3 mb-md-0">
            <legend class=""><?php esc_html_e( 'Round', 'racketmanager' ); ?></legend>
            <div class="form-floating">
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'round_length', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'round_length', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="round_length" id="round_length" placeholder="<?php esc_html_e( 'Round length', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->round_length ) ? esc_html( $competition->config->round_length ) : null; ?>" />
                <label for="round_length" class="form-label"><?php esc_html_e( 'Round length', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                    <?php
                }
                ?>
            </div>
        </fieldset>
        <?php
        if ( $competition->is_league || $competition->is_cup ) {
            ?>
            <fieldset class="col-md-3 mb-3 mb-md-0">
                <legend class=""><?php esc_html_e( 'Reverse fixture gap', 'racketmanager' ); ?></legend>
                <div class="form-floating mb-3">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'home_away_diff', $validator->err_flds, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'home_away_diff', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="home_away_diff" id="home_away_diff" value="<?php echo isset( $competition->config->home_away_diff ) ? esc_html( $competition->config->home_away_diff ) : null; ?>" onchange="Racketmanager.setEndDate()"/>
                    <label for="home_away_diff" class="form-label"><?php esc_html_e( 'Fixture gap (weeks)', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </fieldset>
            <?php
        }
        ?>
        <?php
        if ( $competition->is_league ) {
            ?>
            <fieldset class="col-md-3">
                <legend class=""><?php esc_html_e( 'Filler', 'racketmanager' ); ?></legend>
                <div class="form-floating mb-3">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'filler_weeks', $validator->err_flds, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'filler_weeks', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="filler_weeks" id="filler_weeks" value="<?php echo isset( $competition->config->filler_weeks ) ? esc_html( $competition->config->filler_weeks ) : null; ?>" onchange="Racketmanager.setEndDate()" />
                    <label for="filler_weeks" class="form-label"><?php esc_html_e( 'Filler weeks', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </fieldset>
            <?php
        }
        ?>
    </div>
    <?php
    if ( $competition->is_league || $competition->is_cup ) {
        ?>
        <fieldset class="row gx-3 mb-3">
            <div class="col-md-3 mb-3 mb-md-0">
                <legend class=""><?php esc_html_e( 'Match days', 'racketmanager' ); ?></legend>
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'match_day_restriction', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'match_day_restriction', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="match_day_restriction" id="match_day_restriction" value="true" <?php checked( 1, empty( $competition->config->match_day_restriction ) ? null : 1 ); ?> />
                    <label class="form-check-label" for="match_day_restriction"><?php esc_html_e( 'Match day restriction', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
                <?php
                $is_invalid = false;
                $msg        = null;
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'match_day_weekends', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'match_day_weekends', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="match_day_weekends" id="match_day_weekends" value="true" <?php checked( 1, empty( $competition->config->match_day_weekends ) ? null : 1 ); ?> />
                    <label class="form-check-label" for="match_day_weekends"><?php esc_html_e( 'Weekend match days', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <?php
                $is_invalid = false;
                $msg        = null;
                $match_days = Util_Lookup::get_match_days();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'fixed_match_dates', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'fixed_match_dates', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <legend class="<?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Match days allowed', 'racketmanager' ); ?></legend>
                <?php
                foreach ( $match_days as $key => $label ) {
                    ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="match_days_allowed[<?php echo esc_html( $key ); ?>]" id="match_days_allowed_<?php echo esc_html( $key ); ?>" value="1" <?php checked( 1, $competition->config->match_days_allowed[ $key ] ?? null ); ?> />
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
                }
                ?>
            </div>
        </fieldset>
        <?php
        }
    ?>
    <?php
    if ( $competition->is_league || $competition->is_cup ) {
        ?>
        <fieldset class="row gx-3 mb-3">
            <legend class=""><?php esc_html_e( 'Start times', 'racketmanager' ); ?></legend>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( isset( $competition->config->default_match_start_time ) ) {
                        if ( is_array( $competition->config->default_match_start_time ) ) {
                            $default_start_time = $competition->config->default_match_start_time['hour'] . ':' . $competition->config->default_match_start_time['minutes'];
                        } else {
                            $default_start_time = $competition->config->default_match_start_time;
                        }
                    } else {
                        $default_start_time = null;
                    }
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'default_match_start_time', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'default_match_start_time', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="default_match_start_time" id="default_match_start_time" placeholder="<?php esc_html_e( 'Default start time', 'racketmanager' ); ?>" value="<?php echo esc_html( $default_start_time ); ?>" />
                    <label for="default_match_start_time" class="form-label"><?php esc_html_e( 'Default start time', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </fieldset>
        <div class="row gx-3 mb-3">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'min_start_time_weekday', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'min_start_time_weekday', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="min_start_time_weekday" id="min_start_time_weekday" placeholder="<?php esc_html_e( 'Min weekday start time', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->start_time['weekday']['min'] ) ? esc_html( $competition->config->start_time['weekday']['min'] ) : null; ?>" />
                    <label for="min_start_time_weekday" class="form-label"><?php esc_html_e( 'Minimum weekday start time', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'max_start_time_weekday', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'max_start_time_weekday', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="max_start_time_weekday" id="max_start_time_weekday" placeholder="<?php esc_html_e( 'Max weekday start time', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->start_time['weekday']['max'] ) ? esc_html( $competition->config->start_time['weekday']['max'] ) : null; ?>" />
                    <label for="max_start_time_weekday" class="form-label"><?php esc_html_e( 'Maximum weekday start time', 'racketmanager' ); ?></label>
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
        <div class="row gx-3 mb-3">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'min_start_time_weekend', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'min_start_time_weekend', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="min_start_time_weekend" id="min_start_time_weekend" placeholder="<?php esc_html_e( 'Min weekend start time', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->start_time['weekend']['min'] ) ? esc_html( $competition->config->start_time['weekend']['min'] ) : null; ?>" />
                    <label for="min_start_time_weekend" class="form-label"><?php esc_html_e( 'Minimum weekend start time', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'max_start_time_weekend', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'max_start_time_weekend', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="time" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="max_start_time_weekend" id="max_start_time_weekend" placeholder="<?php esc_html_e( 'Max weekend start time', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->start_time['weekend']['max'] ) ? esc_html( $competition->config->start_time['weekend']['max'] ) : null; ?>" />
                    <label for="max_start_time_weekend" class="form-label"><?php esc_html_e( 'Maximum weekend start time', 'racketmanager' ); ?></label>
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
        <?php
        }
    ?>
</div>
