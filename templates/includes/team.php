<?php
/**
 * Template page for team
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $club */
/** @var object $team */
if ( isset( $league ) ) {
	$is_team_entry   = $league->event->competition->is_team_entry;
	$is_player_entry = $league->event->competition->is_player_entry;
	$object          = $league;
	$object_type     = 'league';
    $event_id        = $league->event->id;
} elseif ( isset( $event ) ) {
	$is_team_entry   = $event->competition->is_team_entry;
	$is_player_entry = $event->competition->is_player_entry;
	$object          = $event;
	$object_type     = 'event';
    $event_id        = $event->id;
} else {
    $object          = null;
    $object_type     = null;
    $is_team_entry   = null;
    $is_player_entry = null;
    $event_id        = null;
}
if ( empty( $item_link ) ) {
    $team_link = null;
	$onclick   = 'onclick=Racketmanager.teamEditModal(event,' . $team->id . ',' . $event_id . ')';
} else {
	$team_link = $item_link . '/team/' . esc_attr( seo_url( $team->title ) ) . '/';
	$onclick   = 'onclick=Racketmanager.' . $object_type . 'TabDataLink(event,' . $object->id . ',' . $object->current_season['name'] . ",'" . $team_link . "'," . $team->id . ",'teams')";
}
if ( isset( $team->info ) ) {
	$team->contactno    = $team->info->contactno ?? null;
	$team->contactemail = $team->info->contactemail ?? null;
	$team->match_time   = $team->info->match_time ?? null;
	$team->match_day    = $team->info->match_day ?? null;
	$team->captain      = $team->info->captain ?? null;
}
if ( empty( $event_id ) ) {
    $suffix = null;
} else {
    $suffix = '-' . $event_id . '-' . $team->id;
}
?>
<div class="">
	<div class="media">
		<div class="media__wrapper">
			<div class="media__img">
				<span class="profile-icon">
					<span class="profile-icon__abbr">
						<?php
						$words    = explode( ' ', $team->title );
						$initials = null;
						foreach ( $words as $w ) {
							$initials .= $w[0];
						}
						echo esc_html( $initials );
						?>
					</span>
				</span>
			</div>
			<div class="media__content">
				<h4 class="media__title">
					<a class="nav--link" href="<?php echo esc_attr( $team_link ); ?>" <?php echo esc_attr( $onclick ); ?>>
						<span><?php echo esc_html( $team->title ); ?></span>
					</a>
                </h4>
			</div>
			<div class="media__aside">
				<?php
				if ( ! empty( $league ) && ! $object->is_championship ) {
					?>
					<a href="/index.php?league_id=<?php echo esc_html( $league->id ); ?>&team_id=<?php echo esc_html( $team->id ); ?>&team=<?php echo esc_html( $team->title ); ?>&season=<?php echo esc_html( $league->current_season['name'] ); ?>&racketmanager_export=calendar" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
						<i class="racketmanager-svg-icon">
							<?php racketmanager_the_svg( 'icon-calendar' ); ?>
						</i>
						<span class="nav-link__value text-uppercase">
							<?php esc_html_e( 'Calendar', 'racketmanager' ); ?>
						</span>
					</a>
					<?php
				}
				?>
				<?php
				$favourite_type = 'team';
				$favourite_id   = $team->id;
				require_once RACKETMANAGER_PATH . 'templates/includes/favourite-button.php';
				?>
			</div>
		</div>
	</div>

	<ul class="list list--flex list--indent">
		<?php
		if ( $is_team_entry ) {
			?>
			<li class="list__item">
				<span class="nav--link">
					<svg width="16" height="16" class="icon-team">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons-extra.svg#icon-team' ); ?>"></use>
					</svg>
					<span class="nav-link__value" id="captain-name<?php echo esc_attr( $suffix ); ?>">
						<?php echo esc_html( $team->captain ); ?>
					</span>
				</span>
			</li>
			<?php
		}
		?>
		<?php
		if ( is_user_logged_in() ) {
			if ( $is_player_entry ) {
				foreach ( $team->info->players as $team_player ) {
					?>
					<h4 class="subheading">
						<?php echo esc_html( $team_player->display_name ); ?>
					</h4>
					<?php
					if ( ! empty( $team_player->contactno ) ) {
						?>
						<li class="list__item">
							<a href="tel:<?php echo esc_html( $team_player->contactno ); ?>" class="nav--link" rel="nofollow">
								<svg width="16" height="16" class="">
									<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
								</svg>
								<span class="nav--link">
									<span class="nav-link__value">
										<?php echo esc_html( $team_player->contactno ); ?>
									</span>
								</span>
							</a>
						</li>
						<?php
					}
					if ( ! empty( $team_player->email ) ) {
						?>
						<li class="list__item">
							<a href="mailto:<?php echo esc_html( $team_player->email ); ?>" class="nav--link">
								<svg width="16" height="16" class="">
									<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
								</svg>
								<span class="nav--link">
									<span class="nav-link__value">
										<?php echo esc_html( $team_player->email ); ?>
									</span>
								</span>
							</a>
						</li>
						<?php
					}
				}
			} else {
				if ( ! empty( $team->contactno ) ) {
					?>
					<li class="list__item">
						<a href="tel:<?php echo esc_html( $team->contactno ); ?>" class="nav--link" rel="nofollow">
							<svg width="16" height="16" class="">
								<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
							</svg>
							<span class="nav--link">
								<span class="nav-link__value" id="captain-contact-no<?php echo esc_attr( $suffix ); ?>">
									<?php echo esc_html( $team->contactno ); ?>
								</span>
							</span>
						</a>
					</li>
					<?php
				}
				if ( ! empty( $team->contactemail ) ) {
					?>
					<li class="list__item">
						<a href="mailto:<?php echo esc_html( $team->contactemail ); ?>" class="nav--link">
							<svg width="16" height="16" class="">
								<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
							</svg>
							<span class="nav--link">
								<span class="nav-link__value" id="captain-contact-email<?php echo esc_attr( $suffix ); ?>">
									<?php echo esc_html( $team->contactemail ); ?>
								</span>
							</span>
						</a>
					</li>
					<?php
				}
			}
		}
		?>
		<?php
		if ( ! empty( $team->match_day ) ) {
			?>
			<li class="list__item">
				<span class="nav--link">
					<svg width="16" height="16" class="icon-team">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
					</svg>
					<span class="nav-link__value">
						<?php echo esc_html( $team->match_day ); ?>
					</span>
				</span>
			</li>
			<?php
		}
		?>
		<?php
		if ( ! empty( $team->match_time ) && '00:00:00' !== $team->match_time ) {
			?>
			<li class="list__item">
				<span class="nav--link">
					<svg width="16" height="16" class="icon-team">
						<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#clock-fill' ); ?>"></use>
					</svg>
					<span class="nav-link__value">
						<?php echo esc_html( mysql2date( 'H:i', $team->match_time ) ); ?>
					</span>
				</span>
			</li>
			<?php
		}
		?>
	</ul>
</div>
