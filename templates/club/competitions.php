<?php
/**
 *
 * Template page to competitions or a competition for a club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *  $competitions: Club_Competition_View_Model list
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Club_Competition_View_Model;

/** @var object $club */
/** @var Club_Competition_View_Model[] $competitions */
require RACKETMANAGER_PATH . 'templates/includes/club-header.php';

foreach ( $competitions as $competition ) {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <a href="<?php echo esc_url( $competition->url ); ?>">
                <h3 class="module__title"><?php echo esc_html( $competition->name ); ?></h3>
            </a>
        </div>
        <div class="module__content">
            <div class="module-container">
                <ol class="list list--bordered">
                <?php
                foreach ( $competition->events as $event ) {
                    ?>
                    <li class="list__item">
                        <?php
                        if ( '' !== $event->url ) {
                            ?>
                            <a href="<?php echo esc_url( $event->url ); ?>">
                            <?php
                        }
                        ?>
                        <h4 class="module__title"><?php echo esc_html( $event->name ); ?></h4>
                        <?php
                        if ( '' !== $event->url ) {
                            ?>
                            </a>
                            <?php
                        }
                        ?>
                        <?php
                        if ( ! empty( $event->team_rows ) ) {
                            ?>
                            <ol class="list list--naked list--indent">
                            <?php
                            foreach ( $event->team_rows as $team ) {
                                ?>
                                <li class="list__item">
                                    <?php require RACKETMANAGER_PATH . 'templates/includes/team.php'; ?>
                                </li>
                                <?php
                            }
                            ?>
                            </ol>
                            <?php
                        } else {
                            esc_html_e( 'Club has no teams in this event', 'racketmanager' );
                        }
                        ?>
                    </li>
                    <?php
                }
                ?>
                </ol>
            </div>
        </div>
    </div>
    <?php
}
?>
<div class="modal" id="teamModal"></div>

