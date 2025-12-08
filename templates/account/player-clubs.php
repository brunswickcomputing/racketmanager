<?php
/**
 * Template for favourites
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
?>
<div class="container">
    <div class="module module--card">
        <div class="module__banner">
            <h2 class="module__title"><?php esc_html_e( 'My memberships', 'racketmanager' ); ?></h2>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                if ( empty( $clubs ) ) {
                    esc_html_e( 'You are not currently a member of any club', 'racketmanager' );
                } else {
                    ?>
                    <ul class="list list--grid list--bordered">
                        <?php
                        $image = 'assets/icons/lta-icons-extra.svg#icon-team';
                        foreach ( $clubs as $player_club ) {
                            $fav_link = '/clubs/' . seo_url( $player_club->club_name ) . '/';
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
                                                    <span class="nav-link__value"><?php echo esc_html( $player_club->club_name ); ?></span>
                                                </a>
                                            </h4>
                                            <?php
                                            if ( ! empty( $player_club->approval_date ) ) {
                                                ?>
                                                <div class="media__content-subinfo">
                                                    <small class="media__subheading">
                                                        <span class="nav--link">
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html__( 'Added', 'racketmanager' ) . ': ' . esc_html( mysql2date( $racketmanager->date_format, $player_club->approval_date ) ); ?>
                                                            </span>
                                                        </span>
                                                    </small>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php
    if ( ! empty( $clubs_archive ) ) {
        ?>
        <div class="module module--card">
            <div class="module__banner">
                <h2 class="module__title"><?php esc_html_e( 'Previous memberships', 'racketmanager' ); ?></h2>
            </div>
            <div class="module__content">
                <div class="module-container">
                    <ul class="list list--grid list--bordered">
                        <?php
                        $image = 'assets/icons/lta-icons-extra.svg#icon-team';
                        foreach ( $clubs_archive as $player_club ) {
                            $fav_link = '/clubs/' . seo_url( $player_club->club_name ) . '/';
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
                                                    <span class="nav-link__value"><?php echo esc_html( $player_club->club_name ); ?></span>
                                                </a>
                                            </h4>
                                            <?php
                                            if ( ! empty( $player_club->removal_date ) ) {
                                                ?>
                                                <div class="media__content-subinfo">
                                                    <small class="media__subheading">
                                                        <span class="nav--link">
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html__( 'Removed', 'racketmanager' ) . ': ' . esc_html( mysql2date( $racketmanager->date_format, $player_club->removal_date ) ); ?>
                                                            </span>
                                                        </span>
                                                    </small>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                            <?php
                                            if ( ! empty( $player_club->approval_date ) ) {
                                                ?>
                                                <div class="media__content-subinfo">
                                                    <small class="media__subheading">
                                                        <span class="nav--link">
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html__( 'Added', 'racketmanager' ) . ': ' . esc_html( mysql2date( $racketmanager->date_format, $player_club->approval_date ) ); ?>
                                                            </span>
                                                        </span>
                                                    </small>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>
