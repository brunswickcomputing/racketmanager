<?php
/**
 * Template page for Team List
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $league league object
 *  $team_rows: precomputed team row view models
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var string $curr_season */
/** @var object $object */
$object = $league ?? ( $event ?? ( $object ?? null ) );

if ( empty( $object->team ) ) {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                $rows = $team_rows ?? [];
                if ( ! empty( $rows ) ) {
                    ?>
                    <ol class="list list--bordered">
                        <?php
                        foreach ( $rows as $team ) {
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
                    esc_html_e( 'No teams found', 'racketmanager' );
                }
                ?>
            </div>
        </div>
    </div>
    <?php
} else {
    require 'team-details.php';
}
?>
