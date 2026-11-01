<?php
/**
 * Template for tournament draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var object $draw */
$tournament = $tournament ?? ( $vm->tournament ?? ( $vars['tournament'] ?? null ) );
$draw       = $draw ?? ( $vm->draw ?? ( $vars['draw'] ?? null ) );
if ( ! $draw ) {
    return;
}
?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title">
                <?php echo esc_html( $draw->name ); ?>
                <?php
                $event          = $draw;
                $favourite_type = 'competition';
                $favourite_id   = $event->id ?? null;
                require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                ?>
            </h3>
        </div>
        <?php
        $fixtures = method_exists( $draw, 'get_meta' ) ? ( $draw->get_meta( 'fixtures' ) ?: [] ) : ( $draw->fixtures ?? [] );
        require 'draw-body.php';
        ?>
    </div>
