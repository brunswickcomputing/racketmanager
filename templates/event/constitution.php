<?php
/**
 * Template for event constitution
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$email_subject = $event->name;
require RACKETMANAGER_PATH . 'templates/email/email-header.php';
require RACKETMANAGER_PATH . 'templates/email/div-top.php';
?>
<div id="leagues">
	<h1><?php echo esc_html( $event->name ) . ' - ' . esc_html( $event->current_season['name'] ); ?></h1>
	<?php
	foreach ( $event->leagues as $league ) {
		$href = $racketmanager->site_url . '/' . __( 'league', 'racketmanager' ) . '/' . seo_url( $league->title ) . '/';
		if ( $event->is_box ) {
			$href .= __( 'round', 'racketmanager' ) . '-';
		}
		$href .= $event->current_season['name'] . '/';
		?>
		<div class="standings-archive">
			<h2 class="header">
				<a href="<?php echo esc_url( $href ); ?>">
					<?php echo esc_html( $league->title ); ?>
				</a>
			</h2>
			<?php
			racketmanager_league_standings(
				$league->id,
				array(
					'season'   => $event->current_season['name'],
					'template' => 'constitution',
				)
			);
			?>
		</div>
		<?php
	}
	?>
</div>
<?php
require RACKETMANAGER_PATH . 'templates/email/div-bottom.php';
require RACKETMANAGER_PATH . 'templates/email/email-footer.php';
