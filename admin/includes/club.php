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
			<a href="/wp-admin/admin.php?page=racketmanager-clubs"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $form_title ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $form_title ); ?></h1>
	<form action="/wp-admin/admin.php?page=racketmanager-clubs<?php echo empty( $club_id ) ? null : '&amp;club_id' . esc_html( $club_id ); ?>" method="post" enctype="multipart/form-data" name="club_edit" class="form-control">
		<?php
        if ( $edit ) {
           wp_nonce_field( 'racketmanager_manage-club' );
        } else {
            wp_nonce_field( 'racketmanager_add-club' );
        }
        ?>

		<div class="form-floating mb-3">
			<input type="text" class="form-control" id="club" name="club" value="<?php echo esc_html( $club->name ); ?>" size="30" placeholder="<?php esc_html_e( 'Add Club', 'racketmanager' ); ?>" />
			<label for="club"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<select class="form-select" size="1" name="type" id="type" >
				<option><?php esc_html_e( 'Select type', 'racketmanager' ); ?></option>
				<option value="Affiliated" <?php selected( 'Affiliated', $club->type ); ?>><?php esc_html_e( 'Affiliated', 'racketmanager' ); ?></option>
				<option value="inactive" <?php selected( 'inactive', $club->type ); ?>><?php esc_html_e( 'Inactive', 'racketmanager' ); ?></option>
				<option value="past" <?php selected( 'past', $club->type ); ?>><?php esc_html_e( 'Past', 'racketmanager' ); ?></option>
			</select>
			<label for="type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
				<input type="text" class="form-control" name="shortcode" id="shortcode"  value="<?php echo esc_html( $club->shortcode ); ?>" size="20" placeholder="<?php esc_html_e( 'Enter shortcode', 'racketmanager' ); ?>" />
				<label for="shortcode"><?php esc_html_e( 'Shortcode', 'racketmanager' ); ?></label>
		</div>
		<?php
        if ( $edit ) {
            ?>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" name="match_secretary_name" id="match_secretary_name" autocomplete="name off" value="<?php echo esc_html( $club->match_secretary_name ); ?>" size="40" /><input type="hidden" name="match_secretary" id="match_secretary" value="<?php echo esc_html( $club->matchsecretary ); ?>" />
				<label for="match_secretary_name"><?php esc_html_e( 'Match secretary', 'racketmanager' ); ?></label>
				<div id="match-secretary-feedback"></div>
			</div>
			<div class="form-floating mb-3">
				<input type="tel" class="form-control" name="match_secretary_contact_no" id="match_secretary_contact_no" autocomplete="tel" value="<?php echo esc_html( $club->match_secretary_contact_no ); ?>" size="20" placeholder="<?php esc_html_e( 'Enter contact number', 'racketmanager' ); ?>" />
				<label for="match_secretary_contact_no"><?php esc_html_e( 'Match secretary contact', 'racketmanager' ); ?></label>
			</div>
			<div class="form-floating mb-3">
				<input type="email" class="form-control" name="match_secretary_email" id="match_secretary_email" autocomplete="email" value="<?php echo esc_html( $club->match_secretary_email ); ?>" size="60" placeholder="<?php esc_html_e( 'Enter contact email', 'racketmanager' ); ?>" />
				<label for="match_secretary_email"><?php esc_html_e( 'Match secretary email', 'racketmanager' ); ?></label>
			</div>
		    <?php
        }
        ?>
		<div class="form-floating mb-3">
			<input type="tel" class="form-control" name="contactno" id="contactno" autocomplete="tel" value="<?php echo esc_html( $club->contactno ); ?>" size="20" placeholder="<?php esc_html_e( 'Enter contact number', 'racketmanager' ); ?>" />
			<label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="url" class="form-control" name="website" id="website"  value="<?php echo esc_html( $club->website ); ?>" size="60" placeholder="<?php esc_html_e( 'Enter club web address', 'racketmanager' ); ?>" />
			<label for="website"><?php esc_html_e( 'Website', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="number" class="form-control" name="founded" id="founded"  value="<?php echo esc_html( $club->founded ); ?>" size="4" placeholder="<?php esc_html_e( 'Enter founded year', 'racketmanager' ); ?>" />
			<label for="founded"><?php esc_html_e( 'Founded', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="text" class="form-control" name="facilities" id="facilities"  value="<?php echo esc_html( $club->facilities ); ?>" size="60" placeholder="<?php esc_html_e( 'Enter club facilities', 'racketmanager' ); ?>" />
			<label for="facilities"><?php esc_html_e( 'Facilities', 'racketmanager' ); ?></label>
		</div>
		<div class="form-floating mb-3">
			<input type="text" class="form-control" name="address" id="address" autocomplete="off" value="<?php echo esc_html( $club->address ); ?>" size="100" />
			<input type="hidden" name="latitude" id="latitude"  value="<?php echo esc_html( $club->latitude ); ?>" size="20" />
			<input type="hidden" name="longitude" id="longitude"  value="<?php echo esc_html( $club->longitude ); ?>" size="20" />
			<label for="address"><?php esc_html_e( 'Address', 'racketmanager' ); ?></label>
		</div>
		<?php do_action( 'racketmanager_club_edit_form', $club ); ?>

		<input type="hidden" name="club_id" id="club_id" value="<?php echo esc_html( $club->id ); ?>" />

		<?php
        if ( $edit ) {
            ?>
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
