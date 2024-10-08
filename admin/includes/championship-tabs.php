<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<!-- Nav tabs -->
	<ul class="nav nav-pills" id="myTab" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="finalresults-tab" data-bs-toggle="pill" data-bs-target="#finalresults" type="button" role="tab" aria-controls="finalresults" aria-selected="true"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="preliminary-tab" data-bs-toggle="pill" data-bs-target="#preliminary" type="button" role="tab" aria-controls="preliminary" aria-selected="false"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></button>
		</li>
		<?php
		if ( $league->event->competition->is_tournament && ! empty( $tournament ) ) {
			?>
			<li class="nav-item">
				<a class="nav-link" href="admin.php?page=racketmanager-tournaments&view=setup&tournament=<?php echo esc_attr( $tournament->id ); ?>&league=<?php echo esc_attr( $league->id ); ?>&season=<?php echo esc_attr( $tournament->season ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
			</li>
			<?php
		}
		?>
	</ul>
	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane fade" id="finalresults" role="tabpanel" aria-labelledby="finalresults-tab">
			<h2><?php esc_html_e( 'Final Results', 'racketmanager' ); ?></h2>
			<?php require RACKETMANAGER_PATH . 'admin/championship/finalresults.php'; ?>
		</div>
		<div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
			<h2><?php echo esc_html( $league->championship->get_final_name() ); ?></h2>
			<?php require RACKETMANAGER_PATH . 'admin/championship/finals.php'; ?>
		</div>
		<div class="tab-pane fade" id="preliminary" role="tabpanel" aria-labelledby="preliminary-tab">
			<h2><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h2>
			<?php require RACKETMANAGER_PATH . 'admin/championship/preliminary.php'; ?>
		</div>
	</div>
