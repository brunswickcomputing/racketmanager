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

?>

<?php
$club_list = true;
foreach ( $clubs as $club ) {
	require 'club.php';
}
