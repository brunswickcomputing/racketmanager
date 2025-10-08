<?php
/**
 * RacketManager Admin club page
 *
 * @author Paul Moffat
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $form_title */
/** @var object $club */
/** @var int    $club_id */
/** @var bool   $edit */
/** @var string $form_action */
?>
<div class="container league-block">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="/wp-admin/admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $form_title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $form_title ); ?></h1>
	<form action="" method="post" enctype="multipart/form-data" name="club_edit" class="form-control">
		<?php
        if ( $edit ) {
           wp_nonce_field( 'racketmanager_manage-club', 'racketmanager_nonce' );
        } else {
            wp_nonce_field( 'racketmanager_add-club', 'racketmanager_nonce' );
        }
        ?>
        <fieldset class="form-control mb-3">
            <legend><?php esc_html_e( 'Details', 'racketmanager' ); ?></legend>
            <div class="row gx-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'club', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'club', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" id="club" name="club" value="<?php echo isset( $club->name ) ? esc_html( $club->name ) : null; ?>" placeholder="<?php esc_html_e( 'Add Club', 'racketmanager' ); ?>" onchange="Racketmanager.setShortcode();" />
                        <label for="club"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'type', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'type', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        ?>
                        <select class="form-select <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" size="1" name="type" id="type" >
                            <option disabled <?php selected( null, $club->type ?? null ); ?>><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
                            <option value="Affiliated" <?php selected( 'Affiliated', $club->type ?? null ); ?>><?php esc_html_e( 'Affiliated', 'racketmanager' ); ?></option>
                            <option value="inactive" <?php selected( 'inactive', $club->type ?? null ); ?>><?php esc_html_e( 'Inactive', 'racketmanager' ); ?></option>
                            <option value="past" <?php selected( 'past', $club->type ?? null ); ?>><?php esc_html_e( 'Past', 'racketmanager' ); ?></option>
                        </select>
                        <label for="type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
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
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'shortcode', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'shortcode', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="shortcode" id="shortcode"  value="<?php echo isset( $club->shortcode ) ? esc_html( $club->shortcode ) : null; ?>" placeholder="<?php esc_html_e( 'Enter shortcode', 'racketmanager' ); ?>" />
                        <label for="shortcode"><?php esc_html_e( 'Shortcode', 'racketmanager' ); ?></label>
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
        if ( $edit ) {
            ?>
            <fieldset class="form-control mb-3">
                <legend><?php esc_html_e( 'Match secretary details', 'racketmanager' ); ?></legend>
                <div class="row gx-3">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="form-floating">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'match_secretary', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'match_secretary', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="match_secretary_name" id="match_secretary_name" autocomplete="off" value="<?php echo empty( $club->match_secretary->display_name ) ? null : esc_html( $club->match_secretary->display_name ); ?>" placeholder="<?php esc_html_e( 'Enter match secretary', 'racketmanager' ); ?>" /><input type="hidden" name="match_secretary" id="match_secretary" value="<?php echo empty( $club->match_secretary->id ) ? null : esc_html( $club->match_secretary->id ); ?>" />
                            <label for="match_secretary_name"><?php esc_html_e( 'Match secretary', 'racketmanager' ); ?></label>
                            <?php
                            if ( $is_invalid ) {
                                ?>
                                <div class="invalid-feedback"><?php echo esc_html( $msg ); ?></div>
                                <?php
                            }
                            ?>
                            <div id="match-secretary-feedback"></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="form-floating">
                            <?php
                            $is_invalid = false;
                            $msg        = null;
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'contactno', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'contactno', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="tel" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="match_secretary_contact_no" id="match_secretary_contact_no" autocomplete="tel" value="<?php echo empty( $club->match_secretary->contactno ) ? null : esc_html( $club->match_secretary->contactno ); ?>" placeholder="<?php esc_html_e( 'Enter contact number', 'racketmanager' ); ?>" />
                            <label for="match_secretary_contact_no"><?php esc_html_e( 'Match secretary contact', 'racketmanager' ); ?></label>
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
                            if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'contactemail', $validator->err_flds, true ) ) ) {
                                $is_invalid = true;
                                $msg_id     = array_search( 'contactemail', $validator->err_flds, true );
                                $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                            }
                            ?>
                            <input type="email" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="match_secretary_email" id="match_secretary_email" autocomplete="email" value="<?php echo empty( $club->match_secretary->email ) ? null : esc_html( $club->match_secretary->email ); ?>" placeholder="<?php esc_html_e( 'Enter contact email', 'racketmanager' ); ?>" />
                            <label for="match_secretary_email"><?php esc_html_e( 'Match secretary email', 'racketmanager' ); ?></label>
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
            <legend><?php esc_html_e( 'Contact details', 'racketmanager' ); ?></legend>
            <div class="row gx-3">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="tel" class="form-control" name="contactno" id="contactno" autocomplete="tel" value="<?php echo isset( $club->contactno ) ? esc_html( $club->contactno ) : null; ?>" placeholder="<?php esc_html_e( 'Enter contact number', 'racketmanager' ); ?>" />
                        <label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="url" class="form-control" name="website" id="website"  value="<?php echo isset( $club->website ) ? esc_html( $club->website ) : null; ?>" placeholder="<?php esc_html_e( 'Enter club web address', 'racketmanager' ); ?>" />
                        <label for="website"><?php esc_html_e( 'Website', 'racketmanager' ); ?></label>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="form-floating">
                        <?php
                        $is_invalid = false;
                        $msg        = null;
                        if ( ! empty( $validator->err_flds ) && is_numeric( array_search( 'address', $validator->err_flds, true ) ) ) {
                            $is_invalid = true;
                            $msg_id     = array_search( 'address', $validator->err_flds, true );
                            $msg        = $validator->err_msgs[ $msg_id ] ?? null;
                        }
                        ?>
                        <input type="text" class="form-control <?php echo $is_invalid ? esc_html( RACKETMANAGER_IS_INVALID ) : null; ?>" name="address" id="address" autocomplete="off" value="<?php echo isset( $club->address ) ? esc_html( $club->address ) : null; ?>" />
                        <input type="hidden" name="latitude" id="latitude"  value="<?php echo isset( $club->latitude ) ? esc_html( $club->latitude ) : null; ?>" />
                        <input type="hidden" name="longitude" id="longitude"  value="<?php echo isset( $club->longitude ) ? esc_html( $club->longitude ) : null; ?>" />
                        <label for="address"><?php esc_html_e( 'Address', 'racketmanager' ); ?></label>
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
            <legend><?php esc_html_e( 'Information', 'racketmanager' ); ?></legend>
            <div class="row gx-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="number" class="form-control" name="founded" id="founded"  value="<?php echo isset( $club->founded ) ? esc_html( $club->founded ) : null; ?>" placeholder="<?php esc_html_e( 'Enter founded year', 'racketmanager' ); ?>" />
                        <label for="founded"><?php esc_html_e( 'Founded', 'racketmanager' ); ?></label>
                    </div>
                </div>
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="form-floating">
                        <input type="text" class="form-control" name="facilities" id="facilities"  value="<?php echo isset( $club->facilities ) ? esc_html( $club->facilities ) : null; ?>" placeholder="<?php esc_html_e( 'Enter club facilities', 'racketmanager' ); ?>" />
                        <label for="facilities"><?php esc_html_e( 'Facilities', 'racketmanager' ); ?></label>
                    </div>
                </div>
            </div>
        </fieldset>
		<?php do_action( 'racketmanager_club_edit_form', $club ); ?>
		<?php
        if ( $edit ) {
            ?>
            <input type="hidden" name="club_id" id="club_id" value="<?php echo esc_html( $club->id ); ?>" />
			<input type="hidden" name="editClub" value="club" />
		    <?php
        } else {
            ?>
			<input type="hidden" name="addClub" value="club" />
		    <?php
        }
        ?>
		<button type="submit" name="action" class="btn btn-primary"><?php echo esc_html( $form_action ); ?></button>
	</form>

</div>
