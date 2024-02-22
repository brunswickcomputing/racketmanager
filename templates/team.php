<?php
/**
 * Template page to display single team
 *
 * @package Racketmanager
 *
 * The following variables are usable:
 * $league: league object
 * $team: team object
 * $next_match: next match object
 * $prev_match: previous match object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

$racketmanager_season   = end( $league->seasons )['name'];
$racketmanager_teaminfo = $league->get_team_dtls( get_team_id() );
?>
<div class="accordion-item">
	<h3 class="accordion-header" id="heading<?php the_team_id(); ?>">
		<button class="accordion-button collapsed frontend" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php the_team_id(); ?>" aria-expanded="false" aria-controls="collapse<?php the_team_id(); ?>">
			<?php echo esc_html( the_team_name() ); ?>
		</button>
	</h3>
	<div id="collapse<?php the_team_id(); ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php the_team_id(); ?>" data-bs-parent="#teamsList">
		<div class="accordion-body">
			<div class="tm-team-content">
				<?php if ( 'championship' !== $league->mode ) { ?>
					<div style='float: right; margin-top: 1em;'>
						<a href="/index.php?league_id=<?php echo esc_html( $league->id ); ?>&team_id=<?php echo esc_html( the_team_id() ); ?>&team=<?php echo esc_html( the_team_name() ); ?>&season=<?php echo esc_html( $racketmanager_season ); ?>&racketmanager_export=calendar" class="roll-button calendar-add" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
							<i class="racketmanager-svg-icon">
								<?php racketmanager_the_svg( 'icon-calendar' ); ?>
							</i>
						</a>
					</div>
				<?php } ?>
				<dl class="team">
					<?php
					if ( ! empty( $racketmanager_teaminfo->captain ) ) {
						?>
						<dt><?php esc_html_e( 'Captain', 'racketmanager' ); ?></dt><dd><?php echo esc_html( $racketmanager_teaminfo->captain ); ?></dd>
						<?php
					}
					if ( is_user_logged_in() ) {
						if ( ! empty( $racketmanager_teaminfo->contactno ) ) {
							?>
							<dt><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></dt><dd><?php echo esc_html( $racketmanager_teaminfo->contactno ); ?></dd>
							<?php
						}
						if ( ! empty( $racketmanager_teaminfo->contactemail ) ) {
							?>
							<dt><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></dt><dd><?php echo esc_html( $racketmanager_teaminfo->contactemail ); ?></dd>
							<?php
						}
					} else {
						?>
						<dt class="contact-login-msg">You need to <a href="<?php echo esc_html( wp_login_url() ); ?>">login</a> to access captain contact details</dt>
						<?php
					}
					if ( ! empty( $racketmanager_teaminfo->match_day ) ) {
						?>
						<dt><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></dt><dd><?php echo esc_html( $racketmanager_teaminfo->match_day ); ?></dd>
						<?php
					}
					if ( ! empty( $racketmanager_teaminfo->match_time ) && $racketmanager_teaminfo->match_time > '00:00:00' ) {
						?>
						<dt><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></dt><dd><?php echo esc_html( $racketmanager_teaminfo->match_time ); ?></dd>
					<?php } ?>
				</dl>

				<?php if ( 'championship' !== $league->mode ) { ?>
					<div class="matches">
						<?php if ( has_next_match() ) { ?>
							<div class="matches-container">
								<div class="next_match">
									<h4 class="header"><?php esc_html_e( 'Next Match', 'racketmanager' ); ?></h4>
									<div class="content">
										<p class="match"><?php echo esc_html( the_match_title() ); ?></p>
										<p class='match_day'>
											<?php
											/* translators: %d: match day */
											echo esc_html( sprintf( __( 'Match Day %d', 'racketmanager' ), get_match_day() ) );
											?>
										</p>
										<p class='match_date'><?php echo esc_html( mysql2date( 'j. F Y', the_match_date() ) ); ?>&#160;<span class='time'><?php echo esc_html( the_match_time() ); ?></span> <span class='location'><?php echo esc_html( the_match_location() ); ?></span></p>
										<p class="score">&#160;</p>
									</div>
								</div>
							</div>
						<?php } ?>

						<?php if ( has_prev_match() ) { ?>
							<div class="matches-container">
								<div class="prev_match">
									<h4 class="header"><?php esc_html_e( 'Last Match', 'racketmanager' ); ?></h4>
									<div class="content">
										<p class="match"><?php echo esc_html( the_match_title() ); ?></p>
										<p class='match_day'>
											<?php
											/* translators: %d: match day */
											echo esc_html( sprintf( __( 'Match Day %d', 'racketmanager' ), get_match_day() ) );
											?>
										</p>
										<p class='match_date'><?php echo esc_html( mysql2date( 'j. F Y', the_match_date() ) ); ?>&#160;<span class='time'><?php echo esc_html( the_match_time() ); ?></span> <span class='location'><?php echo esc_html( the_match_location() ); ?></span></p>
										<p class="score"><?php echo esc_html( the_match_score() ); ?></p>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>
