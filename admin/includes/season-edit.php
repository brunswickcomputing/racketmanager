<?php
/**
 * Season administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $season */
/** @var array  $clubs */
/** @var object $current_season */
/** @var array $seasons */
$is_invalid = false;
$breadcrumb = '<a href="admin.php?page=racketmanager-' . $competition->type . 's">' . ucfirst( $competition->type ) . 's</a> &raquo; <a href="admin.php?page=racketmanager-' . $competition->type . 's&amp;view=seasons&amp;competition_id=' . $competition->id . '">' . $competition->name . '</a> &raquo; ';
if ( empty( $edit_mode ) ) {
    $add_season    = true;
    $modify_season = false;
    $action_form   = 'admin.php?page=racketmanager-' . $competition->type . 's&amp;view=modify&amp;competition_id=' . $competition->id;
    $action_text   = __( 'Add season', 'racketmanager' );
    /* translators: %s: competition name */
    $form_title  = sprintf( __( 'Add season to %s', 'racketmanager' ), $competition->name );
    $form_action = __( 'Add', 'racketmanager' );
} else {
    $add_season    = false;
    $modify_season = true;
    $action_form   = 'admin.php?page=racketmanager-' . $competition->type . 's&amp;view=modify&amp;competition_id=' . $competition->id . '&amp;season=' . $current_season->name;
    $action_text   = __( 'Modify season', 'racketmanager' );
    $breadcrumb .= '<a href="admin.php?page=racketmanager-' . $competition->type . 's&amp;view=overview&amp;competition_id=' . $competition->id . '&amp;season=' . $season . '">' . $season . '</a> &raquo; ';
    /* translators: %s: competition name */
    $form_title  = sprintf( __( 'Modify season for %s', 'racketmanager' ), $competition->name );
    $form_action = __( 'Update', 'racketmanager' );
}
$breadcrumb .= $action_text;
$msg         = null;
?>
<div class='container'>
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <?php echo $breadcrumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
    <h1><?php echo esc_html( $form_title ); ?></h1>
    <form action="<?php echo esc_html( $action_form ); ?>" method='post' enctype='multipart/form-data' name='season_edit'>
        <?php
        if ( $modify_season ) {
            wp_nonce_field( 'racketmanager_manage-season', 'racketmanager_nonce' );
        } else {
            wp_nonce_field( 'racketmanager_add-season', 'racketmanager_nonce' );
        }
        ?>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
            <fieldset class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'season', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'season', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        if ( $modify_season ) {
                            ?>
                            <input class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season" readonly value="<?php echo esc_attr( $current_season->name ); ?>" />
                            <?php
                        } else {
                            ?>
                            <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season">
                                <option disabled <?php selected( null, empty( $current_season->name ) ? null : $current_season->name ); ?>><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $seasons as $season_option ) {
                                    ?>
                                    <option value="<?php echo esc_html( $season_option->name ); ?>" <?php selected( $season_option->name, $current_season->name ?? ''); ?> <?php disabled( isset( $competition->seasons[ $season_option->name ] ) ); ?>><?php echo esc_html( $season_option->name ); ?></option>
                                <?php } ?>
                            </select>
                            <?php
                        }
                        ?>
                        <label for="season" class="form-label"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
                        <?php
                        if ( $is_invalid ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( empty( $current_season->grade ) ) {
                            $current_grade = empty( $competition->grade ) ? null : $competition->grade;
                        } else {
                            $current_grade = $current_season->grade;
                        }
                        $grades = Util::get_event_grades();
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'grade', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'grade', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="grade" id="grade" >
                            <option disabled <?php selected( null, empty( $current_grade ) ? null : $current_grade ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $grades as $grade => $grade_desc ) {
                                ?>
                                <option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, empty( $current_grade ) ? null : $current_grade ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
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
            </fieldset>
            <div class="row g-3">
                <?php
                if ( ! $competition->is_league ) {
                    ?>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'venue', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'venue', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="venue" id="venue" >
                                <option disabled <?php selected( null, empty( $current_season->venue ) ? null : $current_season->venue ); ?>><?php esc_html_e( 'Select venue', 'racketmanager' ); ?></option>
                                <?php
                                foreach ( $clubs as $club ) {
                                    ?>
                                    <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, empty( $current_season->venue ) ? null : $current_season->venue ); ?>><?php echo esc_html( $club->name ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <label for="venue" class="form-label"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></label>
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
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( empty( $current_season->competition_code ) ) {
                            $competition_code = empty( $competition->competition_code ) ? null : $competition->competition_code;
                        } else {
                            $competition_code = $current_season->competition_code;
                        }
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'competition_code', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition_code', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" placeholder="<?php esc_html_e( 'Code', 'racketmanager' ); ?>" value="<?php echo isset( $competition_code ) ? esc_html( $competition_code ) : null; ?>" />
                        <label for="competition_code" class="form-label"><?php esc_html_e( 'Competition code', 'racketmanager' ); ?></label>
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
        </>
        <?php
        if ( $competition->is_league ) {
            ?>
            <fieldset class="form-control mb-3">
                <legend><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></legend>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( empty( $current_season->max_teams ) ) {
                                $max_teams = empty( $competition->max_teams ) ? null : $competition->max_teams;
                            } else {
                                $max_teams = $current_season->max_teams;
                            }
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'max_teams', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'max_teams', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="max_teams" id="max_teams" placeholder="<?php esc_html_e( 'Max teams', 'racketmanager' ); ?>" onchange="Racketmanager.setNumMatchDays()" value="<?php echo isset( $max_teams ) ? esc_html( $max_teams ) : null; ?>" />
                            <label for="max_teams" class="form-label"><?php esc_html_e( 'Max teams per league', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( empty( $current_season->teams_per_club ) ) {
                                $teams_per_club = empty( $competition->teams_per_club ) ? null : $competition->teams_per_club;
                            } else {
                                $teams_per_club = $current_season->teams_per_club;
                            }
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'teams_per_club', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'teams_per_club', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="teams_per_club" id="teams_per_club" placeholder="<?php esc_html_e( 'Club teams', 'racketmanager' ); ?>" value="<?php echo isset( $teams_per_club ) ? esc_html( $teams_per_club ) : null; ?>" />
                            <label for="teams_per_club" class="form-label"><?php esc_html_e( 'Max clubs teams in league', 'racketmanager' ); ?></label>
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
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( empty( $current_season->teams_prom_relg ) ) {
                                $teams_prom_relg = empty( $competition->teams_prom_relg ) ? null : $competition->teams_prom_relg;
                            } else {
                                $teams_prom_relg = $current_season->teams_prom_relg;
                            }
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'teams_prom_relg', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'teams_prom_relg', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="teams_prom_relg" id="teams_prom_relg" placeholder="<?php esc_html_e( 'Promoted/Relegated teams', 'racketmanager' ); ?>" value="<?php echo isset( $teams_prom_relg ) ? esc_html( $teams_prom_relg ) : null; ?>" />
                            <label for="teams_prom_relg" class="form-label"><?php esc_html_e( 'Promoted/Relegated teams', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( empty( $current_season->lowest_promotion ) ) {
                                $lowest_promotion = empty( $competition->lowest_promotion ) ? null : $competition->lowest_promotion;
                            } else {
                                $lowest_promotion = $current_season->lowest_promotion;
                            }
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'lowest_promotion', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'lowest_promotion', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="lowest_promotion" id="lowest_promotion" placeholder="<?php esc_html_e( 'Lowest promotion', 'racketmanager' ); ?>" value="<?php echo isset( $lowest_promotion ) ? esc_html( $lowest_promotion ) : null; ?>" />
                            <label for="lowest_promotion" class="form-label"><?php esc_html_e( 'Lowest promotion', 'racketmanager' ); ?></label>
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
            </fieldset>
            <?php
        }
        ?>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
            <div class="row g-3">
                <div class="col-md-4">
                    <?php
                    $is_invalid        = false;
                    $msg               = null;
                    $fixed_match_dates = $current_season->fixed_match_dates ?? ( $competition->fixed_match_dates ?? null );
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'fixedMatchDates', $validator->err_flds, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'fixedMatchDates', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <legend class="form-check-label <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixed match dates', 'racketmanager' ); ?></legend>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixedMatchDates" id="fixedMatchDatesTrue" value="true" <?php checked( 1, empty( $fixed_match_dates ) ? null : 1 ); ?> />
                        <label class="form-check-label" for="fixedMatchDatesTrue"><?php esc_html_e( 'True', 'racketmanager' ); ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="fixedMatchDates" id="fixedMatchDatesFalse" value="false"<?php checked( 1, empty( $fixed_match_dates ) ? 1 : null ); ?> />
                        <label class="form-check-label" for="fixedMatchDatesFalse"><?php esc_html_e( 'False', 'racketmanager' ); ?></label>
                    </div>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
                <div class="col-md-4">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( $competition->is_league ) {
                        $home_away_desc_true  = __( 'Home and away', 'racketmanager' );
                        $home_away_desc_false = __( 'Home only', 'racketmanager' );
                    } else {
                        $home_away_desc_true  = __( 'Two legs', 'racketmanager' );
                        $home_away_desc_false = __( 'Single leg', 'racketmanager' );
                    }
                    $home_away = $current_season->home_away ?? ( $competition->home_away ?? null );
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'homeAway', $validator->err_flds, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'homeAway', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <legend class="form-check-label <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></legend>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="homeAway" id="homeAwayTrue" onclick="Racketmanager.setNumMatchDays()" value="true" <?php checked( 1, empty( $home_away ) ? null : 1 ); ?> />
                        <label class="form-check-label" for="homeAwayTrue"><?php echo esc_html( $home_away_desc_true ); ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="homeAway" id="homeAwayFalse" onclick="Racketmanager.setNumMatchDays()" value="false"<?php checked( 1, empty( $home_away ) ? 1 : null ); ?> />
                        <label class="form-check-label" for="homeAwayFalse"><?php echo esc_html( $home_away_desc_false ); ?></label>
                    </div>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid     = false;
                        $msg            = null;
                        $num_match_days = $current_season->num_match_days ?? ( $competition->num_match_days ?? null );
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_match_days', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'num_match_days', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_match_days" id="num_match_days" readonly value="<?php echo isset( $num_match_days ) ? esc_html( $num_match_days ) : null; ?>" />
                        <label for="num_match_days" class="form-label"><?php esc_html_e( 'Number of match days', 'racketmanager' ); ?></label>
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
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid   = false;
                        $msg          = null;
                        $round_length = $current_season->round_length ?? ( $competition->round_length ?? null );
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'round_length', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'round_length', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="round_length" id="round_length" value="<?php echo isset( $round_length ) ? esc_html( $round_length ) : null; ?>" onchange="Racketmanager.setEndDate()" />
                        <label for="round_length" class="form-label"><?php esc_html_e( 'Round length (days)', 'racketmanager' ); ?></label>
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
                if ( $competition->is_league || $competition->is_cup ) {
                    ?>
                    <div class="col-md-4">
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid     = false;
                            $msg            = null;
                            $home_away_diff = $current_season->home_away_diff ?? ( $competition->home_away_diff ?? null );
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'home_away_diff', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'home_away_diff', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="home_away_diff" id="home_away_diff" value="<?php echo isset( $home_away_diff ) ? esc_html( $home_away_diff ) : null; ?>" onchange="Racketmanager.setEndDate()"/>
                            <label for="home_away_diff" class="form-label"><?php esc_html_e( 'Fixture gap (weeks)', 'racketmanager' ); ?></label>
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
                        <div class="form-floating mb-3">
                            <?php
                            $is_invalid   = false;
                            $msg          = null;
                            $filler_weeks = $current_season->filler_weeks ?? ( $competition->filler_weeks ?? null );
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'filler_weeks', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'filler_weeks', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[$msg_id] ?? null;
                            }
                            ?>
                            <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="filler_weeks" id="filler_weeks" value="<?php echo isset( $filler_weeks ) ? esc_html( $filler_weeks ) : null; ?>" onchange="Racketmanager.setEndDate()" />
                            <label for="filler_weeks" class="form-label"><?php esc_html_e( 'Filler weeks', 'racketmanager' ); ?></label>
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
        </fieldset>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Entry Fees', 'racketmanager' ); ?></legend>
            <input type="hidden" name="feeId" value="<?php echo empty( $current_season->fee_id ) ? null : esc_attr( $current_season->fee_id ); ?>" />
            <div class="row mb-3 g-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeClub', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeClub', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeClub" id="feeClub" step=".01" value="<?php echo isset( $current_season->fee_competition ) ? esc_html( $current_season->fee_competition ) : null; ?>" <?php echo ( ! empty( $current_season->fee_status ) && 'final' === $current_season->fee_status ) ? 'readonly' : null; ?> />
                        <label for="feeClub"><?php esc_html_e( 'Club Fee', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeClub', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeClub', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeTeam" id="feeTeam" step=".01" value="<?php echo isset( $current_season->fee_event ) ? esc_html( $current_season->fee_event ) : null; ?>" <?php echo ( ! empty( $current_season->fee_status ) && 'final' === $current_season->fee_status ) ? 'readonly' : null; ?> />
                        <label for="feeTeam"><?php esc_html_e( 'Team Fee', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeLeadTime', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeLeadTime', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeLeadTime" id="feeLeadTime" value="<?php echo isset( $current_season->fee_lead_time ) ? esc_html( $current_season->fee_lead_time ) : null; ?>" <?php echo ( ! empty( $current_season->fee_status ) && 'final' === $current_season->fee_status ) ? 'readonly' : null; ?> />
                        <label for="feeLeadTime"><?php esc_html_e( 'Fee Lead Time (weeks)', 'racketmanager' ); ?></label>
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
        </fieldset>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Dates', 'racketmanager' ); ?></legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_open', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_open', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateOpen" id="dateOpen" value="<?php echo isset( $current_season->date_open ) ? esc_html( $current_season->date_open ) : null; ?>" />
                        <label for="dateOpen" class="form-label"><?php esc_html_e( 'Opening Date', 'racketmanager' ); ?></label>
                        <?php
                        if ( $is_invalid ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_closing', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_closing', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateClose" id="dateClose" value="<?php echo isset( $current_season->date_closing ) ? esc_html( $current_season->date_closing ) : null; ?>" />
                        <label for="dateClose" class="form-label"><?php esc_html_e( 'Closing Date', 'racketmanager' ); ?></label>
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
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_start', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_start', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateStart" id="dateStart" value="<?php echo isset( $current_season->date_start ) ? esc_html( $current_season->date_start ) : null; ?>" onchange="Racketmanager.setEndDate()" />
                        <label for="dateStart" class="form-label"><?php esc_html_e( 'Start Date', 'racketmanager' ); ?></label>
                        <?php
                        if ( $is_invalid ) {
                            ?>
                            <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_end', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_end', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateEnd" id="dateEnd" value="<?php echo isset( $current_season->date_end ) ? esc_html( $current_season->date_end ) : null; ?>" <?php echo 'league' === $competition->type ? 'readonly' : null; ?> />
                        <label for="dateEnd" class="form-label"><?php esc_html_e( 'End Date', 'racketmanager' ); ?></label>
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
        </fieldset>

        <input type="hidden" name="competition_id" id="competition_id" value="<?php echo esc_html( $competition->id ); ?>" />
        <input type="hidden" name="update<?php echo esc_attr( ucfirst( $competition->type ) ); ?>" value="<?php echo esc_attr( $competition->type ); ?>" />

        <?php
        if ( $add_season ) {
            ?>
            <input type="hidden" name="addSeason" id="competitionType" value="<?php echo esc_attr( $competition->type ); ?>" />
            <?php
        } else {
            ?>
            <input type="hidden" name="editSeason" id="competitionType" value="<?php echo esc_attr( $competition->type ); ?>" />
            <?php
        }
        ?>
        <input type="submit" name="action" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
    </form>

</div>
