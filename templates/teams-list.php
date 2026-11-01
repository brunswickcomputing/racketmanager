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
$object      = $object ?? ( $vars['object'] ?? ( $vars['league'] ?? ( $vars['event'] ?? ( $league ?? ( $event ?? ( $vm->object ?? ( $vm->league ?? ( $vm->event ?? null ) ) ) ) ) ) ) );
$teams       = $teams ?? ( $vars['teams'] ?? ( $vm->teams ?? ( $object->teams ?? [] ) ) );
$team_rows   = $team_rows ?? ( $vars['team_rows'] ?? ( $vm->team_rows ?? [] ) );
$curr_season = $curr_season ?? ( $vars['curr_season'] ?? ( $vm->curr_season ?? ( $object->season ?? '' ) ) );
$is_admin    = $is_admin ?? ( $vars['is_admin'] ?? ( $vm->is_admin ?? false ) );

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
