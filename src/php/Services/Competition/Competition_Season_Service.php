<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Util\Util;
use stdClass;

/**
 * Service for managing competition seasons and their lifecycle states.
 */
class Competition_Season_Service {
	/**
	 * Resolves and sets the current season for a competition.
	 *
	 * @param Competition $competition The competition entity.
	 * @param string      $season      Optional specific season name.
	 * @param bool        $force       Whether to force use of the provided season.
	 * @return array The resolved season data.
	 */
	public function resolve_current_season( Competition $competition, string $season = '', bool $force = false ): array {
		global $wp;

		$seasons = $competition->seasons;
		$data    = null;

		// 1. Resolve which season to use
		if ( ! empty( $season ) && $force ) {
			$data = $seasons->get( $season );
		} elseif ( ! empty( $_GET['season'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['season'] ) ) );
			$data = $seasons->get( $key ) ?? false;
		} elseif ( isset( $_GET[ 'season_' . $competition->get_id() ] ) && ! empty( $_GET[ 'season_' . $competition->get_id() ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET[ 'season_' . $competition->get_id() ] ) ) );
			$data = $seasons->get( $key ) ?? false;
		} elseif ( isset( $wp->query_vars['season'] ) ) {
			$key = $wp->query_vars['season'];
			$data = $seasons->get( $key ) ?? false;
		} elseif ( ! empty( $season ) ) {
			$data = $seasons->get( $season );
		}

		// 2. Fallback to active or latest season if not resolved
		if ( ! isset( $data ) || false === $data ) {
			$today = gmdate( 'Y-m-d' );
			foreach ( $seasons->reverse() as $season_item ) {
				$date_active = empty( $season_item['date_closing'] ) ? null : Util::amend_date( $season_item['date_closing'], 7 );
				if ( ! empty( $date_active ) && $date_active <= $today ) {
					$data = $season_item;
					break;
				}
			}
		}

		if ( empty( $data ) ) {
			$data = $seasons->latest() ?? [];
		}

		// 3. Hydrate basic dates if missing
		$count_match_dates = isset( $data['match_dates'] ) && is_array( $data['match_dates'] ) ? count( $data['match_dates'] ) : 0;
		if ( empty( $data['date_end'] ) && $count_match_dates >= 2 ) {
			$data['date_end'] = end( $data['match_dates'] );
		}
		if ( empty( $data['date_start'] ) && $count_match_dates >= 2 ) {
			$data['date_start'] = $data['match_dates'][0];
		}

		// 4. Hydrate venue name
		$data['venue_name'] = null;
		if ( ! empty( $data['venue'] ) && function_exists( 'Racketmanager\get_club' ) ) {
			$venue_club = \Racketmanager\get_club( (int) $data['venue'] );
			if ( $venue_club ) {
				$data['venue_name'] = $venue_club->shortcode;
			}
		}

		return $data;
	}

	/**
	 * Calculates the current phase of a competition based on season dates.
	 *
	 * @param array $season_data The hydrated season data.
	 * @return string The phase name.
	 */
	public function calculate_phase( array $season_data ): string {
		$today = gmdate( 'Y-m-d' );
		
		if ( ! empty( $season_data['date_end'] ) && $today > $season_data['date_end'] ) {
			return 'end';
		}
		if ( ! empty( $season_data['date_start'] ) && $today >= $season_data['date_start'] ) {
			return 'start';
		}
		if ( ! empty( $season_data['date_closing'] ) && $today > $season_data['date_closing'] ) {
			return 'close';
		}
		if ( ! empty( $season_data['date_open'] ) ) {
			if ( $today >= $season_data['date_open'] ) {
				return 'open';
			}
			return 'pending';
		}
		
		return 'complete';
	}

	/**
	 * Cascades a new season to all events within a competition.
	 *
	 * @param Competition $competition The competition entity.
	 * @param object      $season      The new season data object.
	 * @return void
	 */
	public function cascade_season_to_events( Competition $competition, object $season ): void {
		$events = $competition->get_events();
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
				$event->add_season( $event_season );
			}
		}
	}
}
