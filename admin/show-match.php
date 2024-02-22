<?php
/**
 * League main page administration panel
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$tab = ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( ! empty( $referrer ) ) {
	$tab = $referrer; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager-results&tab=results"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $match->match_title ); ?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Match details', 'racketmanager' ); ?></h1>
	<div id="matchrubbers" class="">
		<div id="matchheader">
			<div class="row justify-content-between" id="match-header-1">
				<div class="col-auto leaguetitle"><?php echo esc_html( $match->league->title ); ?></div>
				<?php if ( isset( $match->match_day ) && $match->match_day > 0 ) { ?>
					<div class="col-auto matchday">Week <?php echo esc_html( $match->match_day ); ?></div>
				<?php } ?>
				<div class="col-auto matchdate"><?php echo esc_html( substr( $match->date, 0, 10 ) ); ?></div>
			</div>
			<div class="row justify-content-center" id="match-header-2">
				<div class="col-auto matchtitle"><?php echo esc_html( $match->match_title ); ?></div>
			</div>
		</div>
	</div>
	<div id="viewMatchRubbers">
		<div id="splash" style="display:none">
			<div class="d-flex justify-content-center">
				<div class="spinner-border" role="status">
				<span class="visually-hidden">Loading...</span>
				</div>
			</div>
		</div>
		<div id="showMatchRubbers">
			<?php echo $this->show_match_screen( $match ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
	<div class="">
		<a href="admin.php?page=racketmanager-results&amp;tab=<?php echo esc_html( $tab ); ?>" class="button button-secondary"><?php esc_html_e( 'Back to results', 'racketmanager' ); ?></a>
	</div>
</div>
