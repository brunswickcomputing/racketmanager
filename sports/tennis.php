<?php
/**
 * League_Tennis API: League_Tennis class
 *
 * @author Kolja Schleich
 * @package RacketManager
 * @subpackage Sports/Tennis
 */

namespace Racketmanager;

add_filter( 'racketmanager_sports', 'Racketmanager\\racketmanager_sports_tennis' );
/**
 * Add tennis to list
 *
 * @param array $sports sports array.
 *
 * @return array
 */
function racketmanager_sports_tennis( array $sports ): array {
	$sports['tennis'] = __( 'Tennis', 'racketmanager' );
	return $sports;
}

require_once 'class-competition-tennis.php';
require_once 'class-league-tennis.php';
