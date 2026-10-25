<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\Queries;

use BackedEnum;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Util\Util;
use function __;
use function current_user_can;
use function get_current_user_id;
use function is_array;
use function is_iterable;
use function is_user_logged_in;
use function iterator_to_array;
use Throwable;

/**
 * Query handler for [teams] shortcode.
 */
class Get_League_Teams_Handler {
	public function __construct(
		private readonly League_Service $league_service,
		private readonly Team_Service $team_service,
		private readonly Player_Stats_Service $player_stats_service,
		private readonly Tournament_Presenter $tournament_presenter,
		private readonly Team_Details_Presenter $team_details_presenter,
		private readonly League_Query_Service $league_query_service
	) {}

	public function handle( Get_League_Teams_Query $query ): Shortcode_View_Model {
		$league = null;
		if ( ! empty( $query->league_id ) ) {
			$league = $this->league_service->get_league( $query->league_id );
		}
		if ( ! $league ) {
			return Shortcode_View_Model::for_error( __( 'League not found', 'racketmanager' ) );
		}

		$league->set_template( 'teams', $query->template );
		if ( false !== $this->league_query_service->resolve_requested_season( $league ) ) {
			$this->league_query_service->set_season( $league );
		}
		$league->set_group( $query->group ?? '' );

		if ( $query->team_id ) {
			$team_details = null;
			try {
				$team_details = $this->team_service->get_team_details( $query->team_id );
			} catch ( Throwable ) {
				$team_details = null;
			}
			$team = $team_details?->team ?? null;
			if ( ! $team ) {
				return Shortcode_View_Model::for_error( __( 'Team not found in league', 'racketmanager' ) );
			}

			$info            = $this->league_query_service->get_team_dtls( $league, $team->id );
			if ( is_object( $info ) ) {
				$team->info = $info;
			}
			$standings = $this->league_query_service->get_league_team( $league, $team->id );
			if ( is_object( $standings ) || is_array( $standings ) ) {
				$team->standings = $standings;
			}
			if ( empty( $team->standings->rank ) ) {
				return Shortcode_View_Model::for_error( __( 'Team not found in league', 'racketmanager' ) );
			}

			$team_matches = $this->league_query_service->get_matches( $league, [
				'team_id'          => $team->id,
				'match_day'        => false,
				'limit'            => 'false',
				'reset_query_args' => true,
			] );
			$competition = $league->event?->competition ?? null;
			$raw_matches = is_array( $team_matches ) ? $team_matches : ( is_iterable( $team_matches ) ? iterator_to_array( $team_matches ) : [] );
			// Same condition as templates/team-details.php, which renders match cards for player-entry competitions.
			if ( $competition?->is_player_entry ?? false ) {
				// League pages have no Tournament context: cards link to the league match page, like the legacy template.
				$team->matches = $this->tournament_presenter->present_match_cards(
					$raw_matches,
					null,
					null,
					'',
					false,
					$this->tournament_presenter->is_tournament_competition( $competition )
				);
			} else {
				$team->matches = $this->tournament_presenter->present_match_list(
					$raw_matches,
					null,
					[
						'current_team' => (int) $team->id,
						'show_header'  => false,
					]
				);
			}
			$team->players = $this->league_query_service->get_players( $league, [
				'team' => $team->id,
			] );
			$team->players = $this->player_stats_service->populate_players_stats(
				$team->players,
				$league,
				$league->current_season['name'] ?? null,
				'league'
			);
			$league->team = $team;

			$user_can_edit_team = is_user_logged_in() && (
				current_user_can( 'manage_racketmanager' ) ||
				( ! empty( $team_details->match_secretary->id ) && (int) $team_details->match_secretary->id === get_current_user_id() )
			);
			$vm = $this->team_details_presenter->present( $league, $team_details, $user_can_edit_team );

			return Shortcode_View_Model::for_template(
				'team-details',
				[
					'league'       => $league,
					'teams'        => [],
					'object'       => $league,
					'team_details' => $team_details,
					'vm'           => $vm,
				],
				false
			);
		}

		$team_args = [ 'orderby' => [ 'title' => 'ASC' ] ];
		if ( $query->group ) {
			$team_args['group'] = $query->group;
		}

		$teams        = $this->league_query_service->get_league_teams( $league, $team_args );
		$league_teams = [];
		if ( is_iterable( $teams ) ) {
			foreach ( $teams as $team ) {
				$info = $this->league_query_service->get_team_dtls( $league, $team->id );
				if ( is_object( $info ) ) {
					$team->info = $info;
				}
				$league_teams[] = $team;
			}
		}

		$comp_type = isset( $league->event->competition->type )
			? ( $league->event->competition->type instanceof BackedEnum ? $league->event->competition->type->value : (string) $league->event->competition->type )
			: 'league';
		$season_name = $league->current_season['name'] ?? '';
		$item_link   = '/' . $comp_type . '/' . Util::seo_url( (string) ( $league->title ?? '' ) ) . '/' . $season_name;

		$presented_teams = $this->team_details_presenter->present_team_rows(
			$league_teams,
			$league,
			$item_link
		);

		return Shortcode_View_Model::for_template(
			'teams-list',
			[
				'league'       => $league,
				'team_rows'    => $presented_teams,
				'object'       => $league,
				'team_details' => [],
			],
			false
		);
	}
}
