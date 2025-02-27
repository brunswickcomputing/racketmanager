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
			<?php
			if ( isset( $club_id ) ) {
				?>
				<a href="admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <a href="admin.php?page=racketmanager-clubs&view=players&club_id=<?php echo esc_attr( $club_id ); ?>"><?php echo esc_html( $club->shortcode ); ?></a> &raquo; <?php esc_html_e( 'Players', 'racketmanager' ); ?>
				<?php
			} else {
				?>
				<a href="admin.php?page=racketmanager-players"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a>
				<?php
			}
			?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Edit Player', 'racketmanager' ); ?> - <?php echo esc_html( $player->display_name ); ?></h1>

	<!-- Edit Player -->
	<div class="mb-3">
		<?php require_once RACKETMANAGER_PATH . '/admin/includes/player.php'; ?>
	</div>
	<?php
	if ( isset( $player_id ) ) {
		?>
		<div class="">
			<a href="<?php echo esc_attr( $page_referrer ); ?>" class="button button-secondary"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
		</div>
		<?php
	}
	?>
</div>
