<?php
/**
 * Template for tournament players
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\Tournament\Tournament;

/** @var Tournament $tournament */
/** @var array $tournament_players */
$tournament = $tournament ?? $vars['tournament'] ?? $vm->tournament ?? null;
if ( ! $tournament ) {
    return;
}
$tournament_player = $tournament_player ?? $vars['tournament_player'] ?? $vm->tournament_player ?? null;
$tournament_players = $tournament_players ?? $vars['tournament_players'] ?? $vm->tournament_players ?? array();
?>
<div class="container">
    <?php
    if ( empty( $tournament_player ) ) {
        if ( ! empty( $tournament_players ) ) {
            $player_list = $tournament_players;
            require RACKETMANAGER_PATH . 'templates/includes/player-list-names.php';
        }
    } else {
        require 'player.php';
    }
    ?>
</div>
