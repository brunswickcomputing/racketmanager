<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$page_param     = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sub_page_param = isset( $_GET['subpage'] ) ? sanitize_text_field( wp_unslash( $_GET['subpage'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>

<div class="container">
	<?php
	if ( isset( $league->groups ) && ! empty( $league->groups ) ) {
		?>
		<div class="alignright" style="margin-right: 1em;">
			<form action="admin.php" method="get" style="display: inline;">
				<input type="hidden" name="page" value="<?php echo esc_html( $page_param ); ?>" />
				<input type="hidden" name="subpage" value="<?php echo esc_html( $sub_page_param ); ?>" />
				<input type="hidden" name="league_id" value="<?php echo esc_html( $league->id ); ?>" />
				<select name="group" size="1">
					<?php
					foreach ( $league->championship->get_groups() as $key => $g ) {
						?>
						<option value="<?php echo esc_html( $g ); ?>"<?php selected( $g, $group ); ?>>
							<?php
							/* translators: %s: group */
							echo esc_html( sprintf( __( 'Group %s', 'racketmanager' ), $g ) );
							?>
						</option>
						<?php
					}
					?>
				</select>
				<input type="hidden" name="league-tab" value="<?php echo esc_html( $tab ); ?>" />
				<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
			</form>
		</div>
		<?php
	}
	?>
	<!-- Nav tabs -->
	<ul class="nav nav-pills" id="myTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="finalresults-tab" data-bs-toggle="pill" data-bs-target="#finalresults" type="button" role="tab" aria-controls="finalresults" aria-selected="true"><?php esc_html_e( 'Final Results', 'racketmanager' ); ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Finals', 'racketmanager' ); ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="preliminary-tab" data-bs-toggle="pill" data-bs-target="#preliminary" type="button" role="tab" aria-controls="preliminary" aria-selected="false"><?php esc_html_e( 'Preliminary Rounds', 'racketmanager' ); ?></button>
		</li>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="finalresults" role="tabpanel" aria-labelledby="finalresults-tab">
			<h2><?php esc_html_e( 'Final Results', 'racketmanager' ); ?></h2>
			<?php require 'championship/finalresults.php'; ?>
		</div>
		<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
			<h2><?php echo esc_html( $league->championship->get_final_name() ); ?></h2>
			<?php require 'championship/finals.php'; ?>
		</div>
		<div class="tab-pane fade" id="preliminary" role="tabpanel" aria-labelledby="preliminary-tab">
			<h2><?php esc_html_e( 'Preliminary Rounds', 'racketmanager' ); ?></h2>
			<?php require 'championship/preliminary.php'; ?>
		</div>
	</div>
</div>
