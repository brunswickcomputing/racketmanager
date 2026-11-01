<?php
/**
 * Template page to display all clubs
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $clubs: array of club objects
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

use Racketmanager\Presentation\Presenters\Club_Presenter;

/** @var array $clubs */
$clubs          = $clubs ?? $vm->clubs ?? [];
$club_presenter = $club_presenter ?? $vars['club_presenter'] ?? $vm->club_presenter ?? new Club_Presenter();
foreach ( $clubs as $club ) {
    $club_vm = $club_presenter->present( $club, standalone: false, google_maps_key: $google_maps_key ?? $vm->google_maps_key ?? null );
    ?>
    <div class="mb-3">
        <?php
        ( static function ( object $club, object $vm, array $vars, Club_Presenter $club_presenter ): void {
            $club_list = true;
            require __DIR__ . '/club.php';
        } )( $club, $club_vm, $vars ?? [], $club_presenter );
        ?>
    </div>
    <?php
}
