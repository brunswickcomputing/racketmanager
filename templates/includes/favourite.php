<?php
/**
 * Template for showing favourite
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
    <div class="fav-icon">
        <a href="" id="fav-<?php echo esc_html( $favourite_id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top"  title="<?php echo esc_html( $tooltip_title ); ?>" data-js="add-favourite" data-type="<?php echo esc_attr( $favourite_type ); ?>" data-favourite="<?php echo esc_html( $favourite_id ); ?>">
            <i class="fav-icon-svg <?php echo esc_html( $visible ); ?> racketmanager-svg-icon <?php echo $is_favourite ? ' fav-icon-svg-selected' : ''; ?>
            ">
                <svg width="16" height="16" class="">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#star-fill' ); ?>"></use>
                </svg>
            </i>
        </a>
        <div class="fav-msg" id="fav-msg-<?php echo esc_html( $favourite_id ); ?>"></div>
    </div>
    <?php
} ?>
