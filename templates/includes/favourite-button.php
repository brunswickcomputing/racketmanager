<?php
/**
 * Template for showing favourites in a button
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

/** @var string $favourite_type */
/** @var int    $favourite_id */
global $racketmanager;
if ( is_user_logged_in() ) {
	if ( ! empty( $hidden ) ) {
		$visible = 'hidden-svg';
	} else {
		$visible = '';
	}

	$is_favourite = $racketmanager->is_user_favourite( $favourite_type, $favourite_id );
	if ( $is_favourite ) {
		$tooltip_title = __( 'Remove favourite', 'racketmanager' );
	} else {
		$tooltip_title = __( 'Add favourite', 'racketmanager' );
	}
	?>
	<span type="button" class="btn btn-sm btn-favourite <?php echo $is_favourite ? ' is-favourite' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="top"  title="<?php echo esc_html( $tooltip_title ); ?>" id="fav-<?php echo esc_attr( $favourite_id ); ?>" data-js="add-favourite" data-type="<?php echo esc_attr( $favourite_type ); ?>" data-favourite="<?php echo esc_html( $favourite_id ); ?>" data-status="<?php echo esc_attr( $is_favourite ); ?>">
		<span class="fav-icon">
			<a href="" >
				<i class="fav-icon-svg <?php echo esc_html( $visible ); ?> racketmanager-svg-icon <?php echo $is_favourite ? ' fav-icon-svg-selected' : ''; ?>
				">
				<svg width="12" height="12" class="">
					<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#suit-heart-fill' ); ?>"></use>
				</svg>
				</i>
				<span class="nav-link__value" id=""><?php esc_html_e( 'favourite', 'racketmanager' ); ?></span>
			</a>
			<span class="fav-msg" id="fav-msg-<?php echo esc_html( $favourite_id ); ?>"></span>
		</span>
	</span>
	<?php
}
