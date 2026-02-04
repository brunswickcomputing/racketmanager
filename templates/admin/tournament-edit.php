<?php
/**
 * Tournament administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var string  $form_title */
/** @var boolean $edit */
/** @var array   $competitions */
/** @var array   $clubs */
/** @var string  $form_action */
/** @var object  $tournament */
/** @var array   $seasons */
?>
<div class='container'>
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo empty( $edit ) ? '' : '<a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=' . esc_attr( $tournament->id ) . '&amp;season=' . esc_attr( $tournament->season ) . '">' . esc_html( $tournament->name ) . '</a> &raquo '; ?><?php echo esc_html( $form_title ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $form_title ); ?></h1>
    <?php
    if ( empty( $tournament->id ) ) {
        $action_form = 'admin.php?page=racketmanager-tournaments&amp;view=modify';
    } else {
        $action_form = 'admin.php?page=racketmanager-tournaments&amp;view=modify&amp;tournament=' . $tournament->id;
    }
    ?>
    <form action="<?php echo esc_html( $action_form ); ?>" method='post' enctype='multipart/form-data' name='tournament_edit'>
        <?php
        if ( $edit ) {
            wp_nonce_field( 'racketmanager_manage-tournament', 'racketmanager_nonce' );
        } else {
            wp_nonce_field( 'racketmanager_add-tournament', 'racketmanager_nonce' );
        }
        ?>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
            <div class="row">
                <div class="form-floating mb-3">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'tournamentName', $validator->err_flds, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'tournamentName', $validator->err_flds, true );
                        $msg        = $validator->err_msgs[$msg_id] ?? null;
                    }
                    $tournament_name = $_POST['tournamentName'] ?? $tournament->name ?? null;
                    ?>
                    <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" id="tournamentName" name="tournamentName" value="<?php echo esc_html( $tournament_name ); ?>" placeholder="<?php esc_html_e( 'Add tournament', 'racketmanager' ); ?>" />
                    <label class="form-label" for="tournamentName"><?php esc_html_e( 'Name', 'racketmanager' ); ?></label>
                    <?php
                    if ( $is_invalid ) {
                        ?>
                        <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'competition', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $competition_id = $_POST['competition_id'] ?? $tournament->competition_id ?? null;
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_id" id="competition_id" >
                            <option disabled <?php selected( null, $competition_id ); ?>><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $competitions as $competition ) {
                                ?>
                                <option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $competition_id ); ?>><?php echo esc_html( $competition->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="competition_id" class="form-label"><?php esc_html_e( 'Competition', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'season', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'season', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $tournament_season = $_POST['season'] ?? $tournament->season ?? null;
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season" >
                            <option disabled <?php selected( null, $tournament_season ); ?>><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $seasons as $season ) {
                                ?>
                                <option value="<?php echo esc_html( $season->name ); ?>" <?php selected( $season->name, $tournament_season ); ?>><?php echo esc_html( $season->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
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
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'venue', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'venue', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $tournament_venue = $_POST['venue'] ?? $tournament->venue ?? null;
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="venue" id="venue" >
                            <option disabled <?php selected( null, $tournament_venue ); ?>><?php esc_html_e( 'Select venue', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $clubs as $club ) {
                                ?>
                                <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, $tournament_venue ); ?>><?php echo esc_html( $club->shortcode ); ?></option>
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
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'competition_code', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition_code', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $competition_code = $_POST['competition_code'] ?? $tournament->competition_code ?? null;
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" value="<?php echo esc_html( $competition_code ); ?>" />
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
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        $grades     = Util_Lookup::get_event_grades();
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'grade', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'grade', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $tournament_grade = $_POST['grade'] ?? $tournament->grade ?? null;
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="grade" id="grade" >
                            <option disabled <?php selected( null, $tournament_grade ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $grades as $grade => $grade_desc ) {
                                ?>
                                <option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, $tournament_grade ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
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
        </fieldset>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Fees', 'racketmanager' ); ?></legend>
            <input type="hidden" name="feeId" value="<?php echo empty( $fees->id ) ? null : esc_attr( $fees->id ); ?>" />
            <div class="row mb-3 g-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeCompetition', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeCompetition', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $fee_competition = $_POST['feeCompetition'] ?? $fees->competition ?? null;
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeCompetition" id="feeCompetition" value="<?php echo esc_html( $fee_competition ); ?>" <?php echo ( ! empty( $fees->status ) && 'final' === $fees->status ) ? 'readonly' : null; ?> />
                        <label for="feeCompetition"><?php esc_html_e( 'Tournament Fee', 'racketmanager' ); ?></label>
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
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeEvent', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeEvent', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $fee_event = $_POST['feeEvent'] ?? $fees->event ?? null;
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeEvent" id="feeEvent" value="<?php echo esc_html( $fee_event ); ?>" <?php echo ( ! empty( $fees->status ) && 'final' === $fees->status ) ? 'readonly' : null; ?> />
                        <label for="feeEvent"><?php esc_html_e( 'Event Fee', 'racketmanager' ); ?></label>
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
            <legend><?php esc_html_e( 'Entries', 'racketmanager' ); ?></legend>
            <div class="row mb-3 g-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        $tournament_num_entries = $_POST['num_entries'] ?? $tournament->num_entries ?? null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'num_entries', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'num_entries', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_entries" id="num_entries" value="<?php echo esc_html( $tournament_num_entries ); ?>" />
                        <label for="num_entries"><?php esc_html_e( 'Maximum number of entries', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_start', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_start', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        $date_start = $_POST['dateStart'] ?? $tournament->date_start ?? null;
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateStart" id="dateStart" value="<?php echo esc_html( $date_start ); ?>" onchange="Racketmanager.setTournamentOpenDate()" />
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
                        $date_end = $_POST['dateEnd'] ?? $tournament->date ?? null;
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateEnd" id="dateEnd" value="<?php echo esc_html( $date_end ); ?>" />
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
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_open', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_open', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $date_open = $_POST['dateOpen'] ?? $tournament->date_open ?? null;
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateOpen" id="dateOpen" value="<?php echo esc_html( $date_open ); ?>" readonly />
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
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_closing', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_closing', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $date_closing = $_POST['dateClose'] ?? $tournament->date_closing ?? null;
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateClose" id="dateClose" value="<?php echo esc_html( $date_closing ); ?>" readonly />
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
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date_withdrawal', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date_withdrawal', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        $date_withdrawal = $_POST['dateWithdraw'] ?? $tournament->date_withdrawal ?? null;
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateWithdraw" id="dateWithdraw" value="<?php echo esc_html( $date_withdrawal ); ?>" readonly />
                        <label for="dateWithdraw" class="form-label"><?php esc_html_e( 'Withdrawal Date', 'racketmanager' ); ?></label>
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
            <div class="alert_rm" id="alert-dates" style="display:none;">
                <div class="alert__body">
                    <div class="alert__body-inner" id="alert-dates-response">
                    </div>
                </div>
            </div>
        </fieldset>
        <?php do_action( 'racketmanager_tournament_edit_form', $tournament ); ?>

        <input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo empty( $tournament->id ) ? null : esc_html( $tournament->id ); ?>" />
        <input type="hidden" name="updateLeague" value="tournament" />

        <?php
        if ( $edit ) {
            ?>
            <input type="hidden" name="editTournament" value="tournament" />
            <?php
        } else {
            ?>
            <input type="hidden" name="addTournament" value="tournament" />
            <?php
        }
        ?>
        <input type="submit" name="action" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
    </form>

</div>
