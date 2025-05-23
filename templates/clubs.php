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

/** @var array $clubs */
$club_list = true;
foreach ( $clubs as $club ) {
	?>
	<div class="mb-3">
		<?php require 'club.php'; ?>
	</div>
	<?php
}
