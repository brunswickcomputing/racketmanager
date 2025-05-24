<?php
/**
 * Results main page administration panel
 *
 * @package  Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $tab */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_attr( $tab ); ?>');
});
</script>
<div class="container">
	<h1><?php esc_html_e( 'Results', 'racketmanager' ); ?></h1>
	<div class="container">
		<nav class="navbar navbar-expand-lg bg-body-tertiary">
			<div class="container-fluid">
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<!-- Nav tabs -->
					<ul class="navbar-nav nav-pills" id="myTab" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="resultschecker-tab" data-bs-toggle="tab" data-bs-target="#resultschecker" type="button" role="tab" aria-controls="resultschecker" aria-selected="false">Results Checker</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="pending-results-tab" data-bs-toggle="tab" data-bs-target="#pending-results" type="button" role="tab" aria-controls="pending-results" aria-selected="true">Pending Results</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="awaiting-confirmation-tab" data-bs-toggle="tab" data-bs-target="#awaiting-confirmation" type="button" role="tab" aria-controls="awaiting-confirmation" aria-selected="true">Awaiting confirmation</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="challenge-results-tab" data-bs-toggle="tab" data-bs-target="#challenge-results" type="button" role="tab" aria-controls="challenge-results" aria-selected="true">Challenged Results</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="results-tab" data-bs-toggle="tab" data-bs-target="#results" type="button" role="tab" aria-controls="results" aria-selected="true">Results</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="postponed-tab" data-bs-toggle="tab" data-bs-target="#postponed" type="button" role="tab" aria-controls="postponed" aria-selected="true">Postponed</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="tournament-tab" data-bs-toggle="tab" data-bs-target="#tournament" type="button" role="tab" aria-controls="tournament" aria-selected="true"><?php esc_html_e( 'Tournament', 'racketmanager' ); ?></button>
						</li>
					</ul>
				</div>

			</div>
		</nav>
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="resultschecker" role="tabpanel" aria-labelledby="resultschecker-tab">
				<h2 class="header"><?php esc_html_e( 'Results Checker', 'racketmanager' ); ?></h2>
				<?php require 'results/results-checker.php'; ?>
			</div>
			<div class="tab-pane fade" id="pending-results" role="tabpanel" aria-labelledby="pending-results-tab">
				<h2 class="header"><?php esc_html_e( 'Results not yet in', 'racketmanager' ); ?></h2>
				<?php require 'results/pending-results.php'; ?>
			</div>
			<div class="tab-pane fade" id="awaiting-confirmation" role="tabpanel" aria-labelledby="awaiting-confirmation-tab">
				<h2 class="header"><?php esc_html_e( 'Results awaiting confirmation', 'racketmanager' ); ?></h2>
				<?php require 'results/awaiting-confirmation.php'; ?>
			</div>
			<div class="tab-pane fade" id="challenge-results" role="tabpanel" aria-labelledby="challenge-results-tab">
				<h2 class="header"><?php esc_html_e( 'Challenged results', 'racketmanager' ); ?></h2>
				<?php require 'results/challenged-results.php'; ?>
			</div>
			<div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
				<h2 class="header"><?php esc_html_e( 'Results requiring action', 'racketmanager' ); ?></h2>
				<?php require 'results/results.php'; ?>
			</div>
			<div class="tab-pane fade" id="postponed" role="tabpanel" aria-labelledby="postponed-tab">
				<h2 class="header"><?php esc_html_e( 'Postponed matches', 'racketmanager' ); ?></h2>
				<?php require 'results/postponed.php'; ?>
			</div>
			<div class="tab-pane fade" id="tournament" role="tabpanel" aria-labelledby="tournament-tab">
				<h2 class="header"><?php esc_html_e( 'Tournament matches not yet in', 'racketmanager' ); ?></h2>
				<?php require 'results/tournament.php'; ?>
			</div>
		</div>
	</div>
</div>
