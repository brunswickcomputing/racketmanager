<?php
/**
 * Charge administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Util\Util_Lookup;

/** @var object $charge */
/** @var string $form_title */
/** @var string $edit */
/** @var string $form_action */
/** @var array  $club_charges */
/** @var array  $competitions */
global $racketmanager;
$is_invalid = false;
$msg        = null;
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-finances"><?php esc_html_e( 'RacketManager Finances', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=charges"><?php esc_html_e( 'Charges', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $form_title ); ?>
        </div>
    </div>
    <div class="row mb-3">
        <h1><?php echo esc_html( $form_title ); ?></h1>
        <form method="post" enctype="multipart/form-data" name="charges_edit" class="form-control">
            <?php wp_nonce_field( 'racketmanager_manage-charges', 'racketmanager_nonce' ); ?>
            <fieldset class="row gx-3 mb-3">
                <legend><?php esc_html_e( 'Competition', 'racketmanager' ); ?></legend>
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'competition', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'competition', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="competition_id" id="competition_id" >
                            <option disabled <?php selected( null, empty( $charge->competition_id ) ? null : $charge->competition_id ); ?>><?php esc_html_e( 'Select competition', 'racketmanager' ); ?></option>
                            <?php
                            foreach ( $competitions as $competition ) {
                                ?>
                                <option value="<?php echo esc_attr( $competition->id ); ?>" <?php selected( $competition->id, $charge->competition_id ?? null ); ?>><?php echo esc_html( $competition->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="competition_id"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'season', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'season', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="season" id="season" >
                            <option disabled <?php selected( null, empty( $charge->season ) ? null : $charge->season ); ?>><?php esc_html_e( 'Select season', 'racketmanager' ); ?></option>
                            <?php
                            $racketmanager_seasons = $racketmanager->get_seasons( 'DESC' );
                            foreach ( $racketmanager_seasons as $racketmanager_season ) {
                                ?>
                                <option value="<?php echo esc_html( $racketmanager_season->name ); ?>" <?php selected( $racketmanager_season->name, $charge->season ?? ''); ?>><?php echo esc_html( $racketmanager_season->name ); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
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
            <fieldset class="row gx-3 mb-3">
                <legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'status', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'status', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="status" id="status" >
                            <option disabled <?php selected( null, empty( $charge->status ) ? null : $charge->status ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                            <option value="draft" <?php selected( 'draft', $charge->status ?? '' ); ?>><?php esc_html_e( 'Draft', 'racketmanager' ); ?></option>
                            <option value="final" <?php selected( 'final', $charge->status ?? '' ); ?>><?php esc_html_e( 'Final', 'racketmanager' ); ?></option>
                        </select>
                        <label for="status"><?php esc_html_e( 'Status', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'date', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'date', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="date" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="date" id="date" value="<?php echo empty( $charge->date ) ? null : esc_html( $charge->date ); ?>" />
                        <label for="date"><?php esc_html_e( 'Date', 'racketmanager' ); ?></label>
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
            <fieldset class="row gx-3 mb-3">
                <legend><?php esc_html_e( 'Fees', 'racketmanager' ); ?></legend>
                <div class="col-md-6 mb-3 mb-md-0">
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
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeClub" id="feeClub" value="<?php echo empty( $charge->fee_competition ) ? null : esc_html( $charge->fee_competition ); ?>" />
                        <label for="feeClub"><?php esc_html_e( 'Competition Fee', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'feeTeam', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'feeTeam', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[$msg_id] ?? null;
                        }
                        ?>
                        <input type="number" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="feeTeam" id="feeTeam" value="<?php echo empty( $charge->fee_event ) ? null : esc_html( $charge->fee_event ); ?>" />
                        <label for="feeTeam"><?php esc_html_e( 'Event Fee', 'racketmanager' ); ?></label>
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
            <?php do_action( 'racketmanager_charges_edit_form', $charge ); ?>

            <input type="hidden" name="charges_id" id="charges_id" value="<?php echo empty( $charge->id ) ? null : esc_html( $charge->id ); ?>" />
            <input type="hidden" name="updateCharges" value="charges" />

            <?php
            if ( $edit ) {
                ?>
                <input type="hidden" name="editCharge" value="charges" />
                <?php
            } else {
                ?>
                <input type="hidden" name="addCharge" value="charges" />
                <?php
            }
            ?>
            <div class="mb-3">
                <button type="submit" name="saveCharge" class="btn btn-primary"><?php echo esc_html( $form_action ); ?></button>
            </div>
        </form>
    </div>
    <div class="row mb-3">
        <?php
        if ( $edit && $charge->competition_is_team_entry ) {
            ?>
            <h2><?php esc_html_e( 'Club charges', 'racketmanager' ); ?></h2>
            <?php
            if ( $club_charges ) {
                ?>
                <form action="/wp-admin/admin.php?page=racketmanager-finances&view=charge&charges=<?php echo esc_attr( $charge->id ); ?>" method="post" enctype="multipart/form-data" name="clubcharges" class="form-control">
                    <?php wp_nonce_field( 'racketmanager_charges-bulk', 'racketmanager_nonce' ); ?>
                    <input type="hidden" name="view" value="charge" />
                    <div class="row fw-bold">
                        <div class="col-5"><?php esc_html_e( 'Club', 'racketmanager' ); ?></div>
                        <div class="col-2"><?php esc_html_e( 'Number of Teams', 'racketmanager' ); ?></div>
                        <div class="col-2"><?php esc_html_e( 'Fee', 'racketmanager' ); ?></div>
                    </div>
                    <?php
                    foreach ( $club_charges as $club_charge ) {
                        ?>
                        <div class="row mt-3">
                            <div class="col-5"><?php echo esc_html( $club_charge->name ); ?></div>
                            <div class="col-2"><?php echo esc_html( $club_charge->num_teams ); ?></div>
                            <div class="col-2"><?php the_currency_amount( $club_charge->fee ); ?></div>
                            <?php
                            foreach ( $club_charge->events as $event ) {
                                ?>
                                <div class="col-2"></div>
                                <div class="col-3"><?php echo esc_html( Util_Lookup::get_event_type( $event->type ) ); ?></div>
                                <div class="col-2"><?php echo esc_html( $event->count ); ?></div>
                                <div class="col-2"><?php the_currency_amount( $event->fee ); ?></div>
                                <div class="col-3"></div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="mt-3 mb-3">
                        <input type="hidden" name="charges_id" id="charges_id" value="<?php echo esc_html( $charge->id ); ?>" />
                        <button type="submit" name="generateInvoices" class="btn btn-primary"><?php esc_html_e( 'Generate Invoices', 'racketmanager' ); ?></button>
                    </div>
                </form>
                <?php
            }
        }
        ?>
    </div>
    <div class="mb-3">
        <a href="/wp-admin/admin.php?page=racketmanager-finances&amp;view=charges" class="btn btn-secondary"><?php esc_html_e( 'Back to charges', 'racketmanager' ); ?></a>
    </div>
</div>
