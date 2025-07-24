<?php
/**
 *
 * Template page for a player
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $player */
?>
<div class="container">
    <?php
    $is_page_header = true;
    require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
    ?>
    <div class="page_content row">
        <div class="page-content__main col-12 col-lg-8">
            <?php
            require RACKETMANAGER_PATH . 'templates/player/statistics.php';
            foreach ( $player->competitions as $competition_type ) {
                if ( ! empty( $player->$competition_type ) ) {
                    ?>
                    <div class="module module--card">
                        <div class="module__banner">
                            <h4 class="module__title"><?php echo esc_html( ucfirst( $competition_type ) ); ?>s</h4>
                        </div>
                        <div class="module__content">
                            <div class="module-container">
                                <ul class="list list--bordered">
                                    <?php
                                    $competition_list = $player->$competition_type;
                                    $full_width       = true;
                                    require 'includes/competition-list.php';
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <div class="page-content__sidebar col-12 col-lg-4">
            <?php
            if ( ! empty( $player->titles ) ) {
                require RACKETMANAGER_PATH . 'templates/player/titles.php';
            }
            ?>
        </div>
    </div>
</div>
