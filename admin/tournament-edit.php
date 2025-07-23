<?php
/**
 * Tournament administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string  $form_title */
/** @var boolean $edit */
/** @var array   $competitions */
/** @var array   $clubs */
/** @var string  $form_action */
/** @var object  $tournament */
$is_invalid = false;
$msg        = null;
?>
<div class='container'>
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo empty( $tournament->name ) ? '' : '<a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=' . esc_attr( $tournament->id ) . '&amp;season=' . esc_attr( $tournament->season ) . '">' . esc_html( $tournament->name ) . '</a> &raquo '; ?><?php echo esc_html( $form_title ); ?>
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
            wp_nonce_field( 'racketmanager_manage-tournament' );
        } else {
            wp_nonce_field( 'racketmanager_add-tournament' );
        }
        ?>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
            <div class="row">
                <div class="form-floating mb-3">
                    <?php
                    $is_invalid = false;
                    $msg        = null;
                    if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'tournamentName', $racketmanager->error_fields, true ) ) ) {
                        $is_invalid = true;
                        $msg_id     = array_search( 'tournamentName', $racketmanager->error_fields, true );
                        $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                    }
                    ?>
                    <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" id="tournamentName" name="tournamentName" value="<?php echo esc_html( $tournament->name ); ?>" placeholder="<?php esc_html_e( 'Add tournament', 'racketmanager' ); ?>" />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'competition_id', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition_id', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_id" id="competition_id" >
                            <option disabled <?php selected( null, empty( $tournament->competition_id ) ? null : $tournament->competition_id ); ?>><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $competitions as $competition ) {
                                ?>
                                <option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $tournament->competition_id ); ?>><?php echo esc_html( $competition->name ); ?></option>
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'season', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'season', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="season" id="season" >
                            <option disabled <?php selected( null, empty( $tournament->season ) ? null : $tournament->season ); ?>><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $seasons as $season ) {
                                ?>
                                <option value="<?php echo esc_html( $season->name ); ?>" <?php selected( $season->name, $tournament->season ?? ''); ?>><?php echo esc_html( $season->name ); ?></option>
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'venue', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'venue', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="venue" id="venue" >
                            <option disabled <?php selected( null, empty( $tournament->venue ) ? null : $tournament->venue ); ?>><?php esc_html_e( 'Select venue', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $clubs as $club ) {
                                ?>
                                <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, empty( $tournament->venue ) ? null : $tournament->venue ); ?>><?php echo esc_html( $club->shortcode ); ?></option>
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'competition_code', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition_code', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="competition_code" id="competition_code" value="<?php echo esc_html( $tournament->competition_code ); ?>" />
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
                        $grades     = Racketmanager_Util::get_event_grades();
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'grade', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'grade', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="grade" id="grade" >
                            <option disabled <?php selected( null, empty( $tournament->grade ) ? null : $tournament->grade ); ?>><?php esc_html_e( 'Select grade', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $grades as $grade => $grade_desc ) {
                                ?>
                                <option value="<?php echo esc_html( $grade ); ?>" <?php selected( $grade, empty( $tournament->grade ) ? null : $tournament->grade ); ?>><?php echo esc_html__( 'Grade', 'racketmanager' ) . ' ' . esc_html( $grade ); ?></option>
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
            <input type="hidden" name="feeId" value="<?php echo empty( $tournament->fees->id ) ? null : esc_attr( $tournament->fees->id ); ?>" />
            <div class="row mb-3 g-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'feeCompetition', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeCompetition', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeCompetition" id="feeCompetition" value="<?php echo isset( $tournament->fees->competition ) ? esc_html( $tournament->fees->competition ) : null; ?>" <?php echo ( ! empty( $tournament->fees->status ) && 'final' === $tournament->fees->status ) ? 'readonly' : null; ?> />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'feeEvent', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeEvent', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeEvent" id="feeEvent" value="<?php echo isset( $tournament->fees->event ) ? esc_html( $tournament->fees->event ) : null; ?>" <?php echo ( ! empty( $tournament->fees->status ) && 'final' === $tournament->fees->status ) ? 'readonly' : null; ?> />
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
                        if ( empty( $tournament->competition->num_entries ) ) {
                            $num_entries = empty( $tournament->num_entries ) ? null : $tournament->num_entries;
                        } else {
                            $num_entries = empty( $tournament->num_entries ) ? $tournament->competition->num_entries : $tournament->num_entries;
                        }
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'num_entries', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'num_entries', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="num_entries" id="num_entries" value="<?php echo isset( $num_entries ) ? esc_html( $num_entries ) : null; ?>" />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'dateStart', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'dateStart', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateStart" id="dateStart" value="<?php echo esc_html( $tournament->date_start ); ?>" onchange="Racketmanager.setTournamentOpenDate()" />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'dateEnd', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'dateEnd', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateEnd" id="dateEnd" value="<?php echo esc_html( $tournament->date ); ?>" />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'dateOpen', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'dateOpen', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateOpen" id="dateOpen" value="<?php echo esc_html( $tournament->date_open ); ?>" readonly />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'dateClose', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'dateClose', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateClose" id="dateClose" value="<?php echo esc_html( $tournament->date_closing ); ?>" readonly />
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
                        if ( ! empty( $racketmanager->error_fields ) && is_numeric( array_search( 'dateWithdraw', $racketmanager->error_fields, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'dateWithdraw', $racketmanager->error_fields, true );
                            $msg        = $racketmanager->error_messages[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="dateWithdraw" id="dateWithdraw" value="<?php echo isset( $tournament->date_withdrawal ) ? esc_html( $tournament->date_withdrawal ) : null; ?>" readonly />
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
