<?php
/**
 * Challenged Results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
$racketmanager_match_args            = array();
$racketmanager_match_args['status']  = 'C';
$racketmanager_match_args['orderby'] = array(
	'updated' => 'ASC',
	'id'      => 'ASC',
);
$racketmanager_options               = $racketmanager->get_options( 'league' );
$racketmanager_confirmation_pending  = '';
if ( isset( $racketmanager_options['confirmationPending'] ) ) {
	$racketmanager_confirmation_pending              = $racketmanager_options['confirmationPending'];
	$racketmanager_match_args['confirmationPending'] = $racketmanager_confirmation_pending;
}
$racketmanager_matches     = $racketmanager->get_matches( $racketmanager_match_args );
$racketmanager_prev_league = 0;
?>
<div class="container">
	<?php wp_nonce_field( 'results-update' ); ?>
	<div class="row table-header">
		<div class="col-4 col-sm-2 "><?php esc_html_e( 'Date', 'racketmanager' ); ?></div>
		<div class="col-8 col-md-4"><?php esc_html_e( 'Match', 'racketmanager' ); ?></div>
		<div class="col-4 col-md-2"><?php esc_html_e( 'Status', 'racketmanager' ); ?></div>
		<div class="col-2 col-md-2"><?php esc_html_e( 'Score', 'racketmanager' ); ?></div>
	</div>
	<?php
	if ( $racketmanager_matches ) {
		$racketmanager_class = '';
		foreach ( $racketmanager_matches as $racketmanager_match ) {
			$racketmanager_match         = get_match( $racketmanager_match );
			$racketmanager_overdue_class = '';
			$racketmanager_overdue       = false;
			if ( $racketmanager_confirmation_pending ) {
				$racketmanager_now          = date_create();
				$racketmanager_date_overdue = date_create( $racketmanager_match->confirmation_overdue_date );
				if ( $racketmanager_date_overdue < $racketmanager_now ) {
					$racketmanager_overdue_class = 'bg-warning';
					$racketmanager_overdue       = true;
				}
			}
			if ( $racketmanager_match->league->is_championship ) {
				$racketmanager_match_link = 'final=' . $racketmanager_match->final_round . '&amp;league-tab=matches';
			} else {
				$racketmanager_match_link = 'match_day=' . $racketmanager_match->match_day;
			}
			$racketmanager_class = ( 'alternate' === $racketmanager_class ) ? '' : 'alternate';
			?>

			<div class="row table-row <?php echo esc_html( $racketmanager_class . ' ' . $racketmanager_overdue_class ); ?> align-items-center"
				<?php
				if ( $racketmanager_overdue ) {
					/* translators: %d: days overdue  */
					echo esc_html( ' title="' . sprintf( __( 'Confirmation racketmanager_overdue by %d days', 'racketmanager' ), intval( $racketmanager_match->overdue_time ) ) . '"' );
				}
				?>
			>
				<?php
				if ( $racketmanager_prev_league !== $racketmanager_match->league_id ) {
					$racketmanager_prev_league = $racketmanager_match->league_id;
					?>
					<div class="col-12"><?php echo esc_html( $racketmanager_match->league->title ); ?></div>
				<?php } ?>
				<div class="col-4 col-sm-2"><?php echo esc_html( mysql2date( 'Y-m-d', $racketmanager_match->date ) ); ?></div>
				<div class="col-8 col-md-4 match-title"><?php echo esc_html( $racketmanager_match->match_title ); ?></div>
				<div class="col-4 col-md-2">
					<?php echo esc_html( $racketmanager_match->confirmed_display ); ?>
				</div>
				<div class="col-2 col-md-1">
					<?php echo esc_html( $racketmanager_match->score ); ?>
				</div>
				<div class="col-auto">
					<a href="admin.php?page=racketmanager-results&amp;subpage=match&amp;match_id=<?php echo esc_html( $racketmanager_match->id ); ?>&amp;referrer=challangeresults" class="btn btn-secondary"><?php esc_html_e( 'View result', 'racketmanager' ); ?></a>
				</div>
			</div>
			<?php
		}
	} else {
		?>
	<div class="col-auto my-3"><?php esc_html_e( 'No matches found for criteria', 'racketmanager' ); ?></div>
	<?php } ?>
</div>
