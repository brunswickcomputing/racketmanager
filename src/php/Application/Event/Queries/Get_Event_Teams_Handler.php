<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use Racketmanager\Application\Event\DTOs\Event_Teams_Result;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Team_Service;
use function __;
use function is_array;

class Get_Event_Teams_Handler {
	public function __construct(
		private readonly Competition_Service $competition_service,
		private readonly Player_Stats_Service $player_stats_service,
		private readonly ?Team_Service $team_service,
		private readonly Event_Query_Service $event_query_service
	) {}

	public function handle( Get_Event_Teams_Query $query ): Event_Teams_Result {
		$identifier = $query->event_id ?? $query->event_name;
		if ( null === $identifier || '' === $identifier ) {
			return Event_Teams_Result::error( __( 'Event not found', 'racketmanager' ) );
		}

		try {
			$event = $this->competition_service->get_event_by_id( $identifier );
		} catch ( Competition_Not_Found_Exception ) {
			return Event_Teams_Result::error( __( 'Event not found', 'racketmanager' ) );
		}

		$this->event_query_service->set_season( $event, $query->season );
		$current_season = $event->current_season['name'] ?? '';

		if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $current_season ?: $query->season, $this->competition_service ) ) {
			return Event_Teams_Result::error( __( 'Event not found for competition', 'racketmanager' ) );
		}

		$event->teams   = $this->event_query_service->get_teams( $event, [
			'season'  => $current_season,
			'orderby' => [ 'name' => 'ASC' ],
		] );

		$team_identifier = $query->team;
		$team            = null;

		if ( ! empty( $team_identifier ) && $this->team_service ) {
			try {
				$team = $this->team_service->get_team_by_id( $team_identifier );
			} catch ( \Throwable ) {
				$team = null;
			}
			if ( $team ) {
				$team->info    = $this->event_query_service->get_team_info( $event, $team->id );
				$team->matches = $this->event_query_service->get_matches( $event, [
					'team_id'          => $team->id,
					'match_day'        => false,
					'limit'            => 'false',
					'reset_query_args' => true,
					'season'           => $current_season,
					'orderby'          => [ 'date' => 'ASC' ],
				] );
				$team->players = $this->event_query_service->get_players( $event, [
					'team' => $team->id,
				] );
				$team->players = $this->player_stats_service->populate_players_stats( $team->players, $event, $current_season, 'event' );
				$event->team   = $team;
			}
		}

		$template = $query->template;
		if ( null === $team && ! empty( $event->competition->is_championship ) && empty( $template ) ) {
			$template = 'list';
		}

		$filename = empty( $template ) ? 'teams' : 'teams-' . $template;

		return new Event_Teams_Result(
			event: $event,
			team: $team,
			teams: is_array( $event->teams ) ? $event->teams : [],
			curr_season: $current_season,
			template: $filename
		);
	}
}
