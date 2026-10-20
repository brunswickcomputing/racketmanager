<?php
/**
 * Template for showing favourite
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
    <div class="fav-icon">
        <a href="" id="fav-<?php echo esc_html( $favourite_id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top"  title="<?php echo esc_html( $favourite_state->title ); ?>" data-js="add-favourite" data-type="<?php echo esc_attr( $favourite_type ); ?>" data-favourite="<?php echo esc_html( $favourite_id ); ?>">
            <i class="fav-icon-svg <?php echo esc_html( $visible ); ?> racketmanager-svg-icon <?php echo esc_attr( $favourite_state->icon_class ); ?>
            ">
                <svg width="16" height="16" class="">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#star-fill' ); ?>"></use>
                </svg>
            </i>
        </a>
        <div class="fav-msg" id="fav-msg-<?php echo esc_html( $favourite_id ); ?>"></div>
    </div>
    <?php
    unset( $favourite_state );
} ?>
