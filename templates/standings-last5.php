<?php
/**
 * Standings table template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var array $teams */
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
			if ( ! empty( $teams ) ) {
				?>
				<div class="table-responsive">
					<table class="table table-striped table-borderless align-middle" aria-describedby="<?php esc_html_e( 'Standings table', 'racketmanager' ) . ' ' . esc_html( $league->title ); ?>">
						<thead class="">
							<tr>
								<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Position', 'racketmanager' ); ?>">
									<?php esc_html_e( 'Pos', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'status' ) ) {
									?>
									<th class="num d-none d-md-table-cell">
										&#160;
									</th>
									<?php
								}
								?>
								<th class="team">
									<?php esc_html_e( 'Team', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'pld' ) ) {
									?>
									<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Played', 'racketmanager' ); ?>">
										<?php esc_html_e( 'Pld', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'won' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Won', 'racketmanager' ); ?>">
										<?php esc_html_e( 'W', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'tie' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Drawn', 'racketmanager' ); ?>">
										<?php esc_html_e( 'T', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'lost' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Lost', 'racketmanager' ); ?>">
										<?php esc_html_e( 'L', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'winPercent' ) ) {
									?>
									<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Win Percentage', 'racketmanager' ); ?>">
										<?php esc_html_e( 'PCT', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'sets' ) ) {
									?>
									<th class='num' data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Sets', 'racketmanager' ); ?>">
										<?php esc_html_e( 'S', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<?php
								if ( show_standings( 'games' ) ) {
									?>
									<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Games', 'racketmanager' ); ?>">
										<?php esc_html_e( 'G', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
								<th class="num d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Adjusted Points', 'racketmanager' ); ?>">
									<?php esc_html_e( '+/-', 'racketmanager' ); ?>
								</th>
								<th class="num" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Points', 'racketmanager' ); ?>">
									<?php esc_html_e( 'Pts', 'racketmanager' ); ?>
								</th>
								<?php
								if ( show_standings( 'last5' ) ) {
									?>
									<th class="d-none d-md-table-cell" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'History', 'racketmanager' ); ?>">
										<?php esc_html_e( 'Last 5', 'racketmanager' ); ?>
									</th>
									<?php
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
                            foreach ( $teams as $team ) {
                                ?>
                                <tr class="">
                                    <td class='num'>
										<span class="rank">
											<?php echo esc_html( $team->rank ); ?>
										</span>
                                    </td>
									<?php
									if ( show_standings( 'status' ) ) {
										?>
                                        <td class="num d-none d-md-table-cell" title="<?php echo esc_html( $team->status_text ); ?>">
                                            <i class="racketmanager-svg-icon">
												<?php racketmanager_the_svg( $team->status_icon ); ?>
                                            </i>
                                        </td>
										<?php
									}
									?>
                                    <td>
										<?php
										if ( $team->is_withdrawn ) {
                                            $title_text = $team->title . ' ' . __( 'has withdrawn', 'racketmanager' );
                                            ?>
                                            <s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr( $title_text ); ?>">
											<?php
                                        }
                                        if ( $league->event->is_box ) {
                                            $season_link = __( 'round', 'racketmanager' ) . '-' . $league->current_season['name'];
                                        } else {
                                            $season_link = $league->current_season['name'];
                                        }
                                        $team_link = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/' . $season_link . '/team/' . seo_url( $team->title ) . '/';
                                        ?>
                                        <a href="<?php echo esc_attr( $team_link ); ?>" class="tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( $league->id ); ?>" data-season="<?php echo esc_attr( $league->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $team_link ); ?>" data-link-id="<?php echo esc_attr( $team->id ); ?>" data-link-type="teams">
                                            <?php echo esc_html( $team->title ); ?>
                                        </a>
                                        <?php
                                        if ( $team->is_withdrawn ) {
											?>
                                            </s>
    									    <?php
									    }
									    ?>
                                    </td>
									<?php
									if ( show_standings( 'pld' ) ) {
										?>
                                        <td class='num'>
											<?php echo esc_html( $team->done_matches ); ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'won' ) ) {
										?>
                                        <td class='num d-none d-md-table-cell'>
											<?php echo esc_html( $team->won_matches ); ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'tie' ) ) {
										?>
                                        <td class='num d-none d-md-table-cell'>
											<?php echo esc_html( $team->draw_matches ); ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'lost' ) ) {
										?>
                                        <td class='num d-none d-md-table-cell'>
											<?php echo esc_html( $team->lost_matches ) ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'winPercent' ) ) {
										?>
                                        <td class="num d-none d-md-table-cell">
											<?php echo esc_html( $team->win_percent ); ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'sets' ) ) {
										?>
                                        <td class='num'>
											<?php echo esc_html( $team->sets_won . '-' . $team->sets_allowed ); ?>
                                        </td>
										<?php
									}
									?>
									<?php
									if ( show_standings( 'games' ) ) {
										?>
                                        <td class='num'>
											<?php echo esc_html( $team->games_won . '-' . $team->games_allowed ); ?>
                                        </td>
										<?php
									}
									?>
                                    <td class='num d-none d-md-table-cell'>
										<?php echo esc_html( $team->add_points ); ?>
                                    </td>
                                    <td class='num'>
										<?php echo esc_html( $team->points_formatted['primary'] ); ?>
                                    </td>
									<?php
									if ( show_standings( 'last5' ) ) {
										?>
                                        <td class="standing-form d-none d-md-table-cell">
											<?php echo $team->last5(); ?>
                                        </td>
										<?php
									}
									?>
                                </tr>
                                <?php
                            }
							?>
						</tbody>
					</table>
				</div>
				<?php
			} else {
				esc_html_e( 'No teams found', 'racketmanager' );
			}
			?>
		</div>
	</div>
</div>
