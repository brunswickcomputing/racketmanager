<?php
/**
 * Tournament draw administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array  $match_dates */
/** @var int    $match_count */
/** @var object $tournament */
/** @var string $season */
$num_match_dates = is_array( $match_dates ) ? count( $match_dates ) : 0;
if ( $num_match_dates ) {
	$match_date_index = $num_match_dates - 1;
} else {
	$match_date_index = null;
}
if ( empty( $league ) ) {
	$button_text  = __( 'Set round dates', 'racketmanager' );
	$match_action = null;
} elseif ( $match_count ) {
	$button_text  = __( 'Replace matches', 'racketmanager' );
	$match_action = 'replace';
} else {
	$button_text  = __( 'Add matches', 'racketmanager' );
	$match_action = 'add';
}
?>
<div class="container">
	<div class='row justify-content-end'>
		<div class='col-auto racketmanager_breadcrumb'>
			<?php
			if ( empty( $league ) ) {
				?>
				<a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <?php esc_html_e( 'Setup', 'racketmanager' ); ?>
				<?php
			} else {
				?>
				<a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a>  &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=draw&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>&amp;league=<?php echo esc_attr( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo; <?php esc_html_e( 'Setup', 'racketmanager' ); ?>
				<?php
			}
			?>
		</div>
	</div>
	<h1><?php esc_html_e( 'Setup', 'racketmanager' ); ?> <?php echo empty( $league ) ? null : ' - ' . esc_html( $league->title ); ?> - <?php echo esc_html( $tournament->name ); ?></h1>
	<?php
	if ( ! empty( $league ) ) {
		?>
		<h2><?php echo esc_html( $league->title ) . ' - ' . esc_html( $season ); ?></h2>
		<div class="row mb-3">
			<div class="col-4"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></div>
			<div class="col-auto"><?php echo esc_html( $league->num_teams_total ); ?></div>
		</div>
		<div class="row mb-3">
			<div class="col-4"><?php esc_html_e( 'Rounds', 'racketmanager' ); ?></div>
			<div class="col-auto"><?php echo esc_html( $league->championship->num_rounds ); ?></div>
		</div>
		<?php
	}
	?>
	<form method="post" class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_add_championship-matches', 'racketmanager_nonce' ); ?>
		<?php
		if ( empty( $league ) ) {
			?>
			<input type="hidden" name="tournament_id" value="<?php echo esc_attr( $tournament->id ); ?>" />
			<?php
		} else {
			?>
			<input type="hidden" name="league_id" value="<?php echo esc_attr( $league->id ); ?>" />
			<?php
		}
		?>
		<input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
		<input type="hidden" name="action" value="<?php echo esc_attr( $match_action ); ?>" />
		<div class="row mb-3 fw-bold">
			<div class="col-4"><?php esc_html_e( 'Round', 'racketmanager' ); ?></div>
			<div class="col-4"><?php esc_html_e( 'Round Date', 'racketmanager' ); ?></div>
		</div>
		<?php
		$round = 0;
		if ( empty( $league ) ) {
			$object = $tournament;
		} else {
			$object = $league->championship;
		}
		foreach ( $object->finals as $final ) {
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
                    <label class="visually-hidden" for="rounds-<?php echo esc_attr( $round ); ?>-match_date"></label><input type="date" class="form-control" value="<?php echo esc_html( $round_date ); ?>" name="rounds[<?php echo esc_attr( $round ); ?>][match_date]" id="rounds-<?php echo esc_attr( $round ); ?>-match_date" />
				</div>
			</div>
			<?php
			--$match_date_index;
			++$round;
		}
		if ( ! empty( $match_count ) ) {
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
	<?php
	if ( empty( $league ) ) {
		?>
		<form method="post" class="mb-3">
			<?php wp_nonce_field( 'racketmanager_calculate_ratings', 'racketmanager_nonce' ); ?>
			<input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
			<input type="hidden" name="tournament_id" value="<?php echo esc_attr( $tournament->id ); ?>" />
			<input type="hidden" name="rank" value="calculate_rank" />
			<button class="btn btn-primary"><?php esc_html_e( 'Generate ratings', 'racketmanager' ); ?></button>
		</form>
		<?php
	}
	?>

</div>
