<?php
/**
 * Pending results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
$racketmanager_match_args                     = array();
$racketmanager_match_args['time']             = 'outstanding';
$racketmanager_match_args['competition_type'] = 'league';
$racketmanager_match_args['orderby']          = array(
	'updated' => 'ASC',
	'id'      => 'ASC',
);
$racketmanager_options                        = $racketmanager->get_options( 'league' );
$racketmanager_result_pending                 = '';
if ( isset( $racketmanager_options['resultPending'] ) ) {
	$racketmanager_result_pending              = $racketmanager_options['resultPending'];
	$racketmanager_match_args['resultPending'] = $racketmanager_result_pending;
}
$racketmanager_matches     = $racketmanager->get_matches( $racketmanager_match_args );
$racketmanager_prev_league = 0;
?>
<div class="container">
	<div class="row table-header">
		<div class="col-4 col-sm-2 col-xxl-1"><?php esc_html_e( 'Date', 'racketmanager' ); ?></div>
		<div class="col-5"><?php esc_html_e( 'Match', 'racketmanager' ); ?></div>
	</div>
	<?php
	if ( $racketmanager_matches ) {
		$racketmanager_class = '';
		foreach ( $racketmanager_matches as $racketmanager_match ) {
			$racketmanager_match         = get_match( $racketmanager_match );
			$racketmanager_overdue_class = '';
			$racketmanager_overdue       = false;
			if ( $racketmanager_result_pending ) {
				$racketmanager_now          = date_create();
				$racketmanager_date_overdue = date_create( $racketmanager_match->result_overdue_date );
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
					echo esc_html( ' title="' . sprintf( __( 'Result overdue by %d days', 'racketmanager' ), intval( ceil( $racketmanager_match->overdue_time ) ) ) . '"' );
				}
				?>
			>
				<div class="col-4 col-sm-2 col-xxl-1"><?php echo esc_html( mysql2date( 'Y-m-d', $racketmanager_match->date ) ); ?></div>
				<div class="col-6 col-sm-5 col-lg-4 match-title">
					<a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo esc_html( $racketmanager_match->league->id ); ?>&amp;season=<?php echo esc_html( $racketmanager_match->season ); ?>&amp;<?php echo esc_html( $racketmanager_match_link ); ?> "><?php echo esc_html( $racketmanager_match->match_title ); ?></a>
				</div>
				<div class="col-auto">
					<a href="<?php echo esc_html( $racketmanager_match->link ); ?>result/?referrer=pendingresults" class="btn btn-primary"><?php esc_html_e( 'Enter result', 'racketmanager' ); ?></a>
				</div>
				<div class="col-auto">
					<a class="btn btn-secondary" onclick="Racketmanager.chaseMatchResult('<?php echo esc_html( $racketmanager_match->id ); ?>');"><?php esc_html_e( 'Chase result', 'racketmanager' ); ?></a>
				</div>
				<div class="col-12 col-md-auto"><span id="notifyMessage-<?php echo esc_html( $racketmanager_match->id ); ?>"></span></div>
			</div>
			<?php
		}
	} else {
		?>
		<div class="col-auto my-3"><?php esc_html_e( 'No matches with pending results', 'racketmanager' ); ?></div>
		<?php
	}
	?>
</div>
