<?php
/**
 * Template for showing favourites in a button
 *
 * @package Racketmanager/Templates/Includes
 */

namespace Racketmanager;

/** @var string $favourite_type */
/** @var int    $favourite_id */
/** @var \Racketmanager\Presentation\View_Models\Favourite_Controls_View_Model|null $favourite_controls */
if ( ! empty( $favourite_controls->can_toggle ) ) {
    if ( ! empty( $hidden ) ) {
        $visible = 'hidden-svg';
    } else {
        $visible = '';
    }

    $favourite_state = $favourite_controls->selected[ $favourite_type ][ $favourite_id ] ?? $favourite_controls->unselected;
    ?>
    <span type="button" class="btn btn-sm btn-favourite <?php echo esc_attr( $favourite_state->button_class ); ?>" data-bs-toggle="tooltip" data-bs-placement="top"  title="<?php echo esc_html( $favourite_state->title ); ?>" id="fav-<?php echo esc_attr( $favourite_id ); ?>" data-js="add-favourite" data-type="<?php echo esc_attr( $favourite_type ); ?>" data-favourite="<?php echo esc_html( $favourite_id ); ?>" data-status="<?php echo esc_attr( $favourite_state->status ); ?>">
        <span class="fav-icon">
            <a href="" >
                <i class="fav-icon-svg <?php echo esc_html( $visible ); ?> racketmanager-svg-icon <?php echo esc_attr( $favourite_state->icon_class ); ?>
                ">
                <svg width="12" height="12" class="">
                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#suit-heart-fill' ); ?>"></use>
                </svg>
                </i>
                <span class="nav-link__value" id=""><?php esc_html_e( 'favourite', 'racketmanager' ); ?></span>
            </a>
            <span class="fav-msg" id="fav-msg-<?php echo esc_html( $favourite_id ); ?>"></span>
        </span>
    </span>
    <?php
    unset( $favourite_state );
}
