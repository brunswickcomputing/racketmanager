<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;

/**
 * Service for managing competition seasons and their lifecycle states.
 */
class Competition_Season_Service {
	public function __construct(
		private League_Team_Repository_Interface $league_team_repository,
		private Club_Repository_Interface $club_repository,
		private Event_Repository_Interface $event_repository,
	) {
	}

	/**
	 * Resolves the request-specific current season of a competition and applies it to the entity.
	 *
	 * Precedence: a forced season, then the request (`?season=`, `?season_<competition id>=`, the `season`
	 * query var), then the given season, then the domain fallback (active or latest season). Unknown
	 * request seasons are ignored. The venue club is resolved once and exposed as `venue_name`.
	 *
	 * Repositories return request-neutral competitions; call this at the application boundary only.
	 *
	 * @param Competition $competition The competition entity.
	 * @param string      $season      Optional specific season name.
	 * @param bool        $force       Whether the given season takes precedence over the request.
	 * @return array The resolved season data.
	 */
	public function resolve_current_season( Competition $competition, string $season = '', bool $force = false ): array {
		$season_name = ( '' !== $season && $force ) ? $season : '';
		if ( '' === $season_name ) {
			$request_season = $this->get_request_season( $competition );
			if ( null !== $request_season && null !== $competition->seasons->get( $request_season ) ) {
				$season_name = $request_season;
			}
		}
		if ( '' === $season_name ) {
			$season_name = $season;
		}

		$data               = $competition->resolve_season_data( $season_name );
		$data['venue_name'] = $this->get_venue_name( $data['venue'] ?? null );

		$competition->apply_current_season( $data );

		return $data;
	}

	/**
	 * Season selected by the current request, if any.
	 */
	private function get_request_season( Competition $competition ): ?string {
		global $wp;

		$competition_key = 'season_' . $competition->get_id();
		if ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw = $_GET['season']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( ! empty( $_GET[ $competition_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$raw = $_GET[ $competition_key ]; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( ! empty( $wp->query_vars['season'] ) ) {
			return (string) $wp->query_vars['season'];
		} else {
			return null;
		}

		return is_scalar( $raw ) ? htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $raw ) ) ) : null;
	}

	/**
	 * Shortcode of the venue club, or null when there is no venue or the club does not exist.
	 */
	private function get_venue_name( mixed $venue ): ?string {
		if ( empty( $venue ) ) {
			return null;
		}
		$venue_club = $this->club_repository->find_by_id( (int) $venue );

		return $venue_club?->get_shortcode();
	}

	/**
	 * Calculates the current phase of a competition based on season dates.
	 *
	 * @param array $season_data The hydrated season data.
	 * @return string The phase name.
	 */
	public function calculate_phase( array $season_data ): string {
		return Competition::calculate_phase( $season_data );
	}

	/**
	 * Cascades a new season to all events within a competition.
	 *
	 * @param Competition $competition The competition entity.
	 * @param object      $season      The new season data object.
	 * @return void
	 */
	public function cascade_season_to_events( Competition $competition, object $season ): void {
		$events = $this->event_repository->find_by_competition_id( (int) $competition->get_id() );

		if ( empty( $events ) ) {
			return;
		}

		$event_season = [
			'name'           => $season->name,
			'home_away'      => $season->home_away ?? null,
			'num_match_days' => $season->num_match_days ?? null,
			'match_dates'    => $season->match_dates ?? null,
		];

		foreach ( $events as $event ) {
			if ( method_exists( $event, 'add_season' ) ) {
				$event->add_season( $event_season, $this->league_team_repository );
			}
		}
	}
}
