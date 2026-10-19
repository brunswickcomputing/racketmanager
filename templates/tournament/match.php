<?php
/**
 * Template for a tournament match
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;

/** @var Tournament_Match_Card_View_Model|null $card */
if ( ! isset( $card ) || ! ( $card instanceof Tournament_Match_Card_View_Model ) ) {
	return;
}
if ( $card->is_missing ) {
	require RACKETMANAGER_PATH . 'templates/tournament/match-unavailable.php';
	return;
}

$model = $card;
?>
<div class="match tournament-match <?php echo esc_attr( $model->match_display_class ); ?> <?php echo esc_attr( $model->selected_class ); ?>">
	<div class="match__header">
		<ul class="match__header-title">
			<li class="match__header-title-item">
				<?php echo esc_html( $model->final_name ); ?>
			</li>
			<?php if ( $model->show_draw_link ) : ?>
				<li class="match__header-title-item">
					<a href="<?php echo esc_url( $model->draw_link ); ?>">
						<?php echo esc_html( $model->league_title ); ?>
					</a>
				</li>
			<?php elseif ( $model->show_header_date ) : ?>
				<li class="match__header-title-item">
					<?php
					if ( $model->header_play_by ) {
						esc_html_e( 'Play by', 'racketmanager' );
						echo ' ';
					}
					echo esc_html( $model->header_formatted_date );
					if ( ! empty( $model->header_time_display ) ) {
						echo esc_html( $model->header_time_display );
					}
					?>
				</li>
			<?php endif; ?>
			<?php if ( $model->show_header_location ) : ?>
				<li class="match__header-title-item match__location">
					<?php echo esc_html( $model->location ); ?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
	<div class="match__body">
		<div class="match__row-wrapper">
			<?php foreach ( $model->opponents as $opp ) : ?>
				<div class="match__row <?php echo esc_attr( $opp->winner_class ); ?>">
					<div class="match__row-title">
						<?php foreach ( $opp->titles as $item ) : ?>
							<div class="match__row-title-value">
								<?php if ( $item->is_link ) : ?>
									<a href="<?php echo esc_attr( $item->link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $item->tournament_id ); ?>" data-season=""
									   data-link="<?php echo esc_attr( $item->link ); ?>" data-link-id="<?php echo esc_attr( $item->player_id ); ?>" data-link-type="players">
										<?php echo esc_html( $item->name ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $item->name ); ?>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
					<?php if ( $opp->show_w_badge ) : ?>
						<span class="match__status winner">W</span>
					<?php elseif ( ! empty( $opp->warning_message ) ) : ?>
						<span class="match__message match-warning"><?php echo esc_html( $opp->warning_message ); ?></span>
						<?php if ( $opp->show_l_badge ) : ?>
							<span class="match__status loser">L</span>
						<?php endif; ?>
					<?php elseif ( $opp->show_l_badge ) : ?>
						<span class="match__status loser">L</span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="match__result">
			<?php foreach ( $model->sets as $set ) : ?>
				<ul class="match-points">
					<li class="match-points__cell <?php echo esc_attr( $set->player1_winner_class ); ?>">
						<?php echo esc_html( $set->player1_games ); ?>
						<?php if ( $set->show_p1_tiebreak ) : ?>
							<span class="player-row__tie-break"><?php echo esc_html( $set->tiebreak ); ?></span>
						<?php endif; ?>
					</li>
					<li class="match-points__cell <?php echo esc_attr( $set->player2_winner_class ); ?>">
						<?php echo esc_html( $set->player2_games ); ?>
						<?php if ( $set->show_p2_tiebreak ) : ?>
							<span class="player-row__tie-break"><?php echo esc_html( $set->tiebreak ); ?></span>
						<?php endif; ?>
					</li>
				</ul>
			<?php endforeach; ?>
		</div>
		<?php if ( $model->show_update_button ) : ?>
			<div class="match__button">
				<a href="<?php echo esc_url( $model->update_link ); ?>" class="btn match__btn">
					<svg width="16" height="16" class="icon ">
						<use xlink:href="<?php echo esc_url( $model->pencil_icon_url ); ?>"></use>
					</svg>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<div class="match__footer">
		<ul class="match__footer-list">
			<li class="match__footer-list-item">
				<?php echo esc_html( $model->footer_location ?? '' ); ?>
			</li>
			<?php if ( $model->show_footer_date ) : ?>
				<li class="match__header-title-item">
					<?php
					if ( $model->footer_play_by ) {
						esc_html_e( 'Play by', 'racketmanager' );
						echo ' ';
					}
					echo esc_html( $model->footer_formatted_date );
					if ( ! empty( $model->footer_time_display ) ) {
						echo esc_html( $model->footer_time_display );
					}
					?>
				</li>
			<?php endif; ?>
		</ul>
	</div>
</div>
