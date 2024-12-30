<?php
/**
 * Admin screen for championship teams.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<div class="championship-block">
	<form action="" method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_championship_proceed', 'racketmanager_proceed_nonce' ); ?>
		<input type="hidden" name="league-tab" value="preliminary" />
		<input type="hidden" name="action" value="startFinals" />
		<p><?php esc_html_e( 'After adding the teams and arranging the rankings then ', 'racketmanager' ); ?></p>
		<div class="mb-3">
			<?php
			if ( $league->championship->is_consolation ) {
				$link_ref = 'admin.php?page=racketmanager-' . $league->event->competition->type . 's&amp;view=teams&amp;league_id=' . $league->id . '&amp;season=' . $season;
				if ( empty( $tournament ) ) {
					$link_ref .= '&amp;competition_id=' . $league->event->competition->id;
				} else {
					$link_ref .= '&amp;tournament=' . $tournament->id;
				}
				?>
				<a class="btn btn-secondary" href="<?php echo esc_attr( $link_ref ); ?>"><?php esc_html_e( 'Add Teams', 'racketmanager' ); ?></a>
				<?php
			}
			?>
			<button class="btn btn-primary"><?php esc_html_e( 'Proceed to Final Rounds', 'racketmanager' ); ?></button>
		</div>
		<p><?php esc_html_e( 'Afterwards changes to rankings will NOT affect the final results', 'racketmanager' ); ?></p>
	</form>
	<?php
	if ( $league->is_championship && ! empty( $league->championship->num_seeds ) ) {
		?>
		<div class="mb-3">
			<span><?php /* translators: %d: number of seeds */ printf( esc_html__( 'The top %d teams will be seeded and are highlighted below.', 'racketmanager' ), esc_attr( $league->championship->num_seeds ) ); ?></span>
		</div>
		<?php
	}
	$teams = $league->get_league_teams( array() );
	require RACKETMANAGER_PATH . 'admin/league/standings.php';
	?>
</div>
