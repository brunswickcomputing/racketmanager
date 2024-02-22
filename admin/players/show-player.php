<?php
/**
 * Player main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<?php if ( isset( $club_id ) ) { ?>
				<a href="admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php esc_html_e( 'Players', 'racketmanager' ); ?>
			<?php } else { ?>
				<a href="admin.php?page=racketmanager-players"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a>
			<?php } ?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Edit Player', 'racketmanager' ); ?> - <?php echo esc_html( $player->display_name ); ?></h1>

	<!-- Edit Player -->
	<div class="mb-3">
		<?php require_once RACKETMANAGER_PATH . '/admin/includes/player.php'; ?>
	</div>
	<?php if ( isset( $player_id ) ) { ?>
		<div class="">
			<?php if ( isset( $club_id ) ) { ?>
				<a href="admin.php?page=racketmanager-clubs&amp;view=players&amp;club_id=<?php echo esc_html( $club_id ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to players', 'racketmanager' ); ?></a>
			<?php } else { ?>
				<a href="admin.php?page=racketmanager-players&amp;tab=players" class="button button-secondary"><?php esc_html_e( 'Back to players', 'racketmanager' ); ?></a>
			<?php } ?>
		</div>
	<?php } ?>
</div>
