<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use InvalidArgumentException;
use function __;
use function get_current_user_id;
use function get_debug_type;
use function is_numeric;
use function is_user_logged_in;
use function method_exists;
use function mysql2date;
use function rtrim;
use function sprintf;
use function strtotime;

/**
 * Presenter responsible for building immutable Team_Match_Row_View_Model instances.
 */
final readonly class Team_Match_Row_Presenter {
	public function __construct(
		private Option_Gateway_Interface $option_gateway,
		private Fixture_Relation_Loader $relation_loader,
		private Fixture_Permission_Service $permission_service,
		private Fixture_Link_Service $link_service,
		private Club_Repository_Interface $club_repository,
		private League_Repository_Interface $league_repository
	) {}

	public function get_date_format(): string {
		$format = $this->option_gateway->get_option( 'date_format', 'Y-m-d' );
		return is_string( $format ) && '' !== $format ? $format : 'Y-m-d';
	}

	public function get_time_format(): string {
		$format = $this->option_gateway->get_option( 'time_format', 'H:i' );
		return is_string( $format ) && '' !== $format ? $format : 'H:i';
	}

	/**
	 * Build an immutable Team_Match_Row_View_Model from a domain Fixture, DTO, or adapter.
	 *
	 * @param Fixture|Fixture_Details_DTO|Fixture_Presentation_Adapter|Team_Match_Row_View_Model $match
	 * @param array $options Context options (current_club, current_team, show_header, by_date, matches_key, etc.)
	 * @return Team_Match_Row_View_Model
	 * @throws InvalidArgumentException When the input is not a supported match type.
	 */
	public function present( mixed $match, array $options = [] ): Team_Match_Row_View_Model {
		if ( $match instanceof Team_Match_Row_View_Model ) {
			return $match;
		}

		$current_club = $options['current_club'] ?? null;
		$current_team = $options['current_team'] ?? null;
		$matches_key  = $options['matches_key'] ?? null;
		$show_header  = $options['show_header'] ?? ( 'league' !== $matches_key );
		$by_date      = $options['by_date'] ?? false;
		$date_format  = $options['date_format'] ?? $this->get_date_format();
		$time_format  = $options['time_format'] ?? $this->get_time_format();
		$allow_update = $options['allow_user_update'] ?? true;
		$is_tournament = (bool) ( $options['is_tournament'] ?? false );

		$source = $this->extract( $match );
		/** @var Fixture $fixture */
		$fixture        = $source['fixture'];
		$dto            = $source['dto'];
		$league         = $source['league'];
		$link           = $source['link'];
		$home_team_id   = $fixture->get_home_team();
		$away_team_id   = $fixture->get_away_team();
		$home_name      = $source['home_name'];
		$away_name      = $source['away_name'];
		$home_withdrawn = $source['home_withdrawn'];
		$away_withdrawn = $source['away_withdrawn'];
		$home_club_id   = $source['home_club_id'];
		$away_club_id   = $source['away_club_id'];

		$id          = $fixture->get_id();
		$raw_date    = $fixture->get_date();
		$start_time  = $fixture->get_start_time();
		$match_day   = $fixture->get_match_day();
		$leg         = $fixture->get_leg();
		$winner_id   = $fixture->get_winner_id();
		$loser_id    = $fixture->get_loser_id();
		$home_points = $fixture->get_home_points();
		$away_points = $fixture->get_away_points();
		$status      = (int) $fixture->get_status();

		$league_title = $this->get_league_title( $league, $fixture );

		$final_round = $fixture->get_final();
		$final_name  = $final_round ? ( Util::get_final_name( (string) $final_round ) ?: (string) $final_round ) : null;

		$match_day_text = ( null !== $match_day && (int) $match_day > 0 ) ? sprintf( __( 'Match Day %d', 'racketmanager' ), (int) $match_day ) : null;
		$leg_text       = ( null !== $leg && (int) $leg > 0 ) ? sprintf( __( 'Leg %d', 'racketmanager' ), (int) $leg ) : null;

		$formatted_date = ! empty( $raw_date ) ? (string) mysql2date( $date_format, (string) $raw_date ) : null;

		$status_message = $status > 0 ? Util_Lookup::get_match_status( $status ) : null;

		$is_pending = empty( $winner_id ) && '-1' !== (string) $loser_id && ( null === $home_points || '' === (string) $home_points ) && ( null === $away_points || '' === (string) $away_points );

		if ( $is_pending ) {
			$score_class = 'is-not-played';
			$score_text  = ( ! empty( $start_time ) && '00:00:00' !== $start_time ) ? (string) mysql2date( $time_format, (string) $start_time ) : __( 'vs', 'racketmanager' );
		} else {
			$score_class = '';
			$score_text  = null;
		}

		$formatted_home_points = ( null !== $home_points && '' !== (string) $home_points ) ? (string) $home_points : null;
		$formatted_away_points = ( null !== $away_points && '' !== (string) $away_points ) ? (string) $away_points : null;

		$is_highlighted = false;
		$status_class   = null;
		$status_text    = null;
		$subject_id     = null;

		if ( null !== $current_club && ( (int) $current_club === (int) $home_club_id || (int) $current_club === (int) $away_club_id ) ) {
			$is_highlighted = true;
			$subject_id     = ( (int) $current_club === (int) $home_club_id ) ? $home_team_id : $away_team_id;
		} elseif ( null !== $current_team && ( (int) $current_team === (int) $home_team_id || (int) $current_team === (int) $away_team_id ) ) {
			$is_highlighted = true;
			$subject_id     = $current_team;
		}

		if ( $is_highlighted && ! $is_pending && null !== $subject_id ) {
			if ( (int) $winner_id === (int) $subject_id ) {
				$status_class = 'winner';
				$status_text  = 'W';
			} elseif ( '-1' === (string) $winner_id || '-1' === (string) $loser_id ) {
				$status_class = 'tie';
				$status_text  = 'T';
			} elseif ( ! empty( $winner_id ) ) {
				$status_class = 'loser';
				$status_text  = 'L';
			}
		}

		if ( isset( $options['is_selected'] ) ) {
			$is_selected = (bool) $options['is_selected'];
		} elseif ( ! $is_highlighted && is_user_logged_in() ) {
			$user_id     = (int) get_current_user_id();
			$is_selected = ( $user_id > 0 ) && ( $this->is_player_in_team_club( $home_team_id, $user_id ) || $this->is_player_in_team_club( $away_team_id, $user_id ) );
		} else {
			$is_selected = false;
		}

		$user_can_update = false;
		if ( $allow_update ) {
			$permission      = $source['permission'] ?? $this->permission_service->is_update_allowed( $fixture );
			$user_can_update = ! empty( $permission->user_can_update );
		}

		$update_link     = ( $user_can_update && ! empty( $link ) ) ? rtrim( (string) $link, '/' ) . '/result/' : null;
		$pencil_icon_url = RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil';

		return new Team_Match_Row_View_Model(
			id: $id,
			is_selected: $is_selected,
			show_header: $show_header,
			league_title: $league_title,
			match_link: $link ? (string) $link : null,
			final_name: $final_name,
			match_day: $match_day ? (int) $match_day : null,
			match_day_text: $match_day_text,
			leg: $leg ? (int) $leg : null,
			leg_text: $leg_text,
			show_date: ! $by_date,
			date: $raw_date ? (string) $raw_date : null,
			formatted_date: $formatted_date,
			status_message: $status_message,
			is_highlighted: $is_highlighted,
			status_class: $status_class,
			status_text: $status_text,
			is_pending: $is_pending,
			score_class: $score_class,
			score_text: $score_text,
			home_name: $home_name,
			home_is_withdrawn: $home_withdrawn,
			home_withdrawn_title: $home_withdrawn ? sprintf( __( '%s has withdrawn', 'racketmanager' ), $home_name ) : null,
			home_points: $formatted_home_points,
			away_name: $away_name,
			away_is_withdrawn: $away_withdrawn,
			away_withdrawn_title: $away_withdrawn ? sprintf( __( '%s has withdrawn', 'racketmanager' ), $away_name ) : null,
			away_points: $formatted_away_points,
			user_can_update: $user_can_update,
			update_link: $update_link,
			pencil_icon_url: $pencil_icon_url,
			is_tournament: $is_tournament,
			dto: $dto,
			match: $match
		);
	}

	/**
	 * Normalise the supported inputs into a common set of names, flags and relations.
	 *
	 * @param mixed $match
	 * @return array
	 * @throws InvalidArgumentException When the input is not a supported match type.
	 */
	private function extract( mixed $match ): array {
		if ( $match instanceof Fixture ) {
			$this->relation_loader->load( $match );
			$teams  = $match->get_teams( __( 'Bye', 'racketmanager' ), __( 'Unknown', 'racketmanager' ) );
			$home   = $teams['home'] ?? null;
			$away   = $teams['away'] ?? null;
			$league = $match->get_league();

			return array(
				'fixture'        => $match,
				'dto'            => null,
				'league'         => $league,
				'home_name'      => $this->team_name( $home, $match->get_home_team(), null ),
				'away_name'      => $this->team_name( $away, $match->get_away_team(), null ),
				'home_withdrawn' => (bool) ( $home?->is_withdrawn ?? false ),
				'away_withdrawn' => (bool) ( $away?->is_withdrawn ?? false ),
				'home_club_id'   => $home?->club?->id ?? null,
				'away_club_id'   => $away?->club?->id ?? null,
				'link'           => $this->resolve_link( $match, $league, $home, $away ),
				'permission'     => null,
			);
		}
		if ( $match instanceof Fixture_Details_DTO ) {
			$fixture = $match->fixture;
			$home    = $match->home_team;
			$away    = $match->away_team;

			return array(
				'fixture'        => $fixture,
				'dto'            => $match,
				'league'         => $match->league,
				'home_name'      => $home?->team ? (string) $home->team->get_name() : $this->team_name( null, $fixture->get_home_team(), $match->prev_home_fixture_title ?? null ),
				'away_name'      => $away?->team ? (string) $away->team->get_name() : $this->team_name( null, $fixture->get_away_team(), $match->prev_away_fixture_title ?? null ),
				'home_withdrawn' => (bool) ( $home?->is_withdrawn ?? false ),
				'away_withdrawn' => (bool) ( $away?->is_withdrawn ?? false ),
				'home_club_id'   => $home?->club ? $home->club->get_id() : null,
				'away_club_id'   => $away?->club ? $away->club->get_id() : null,
				'link'           => ! empty( $match->link ) ? $match->link : $this->resolve_link( $fixture, $match->league, $home?->team, $away?->team ),
				'permission'     => $match->is_update_allowed,
			);
		}
		if ( $match instanceof Fixture_Presentation_Adapter ) {
			$fixture = $match->fixture;
			$teams   = $match->teams;
			$home    = $teams['home'] ?? null;
			$away    = $teams['away'] ?? null;

			return array(
				'fixture'        => $fixture,
				'dto'            => null,
				'league'         => $match->league,
				'home_name'      => $this->team_name( $home, $fixture->get_home_team(), null ),
				'away_name'      => $this->team_name( $away, $fixture->get_away_team(), null ),
				'home_withdrawn' => (bool) ( $home?->is_withdrawn ?? false ),
				'away_withdrawn' => (bool) ( $away?->is_withdrawn ?? false ),
				'home_club_id'   => $home?->club?->id ?? null,
				'away_club_id'   => $away?->club?->id ?? null,
				'link'           => ! empty( $match->link ) ? $match->link : $this->resolve_link( $fixture, $match->league, $home, $away ),
				'permission'     => $match->is_update_allowed,
			);
		}

		throw new InvalidArgumentException(
			sprintf(
				'Team_Match_Row_Presenter cannot present %s; expected Fixture, Fixture_Details_DTO or Fixture_Presentation_Adapter.',
				get_debug_type( $match )
			)
		);
	}

	private function resolve_link( Fixture $fixture, ?object $league, ?object $home, ?object $away ): ?string {
		if ( null === $league ) {
			return $fixture->get_link();
		}
		return $this->link_service->get_fixture_link( $fixture, $league, $home, $away ) ?: $fixture->get_link();
	}

	private function team_name( ?object $team, int|string|null $team_id, ?string $fallback_title ): string {
		$name = $team?->name ?? $team?->title ?? null;
		if ( null !== $name && '' !== $name ) {
			return (string) $name;
		}
		if ( '-1' === (string) $team_id ) {
			return __( 'Bye', 'racketmanager' );
		}
		return $fallback_title ?? __( 'Unknown', 'racketmanager' );
	}

	private function get_league_title( ?object $league, Fixture $fixture ): string {
		$title = $this->league_name( $league );
		if ( '' !== $title ) {
			return $title;
		}
		$league_id = $fixture->get_league_id();
		if ( ! empty( $league_id ) ) {
			return $this->league_name( $this->league_repository->find_by_id( (int) $league_id ) );
		}
		return '';
	}

	private function league_name( ?object $league ): string {
		if ( null === $league ) {
			return '';
		}
		$name = method_exists( $league, 'get_name' ) ? (string) $league->get_name() : '';
		if ( '' === $name ) {
			$name = (string) ( $league->title ?? $league->name ?? '' );
		}
		return $name;
	}

	/**
	 * Present multiple matches. Tournament cards and already-built rows pass through unchanged.
	 *
	 * @param iterable $matches
	 * @param array $options
	 * @return array<Team_Match_Row_View_Model|Tournament_Match_Card_View_Model>
	 */
	public function present_many( iterable $matches, array $options = [] ): array {
		$result = [];
		foreach ( $matches as $key => $match ) {
			$result[ $key ] = $match instanceof Tournament_Match_Card_View_Model ? $match : $this->present( $match, $options );
		}
		return $result;
	}

	/**
	 * Build a Team_Matches_Group_View_Model for a list of matches.
	 *
	 * @param string|int $key
	 * @param iterable $matches
	 * @param string|null $header
	 * @param array $options
	 * @return Team_Matches_Group_View_Model
	 */
	public function present_group( string|int $key, iterable $matches, ?string $header = null, array $options = [] ): Team_Matches_Group_View_Model {
		if ( null === $header ) {
			$date_format = $options['date_format'] ?? $this->get_date_format();
			$matches_key = $options['matches_key'] ?? null;
			if ( 'match_day' === $matches_key ) {
				$header_text = sprintf( __( 'Match Day %s', 'racketmanager' ), (string) $key );
			} elseif ( ! empty( $options['by_date'] ) || ( is_string( $key ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', $key ) ) ) {
				$header_text = false !== strtotime( (string) $key ) ? (string) mysql2date( $date_format, (string) $key ) : (string) $key;
			} else {
				$header_text = (string) $key;
			}
		} else {
			$header_text = $header;
		}

		return new Team_Matches_Group_View_Model(
			key: (string) $key,
			header: $header_text,
			matches: $this->present_many( $matches, $options )
		);
	}

	private function is_player_in_team_club( int|string|null $team_id, int $user_id ): bool {
		if ( ! is_numeric( $team_id ) || (int) $team_id <= 0 || $user_id <= 0 ) {
			return false;
		}
		return $this->club_repository->is_player_in_club( [
			'team'   => (int) $team_id,
			'player' => $user_id,
			'active' => true,
		] );
	}
}
