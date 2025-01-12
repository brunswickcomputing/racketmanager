<?php
/**
 * Admin screen for championship matches.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

$match_dates     = $league->event->seasons[ $season ]['match_dates'];
$num_match_dates = count( $match_dates );
if ( $num_match_dates ) {
	$match_date_index = $num_match_dates - 1;
}
if ( $match_count ) {
	$button_text  = __( 'Replace matches', 'racketmanager' );
	$match_action = 'replace';
} else {
	$button_text  = __( 'Add matches', 'racketmanager' );
	$match_action = 'add';
}
?>
<div class="container">
	<div class="row justify-content-end">
		<div class="col-auto racketmanager_breadcrumb">
			<a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo esc_html( $league->event->competition->id ); ?>"><?php echo esc_html( $league->event->competition->name ); ?></a> &raquo;
			<a href="admin.php?page=racketmanager&amp;subpage=show-event&amp;event_id=<?php echo esc_html( $league->event->id ); ?>&amp;season=<?php echo esc_html( $league->current_season['name'] ); ?>"><?php echo esc_html( $league->event->name ); ?></a> &raquo;
			<a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo esc_html( $league->id ); ?>&amp;season=<?php echo esc_html( $league->current_season['name'] ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo;
			<?php esc_html_e( 'Add matches', 'racketmanager' ); ?>
		</div>
	</div>
	<h1><?php echo esc_html( $league->title ) . ' - ' . esc_html( $season ); ?></h1>
	<div class="row mb-3">
		<div class="col-4"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></div>
		<div class="col-auto"><?php echo esc_html( $league->num_teams_total ); ?></div>
	</div>
	<div class="row mb-3">
		<div class="col-4"><?php esc_html_e( 'Rounds', 'racketmanager' ); ?></div>
		<div class="col-auto"><?php echo esc_html( $league->championship->num_rounds ); ?></div>
	</div>
	<form method="post" class="form-control">
		<?php wp_nonce_field( 'racketmanager_add_championship-matches', 'racketmanager_nonce' ); ?>
		<input type="hidden" name="league_id" value="<?php echo esc_attr( $league->id ); ?>" />
		<input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
		<input type="hidden" name="action" value="<?php echo esc_attr( $match_action ); ?>" />
		<div class="row mb-3 fw-bold">
			<div class="col-4"><?php esc_html_e( 'Round', 'racketmanager' ); ?></div>
			<div class="col-4"><?php esc_html_e( 'Round Date', 'racketmanager' ); ?></div>
		</div>
		<?php
		$round = 0;
		foreach ( $league->championship->finals as $final ) {
			if ( ! empty( $match_dates[ $match_date_index ] ) ) {
				$round_date = $match_dates[ $match_date_index ];
			} else {
				$round_date = '';
			}
			?>
			<div class="row mb-3">
				<input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][key]" value="<?php echo esc_attr( $final['key'] ); ?>" />
				<input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][num_matches]" value="<?php echo esc_attr( $final['num_matches'] ); ?>" />
				<input type="hidden" name="rounds[<?php echo esc_attr( $round ); ?>][round]" value="<?php echo esc_attr( $final['round'] ); ?>" />
				<div class="col-4"><?php echo esc_html( $final['name'] ); ?></div>
				<div class="col-4">
					<input type="date" class="form-control" value="<?php echo esc_html( $round_date ); ?>" name="rounds[<?php echo esc_attr( $round ); ?>][match_date]" />
				</div>
			</div>
			<?php
			--$match_date_index;
			++$round;
		}
		if ( $match_count ) {
			?>
			<div class="alert_rm alert--info">
				<div class="alert__body">
					<div class="alert__body-inner">
						<span><?php esc_html_e( 'Existing matches will be replaced', 'racketmanager' ); ?></span>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		<button class="btn btn-primary"><?php echo esc_html( $button_text ); ?></button>
	</form>
</div>
