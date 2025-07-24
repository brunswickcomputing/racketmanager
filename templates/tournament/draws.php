<?php
/**
 * Template for tournament draws
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
if ( empty( $draw ) ) {
    ?>
    <div class="container">
        <div class="module module--card">
            <div class="module__banner">
                <h3 class="module__title"><?php esc_html_e( 'Draws', 'racketmanager' ); ?></h3>
            </div>
            <div class="module__content">
                <div class="module-container">
                    <div class="col-12 col-md-6">
                        <div class="row mb-2 row-header">
                            <div class="col-1"></div>
                            <div class="col-8">
                                <?php esc_html_e( 'Draw', 'racketmanager' ); ?>
                            </div>
                            <div class="col-3">
                                <?php esc_html_e( 'Size', 'racketmanager' ); ?>
                            </div>
                        </div>
                        <?php
                        foreach ( $tournament->events as $event ) {
                            ?>
                            <div class="row mb-2 row-list">
                                <div class="col-1" name="<?php esc_html_e( 'Favourite', 'racketmanager' ); ?>">
                                    <?php
                                    $draw_link      = '/tournament/' . seo_url( $tournament->name ) . '/draw/' . seo_url( $event->name ) . '/';
                                    $hidden         = true;
                                    $favourite_type = 'competition';
                                    $favourite_id   = $event->id;
                                    require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                                    ?>
                                </div>
                                <div class="col-8" name="<?php esc_html_e( 'Draw', 'racketmanager' ); ?>">
                                    <a href="<?php echo esc_url( $draw_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $draw_link ); ?>" data-link-id="<?php echo esc_attr( $event->id ); ?>" data-link-type="draws">
                                        <?php echo esc_html( $event->name ); ?>
                                    </a>
                                </div>
                                <div class="col-3" name="<?php esc_html_e( 'Draw size', 'racketmanager' ); ?>">
                                    <?php
                                    if ( ! empty( $event->draw_size ) ) {
                                        echo esc_html( $event->draw_size );
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title">
                <?php echo esc_html( $draw->name ); ?>
                <?php
                $event          = $draw;
                $favourite_type = 'competition';
                $favourite_id   = $event->id;
                require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                ?>
            </h3>
        </div>
        <?php
        require 'draw-body.php';
        ?>
    </div>
    <?php
}
?>
