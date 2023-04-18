<?php
/**
* Player main page administration panel
*
*/
namespace ns;
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<?php if ( isset($club_id) ) { ?>
				<a href="admin.php?page=racketmanager-clubs"><?php _e( 'Clubs', 'racketmanager' ) ?></a> &raquo; <?php _e( 'Players', 'racketmanager' ) ?>
			<?php } else { ?>
				<a href="admin.php?page=racketmanager-players"><?php _e( 'Players', 'racketmanager' ) ?></a>
			<?php } ?>
		</div>
	</div>
	<h1><?php _e( 'Edit Player', 'racketmanager' ) ?> - <?php echo $player->display_name ?></h1>

	<!-- Edit Player -->
	<div class="mb-3">
		<?php include_once( RACKETMANAGER_PATH . '/admin/includes/player.php' ); ?>
	</div>
	<?php if ( isset($player_id) ) { ?>
		<div class="">
			<?php if ( isset($club_id) ) { ?>
				<a href="admin.php?page=racketmanager-clubs&amp;view=roster&amp;club_id=<?php echo $club_id ?>" class="button button-secondary"><?php _e('Back to players', 'racketmanager'); ?></a>
			<?php } else { ?>
				<a href="admin.php?page=racketmanager-players&amp;tab=players" class="button button-secondary"><?php _e('Back to players', 'racketmanager'); ?></a>
			<?php } ?>
		</div>
	<?php } ?>
</div>
