<?php
declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Domain\Fixture\Fixture;
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
use Racketmanager\Services\Result_Factory;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Util\Util_Lookup;
use stdClass;

class League_Service {

    private ?RacketManager $racketmanager;
    private League_Team_Repository_Interface $league_team_repository;
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private Team_Repository_Interface $team_repository;
    private Fixture_Repository_Interface|\Closure|null $fixture_repository;
    private Fixture_Result_Manager|\Closure|null $fixture_result_manager;
    private Standings_Service|\Closure|null $standings_service;
    private ?Notification_Service $notification_service;
    private ?Competition_Notification_Service $competition_notification_service;
    private League_Query_Service|\Closure|null $league_query_service;

    public function __construct(
        ?RacketManager $racketmanager,
        League_Repository_Interface $league_repository,
        Event_Repository_Interface $event_repository,
        League_Team_Repository_Interface $league_team_repository,
        Team_Repository_Interface $team_repository,
        Fixture_Repository_Interface|\Closure|null $fixture_repository = null,
        Fixture_Result_Manager|\Closure|null $fixture_result_manager = null,
        Standings_Service|\Closure|null $standings_service = null,
        ?Notification_Service $notification_service = null,
        ?Competition_Notification_Service $competition_notification_service = null,
        League_Query_Service|\Closure|null $league_query_service = null
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

    private function get_fixture_repository(): ?Fixture_Repository_Interface {
        if ( $this->fixture_repository instanceof \Closure ) {
            $this->fixture_repository = ( $this->fixture_repository )();
        }
        return $this->fixture_repository instanceof Fixture_Repository_Interface ? $this->fixture_repository : null;
    }

    private function get_fixture_result_manager(): ?Fixture_Result_Manager {
        if ( $this->fixture_result_manager instanceof \Closure ) {
            $this->fixture_result_manager = ( $this->fixture_result_manager )();
        }
        return $this->fixture_result_manager instanceof Fixture_Result_Manager ? $this->fixture_result_manager : null;
    }

    private function get_standings_service(): ?Standings_Service {
        if ( $this->standings_service instanceof \Closure ) {
            $this->standings_service = ( $this->standings_service )();
        }
        return $this->standings_service instanceof Standings_Service ? $this->standings_service : null;
    }

    public function add_league_to_event( int $event_id, ?string $name = null ): League {
        $event = $this->event_repository->find_by_id( $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( __( 'Event not found', 'racketmanager' ) );
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

    public function add_team_to_league( int $team_id, int $league_id, int $season ): League_Team {
        $team = $this->team_repository->find_by_id( $team_id );
        if ( ! $team ) {
            throw new Team_Not_Found_Exception( __( 'Team not found', 'racketmanager' ) );
        }
        $league = $this->league_repository->find_by_id( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $existing_league_team = $this->league_team_repository->find_by_team_league_and_season( $team_id, $league_id, $season );
        if ( $existing_league_team ) {
            return $existing_league_team;
        }

        $league_team = League_Team_Factory::from_array( array(
            'league_id' => $league_id,
            'team_id'   => $team_id,
            'season'    => (string) $season,
            'profile'   => Team_Profile::ACTIVE->value,
        ) );
        $this->league_team_repository->save( $league_team );
        return $league_team;
    }

    /**
     * Bulk add teams to a league.
     *
     * @param array<int> $team_ids
     * @param int        $league_id
     * @param int        $season
     *
     * @return int Number of teams added.
     *
     * @throws Team_Not_Found_Exception
     * @throws League_Not_Found_Exception
     */
    public function add_teams_to_league( array $team_ids, int $league_id, int $season ): int {
        $added = 0;
        foreach ( $team_ids as $team_id ) {
            $this->add_team_to_league( $team_id, $league_id, $season );
            $added++;
        }
        return $added;
    }

    /**
     * Remove a team from a league.
     *
     * @param int $team_id
     * @param int $league_id
     * @param int $season
     *
     * @return void
     *
     * @throws Team_Has_Matches_Exception
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

        $matches = $this->league_query()->get_matches( $league,
            array(
                'team_id' => $team_id,
                'season'  => $season,
                'final'   => 'all',
            )
        );

        if ( $matches ) {
            throw new Team_Has_Matches_Exception( __( 'Team has matches and cannot be deleted', 'racketmanager' ) );
        }

        $this->league_team_repository->delete( $league_team->get_id() );
    }

    /**
     * Remove multiple teams from a league (using league_team table IDs).
     *
     * @param array<int> $league_team_ids IDs from the league_teams table.
     * @param int        $league_id
     * @param int        $season
     *
     * @return array List containing [messages => array, any_error => bool]
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

    public function get_league_by_name( string $name ): ?object {
        return $this->league_repository->find_by_name( $name );
    }

    /**
     * Get an eligible consolation teams list.
     *
     * @param League $league
     * @param int $season
     *
     * @return array<int,object>
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

    private function build_loser_team( string $final_name, object $match ): object {
        $team          = new stdClass();
        $team->id      = '2_' . $final_name . '_' . $match->id;
        $team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
        $team->stadium = '';
        return $team;
    }

    public function get_league_standings( ?int $league_id, ?int $season ): array {
        $league = $this->get_league( $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( $league_id );
        }
        return $this->league_team_repository->find_league_standings( $league->get_id(), $season );
    }

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
     * @param array $post Post data containing ranking info.
     * @param array $team_ids IDs of teams to rank.
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

    public function get_ranking( object $league, array $teams ): array {
        $rank      = 1;
        $incr      = 1;
        $new_teams = array();
        foreach ( $teams as $key => $team ) {
            $team->old_rank = $team->get_rank();

            if ( $key > 0 ) {
                $is_tie = false;
                if ( ! $league->is_championship && isset( $team->points ) ) {
                    $standings_service = $this->get_standings_service() ?? throw new \LogicException( 'Standings_Service has not been configured.' );
                    $is_tie = $standings_service->is_tie( $team, $teams[ $key - 1 ] );
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
        $ranks = array_map( function ( $row ) {
            return $row->get_rank();
        }, $new_teams );
        array_multisort( $ranks, SORT_ASC, $new_teams );

        return $new_teams;
    }

    /**
     * Get team status depending on previous rank
     *
     * @param object $team team.
     * @param int $rank rank.
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
     * Update Team Rank and status
     *
     * @param array $teams teams to be ranked.
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

    public function sort_team_ids_random( array $team_ids ): array {
        shuffle( $team_ids );

        return $team_ids;
    }

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
     * Find fixture IDs matching criteria for destructive/bulk league mutations.
     *
     * @param int $league_id
     * @param array $criteria
     * @return int[]
     */
    public function find_fixture_ids( object|int $league, array $criteria = array() ): array {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : $this->league_repository->find_by_id( $league_id );
        $query_args = array_merge(
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
            $fixture_id = is_object( $row ) ? (int) ( $row->id ?? 0 ) : (int) $row;
            if ( $fixture_id > 0 ) {
                $fixture_ids[ $fixture_id ] = $fixture_id;
            }
        }
        return array_values( $fixture_ids );
    }

    /**
     * Delete a league, cascading deletion to all its fixtures and table entries.
     *
     * @param League|object|int $league
     *
     * @throws \RuntimeException
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
                throw new \RuntimeException( 'Cannot delete League: Fixture_Repository_Interface collaborator is required.' );
            }
            $failed_ids = array();
            foreach ( $fixture_ids as $fixture_id ) {
                if ( ! $fixture_repository->delete( $fixture_id ) ) {
                    $failed_ids[] = $fixture_id;
                }
            }
            if ( ! empty( $failed_ids ) ) {
                throw new \RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for league ' . $league_id );
            }
        }

        $this->league_repository->delete_league_teams( $league_id );
        $this->league_repository->delete( $league_id );
        wp_cache_delete( $league_id, 'leagues' );
    }

    /**
     * Delete a team and its fixtures from a league for a given season.
     *
     * @param League|object|int $league
     * @param int $team_id
     * @param string|null $season
     *
     * @throws \RuntimeException
     */
    public function delete_team( object|int $league, int $team_id, ?string $season ): void {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : null;
        if ( $league_id <= 0 || $team_id <= 0 ) {
            return;
        }

        if ( empty( $season ) || '' === trim( (string) $season ) ) {
            throw new \InvalidArgumentException( 'Cannot delete team from League: season is required.' );
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
                throw new \RuntimeException( 'Cannot delete team from League: Fixture_Repository_Interface collaborator is required.' );
            }
            $failed_ids = array();
            foreach ( $fixture_ids as $fixture_id ) {
                if ( ! $fixture_repository->delete( $fixture_id ) ) {
                    $failed_ids[] = $fixture_id;
                }
            }
            if ( ! empty( $failed_ids ) ) {
                throw new \RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for team ' . $team_id . ' in league ' . $league_id );
            }
        }

        $this->league_repository->delete_team( $league_id, $team_id, (string) $season );
        wp_cache_delete( $team_id, '$league_team' );
        wp_cache_delete( $league_id, 'leaguetable' );
    }

    /**
     * Delete all fixtures of a league for a given season.
     *
     * @param League|object|int $league
     * @param string|int $season
     *
     * @throws \RuntimeException
     */
    public function delete_season_fixtures( object|int $league, string|int $season ): void {
        $league_id  = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        $league_obj = is_object( $league ) ? $league : null;
        if ( $league_id <= 0 ) {
            return;
        }

        if ( '' === trim( (string) $season ) ) {
            throw new \InvalidArgumentException( 'Cannot delete season fixtures from League: season is required.' );
        }

        $fixture_repository = $this->get_fixture_repository();
        if ( ! $fixture_repository ) {
            throw new \RuntimeException( 'Cannot delete season matches from League: Fixture_Repository_Interface collaborator is required.' );
        }

        $fixture_ids = $this->find_fixture_ids(
            $league_obj ?? $league_id,
            array(
                'season' => (string) $season,
            )
        );
        $failed_ids = array();
        foreach ( $fixture_ids as $fixture_id ) {
            if ( ! $fixture_repository->delete( $fixture_id ) ) {
                $failed_ids[] = $fixture_id;
            }
        }
        if ( ! empty( $failed_ids ) ) {
            throw new \RuntimeException( 'Failed to delete fixture(s) [' . implode( ', ', $failed_ids ) . '] for season ' . $season . ' in league ' . $league_id );
        }
    }

    /**
     * Alias for delete_season_fixtures.
     *
     * @param League|object|int $league
     * @param string|int $season
     */
    public function delete_season_matches( object|int $league, string|int $season ): void {
        $this->delete_season_fixtures( $league, $season );
    }

    /**
     * Withdraw a team from a league for a season, setting fixtures to withdrawn/walkover and updating standings.
     *
     * @param League|object|int $league
     * @param int $team_id
     * @param string|int $season
     *
     * @throws \RuntimeException
     */
    public function withdraw_team( object|int $league, int $team_id, string|int $season ): void {
        $league_id = is_int( $league ) ? $league : (int) ( method_exists( $league, 'get_id' ) ? $league->get_id() : ( $league->id ?? 0 ) );
        if ( $league_id <= 0 || $team_id <= 0 ) {
            return;
        }

        $league_obj = is_int( $league ) ? $this->league_repository->find_by_id( $league ) : ( $league instanceof League ? $league : $this->league_repository->find_by_id( $league_id ) );

        if ( '' === trim( (string) $season ) ) {
            throw new \InvalidArgumentException( 'Cannot withdraw team from League: season is required.' );
        }

        if ( null === $league_obj ) {
            throw new League_Not_Found_Exception( 'Cannot withdraw team from League: League not found.' );
        }

        $standings_service = $this->get_standings_service();
        if ( empty( $league_obj->is_championship ) && ! $standings_service ) {
            throw new \RuntimeException( 'Cannot update standings after team withdrawal: Standings_Service collaborator is required.' );
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
                throw new \RuntimeException( 'Cannot withdraw team from League: Fixture_Repository_Interface collaborator is required.' );
            }
            if ( ! $fixture_result_manager ) {
                throw new \RuntimeException( 'Cannot withdraw team from League: Fixture_Result_Manager collaborator is required.' );
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
                        if ( empty( $fixture->get_leg() ) || 2 === (int) $fixture->get_leg() ) {
                            if ( $notification_service ) {
                                $notification_service->notify_team_withdrawal( $fixture, $team_id );
                            }
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
            if ( $this->competition_notification_service ) {
                $this->competition_notification_service->notify_withdrawn_team( $league_obj, $team_id, (string) $season );
            }
        }
    }

    private function league_query(): League_Query_Service {
        if ( $this->league_query_service instanceof \Closure ) {
            $this->league_query_service = ( $this->league_query_service )();
        }
        if ( ! $this->league_query_service instanceof League_Query_Service ) {
            throw new \LogicException( 'League_Query_Service is required by League_Service but was not configured.' );
        }
        return $this->league_query_service;
    }

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
