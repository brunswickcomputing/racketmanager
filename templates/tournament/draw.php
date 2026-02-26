<?php
/**
 * Template for tournament draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var object $draw */
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
        $fixtures = $draw->get_meta( 'fixtures' );
        require 'draw-body.php';
        ?>
    </div>
