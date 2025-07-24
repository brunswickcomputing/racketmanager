<?php
/**
 * Template for event matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <?php
            foreach ( $event->leagues as $league ) {
                ?>
                <h4 class="header">
                    <?php echo esc_html( $league->title ); ?>
                </h4>
                <?php
                $finals   = $league->finals;
                $champion = null;
                require RACKETMANAGER_PATH . 'templates/championship-matches.php';
                ?>
                <?php
            }
            ?>
        </div>
    </div>
</div>
