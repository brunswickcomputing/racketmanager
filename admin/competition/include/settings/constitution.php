<?php
/**
 * Competition Settings constitution administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
$tab_name   = 'constitution';
$is_invalid = false;
$msg        = null;
?>
<div class="form-control">
    <?php
    if ( 'league' === $competition->type ) {
        ?>
        <fieldset class="row gx-3 mb-3">
            <legend class=""><?php esc_html_e( 'Size limits', 'racketmanager' ); ?></legend>
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'max_teams', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'max_teams', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="max_teams" id="max_teams" placeholder="<?php esc_html_e( 'Max teams', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->max_teams ) ? esc_html( $competition->config->max_teams ) : null; ?>" />
                    <label for="max_teams" class="form-label"><?php esc_html_e( 'Max teams per league', 'racketmanager' ); ?></label>
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
                <div class="form-floating">
                    <?php
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'teams_per_club', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'teams_per_club', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="teams_per_club" id="teams_per_club" placeholder="<?php esc_html_e( 'Club teams', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->teams_per_club ) ? esc_html( $competition->config->teams_per_club ) : null; ?>" />
                    <label for="teams_per_club" class="form-label"><?php esc_html_e( 'Max clubs teams in league', 'racketmanager' ); ?></label>
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
        </fieldset>
        <?php
    } elseif ( 'tournament' === $competition->type ) {
        ?>
        <fieldset class="row gx-3 mb-3">
            <legend class=""><?php esc_html_e( 'Size limits', 'racketmanager' ); ?></legend>
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_entries', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'num_entries', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_entries" id="num_entries" placeholder="<?php esc_html_e( 'Max number of entries', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->num_entries ) ? esc_html( $competition->config->num_entries ) : null; ?>" />
                    <label for="num_entries" class="form-label"><?php esc_html_e( 'Max entries', 'racketmanager' ); ?></label>
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
        </fieldset>
        <?php
    }
    ?>
    <?php
    if ( 'league' === $competition->type ) {
        ?>
        <fieldset class="row gx-3 mb-3">
            <legend class=""><?php esc_html_e( 'Promotion/relegation', 'racketmanager' ); ?></legend>
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="form-floating">
                    <?php
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'teams_prom_relg', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'teams_prom_relg', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="teams_prom_relg" id="teams_prom_relg" placeholder="<?php esc_html_e( 'Promoted/Relegated teams', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->teams_prom_relg ) ? esc_html( $competition->config->teams_prom_relg ) : null; ?>" />
                    <label for="teams_prom_relg" class="form-label"><?php esc_html_e( 'Promoted/Relegated teams', 'racketmanager' ); ?></label>
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
                <div class="form-floating">
                    <?php
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'lowest_promotion', $validator->err_flds, true ) ) ) {
                        $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                        $is_invalid = true;
                        $msg_id     = array_search( 'lowest_promotion', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    ?>
                    <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="lowest_promotion" id="lowest_promotion" placeholder="<?php esc_html_e( 'Lowest promotion', 'racketmanager' ); ?>" value="<?php echo isset( $competition->config->lowest_promotion ) ? esc_html( $competition->config->lowest_promotion ) : null; ?>" />
                    <label for="lowest_promotion" class="form-label"><?php esc_html_e( 'Lowest promotion', 'racketmanager' ); ?></label>
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
        </fieldset>
        <?php
    }
    ?>
    <fieldset class="row gx-3 mb-3">
        <legend class=""><?php esc_html_e( 'Ranking', 'racketmanager' ); ?></legend>
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="form-floating">
                <?php
                $ranking_types = Util::get_ranking_types();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'team_ranking', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'team_ranking', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="team_ranking" id="team_ranking" >
                    <option disabled <?php selected( null, empty( $competition->config->type ) ? null : $competition->config->type ); ?>><?php esc_html_e( 'Select team ranking', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $ranking_types as $ranking_type => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $ranking_type ); ?>" <?php selected( $ranking_type, empty( $competition->config->team_ranking ) ? null : $competition->config->team_ranking ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="team_ranking" class="form-label"><?php esc_html_e( 'Team ranking', 'racketmanager' ); ?></label>
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
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend class=""><?php esc_html_e( 'Points', 'racketmanager' ); ?></legend>
        <div class="col-md-6">
            <div class="form-floating">
                <?php
                $point_rules = Util::get_point_rules();
                if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'point_rule', $validator->err_flds, true ) ) ) {
                    $error_tab  = empty( $error_tab ) ? $tab_name : $error_tab;
                    $is_invalid = true;
                    $msg_id     = array_search( 'point_rule', $validator->err_flds, true );
                    $msg        = $validator->err_msgs[$msg_id] ?? null;
                }
                ?>
                <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="point_rule" id="point_rule" >
                    <option disabled <?php selected( null, empty( $competition->config->type ) ? null : $competition->config->type ); ?>><?php esc_html_e( 'Select team ranking', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $point_rules as $point_rule => $type_desc ) {
                        ?>
                        <option value="<?php echo esc_html( $point_rule ); ?>" <?php selected( $point_rule, empty( $competition->config->point_rule ) ? null : $competition->config->point_rule ); ?>><?php echo esc_html( ucfirst( $type_desc ) ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="point_rule" class="form-label"><?php esc_html_e( 'Point rule', 'racketmanager' ); ?></label>
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
    </fieldset>
</div>
