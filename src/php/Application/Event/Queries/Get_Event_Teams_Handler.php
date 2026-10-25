<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use BackedEnum;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util;
use function __;
use function is_array;

class Get_Event_Teams_Handler {
	public function __construct(
		private readonly Competition_Service $competition_service,
		private readonly Player_Stats_Service $player_stats_service,
		private readonly Tournament_Presenter $tournament_presenter,
		private readonly ?Team_Service $team_service,
		private readonly Team_Details_Presenter $team_details_presenter,
		private readonly Competition_Shortcode_Presenter $competition_presenter,
		private readonly Event_Query_Service $event_query_service
	) {}

	public function handle( Get_Event_Teams_Query $query ): Shortcode_View_Model {
		$identifier = $query->event_id ?? $query->event_name;
		if ( null === $identifier || '' === $identifier ) {
			return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
		}

		try {
			$event = $this->competition_service->get_event_by_id( $identifier );
		} catch ( Competition_Not_Found_Exception ) {
			return Shortcode_View_Model::for_error( __( 'Event not found', 'racketmanager' ) );
		}

		$this->event_query_service->set_season( $event, $query->season );
		$current_season = $event->current_season['name'] ?? '';

		if ( ! Event_Allowlist_Guard::is_event_allowed( $event, $current_season ?: $query->season, $this->competition_service ) ) {
			return Shortcode_View_Model::for_error( __( 'Event not found for competition', 'racketmanager' ) );
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
				if ( is_array( $team->matches ) ) {
					// templates/team-details.php renders match cards for player-entry competitions.
					$team->matches = ( $event->competition->is_player_entry ?? false )
						? $this->tournament_presenter->present_match_cards( $team->matches, null, null, '', false, $this->tournament_presenter->is_tournament_competition( $event->competition ) )
						: $this->tournament_presenter->present_match_list(
							$team->matches,
							$this->tournament_presenter->is_tournament_competition( $event->competition ?? null ),
							[
								'current_team' => (int) $team->id,
								'show_header'  => false,
							]
						);
				}
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

		$team_rows = [];
		if ( null === $team && 'list' === $template ) {
			$comp_type = isset( $event->competition->type )
				? ( $event->competition->type instanceof BackedEnum ? $event->competition->type->value : (string) $event->competition->type )
				: 'event';
			$item_link = '/' . $comp_type . '/' . Util::seo_url( (string) $event->name ) . '/' . $current_season;
			$team_rows = $this->team_details_presenter->present_team_rows(
				is_array( $event->teams ) ? $event->teams : [],
				$event,
				$item_link
			);
		}

		$tab        = 'teams';
		$filename   = empty( $template ) ? 'teams' : 'teams-' . $template;
		$team_links = $this->competition_presenter->present_event_teams_links( $event, $event->teams ?? [] );

		return Shortcode_View_Model::for_template(
			$filename,
			[
				'event'       => $event,
				'tab'         => $tab,
				'curr_season' => $current_season,
				'team_rows'   => $team_rows,
				'team_links'  => $team_links,
			],
			'event'
		);
	}
}
