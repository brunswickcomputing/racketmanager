<?php
/**
 * Template for favourites display
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $favourite_name */
global$racketmanager;
?>
<ul class="list list--grid list--bordered">
    <?php
    if ( empty( $favourites ) ) {
        ?>
        <li class="list__item col-12 text-center"><?php esc_html_e( 'No favourite found', 'racketmanager' ); ?></li>
        <?php
    } else {
        foreach ( $favourites as $favourite ) {
            if ( is_user_logged_in() ) {
                $is_favourite = $racketmanager->is_user_favourite( $favourite_name, $favourite->id );
                if ( $is_favourite ) {
                    $link_title = __( 'Remove favourite', 'racketmanager' );
                } else {
                    $link_title = __( 'Add favourite', 'racketmanager' );
                }
                ?>
                <?php
            }
            switch ( $favourite_name ) {
                case 'league':
                    $image    = 'assets/icons/bootstrap-icons.svg#table';
                    $fav_link = '/league/' . seo_url( $favourite->detail->title ) . '/';
                    break;
                case 'club':
                    $fav_link = '/clubs/' . seo_url( $favourite->detail->shortcode ) . '/';
                    $image    = 'assets/icons/lta-icons-extra.svg#icon-team';
                    break;
                case 'team':
                    $fav_link = '/clubs/' . seo_url( $favourite->detail->club->shortcode ) . '/competitions/';
                    $image    = 'assets/icons/lta-icons-extra.svg#icon-team';
                    break;
                case 'competition':
                    $fav_link = '/tournaments/' . seo_url( $favourite->detail->competition->name ) . '/' . seo_url( $favourite->detail->name ) . '/';
                    $image    = 'assets/icons/lta-icons.svg#icon-bracket';
                    break;
                case 'player':
                    $fav_link = '/player/' . seo_url( $favourite->detail->display_name ) . '/';
                    $image    = 'assets/icons/bootstrap-icons.svg#person-fill';
                    break;
                default:
                    $image    = null;
                    $fav_link = null;
                    break;
            }
            ?>
            <li class="list__item col-12 col-sm-6">
                <div class="media">
                    <div class="media__wrapper">
                        <div class="media__img">
                            <svg width="16" height="16" class="media__img-element--icon">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
                            </svg>
                        </div>
                        <div class="media__content">
                            <h4 class="media__title">
                                <a class="nav--link media__link" href="<?php echo esc_attr( $fav_link ); ?>">
                                    <span class="nav-link__value"><?php echo esc_html( $favourite->name ); ?></span>
                                </a>
                            </h4>
                        </div>
                    </div>
                    <ul class="media__icons">
                        <li class="media__icons-item">
                            <?php
                            $favourite_type = $favourite_name;
                            $favourite_id   = $favourite->detail->id;
                            require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
                            ?>
                        </li>
                    </ul>
                </div>
            </li>
            <?php
        }
    }
    ?>
</ul>
