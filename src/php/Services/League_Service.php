<?php
declare( strict_types=1 );

namespace Racketmanager\Services;

use Closure;
use InvalidArgumentException;
use LogicException;
use Racketmanager\Application\League\Ports\League_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Util\Util_Lookup;
use RuntimeException;
use stdClass;

/**
 * Coordinates league management operations for events, teams, fixtures, standings, and notifications.
 *
 * Provides application-level behaviour for creating and retrieving leagues, associating leagues
 * with events, resolving participating teams, accessing fixture-related collaborators, and
 * delegating persistence to repository interfaces. The service acts as an orchestration layer
 * between domain entities, repositories, query services, standings processing, fixture result
 * management, and optional notification services.
 *
 * Required repositories are injected directly, while selected collaborators may be supplied
 * as closures to support deferred resolution. Deferred dependencies are resolved lazily by the
 * service when their functionality is first needed.
 *
 * @implements League_Service_Interface
 */
class League_Service implements League_Service_Interface {

    private ?RacketManager $racketmanager;
    private League_Team_Repository_Interface $league_team_repository;
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private Team_Repository_Interface $team_repository;
    private Fixture_Repository_Interface|Closure|null $fixture_repository;
    private Fixture_Result_Manager|Closure|null $fixture_result_manager;
    private Standings_Service|Closure|null $standings_service;
    private ?Notification_Service $notification_service;
    private ?Competition_Notification_Service $competition_notification_service;
    private League_Query_Service|Closure|null $league_query_service;

    /**
     * Initialises the league service with required repositories and optional collaborators.
     *
     * Stores repository dependencies and optional services used for fixture handling,
     * standings updates, league queries, and notifications. Optional collaborators may
     * be provided directly or as closures for deferred resolution where supported.
     *
     * @param RacketManager|null                        $racketmanager RacketManager instance, or null when unavailable.
     * @param League_Repository_Interface               $league_repository League repository dependency.
     * @param Event_Repository_Interface                $event_repository Event repository dependency.
     * @param League_Team_Repository_Interface          $league_team_repository League team repository dependency.
     * @param Team_Repository_Interface                 $team_repository Team repository dependency.
     * @param Fixture_Repository_Interface|Closure|null $fixture_repository Fixture repository instance, deferred provider, or null.
     * @param Fixture_Result_Manager|Closure|null       $fixture_result_manager Fixture result manager instance, deferred provider, or null.
     * @param Standings_Service|Closure|null            $standings_service Standings service instance, deferred provider, or null.
     * @param Notification_Service|null                 $notification_service Notification service dependency, or null.
     * @param Competition_Notification_Service|null     $competition_notification_service Competition notification service dependency, or null.
     * @param League_Query_Service|Closure|null         $league_query_service League query service instance, deferred provider, or null.
     *
     * @return void
     */
    public function __construct(
        ?RacketManager $racketmanager,
        League_Repository_Interface $league_repository,
        Event_Repository_Interface $event_repository,
        League_Team_Repository_Interface $league_team_repository,
        Team_Repository_Interface $team_repository,
        Fixture_Repository_Interface|Closure|null $fixture_repository = null,
        Fixture_Result_Manager|Closure|null $fixture_result_manager = null,
        Standings_Service|Closure|null $standings_service = null,
        ?Notification_Service $notification_service = null,
        ?Competition_Notification_Service $competition_notification_service = null,
        League_Query_Service|Closure|null $league_query_service = null
    ) {
        $this->racketmanager                    = $racketmanager;
        $this->league_repository                = $league_repository;
        $this->event_repository                 = $event_repository;
        $this->league_team_repository           = $league_team_repository;
        $this->team_repository                  = $team_repository;
        $this->fixture_repository               = $fixture_repository;
        $this->fixture_result_manager           = $fixture_result_manager;
        $this->standings_service                = $standings_service;
        $this->notification_service             = $notification_service;
        $this->competition_notification_service = $competition_notification_service;
        $this->league_query_service             = $league_query_service;
    }

    /**
     * Retrieves the fixture repository collaborator.
     *
     * Lazily resolves the repository when it has been provided as a closure and
     * returns null when the resolved value does not implement the expected interface.
     *
     * @return Fixture_Repository_Interface|null Fixture repository instance, or null if unavailable.
     */
    private function get_fixture_repository(): ?Fixture_Repository_Interface {
        if ( $this->fixture_repository instanceof Closure ) {
            $this->fixture_repository = ( $this->fixture_repository )();
        }
        return $this->fixture_repository instanceof Fixture_Repository_Interface ? $this->fixture_repository : null;
    }

    /**
     * Retrieves the fixture result manager instance.
     *
     * Lazily resolves the fixture result manager when it has been provided as a closure.
     *
     * @return Fixture_Result_Manager|null Fixture result manager instance, or null if unavailable.
     */
    private function get_fixture_result_manager(): ?Fixture_Result_Manager {
        if ( $this->fixture_result_manager instanceof Closure ) {
            $this->fixture_result_manager = ( $this->fixture_result_manager )();
        }
        return $this->fixture_result_manager instanceof Fixture_Result_Manager ? $this->fixture_result_manager : null;
    }

    /**
     * Retrieves the standings service collaborator.
     *
     * Lazily resolves the standings service when it has been provided as a closure,
     * then returns it only when the resolved value is a valid standings service instance.
     *
     * @return Standings_Service|null Standings service instance, or null when unavailable.
     */
    private function get_standings_service(): ?Standings_Service {
        if ( $this->standings_service instanceof Closure ) {
            $this->standings_service = ( $this->standings_service )();
        }
        return $this->standings_service instanceof Standings_Service ? $this->standings_service : null;
    }

    /**
     * Retrieves leagues matching the specified query arguments.
     *
     * @param array $args Optional query arguments used to filter, sort, or limit leagues.
     *
     * @return array List of matching leagues.
     */
    public function get_leagues( array $args = array() ): array {
        return $this->league_repository->find_all( $args );
    }

    /**
     * Adds a league to an event.
     *
     * Creates a new league for the specified event using the provided name, or generates
     * one from the event name and next available sequence number when no name is supplied.
     *
     * @param int         $event_id Event ID to associate with the new league.
     * @param string|null $name Optional league name. If null or empty, a generated name is used.
     *
     * @return League Newly created and saved league.
     *
     * @throws Event_Not_Found_Exception If the event cannot be found.
     */
    public function add_league_to_event( int $event_id, ?string $name = null ): League {
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( esc_html__( 'Event not found', 'racketmanager' ) );
        }
        $league = new stdClass();
        if ( empty( $name ) ) {
            // 1. Get the next available sequence number
            $next_sequence_number = $this->league_repository->find_next_sequence_number( $event->get_name() );
            // 2. Generate the team name
            $league_name = $event->get_name() . ' ' . $next_sequence_number;
            // 3. Create the Domain Entity and Save it
            $league->title = $league_name;
        } else {
            $league->title = $name;
        }
        $league->event_id = $event->get_id();
        $league           = League_Factory::from_object( $league, $event );
        $this->league_repository->save( $league );

        return $league;
    }

    /**
     * Adds a team to a league for the specified season.
     *
     * Returns the existing league-team association when one already exists; otherwise,
     * creates and saves a new active league-team association.
     *
     * @param int $team_id Team ID to add.
     * @param int $league_id League ID to add the team to.
     * @param int $season Season identifier.
     *
     * @return League_Team Existing or newly created league-team association.
     *
     * @throws Team_Not_Found_Exception   If the team cannot be found.
     * @throws League_Not_Found_Exception If the league cannot be found.
     */
    public function add_team_to_league( int $team_id, int $league_id, int $season ): League_Team {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( esc_html__( 'Team not found', 'racketmanager' ) );
        }
        $league = $this->league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( esc_html__( 'League not found', 'racketmanager' ) );
        }

        $existing_league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season );
        if ( $existing_league_team ) {
            return $existing_league_team;
        }

        $league_team = League_Team_Factory::from_array(
            array(
                'league_id' => $league_id,
                'team_id'   => $team_id,
                'season'    => (string) $season,
                'profile'   => Team_Profile::ACTIVE->value,
            )
        );
        $this->league_team_repository->save( $league_team );
        return $league_team;
    }

    /**
     * Adds multiple teams to a league for the specified season.
     *
     * Iterates over the provided team IDs and adds each team to the league.
     *
     * @param array $team_ids List of team IDs to add.
     * @param int   $league_id League ID.
     * @param int   $season Season identifier.
     *
     * @return int Number of teams processed for addition.
     */
    public function add_teams_to_league( array $team_ids, int $league_id, int $season ): int {
        $added = 0;
        foreach ( $team_ids as $team_id ) {
            $this->add_team_to_league( $team_id, $league_id, $season );
            ++$added;
        }
        return $added;
    }

    /**
     * Removes a team from a league for the specified season.
     *
     * Deletes the league-team association only when it exists, the league can be found,
     * and the team has no matches recorded in that league season.
     *
     * @param int $team_id Team ID to remove from the league.
     * @param int $league_id League ID from which to remove the team.
     * @param int $season Season identifier for the league-team association.
     *
     * @return void
     *
     * @throws Team_Has_Matches_Exception If the team has matches in the league season.
     */
    public function remove_team_from_league( int $team_id, int $league_id, int $season ): void {
        $league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season );
        if ( ! $league_team ) {
            return;
        }

        $league = $this->league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            return;
        }

        $matches = $this->league_query()->get_matches(
            $league,
            array(
                'team_id' => $team_id,
                'season'  => $season,
                'final'   => 'all',
            )
        );

        if ( $matches ) {
            throw new Team_Has_Matches_Exception( esc_html__( 'Team has matches and cannot be deleted', 'racketmanager' ) );
        }

        $this->league_team_repository->delete( $league_team->get_id() );
    }

    /**
     * Removes multiple league-team entries from a league for the specified season.
     *
     * Looks up each league-team record, removes the associated team from the league,
     * and collects status messages for successful removals or match-related failures.
     *
     * @param array $league_team_ids League-team record IDs to remove.
     * @param int   $league_id League ID from which teams should be removed.
     * @param int   $season Season identifier.
     *
     * @return array Result data containing messages and an error flag.
     */
    public function remove_teams_from_league( array $league_team_ids, int $league_id, int $season ): array {
        $messages  = array();
        $any_error = false;
        foreach ( $league_team_ids as $id ) {
            try {
                $league_team = $this->league_team_repository->find_by_id( $id );
                if ( ! $league_team ) {
                    continue;
                }
                $team_id = $league_team->get_team_id();
                $this->remove_team_from_league( $team_id, $league_id, $season );
                $messages[] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
            } catch ( Team_Has_Matches_Exception $e ) {
                $messages[] = $id . ': ' . $e->getMessage();
                $any_error  = true;
            }
        }
        return array(
            'messages'  => $messages,
            'any_error' => $any_error,
        );
    }

    /**
     * Retrieves a league object from an object, ID, or name.
     *
     * Returns the provided object unchanged, resolves numeric values as league IDs,
     * resolves string values as league names, and returns null for empty or unsupported values.
     *
     * @param object|int|string|null $league_id League object, league ID, league name, or null.
     *
     * @return object|null League object when found, or null when no league can be resolved.
     */
    public function get_league( object|int|string|null $league_id ): ?object {
        if ( is_object( $league_id ) ) {
            return $league_id;
        }
        if ( empty( $league_id ) ) {
            return null;
        }
        if ( is_numeric( $league_id ) ) {
            return $this->league_repository->find_by_id( (int) $league_id );
        }
        if ( is_string( $league_id ) ) {
            return $this->league_repository->find_by_name( $league_id );
        }
        return null;
    }

    /**
     * Retrieves a league by its name.
     *
     * @param string $name League name to search for.
     *
     * @return object|null League object if found, or null if no matching league exists.
     */
    public function get_league_by_name( string $name ): ?object {
        return $this->league_repository->find_by_name( $name );
    }

    /**
     * Retrieves teams eligible for consolation placement for a league season.
     *
     * Uses the league's primary league to identify teams that have lost in the main bracket,
     * filters out teams with ineligible match histories or walkover outcomes, and appends
     * placeholder loser teams from pending final-round matches when applicable.
     *
     * @param League $league League whose related primary league is used to determine consolation teams.
     * @param int    $season Season identifier used to retrieve teams from the primary league.
     *
     * @return array List of team objects and generated loser-team placeholders eligible for consolation.
     */
    public function get_consolation_teams( League $league, int $season ): array {
        $primary_league_id = $league->event->primary_league ?? null;
        if ( ! $primary_league_id ) {
            return array();
        }

        $primary_league = $this->get_league( $primary_league_id );
        if ( ! $primary_league ) {
            return array();
        }

        $teams = $this->league_team_repository->get_teams_by_league_and_season( $primary_league_id, $season );

        foreach ( $teams as $key => $team ) {
            $match_array                     = array();
            $match_array['loser_id']         = $team->id;
            $match_array['count']            = true;
            $match_array['final']            = 'all';
            $match_array['reset_query_args'] = true;
            $matches_count                   = $this->league_query()->get_matches( $primary_league, $match_array );

            if ( ! $matches_count ) {
                unset( $teams[ $key ] );
                continue;
            }

            $match_array['loser_id'] = null;
            $match_array['team_id']  = $team->id;
            $matches_count           = $this->league_query()->get_matches( $primary_league, $match_array );
            $last_match              = null;

            if ( $matches_count > 2 ) {
                unset( $teams[ $key ] );
                continue;
            }

            if ( 2 === $matches_count ) {
                $match_array['count'] = false;
                $matches              = $this->league_query()->get_matches( $primary_league, $match_array );
                if ( is_array( $matches ) && count( $matches ) >= 2 ) {
                    $first_match = $matches[0];
                    if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) {
                        unset( $teams[ $key ] );
                        continue;
                    }
                    $last_match = $matches[1];
                }
            } elseif ( 1 === $matches_count ) {
                $match_array['count'] = false;
                $matches              = $this->league_query()->get_matches( $primary_league, $match_array );
                if ( is_array( $matches ) && ! empty( $matches ) ) {
                    $last_match = $matches[0];
                }
            }

            if ( $last_match && ! empty( $last_match->is_walkover ) ) {
                unset( $teams[ $key ] );
            }
        }

        $match_array                     = array();
        $match_array['reset_query_args'] = true;
        $final_name                      = $primary_league->championship->get_final_keys( 1 );
        $match_array['final']            = $final_name;
        $match_array['pending']          = true;
        $matches                         = $this->league_query()->get_matches( $primary_league, $match_array );

        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $teams[] = $this->build_loser_team( $final_name, $match );
            }
        }

        $final_name           = $primary_league->championship->get_final_keys( 2 );
        $match_array['final'] = $final_name;
        $matches              = $this->league_query()->get_matches( $primary_league, $match_array );

        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $possible   = 0;
                $team_types = array( 'home', 'away' );
                foreach ( $team_types as $team_type ) {
                    $team_ref = $team_type . '_team';
                    if ( is_numeric( $match->$team_ref ) ) {
                        $match_array['pending']   = false;
                        $match_array['final']     = 'all';
                        $match_array['winner_id'] = $match->$team_ref;
                        $team_matches             = $this->league_query()->get_matches( $primary_league, $match_array );
                        if ( is_array( $team_matches ) ) {
                            foreach ( $team_matches as $team_match ) {
                                if ( '-1' === $team_match->home_team || '-1' === $team_match->away_team ) {
                                    ++$possible;
                                }
                            }
                        }
                    }
                }
                if ( $possible ) {
                    $teams[] = $this->build_loser_team( $final_name, $match );
                }
            }
        }

        return $teams;
    }

    /**
     * Builds a placeholder team object representing the loser of a match.
     *
     * Creates a team-like object with a generated ID, display title based on the
     * supplied final name and match participants, and an empty stadium value.
     *
     * @param string $final_name Final or round identifier used to build the placeholder team ID.
     * @param object $match Match object containing an ID and home and away team data.
     *
     * @return object Placeholder team object representing the match loser.
     */
    private function build_loser_team( string $final_name, object $match ): object {
        $team          = new stdClass();
        $team->id      = '2_' . $final_name . '_' . $match->id;
        $team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
        $team->stadium = '';
        return $team;
    }

    /**
     * Retrieves the standings for a league in the specified season.
     *
     * Looks up the league by ID and returns the standings from the league team repository.
     *
     * @param int|null $league_id League ID.
     * @param int|null $season Season identifier.
     *
     * @return array League standings data.
     *
     * @throws League_Not_Found_Exception If the league cannot be found.
     */
    public function get_league_standings( ?int $league_id, ?int $season ): array {
        $league = $this->get_league( $league_id );
        if ( ! $league ) {
            // Translators: League not found for id %d.
            throw new League_Not_Found_Exception( esc_attr( sprintf( __( 'League not found for id %d', 'racketmanager' ), $league_id ) ) );
        }
        return $this->league_team_repository->find_league_standings( $league->get_id(), $season );
    }

    /**
     * Ranks teams by points and tie-breaker statistics.
     *
     * Sorts teams using withdrawal status, points, set difference, game difference,
     * sets won, sets allowed, games won, games allowed, and team title.
     *
     * @param array $teams Teams to rank. Each team is expected to provide points and optional ranking statistics.
     *
     * @return array Ranked teams.
     */
    public function rank_teams_by_points( array $teams ): array {
        $status        = array();
        $points        = array();
        $sets_diff     = array();
        $sets_won      = array();
        $sets_allowed  = array();
        $games_diff    = array();
        $games_won     = array();
        $games_allowed = array();
        $title         = array();

        foreach ( $teams as $key => $team ) {
            $team_sets_won     = $team->sets_won ?? 0;
            $team_sets_allowed = $team->sets_allowed ?? 0;
            if ( ! is_numeric( $team_sets_won ) ) {
                $team_sets_won = 0;
            }
            if ( ! is_numeric( $team_sets_allowed ) ) {
                $team_sets_allowed = 0;
            }
            $team_games_won     = $team->games_won ?? 0;
            $team_games_allowed = $team->games_allowed ?? 0;
            if ( ! is_numeric( $team_games_won ) ) {
                $team_games_won = 0;
            }
            if ( ! is_numeric( $team_games_allowed ) ) {
                $team_games_allowed = 0;
            }
            $points[ $key ]        = $team->points['plus'] ?? 0;
            $sets_diff[ $key ]     = $team_sets_won - $team_sets_allowed;
            $sets_won[ $key ]      = $team_sets_won;
            $sets_allowed[ $key ]  = $team_sets_allowed;
            $games_diff[ $key ]    = $team_games_won - $team_games_allowed;
            $games_won[ $key ]     = $team_games_won;
            $games_allowed[ $key ] = $team_games_allowed;
            if ( isset( $team->status ) && 'W' === $team->status ) {
                $status[ $key ] = $team->status;
            } else {
                $status[ $key ] = null;
            }
            $title[ $key ] = $team->title ?? '';
        }
        array_multisort( $status, SORT_ASC, $points, SORT_DESC, $sets_diff, SORT_DESC, $games_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $title, SORT_ASC, $teams );

        return $teams;
    }

    /**
     * Rank teams in a league
     *
     * @param object $league League object.
     * @param string $mode Ranking mode (random, ratings, manual).
     * @param array  $post Post data containing ranking info.
     * @param array  $team_ids IDs of teams to rank.
     *
     * @return bool True if a ranking was performed, false otherwise.
     */
    public function rank_teams( object $league, string $mode, array $post, array $team_ids ): bool {
        $sorted_team_ids = match ( $mode ) {
            'random' => $this->sort_team_ids_random( $team_ids ),
            'ratings' => $this->sort_team_ids_by_ratings( $league, $post, $team_ids ),
            'manual' => $this->sort_team_ids_manual( $post, $team_ids ),
            default => null,
        };

        if ( null === $sorted_team_ids ) {
            return false;
        }

        $team_ranks = array();
        foreach ( $sorted_team_ids as $key => $team_id ) {
            $team = $this->league_team_repository->find_by_id( $team_id );
            if ( $team ) {
                $team_ranks[ $key ] = $team;
            }
        }

        $team_ranks = $this->get_ranking( $league, $team_ranks );
        $this->update_ranking( $team_ranks );

        return true;
    }

    /**
     * Calculates and assigns rankings for the provided league teams.
     *
     * Preserves each team's previous rank, applies shared ranks for tied teams when
     * standings tie logic is available, updates team status for non-withdrawn teams,
     * and returns the teams ordered by their assigned rank.
     *
     * @param object $league League object used to determine whether championship ranking rules apply.
     * @param array  $teams Ordered list of team objects to rank.
     *
     * @return array Ranked team objects ordered by ascending rank.
     *
     * @throws LogicException If standings tie evaluation is required but the standings service is not configured.
     */
    public function get_ranking( object $league, array $teams ): array {
        $rank      = 1;
        $incr      = 1;
        $new_teams = array();
        foreach ( $teams as $key => $team ) {
            if ( $team instanceof League_Team ) {
                $team->record_previous_rank();
            } else {
                $team->old_rank = $team->get_rank();
            }

            if ( $key > 0 ) {
                $is_tie = false;
                if ( ! $league->is_championship && isset( $team->points ) ) {
                    $standings_service = $this->get_standings_service() ?? throw new LogicException( 'Standings_Service has not been configured.' );
                    $is_tie            = $standings_service->is_tie( $team, $teams[ $key - 1 ] );
                }
                if ( $is_tie ) {
                    ++$incr;
                } else {
                    $rank += $incr;
                    $incr  = 1;
                }
            }

            $team->set_rank( $rank );
            if ( 'W' !== $team->get_status() ) {
                $team->set_status( $this->get_team_status( $team, $rank ) );
            }

            $new_teams[ $key ] = $team;
        }

        // re-order teams by rank.
        $ranks = array_map(
            function ( $row ) {
                return $row->get_rank();
            },
            $new_teams
        );
        array_multisort( $ranks, SORT_ASC, $new_teams );

        return $new_teams;
    }

    /**
     * Get team status depending on previous rank
     *
     * @param object $team team.
     * @param int    $rank rank.
     *
     * @return string
     */
    private function get_team_status( object $team, int $rank ): string {
        if ( 0 !== $team->old_rank && $team->get_done_matches() > 1 ) {
            if ( intval( $team->old_rank ) === $rank ) {
                $status = '=';
            } elseif ( $rank < $team->old_rank ) {
                $status = '+';
            } else {
                $status = '-';
            }
        } else {
            $status = '';
        }
        return $status;
    }

    /**
     * Updates league table ranking records for the given teams.
     *
     * Saves each team ranking entry and clears the related league table cache.
     * When a table record ID is present, the team's current ID is stored as the team ID
     * and the table record ID is used as the persisted entity ID.
     *
     * @param array $teams Team ranking entries to update.
     *
     * @return void
     */
    public function update_ranking( array $teams ): void {
        foreach ( $teams as $team ) {
            if ( ! empty( $team->table_id ) ) {
                $team->set_team_id( $team->id );
                $team->set_id( $team->table_id );
            }
            $this->league_team_repository->save( $team );
            wp_cache_delete( $team->get_league_id(), 'leaguetable' );
        }
    }

    /**
     * Randomly reorders the provided team IDs.
     *
     * @param array $team_ids Team IDs to shuffle.
     *
     * @return array Team IDs in random order.
     */
    public function sort_team_ids_random( array $team_ids ): array {
        shuffle( $team_ids );

        return $team_ids;
    }

    /**
     * Sorts team IDs by submitted rating points and applies championship seeding rules.
     *
     * Orders team IDs by ascending rating points when ratings are provided. For championship
     * leagues with a configured seed count, preserves the seeded teams at the beginning of
     * the list and randomises the remaining unseeded teams.
     *
     * @param object $league League object containing championship and seeding configuration.
     * @param array  $post Submitted data that may include rating points keyed by rating_points.
     * @param array  $team_ids Team IDs to sort.
     *
     * @return array Sorted team IDs, with unseeded championship teams randomized when applicable.
     */
    public function sort_team_ids_by_ratings( object $league, array $post, array $team_ids ): array {
        if ( isset( $post['rating_points'] ) ) {
            $rating_points = array_values( (array) $post['rating_points'] );
            array_multisort( $rating_points, SORT_ASC, $team_ids, SORT_ASC );
        }
        if ( $league->is_championship && $league->championship->num_seeds ) {
            $teams_seeded   = array_slice( $team_ids, 0, $league->championship->num_seeds );
            $teams_unseeded = array_slice( $team_ids, $league->championship->num_seeds );
            shuffle( $teams_unseeded );

            return array_merge( $teams_seeded, $teams_unseeded );
        }

        return $team_ids;
    }

    /**
     * Sorts team IDs according to manually submitted rank values when JavaScript sorting is inactive.
     *
     * Uses the submitted rank values to order the provided team IDs in ascending rank order,
     * with team IDs used as a secondary ascending sort key. If JavaScript sorting is active or
     * no rank values are provided, the original team ID order is returned.
     *
     * @param array $post Submitted form data containing the JavaScript activity flag and optional rank values.
     * @param array $team_ids Team IDs to sort.
     *
     * @return array Sorted team IDs, or the original team IDs when no manual sorting is applied.
     */
    public function sort_team_ids_manual( array $post, array $team_ids ): array {
        if ( ! isset( $post['js-active'] ) || '1' !== strval( $post['js-active'] ) ) {
            $ranks = isset( $post['rank'] ) ? array_values( (array) $post['rank'] ) : array();
            if ( $ranks ) {
                array_multisort( $ranks, SORT_ASC, $team_ids, SORT_ASC );
            }
        }

        return $team_ids;
    }

    /**
     * Finds fixture IDs for a league using the provided match query criteria.
     *
     * Builds default query arguments, merges them with the supplied criteria, queries
     * league matches, and returns a de-duplicated list of positive fixture IDs.
     *
     * @param object|int $league League instance, league-like object with an ID, or league ID.
     * @param array      $criteria Optional query criteria used to filter matching fixtures.
     *
     * @return array List of fixture IDs.
     */
    public function find_fixture_ids( object|int $league, array $criteria = array() ): array {
        $league_id   = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj  = is_object( $league ) ? $league : $this->league_repository->find_by_id( $league_id );
        $query_args  = array_merge(
            array(
                'season'          => 'any',
                'final'           => 'all',
                'current_season'  => $league_obj?->current_season['name'] ?? '',
                'is_championship' => ! empty( $league_obj?->is_championship ),
                'match_day'       => -1,
                'withdrawn'       => true,
                'orderby'         => array(),
                'limit'           => 0,
                'offset'          => 0,
                'cache'           => false,
            ),
            $criteria
        );
        $rows        = $this->league_repository->query_matches( $league_id, $query_args, false );
        $fixture_ids = array();
        foreach ( is_array( $rows ) ? $rows : array() as $row ) {
            $fixture_id = is_object( $row ) ? $row->id ?? 0 : (int) $row;
            if ( $fixture_id > 0 ) {
                $fixture_ids[ $fixture_id ] = $fixture_id;
            }
        }
        return array_values( $fixture_ids );
    }

    /**
     * Deletes a league and its associated records.
     *
     * Removes fixtures associated with the league, deletes league-team relationships,
     * deletes the league record, and clears the league cache entry.
     *
     * @param object|int $league League instance, league-like object with an ID, or league ID.
     *
     * @return void
     *
     * @throws RuntimeException If the fixture repository is unavailable or associated fixtures cannot be deleted.
     */
    public function delete_league( object|int $league ): void {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : null;
        if ( $league_id <= 0 ) {
            return;
        }

        $fixture_repository = $this->get_fixture_repository();
        $fixture_ids        = $this->find_fixture_ids( $league_obj ?? $league_id );
        if ( ! empty( $fixture_ids ) ) {
            if ( ! $fixture_repository ) {
                throw new RuntimeException( 'Cannot delete League: Fixture_Repository_Interface collaborator is required.' );
            }
            $failed_ids = array();
            foreach ( $fixture_ids as $fixture_id ) {
                if ( ! $fixture_repository->delete( $fixture_id ) ) {
                    $failed_ids[] = $fixture_id;
                }
            }
            if ( ! empty( $failed_ids ) ) {
                throw new RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for league ' . $league_id );
            }
        }

        $this->league_repository->delete_league_teams( $league_id );
        $this->league_repository->delete( $league_id );
        wp_cache_delete( $league_id, 'leagues' );
    }

    /**
     * Deletes a team from a league for the specified season.
     *
     * Removes fixtures involving the team for the season, deletes the team from the league,
     * and clears related cache entries.
     *
     * @param object|int  $league League instance, league-like object with an ID, or league ID.
     * @param int         $team_id Team ID to delete.
     * @param string|null $season Season identifier.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the season is empty or null.
     * @throws RuntimeException         If required fixture repository collaboration is unavailable or fixture deletion fails.
     */
    public function delete_team( object|int $league, int $team_id, ?string $season ): void {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : null;
        if ( $league_id <= 0 || $team_id <= 0 ) {
            return;
        }

        if ( empty( $season ) || '' === trim( $season ) ) {
            throw new InvalidArgumentException( 'Cannot delete team from League: season is required.' );
        }

        $fixture_repository = $this->get_fixture_repository();
        $fixture_ids        = $this->find_fixture_ids(
            $league_obj ?? $league_id,
            array(
                'team_id' => $team_id,
                'season'  => $season,
            )
        );
        if ( ! empty( $fixture_ids ) ) {
            if ( ! $fixture_repository ) {
                throw new RuntimeException( 'Cannot delete team from League: Fixture_Repository_Interface collaborator is required.' );
            }
            $failed_ids = array();
            foreach ( $fixture_ids as $fixture_id ) {
                if ( ! $fixture_repository->delete( $fixture_id ) ) {
                    $failed_ids[] = $fixture_id;
                }
            }
            if ( ! empty( $failed_ids ) ) {
                throw new RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for team ' . $team_id . ' in league ' . $league_id );
            }
        }

        $this->league_repository->delete_team( $league_id, $team_id, $season );
        wp_cache_delete( $team_id, '$league_team' );
        wp_cache_delete( $league_id, 'leaguetable' );
    }

    /**
     * Deletes all fixtures for a league in the specified season.
     *
     * Resolves the league ID, retrieves matching fixture IDs for the season,
     * deletes each fixture through the fixture repository, and reports any
     * fixture IDs that could not be deleted.
     *
     * @param object|int $league League instance, league-like object with an ID, or league ID.
     * @param string|int $season Season identifier.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the season is empty.
     * @throws RuntimeException         If the fixture repository is unavailable or one or more fixtures cannot be deleted.
     */
    public function delete_season_fixtures( object|int $league, string|int $season ): void {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : null;
        if ( $league_id <= 0 ) {
            return;
        }

        if ( '' === trim( (string) $season ) ) {
            throw new InvalidArgumentException( 'Cannot delete season fixtures from League: season is required.' );
        }

        $fixture_repository = $this->get_fixture_repository();
        if ( ! $fixture_repository ) {
            throw new RuntimeException( 'Cannot delete season matches from League: Fixture_Repository_Interface collaborator is required.' );
        }

        $fixture_ids = $this->find_fixture_ids(
            $league_obj ?? $league_id,
            array(
                'season' => (string) $season,
            )
        );
        $failed_ids  = array();
        foreach ( $fixture_ids as $fixture_id ) {
            if ( ! $fixture_repository->delete( $fixture_id ) ) {
                $failed_ids[] = $fixture_id;
            }
        }
        if ( ! empty( $failed_ids ) ) {
            throw new RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for season ' . $season . ' in league ' . $league_id );
        }
    }

    /**
     * Deletes all matches for a league season.
     *
     * Removes the season fixtures associated with the specified league.
     *
     * @param object|int $league League instance, league-like object with an ID, or league ID.
     * @param string|int $season Season identifier.
     *
     * @return void
     */
    public function delete_season_matches( object|int $league, string|int $season ): void {
        $this->delete_season_fixtures( $league, $season );
    }

    /**
     * Withdraws a team from a league for the specified season.
     *
     * Cancels or resolves pending fixtures involving the team, records the team withdrawal,
     * clears related caches, and updates standings and notifications when applicable.
     *
     * @param object|int $league League instance, league-like object with an ID, or league ID.
     * @param int        $team_id Team ID to withdraw.
     * @param string|int $season Season identifier.
     *
     * @return void
     *
     * @throws InvalidArgumentException   If the season is empty.
     * @throws League_Not_Found_Exception If the league cannot be found.
     * @throws RuntimeException           If required collaborators are unavailable.
     */
    public function withdraw_team( object|int $league, int $team_id, string|int $season ): void {
        $league_id = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        if ( $league_id <= 0 || $team_id <= 0 ) {
            return;
        }

        $league_obj = is_int( $league ) ? $this->league_repository->find_by_id( $league ) : ( $league instanceof League ? $league : $this->league_repository->find_by_id( $league_id ) );

        if ( '' === trim( (string) $season ) ) {
            throw new InvalidArgumentException( 'Cannot withdraw team from League: season is required.' );
        }

        if ( null === $league_obj ) {
            throw new League_Not_Found_Exception( 'Cannot withdraw team from League: League not found.' );
        }

        $standings_service = $this->get_standings_service();
        if ( empty( $league_obj->is_championship ) && ! $standings_service ) {
            throw new RuntimeException( 'Cannot update standings after team withdrawal: Standings_Service collaborator is required.' );
        }

        $fixture_repository     = $this->get_fixture_repository();
        $fixture_result_manager = $this->get_fixture_result_manager();
        $notification_service   = $this->notification_service;

        $match_criteria = array(
            'team_id' => $team_id,
            'season'  => (string) $season,
            'final'   => '',
            'orderby' => array(
                'date' => 'ASC',
                'id'   => 'ASC',
            ),
        );
        if ( ! empty( $league_obj->is_championship ) ) {
            $match_criteria['pending'] = true;
        }

        $fixture_ids = $this->find_fixture_ids( $league_obj, $match_criteria );
        if ( ! empty( $fixture_ids ) ) {
            if ( ! $fixture_repository ) {
                throw new RuntimeException( 'Cannot withdraw team from League: Fixture_Repository_Interface collaborator is required.' );
            }
            if ( ! $fixture_result_manager ) {
                throw new RuntimeException( 'Cannot withdraw team from League: Fixture_Result_Manager collaborator is required.' );
            }
            foreach ( $fixture_ids as $fixture_id ) {
                $fixture = $fixture_repository->find_by_id( $fixture_id );
                if ( ! $fixture ) {
                    continue;
                }

                $status_code = $fixture->get_status();
                $status      = empty( $status_code ) ? null : Util_Lookup::get_match_status( $status_code );
                if ( 'Withdrawn' !== $status ) {
                    if ( ! empty( $league_obj->is_championship ) ) {
                        if ( intval( $fixture->get_home_team() ) === $team_id ) {
                            $match_status = 'walkover_player2';
                        } else {
                            $match_status = 'walkover_player1';
                        }
                        if ( ( empty( $fixture->get_leg() ) || 2 === (int) $fixture->get_leg() ) && $notification_service ) {
                            $notification_service->notify_team_withdrawal( $fixture, $team_id );
                        }

                        $request = new Fixture_Result_Update_Request( $fixture->get_id(), array(), $match_status, $fixture->get_confirmed() );
                        $fixture_result_manager->handle_fixture_result_update( $fixture, $request );
                    } else {
                        $result_data = array(
                            'home_points' => 0,
                            'away_points' => 0,
                            'status'      => Util_Lookup::get_match_status_code( 'cancelled' ),
                            'custom'      => $fixture->get_custom() ?? array(),
                            'sets'        => array(),
                        );
                        $result      = Result_Factory::from_array( $result_data, (string) $fixture->get_home_team(), (string) $fixture->get_away_team() );
                        $fixture_result_manager->confirm_result( $fixture, '', null, $result );
                    }
                }
            }
        }

        $this->league_repository->withdraw_team( $league_id, $team_id, (string) $season );
        wp_cache_delete( $team_id, '$league_team' );
        wp_cache_delete( $league_id, 'leaguetable' );

        if ( empty( $league_obj->is_championship ) ) {
            $standings_service->update_standings( $league_obj, (string) $season );
            $this->competition_notification_service?->notify_withdrawn_team( $league_obj, $team_id, (string) $season );
        }
    }

    /**
     * Resolve and return the configured league query service.
     *
     * @return League_Query_Service
     *
     * @throws LogicException If the league query service is not configured.
     */
    private function league_query(): League_Query_Service {
        if ( $this->league_query_service instanceof Closure ) {
            $this->league_query_service = ( $this->league_query_service )();
        }
        if ( ! $this->league_query_service instanceof League_Query_Service ) {
            throw new LogicException( 'League_Query_Service is required by League_Service but was not configured.' );
        }
        return $this->league_query_service;
    }

    /**
     * Generate a preview of the league team contact email for the specified season.
     *
     * @param League $league League object.
     * @param string $season Season.
     * @param string $email_title Email title.
     * @param string $email_intro Email intro.
     * @param array  $email_body Email body.
     * @param string $email_close Email close.
     *
     * @return string
     */
    public function get_contact_preview( League $league, string $season, string $email_title, string $email_intro, array $email_body, string $email_close ): string {
        $email_subject = $this->racketmanager->site_name . ' - ' . $league->title . ' ' . $season . ' - ' . __( 'Important Message', 'racketmanager' );

        return strval(
            $this->racketmanager->shortcodes->load_template(
                'contact-teams',
                array(
                    'league'        => $league,
                    'organisation'  => $this->racketmanager->site_name,
                    'season'        => $season,
                    'title_text'    => $email_title,
                    'intro'         => $email_intro,
                    'body'          => $email_body,
                    'closing_text'  => $email_close,
                    'email_subject' => $email_subject,
                ),
                'email'
            )
        );
    }
}
