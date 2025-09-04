<?php
/**
 * Template for event draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></h3>
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
                require RACKETMANAGER_PATH . 'templates/includes/championship-draw.php';
                ?>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<script type="text/javascript">
    let matchLinks = document.querySelectorAll('.score-row__wrapper');
    matchLinks.forEach(el => el.addEventListener('click', function (e) {
        Racketmanager.viewMatch(e)
    }));
</script>
