<?php
/**
 * Template for list of teams matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;

/** @var array $matches */
foreach ( $matches as $match ) {
    if ( $match instanceof Tournament_Match_Card_View_Model ) {
        // Tournament fixtures arrive as pre-built match cards from the application boundary.
        $card = $match;
        ?>
        <ul class="match-group">
            <li class="match-group__item">
                <?php require RACKETMANAGER_PATH . 'templates/tournament/match.php'; ?>
            </li>
        </ul>
        <?php
        continue;
    }

    if ( ! ( $match instanceof Team_Match_Row_View_Model ) ) {
        throw new \UnexpectedValueException( 'Expected Team_Match_Row_View_Model or Tournament_Match_Card_View_Model, got ' . get_debug_type( $match ) );
    }

    if ( $match->is_tournament ) {
        // A tournament fixture that was not converted to a match card upstream: render an explicit state.
        ?>
        <ul class="match-group">
            <li class="match-group__item">
                <?php require RACKETMANAGER_PATH . 'templates/tournament/match-unavailable.php'; ?>
            </li>
        </ul>
        <?php
        continue;
    }
    ?>
    <ul class="match-group">
        <li class="match-group__item">
            <?php require RACKETMANAGER_PATH . 'templates/includes/matches-teams-match.php'; ?>
        </li>
    </ul>
    <?php
}
