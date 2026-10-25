<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Closure;
use LogicException;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Infrastructure\Wordpress\Request\Competition_Request;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;
use function add_query_arg;
use function is_admin;
use function number_format_i18n;
use function paginate_links;
use function sprintf;
use function Racketmanager\show_score;

/**
 * Application service that runs repository-backed League reads and writes.
 *
 * The League aggregate no longer stores repositories. This service supplies
 * collaborators to repository-backed reads and owns persistence for mutations.
 */
class League_Query_Service {
	private League_Repository_Interface $league_repository;
	private Fixture_Repository_Interface|Closure|null $fixture_repository;
	private Rubber_Repository_Interface|Closure|null $rubber_repository;
	private Team_Repository_Interface|Closure|null $team_repository;
	private League_Team_Repository_Interface|Closure|null $league_team_repository;
	private Club_Repository_Interface|Closure|null $club_repository;
	private Player_Repository_Interface|Closure|null $player_repository;

	public function __construct(
		League_Repository_Interface $league_repository,
		Fixture_Repository_Interface|Closure|null $fixture_repository = null,
		Rubber_Repository_Interface|Closure|null $rubber_repository = null,
		Team_Repository_Interface|Closure|null $team_repository = null,
		League_Team_Repository_Interface|Closure|null $league_team_repository = null,
		Club_Repository_Interface|Closure|null $club_repository = null,
		Player_Repository_Interface|Closure|null $player_repository = null
	) {
		$this->league_repository      = $league_repository;
		$this->fixture_repository     = $fixture_repository;
		$this->rubber_repository      = $rubber_repository;
		$this->team_repository        = $team_repository;
		$this->league_team_repository = $league_team_repository;
		$this->club_repository        = $club_repository;
		$this->player_repository      = $player_repository;
	}

	public function get_league_repository(): League_Repository_Interface {
		return $this->league_repository;
	}

	public function get_fixture_repository(): Fixture_Repository_Interface {
		if ( $this->fixture_repository instanceof Closure ) {
			$this->fixture_repository = ( $this->fixture_repository )();
		}
		if ( ! $this->fixture_repository instanceof Fixture_Repository_Interface ) {
			throw new LogicException( 'Fixture_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->fixture_repository;
	}

	public function get_rubber_repository(): Rubber_Repository_Interface {
		if ( $this->rubber_repository instanceof Closure ) {
			$this->rubber_repository = ( $this->rubber_repository )();
		}
		if ( ! $this->rubber_repository instanceof Rubber_Repository_Interface ) {
			throw new LogicException( 'Rubber_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->rubber_repository;
	}

	public function get_team_repository(): Team_Repository_Interface {
		if ( $this->team_repository instanceof Closure ) {
			$this->team_repository = ( $this->team_repository )();
		}
		if ( ! $this->team_repository instanceof Team_Repository_Interface ) {
			throw new LogicException( 'Team_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->team_repository;
	}

	public function get_league_team_repository(): League_Team_Repository_Interface {
		if ( $this->league_team_repository instanceof Closure ) {
			$this->league_team_repository = ( $this->league_team_repository )();
		}
		if ( ! $this->league_team_repository instanceof League_Team_Repository_Interface ) {
			throw new LogicException( 'League_Team_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->league_team_repository;
	}

	public function get_club_repository(): Club_Repository_Interface {
		if ( $this->club_repository instanceof Closure ) {
			$this->club_repository = ( $this->club_repository )();
		}
		if ( ! $this->club_repository instanceof Club_Repository_Interface ) {
			throw new LogicException( 'Club_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->club_repository;
	}

	public function get_player_repository(): Player_Repository_Interface {
		if ( $this->player_repository instanceof Closure ) {
			$this->player_repository = ( $this->player_repository )();
		}
		if ( ! $this->player_repository instanceof Player_Repository_Interface ) {
			throw new LogicException( 'Player_Repository_Interface is required by League_Query_Service but was not configured.' );
		}
		return $this->player_repository;
	}

	/**
	 * Get league teams, querying the repository and hydrating team objects.
	 *
	 * @param object $league League object.
	 * @param array $query_args Query arguments.
	 * @return array|int Database results
	 */
	public function get_league_teams( object $league, array $query_args = array() ): array|int {
		if ( ! $league instanceof League || empty( $league->id ) ) {
			return ( $query_args['count'] ?? false ) ? 0 : array();
		}

		$league_team_repository = $this->optional_league_team_repository();
		$old_query_args         = $league->get_team_query_args();
		$success                = false;

		try {
			// set query args.
			foreach ( $query_args as $key => $value ) {
				$league->set_team_query_arg( $key, $value );
			}
			$team_query_args  = $league->get_team_query_args();
			$reset_query_args = $team_query_args['reset_query_args'] ?? false;
			$count            = $team_query_args['count'] ?? false;

			$repo_args = $team_query_args;
			$season    = $repo_args['season'] ?? '';
			if ( empty( $season ) ) {
				$repo_args['season'] = $league->current_season['name'] ?? '';
			} elseif ( 'any' === $season ) {
				$repo_args['season'] = 'any';
			} elseif ( $league->season_exists( htmlspecialchars( (string) $season ) ) ) {
				$repo_args['season'] = htmlspecialchars( (string) $season );
			} else {
				unset( $repo_args['season'] );
				$repo_args['skip_season_filter'] = true;
			}
			$teams = $this->league_repository->query_league_teams( (int) $league->id, $repo_args, (bool) $count );

			if ( ! $count ) {
				$teams      = is_array( $teams ) ? $teams : array();
				$class      = array();
				$team_index = array();
				foreach ( $teams as $i => $team ) {
					if ( $league_team_repository ) {
						$team = $league_team_repository->find_by_id( (int) $team->table_id );
					} elseif ( ! $team instanceof League_Team ) {
						throw new \InvalidArgumentException( 'League_Team_Repository_Interface collaborator is required to hydrate league teams.' );
					}
					if ( ! $team ) {
						continue;
					}
					$class[]                = ( 'alternate' === $class ) ? '' : 'alternate';
					$team->custom           = isset( $team->custom ) ? stripslashes_deep( maybe_unserialize( $team->custom ) ) : array();
					$team->roster           = isset( $team->roster ) ? maybe_unserialize( $team->roster ) : null;
					$team->title            = htmlspecialchars( stripslashes( (string) ( $team->title ?? '' ) ), ENT_QUOTES );
					$team->class            = implode( ' ', $class );
					$team->points_formatted = array(
						'primary'   => sprintf( $league->point_format ?? '%d:%d', $team->points_plus ?? 0, $team->points_minus ?? 0 ),
						'secondary' => sprintf( $league->point_2_format ?? '%d:%d', $team->points_2_plus ?? 0, $team->points_2_minus ?? 0 ),
					);
					if ( ! empty( $team->players ) && ! empty( $league->event->type ) ) {
						$type        = substr( $league->event->type, 1, 1 );
						$team_rating = 0.0;
						foreach ( $team->players as $player ) {
							$rating = $player->wtn[ $type ] ?? null;
							if ( is_numeric( $rating ) ) {
								$team_rating += (float) $rating;
							}
						}
						$team->rating = (float) $team_rating;
					}

					$team_index[ $team->id ] = $i;
					$teams[ $i ]             = $team;
				}

				$league->teams      = $teams;
				$league->team_index = $team_index;

				$this->set_num_teams( $league, false );

				$get_details = $query_args['get_details'] ?? ( $team_query_args['get_details'] ?? false );
				if ( $get_details ) {
					foreach ( $teams as $team ) {
						if ( isset( $team->id ) ) {
							$team_dtls = $this->get_team_dtls( $league, (int) $team->id );
							if ( is_object( $team_dtls ) ) {
								$team->match_day     = $team_dtls->match_day ?? null;
								$team->match_time    = $team_dtls->match_time ?? null;
								$team->captain_id    = isset( $team_dtls->captain_id ) ? (int) $team_dtls->captain_id : null;
								$team->captain_name  = $team_dtls->captain ?? null;
								$team->contactno     = isset( $team_dtls->contactno ) && '' !== $team_dtls->contactno ? (string) $team_dtls->contactno : null;
								$team->contactemail  = isset( $team_dtls->contactemail ) && '' !== $team_dtls->contactemail ? (string) $team_dtls->contactemail : null;
								$team->league_status = isset( $team_dtls->league_status ) && '' !== $team_dtls->league_status ? (string) $team_dtls->league_status : null;
							}
						}
					}
				}
			}

			// reset team query args.
			if ( true === $reset_query_args ) {
				foreach ( $old_query_args as $key => $query_arg ) {
					$league->set_team_query_arg( $key, $query_arg );
				}

				$league->set_team_query_arg( 'reset_query_args', false );
			}

			$success = true;
			return $teams;
		} finally {
			if ( ! $success ) {
				$league->restore_team_query_args( $old_query_args );
			}
		}
	}

	public function get_team_dtls( object $league, int $team_id, ?string $season = null ): object|false {
		if ( ! $league instanceof League ) {
			return $league->get_team_dtls( $team_id, $season );
		}
		if ( empty( $season ) ) {
			$season = $league->current_season['name'] ?? '';
		}
		if ( -1 === $team_id ) {
			$team               = (object) array(
				'id'     => -1,
				'title'  => 'Bye',
				'player' => array(),
			);
			$team->captain      = '';
			$team->contactno    = '';
			$team->contactemail = '';
			$team->club_id      = '';
			$team->stadium      = '';
			$team->roster       = '';
			return $team;
		}

		$league_id = isset( $league->id ) ? (int) $league->id : 0;
		$team      = $league_id > 0 ? $this->league_repository->get_team_details( $league_id, $team_id, $season ) : null;

		if ( ! isset( $team ) || ! $team ) {
			return false;
		}
		if ( str_contains( (string) ( $team->title ?? '' ), '_' ) ) {
			$team_name = Util::generate_team_name( $team->title );
			if ( ! empty( $team_name ) ) {
				$team->title = $team_name;
			}
		} else {
			$team->title = htmlspecialchars( stripslashes( (string) ( $team->title ?? '' ) ), ENT_QUOTES );
		}
		$captain_field = $team->captain ?? null;
		$captain       = $this->find_player( $this->optional_player_repository(), $captain_field );
		if ( $captain ) {
			$team->captain_id   = $captain_field;
			$team->captain      = $captain->display_name;
			$team->contactno    = $captain->contactno;
			$team->contactemail = $captain->user_email;
		} else {
			$team->captain_id   = 0;
			$team->captain      = '';
			$team->contactno    = '';
			$team->contactemail = '';
		}
		$club_repository = $this->optional_club_repository();
		if ( ! empty( $team->club_id ) ) {
			$team->club_id = stripslashes( (string) $team->club_id );
			$team->club    = $club_repository ? $club_repository->find_by_id( (int) $team->club_id ) : null;
		} else {
			$team->club_id = null;
			$team->club    = null;
		}
		$team->stadium          = isset( $team->stadium ) ? stripslashes( (string) $team->stadium ) : '';
		$team->roster           = isset( $team->roster ) ? maybe_unserialize( $team->roster ) : null;
		$team->points_formatted = array(
			'primary'   => sprintf( $league->point_format ?? '%d:%d', $team->points_plus ?? 0, $team->points_minus ?? 0 ),
			'secondary' => sprintf( $league->point_2_format ?? '%d:%d', $team->points_2_plus ?? 0, $team->points_2_minus ?? 0 ),
		);
		if ( 'P' === ( $team->team_type ?? '' ) && null !== $team->roster && is_iterable( $team->roster ) ) {
			$player_repo   = $this->optional_player_repository();
			$team->players = array();
			$i             = 1;
			foreach ( $team->roster as $player ) {
				$team_player = $this->find_player( $player_repo, $player );
				if ( $team_player ) {
					$team->players[ $i ]   = $team_player;
					$team->player[ $i ]    = $team_player->get_fullname() ?? '';
					$team->player_id[ $i ] = $player;
				}
				++$i;
			}
		}
		$team->is_withdrawn = false;
		if ( isset( $team->league_status ) && 'W' === $team->league_status ) {
			$team->is_withdrawn = true;
		}
		return $team;
	}

	public function get_league_team( object $league, int $team_id ): object|false {
		if ( ! $league instanceof League ) {
			return $league->get_league_team( $team_id );
		}
		if ( isset( $league->team_index[ $team_id ] ) && isset( $league->teams[ $league->team_index[ $team_id ] ] ) ) {
			return $league->teams[ $league->team_index[ $team_id ] ];
		}
		return $this->get_team_dtls( $league, $team_id );
	}

	/**
	 * Get league matches, supplying the repositories the League read needs.
	 *
	 * Request-derived paging is resolved here (the outer boundary) before
	 * delegating to League::get_matches().
	 */

	public function get_players( object $league, array $args = array() ): array|int {
		if ( ! $league instanceof League ) {
			return $league->get_players( $args );
		}
		$defaults = array(
			'offset'  => 0,
			'limit'   => 99999999,
			'season'  => false,
			'orderby' => array(),
			'club'    => false,
			'team'    => false,
			'count'   => false,
			'group'   => false,
		);
		$args     = array_merge( $defaults, $args );
		$count    = $args['count'];
		$group    = $args['group'];

		if ( ! $args['season'] ) {
			$args['season'] = $league->current_season['name'] ?? '';
		}

		if ( empty( $league->id ) ) {
			return $count ? 0 : array();
		}

		if ( $count ) {
			return $this->league_repository->query_players( (int) $league->id, $args, true );
		}

		$players           = $this->league_repository->query_players( (int) $league->id, $args, false );
		$player_repository = $this->optional_player_repository();
		$league_players    = array();
		foreach ( (array) $players as $player ) {
			$player = $this->find_player( $player_repository, $player->player_id ?? null );
			if ( ! $player || $player->system_record ) {
				continue;
			}
			$league_players[] = $player;
		}
		if ( $group ) {
			$league->players = array();
			foreach ( $league_players as $player ) {
				$key = strtoupper( substr( $player->get_fullname(), 0, 1 ) );
				if ( false === array_key_exists( $key, $league->players ) ) {
					$league->players[ $key ] = array();
				}
				// now just add the row data.
				$league->players[ $key ][] = $player;
			}
		} else {
			$league->players = $league_players;
		}

		return $league->players;
	}

	public function get_rank( object $league, int $team_id, string $season ): ?int {
		if ( ! $league instanceof League ) {
			return $league->get_rank( $team_id, $season );
		}
		return $this->league_repository->get_rank( (int) $league->id, $season, $team_id );
	}

	public function get_status( object $league, int $team_id, string $season ): ?string {
		if ( ! $league instanceof League ) {
			return $league->get_status( $team_id, $season );
		}
		return $this->league_repository->get_status( (int) $league->id, $season, $team_id );
	}

	public function get_num_teams( object $league, ?string $status = null, bool $latest = false ): int {
		if ( ! $league instanceof League ) {
			return $league->get_num_teams( $status, $latest );
		}
		$args = array(
			'count'            => true,
			'season'           => $league->current_season['name'] ?? '',
			'reset_query_args' => true,
			'club'             => false,
		);
		if ( $status ) {
			$args['active'] = true;
		}
		if ( $latest ) {
			$args['cache'] = false;
		}
		return (int) $this->league_repository->query_league_teams( (int) $league->id, $args, true );
	}

	public function set_num_teams( object $league, bool $total = false ): void {
		if ( ! $league instanceof League ) {
			$league->set_num_teams( $total );
			return;
		}
		$league->current_page_teams = $this->resolve_requested_page( $league, 'teams' );
		if ( true === $total ) {
			$league->num_teams_total = (int) $this->league_repository->query_league_teams(
				(int) $league->id,
				array(
					'count'            => true,
					'season'           => $league->current_season['name'] ?? '',
					'reset_query_args' => true,
					'club'             => false,
				),
				true
			);
		} else {
			$league->num_teams        = $league->num_teams_total;
			$league->pagination_teams = $this->get_page_links( $league, 'teams' );
		}
	}

	public function set_num_matches( object $league, bool $total = false ): void {
		if ( ! $league instanceof League ) {
			if ( method_exists( $league, 'set_num_matches' ) ) {
				$league->set_num_matches( $total );
			}
			return;
		}
		$league->current_page_matches = $this->resolve_requested_page( $league );
		$match_args                   = array();
		$match_args['count']          = true;
		$match_args['season']         = null;
		$match_args['reset_query_args'] = true;
		if ( true !== $total ) {
			$match_args['limit'] = 0;
		}
		$num_matches = (int) $this->get_matches( $league, $match_args );
		if ( $total ) {
			$league->num_matches_total = $num_matches;
		} else {
			$league->num_matches        = $num_matches;
			$league->pagination_matches = $this->get_page_links( $league );
		}
	}

	public function get_crosstable_field( League $league, int $team_id, int $opponent_id ): string {
		if ( $team_id === $opponent_id ) {
			return '&nbsp;';
		}
		$matches = $this->get_matches(
			$league,
			array(
				'home_team'        => $team_id,
				'away_team'        => $opponent_id,
				'match_day'        => -1,
				'limit'            => false,
				'reset_query_args' => true,
			)
		);
		$matches = is_array( $matches ) ? $matches : ( $matches ? array( $matches ) : array() );
		$home_away = empty( $league->event->current_season['home_away'] ) ? false : $league->event->current_season['home_away'];
		if ( $home_away ) {
			if ( $matches ) {
				$score = '';
				foreach ( $matches as $match ) {
					$score .= show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) ) . '<br>';
				}
			} else {
				$score = '&nbsp;';
			}
		} elseif ( $matches ) {
			$match = $matches[0];
			$score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
		} else {
			$matches = $this->get_matches(
				$league,
				array(
					'home_team'        => $opponent_id,
					'away_team'        => $team_id,
					'match_day'        => -1,
					'limit'            => false,
					'reset_query_args' => true,
				)
			);
			$matches = is_array( $matches ) ? $matches : ( $matches ? array( $matches ) : array() );
			if ( $matches ) {
				$match = $matches[0];
				$score = show_score( $match->id, array( 'team' => $team_id, 'opponent' => $opponent_id, 'home_away' => $home_away ) );
			} else {
				$score = '&nbsp;';
			}
		}
		return $score;
	}

	public function set_season( object $league, false|string $season = false, bool $force_overwrite = false, ?string $final = null ): void {
		if ( ! $force_overwrite ) {
			$requested_season = $this->resolve_requested_season( $league );
			$season = false !== $requested_season ? $requested_season : $season;
		}
		if ( null === $final ) {
			$final = $this->resolve_requested_final();
		}
		if ( ! $league instanceof League ) {
			$league->set_season( $season, $force_overwrite );
			return;
		}
		$league->set_season( $season, $force_overwrite );
		$this->set_num_teams( $league, true );
		if ( 'championship' === ( $league->mode ?? '' ) ) {
			$settings_data        = isset( $league->settings ) ? (array) $league->settings : array();
			$settings             = Championship_Settings::from_array( $settings_data );
			$factory              = new Championship_Factory( $this->league_repository );
			$league->championship = $factory->create( $league, $settings, $final );
		}
	}

	public function set_match_day( object $league, false|string|int $match_day = false ): void {
		$requested_match_day = $this->resolve_requested_match_day( $league );
		$match_day = false !== $requested_match_day ? $requested_match_day : $match_day;
		if ( ! $league instanceof League ) {
			$league->set_match_day( $match_day );
			return;
		}
		if ( is_string( $match_day ) && in_array( $match_day, array( 'last', 'next', 'current', 'latest' ), true ) ) {
			$type       = 'latest' === $match_day ? 'current' : $match_day;
			$match_day  = $this->league_repository->get_match_day(
				(int) ( $league->id ?? 0 ),
				(string) ( $league->current_season['name'] ?? '' ),
				$type
			) ?? 1;
		}
		$resolved_match_day = is_numeric( $match_day ) && 0 !== (int) $match_day ? (int) $match_day : 1;
		$league->set_match_day( $resolved_match_day );
	}

	public function resolve_requested_season( object $league ): false|string {
		return Competition_Request::season( (int) ( $league->id ?? 0 ), true ) ?? false;
	}

	public function resolve_requested_final(): ?string {
		return Competition_Request::final_round();
	}

	public function resolve_requested_match_day( object $league ): false|string|int {
		return Competition_Request::match_day( (int) ( $league->id ?? 0 ) );
	}

	public function resolve_requested_page( object $league, string $which = 'matches' ): int {
		return Competition_Request::page( (int) ( $league->id ?? 0 ), $which );
	}

	public function resolve_requested_tab( object $league ): ?int {
		$id = $league->id ?? 0;
		if ( ! $id ) {
			return null;
		}
		if ( isset( $_GET[ 'match_day_' . $id ] ) || isset( $_GET[ 'team_id_' . $id ] ) || isset( $_GET[ 'match_paged_' . $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 2;
		}
		if ( isset( $_GET[ 'team_' . $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 3;
		}
		if ( isset( $_GET[ 'match_' . $id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return 2;
		}
		return null;
	}

	public function set_tab( object $league, bool $is_archive = false, ?int $tab = null ): void {
		if ( null === $tab ) {
			$tab = $this->resolve_requested_tab( $league );
		}
		if ( ! $league instanceof League ) {
			$league->set_tab( $is_archive );
			return;
		}
		$league->set_tab( $is_archive, $tab );
	}

	public function get_page_links( object $league, string $which = 'matches', ?int $current_page = null, ?array $query_args = null ): string {
		if ( null === $current_page ) {
			$current_page = $this->resolve_requested_page( $league, $which );
		}

		if ( 'matches' === $which ) {
			$base = is_admin() ? 'match_paged' : 'match_paged_' . ( $league->id ?? '' );
		} elseif ( 'teams' === $which ) {
			$base = is_admin() ? 'team_paged' : 'team_paged_' . ( $league->id ?? '' );
		} else {
			$base = '';
		}

		if ( null === $query_args ) {
			$query_args = $league instanceof League ? $league->get_query_args() : array();
			$match_day = $this->resolve_requested_match_day( $league );
			if ( 'matches' === $which && false !== $match_day ) {
				$query_args['match_day'] = $match_day;
			}
		}
		if ( $league instanceof League ) {
			$league->get_current_page( $which, $current_page );
		}

		if ( 'matches' === $which ) {
			$num_pages    = $league->num_pages_matches ?? 1;
			$current_page = $league->current_page_matches ?? 1;
			$num_items    = $league->num_matches ?? 0;
		} elseif ( 'teams' === $which ) {
			$num_pages    = $league->num_pages_teams ?? 1;
			$current_page = $league->current_page_teams ?? 1;
			$num_items    = $league->num_matches ?? 0;
		} else {
			return '';
		}

		$query_args = $query_args ?? ( $league instanceof League ? $league->get_query_args() : array() );
		$page_links = paginate_links(
			array(
				'base'      => add_query_arg( $base, '%#%' ),
				'format'    => '',
				'prev_text' => '&#9668;',
				'next_text' => '&#9658;',
				'total'     => $num_pages,
				'current'   => $current_page,
				'add_args'  => $query_args,
			)
		);

		if ( $page_links && is_admin() ) {
			/* translators: %s: number of matches  */
			$page_links = sprintf( '<span class="displaying-num">' . __( '%s Matches', 'racketmanager' ) . '</span>%s', number_format_i18n( $num_items ), $page_links );
		}

		return (string) $page_links;
	}

	public function add_team( object $league, int|string $team_id, string $season, ?string $rank = null, ?string $status = null, int|string $profile = 1 ): bool|int {
		if ( ! is_numeric( $team_id ) ) {
			$team_repo   = $this->get_team_repository();
			$resolved_id = 0;
			if ( $team_repo ) {
				$resolved_id = $team_repo->find_id_by_title( (string) $team_id );
			}
			if ( $resolved_id <= 0 ) {
				return false;
			}
			$team_id = $resolved_id;
		}

		$team_id = (int) $team_id;
		if ( $team_id <= 0 ) {
			return false;
		}

		$league_id = (int) ( $league->id ?? 0 );
		if ( $league_id <= 0 ) {
			return false;
		}

		return $this->league_repository->add_team_to_league( $league_id, $team_id, $season, $rank, $status, (int) $profile );
	}

	public function update( object $league, string $title, ?string $sequence = null ): void {
		if ( ! $league instanceof League ) {
			$league->update( $title, $sequence );
			return;
		}
		$league->update( $title, $sequence );
		if ( ! empty( $league->id ) ) {
			$this->league_repository->update_title_and_sequence( (int) $league->id, $league->title, $league->sequence );
		}
	}

	public function set_rounds( object $league, string $season, array $rounds ): void {
		if ( ! $league instanceof League ) {
			$league->set_rounds( $season, $rounds );
			return;
		}
		$league->set_rounds( $season, $rounds );
		$this->league_repository->update_seasons( (int) ( $league->id ?? 0 ), $league->seasons );
	}

	private function optional_fixture_repository(): ?Fixture_Repository_Interface {
		return $this->resolve_optional( 'fixture_repository' );
	}

	private function optional_rubber_repository(): ?Rubber_Repository_Interface {
		return $this->resolve_optional( 'rubber_repository' );
	}

	private function optional_league_team_repository(): ?League_Team_Repository_Interface {
		return $this->resolve_optional( 'league_team_repository' );
	}

	private function optional_club_repository(): ?Club_Repository_Interface {
		return $this->resolve_optional( 'club_repository' );
	}

	private function optional_player_repository(): ?Player_Repository_Interface {
		return $this->resolve_optional( 'player_repository' );
	}

	/**
	 * Resolve a lazily-configured repository, returning null when it was not supplied.
	 */
	private function resolve_optional( string $property ): ?object {
		if ( $this->{$property} instanceof Closure ) {
			$this->{$property} = ( $this->{$property} )();
		}
		return $this->{$property} instanceof Closure ? null : $this->{$property};
	}

	/**
	 * Resolve a player through the supplied repository, tolerating missing ids.
	 *
	 * @param Player_Repository_Interface|null $player_repository player repository.
	 * @param mixed $player_id player identifier.
	 *
	 * @return object|null
	 */
	private function find_player( ?Player_Repository_Interface $player_repository, mixed $player_id ): ?object {
		if ( null === $player_repository || empty( $player_id ) || ! is_numeric( $player_id ) ) {
			return null;
		}
		try {
			return $player_repository->find( (int) $player_id );
		} catch ( \Throwable ) {
			return null;
		}
	}

	public function get_matches( mixed $league, array $query_args = array() ): array|int|object {
		if ( ! $league instanceof League ) {
			return $league->get_matches( $query_args );
		}
		$old_query_args = $league->get_match_query_args();
		$success        = false;

		try {
			// set query args.
			foreach ( $query_args as $key => $value ) {
				$league->set_match_query_arg( $key, $value );
			}
			$match_query_args = $league->get_match_query_args();
			$limit            = $match_query_args['limit'] ?? null;
			$season           = $match_query_args['season'] ?? null;
			$count            = $match_query_args['count'] ?? false;
			$reset_limit      = $match_query_args['reset_limit'] ?? false;
			$reset_query_args = $match_query_args['reset_query_args'] ?? false;
			$player           = $match_query_args['player'] ?? false;
			$status           = $match_query_args['status'] ?? false;

			$matches = array();

			// disable limit for championship mode.
			if ( $league->is_championship ) {
				$limit = false;
			}
			if ( ! empty( $season ) && 'any' !== $season && ! $league->season_exists( (string) $season ) ) {
				$success = true;
				return $matches;
			}

			if ( is_numeric( $limit ) && (int) $limit > 0 ) {
				$league->num_matches_per_page = (int) $limit;
			}

			$league->current_page_matches = $this->resolve_requested_page( $league );

			$query_args_to_pass                    = $match_query_args;
			$query_args_to_pass['current_season']  = $league->current_season['name'] ?? '';
			// The requested page is resolved at the request boundary into current_page_matches.
			$offset                                = intval( $limit > 0 ) ? ( max( 1, $league->current_page_matches ) - 1 ) * $limit : 0;
			$query_args_to_pass['offset']          = $offset;
			$query_args_to_pass['is_championship'] = $league->is_championship;

			if ( $status ) {
				$status_code = $status['status_code'] ?? null;
				$compare     = $status['compare'] ?? null;
				if ( $status_code ) {
					$status_value = Util_Lookup::get_match_status_code( $status_code );
					if ( $status_value ) {
						$query_args_to_pass['status_value']   = $status_value;
						$query_args_to_pass['status_compare'] = $compare;
					}
				}
			}

			$has_repository = ! empty( $league->id );
			if ( $count ) {
				$league->set_match_query_arg( 'count', false );
				$matches = $has_repository ? $this->league_repository->query_matches( (int) $league->id, $query_args_to_pass, true ) : 0;
			} else {
				$matches = $has_repository ? $this->league_repository->query_matches( (int) $league->id, $query_args_to_pass, false ) : array();
				$matches = is_array( $matches ) ? $matches : array();
				$fixture_repository = $this->optional_fixture_repository();
				if ( $fixture_repository ) {
					$class = '';
					foreach ( $matches as $i => $match ) {
						$match = $fixture_repository->find_by_id( (int) $match->id );
						$class = ( 'alternate' === $class ) ? '' : 'alternate';
						if ( $match ) {
							$match->class = $class;
						}
						if ( $player && $match ) {
							$rubber_repository = $this->optional_rubber_repository();
							if ( $rubber_repository ) {
								$match->set_rubbers( $rubber_repository->find_by_fixture_id( (int) $match->get_id(), (int) $player ) );
							}
						}

						$matches[ $i ] = $match;
					}
					if ( 1 === $limit && $matches ) {
						$matches = $matches[0];
					}
				}
			}

			// reset match limit.
			if ( true === $reset_limit ) {
				$league->set_match_query_arg( 'limit', $old_query_args['limit'] ?? null );
				$league->set_match_query_arg( 'reset_limit', false );
			}

			// reset match query args.
			if ( true === $reset_query_args ) {
				foreach ( $old_query_args as $key => $query_arg ) {
					$league->set_match_query_arg( $key, $query_arg );
				}
				$league->set_match_query_arg( 'reset_query_args', false );
			}

			if ( true !== $count ) {
				$league->matches = is_array( $matches ) ? $matches : array( $matches );
			} else {
				$league->num_matches = (int) $matches;
			}

			$success = true;
			return $matches;
		} finally {
			if ( ! $success ) {
				$league->restore_match_query_args( $old_query_args );
			}
		}
	}
}
