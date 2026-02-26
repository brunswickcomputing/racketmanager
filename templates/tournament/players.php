<?php
/**
 * Template for tournament players
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\Tournament;

/** @var Tournament $tournament */
/** @var array $tournament_players */
?>
<div class="container">
    <?php
    if ( empty( $tournament_player ) ) {
        if ( ! empty( $tournament_players ) ) {
            $player_list = $tournament_players;
            $player_link = '/tournament/' . seo_url( $tournament->name ) . '/player/';
            require RACKETMANAGER_PATH . 'templates/includes/player-list-names.php';
        }
    } else {
        require 'player.php';
    }
    ?>
</div>
