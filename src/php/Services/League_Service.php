<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\League;
use Racketmanager\Domain\League_Team;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Team_Repository;
use stdClass;

class League_Service {

    private RacketManager $racketmanager;
    private League_Team_Repository $league_team_repository;
    private League_Repository $league_repository;
    private Event_Repository $event_repository;
    private Team_Repository $team_repository;

    public function __construct( RacketManager $plugin_instance, League_Repository $league_repository, Event_Repository $event_repository, League_Team_Repository $league_team_repository, Team_Repository $team_repository ) {
        $this->racketmanager          = $plugin_instance;
        $this->league_repository      = $league_repository;
        $this->event_repository       = $event_repository;
        $this->league_team_repository = $league_team_repository;
        $this->team_repository        = $team_repository;
    }

    public function add_league_to_event( int $event_id, ?string $name ): League {
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
        $league           = new League( $league );
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

        $league_team = new League_Team();
        $league_team->set_league_id( $league_id );
        $league_team->set_team_id( $team_id );
        $league_team->set_season( $season );
        $league_team->set_entered_state( Team_Profile::ACTIVE );
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
     * Remove team from league.
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

        $matches = $league->get_matches(
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

    public function get_league( ?int $league_id ): ?object {
        return $this->league_repository->find_by_id( $league_id );
    }

    /**
     * Get an eligible consolation teams list.
     *
     * @param League $league
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
            $matches_count                   = $primary_league->get_matches( $match_array );

            if ( ! $matches_count ) {
                unset( $teams[ $key ] );
                continue;
            }

            $match_array['loser_id'] = null;
            $match_array['team_id']  = $team->id;
            $matches_count           = $primary_league->get_matches( $match_array );
            $last_match              = null;

            if ( $matches_count > 2 ) {
                unset( $teams[ $key ] );
                continue;
            }

            if ( 2 === $matches_count ) {
                $match_array['count'] = false;
                $matches              = $primary_league->get_matches( $match_array );
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
                $matches              = $primary_league->get_matches( $match_array );
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
        $matches                         = $primary_league->get_matches( $match_array );

        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $teams[] = $this->build_loser_team( $final_name, $match );
            }
        }

        $final_name           = $primary_league->championship->get_final_keys( 2 );
        $match_array['final'] = $final_name;
        $matches              = $primary_league->get_matches( $match_array );

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
                        $team_matches             = $primary_league->get_matches( $match_array );
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
}
