<?php
/**
 * Fixture_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Rubber;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;
use stdClass;

/**
 * Class to implement the Fixture Management Service
 */
class Fixture_Service {

    private RacketManager $racketmanager;
    private Fixture_Repository $fixture_repository;
    private Registration_Service $registration_service;
    private League_Repository $league_repository;
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private Competition_Service $competition_service;
    private Team_Service $team_service;

    public function __construct( RacketManager $plugin_instance, Fixture_Repository $fixture_repository, Registration_Service $registration_service, League_Repository $league_repository, Team_Repository $team_repository, Club_Repository $club_repository, Competition_Service $competition_service, Team_Service $team_service ) {
        $this->racketmanager        = $plugin_instance;
        $this->fixture_repository   = $fixture_repository;
        $this->registration_service = $registration_service;
        $this->league_repository    = $league_repository;
        $this->team_repository      = $team_repository;
        $this->club_repository      = $club_repository;
        $this->competition_service  = $competition_service;
        $this->team_service         = $team_service;
    }

    /**
     * Create a new fixture and associated rubbers
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return Fixture
     */
    public function create_fixture( Fixture $fixture, object $league ): Fixture {
        $this->fixture_repository->save( $fixture );

        if ( ! empty( $league->num_rubbers ) ) {
            $max_rubbers = $this->calculate_max_rubbers( $fixture, $league );
            for ( $ix = 1; $ix <= $max_rubbers; $ix ++ ) {
                $rubber_data = new stdClass();
                $type        = $league->type;
                if ( 'LD' === $league->type ) {
                    if ( 1 === $ix ) {
                        $type = 'WD';
                    } elseif ( 2 === $ix ) {
                        $type = 'MD';
                    } elseif ( 3 === $ix ) {
                        $type = 'XD';
                    }
                }
                $rubber_data->type          = $type;
                $rubber_data->rubber_number = $ix;
                $rubber_data->date          = $fixture->get_date();
                $rubber_data->match_id      = $fixture->get_id();
                new Rubber( $rubber_data );
            }
        }

        // Handle championship leg logic
        if ( ! empty( $league->is_championship ) && ! empty( $league->event->current_season['home_away'] ) && 'final' !== $fixture->get_final() ) {
            $competition_season = $league->event->competition->get_season_by_name( $fixture->get_season() );
            $fixture->set_leg( 1 );
            $this->fixture_repository->save( $fixture );

            $new_fixture = clone $fixture;
            $weeks_diff  = empty( $competition_season['home_away_diff'] ) ? 2 : $competition_season['home_away_diff'];
            $new_fixture->set_date( Util::amend_date( $fixture->get_date(), $weeks_diff, '+', 'weeks' ) );
            $new_fixture->set_linked_match( $fixture->get_id() );
            $new_fixture->set_leg( $fixture->get_leg() + 1 );
            if ( ! empty( $fixture->get_host() ) ) {
                $new_fixture->set_host( 'home' === $fixture->get_host() ? 'away' : 'home' );
            }
            $new_fixture->set_id( null );
            $this->create_fixture( $new_fixture, $league );

            $fixture->set_linked_match( $new_fixture->get_id() );
            $this->fixture_repository->save( $fixture );
        }

        return $fixture;
    }

    /**
     * Calculate maximum rubbers for a fixture
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return int
     */
    private function calculate_max_rubbers( Fixture $fixture, object $league ): int {
        $max_rubbers = (int) $league->num_rubbers;
        if ( ! empty( $league->is_championship ) && ! empty( $league->current_season['home_away'] ) && ! empty( $fixture->get_leg() ) && 2 === $fixture->get_leg() && 'MPL' === $league->event->scoring ) {
            ++ $max_rubbers;
        } elseif ( '1' === $league->event->reverse_rubbers ) {
            $max_rubbers *= 2;
        }

        return $max_rubbers;
    }

    /**
     * Update an existing fixture
     *
     * @param Fixture $fixture
     *
     * @return void
     */
    public function update_fixture( Fixture $fixture ): void {
        $this->fixture_repository->save( $fixture );
    }

    /**
     * Reset finals fixtures for a tournament
     *
     * @param int $tournament_id
     * @param string $fixture_date
     *
     * @return bool
     */
    public function reset_finals_fixtures_for_tournament( int $tournament_id, string $fixture_date ): bool {
        $updates = false;
        $finals  = $this->fixture_repository->find_finals_fixtures_for_tournament( $tournament_id );
        foreach ( $finals as $fixture ) {
            $fixture_changed = $fixture->reset_finals_data( $fixture_date );
            if ( $fixture_changed ) {
                $saved = $this->fixture_repository->save( $fixture );
                if ( $saved ) {
                    $updates = true;
                }
            }
        }

        return $updates;
    }

    /**
     * Update fixture finals data
     *
     * @param int $fixture_id
     * @param string $new_date
     * @param string $court_name
     *
     * @return bool
     */
    public function update_fixture_finals_data( int $fixture_id, string $new_date, string $court_name ): bool {
        $fixture = $this->get_fixture( $fixture_id );
        if ( ! $fixture ) {
            return false;
        }

        $fixture_changed = $fixture->set_finals_data( $new_date, $court_name );
        if ( $fixture_changed ) {
            return $this->fixture_repository->save( $fixture );
        }

        return false;
    }

    /**
     * Get a fixture by ID
     *
     * @param int $fixture_id
     *
     * @return Fixture|null
     */
    public function get_fixture( int $fixture_id ): ?Fixture {
        return $this->fixture_repository->find_by_id( $fixture_id );
    }

    /**
     * Find finals fixtures for a tournament
     *
     * @param int $tournament_id
     *
     * @return Fixture[]
     */
    public function get_finals_fixtures_for_tournament( int $tournament_id ): array {
        return $this->fixture_repository->find_finals_fixtures_for_tournament( $tournament_id );
    }

    public function delete_fixtures_for_season( int $league_id, string $season ): void {
        $this->fixture_repository->delete_by_league_and_season( $league_id, $season );
    }

    /**
     * @param int|null $player_id
     * @param int|null $tournament_id
     *
     * @return Fixture_Details_DTO[]
     */
    public function get_fixtures_for_player_for_tournament( ?int $player_id, ?int $tournament_id ): array {
        $fixtures = $this->fixture_repository->find_fixtures_for_player_by_tournament( $player_id, $tournament_id );

        return array_map( fn( $fixture ) => $this->get_tournament_fixture_with_details( $fixture ), $fixtures );
    }

    public function get_fixture_with_details( int|Fixture|null $fixture_id, bool $is_tournament = false ): ?Fixture_Details_DTO {
        try {
            if ( $fixture_id instanceof Fixture ) {
                $fixture = $fixture_id;
            } else {
                $fixture = $this->get_fixture( $fixture_id );
            }
            $league      = $this->league_repository->find_by_id( $fixture->get_league_id() );
            $event       = $this->competition_service->get_event_by_id( $league->event->id );
            $competition = $this->competition_service->get_by_id( $event->competition->id );

            $home_team = null;
            if ( ! empty( $fixture->get_home_team() ) ) {
                if ( is_numeric( $fixture->get_home_team() ) ) {
                    $home_team = $this->team_service->get_team_details( (int) $fixture->get_home_team() );
                } else {
                    $home_team = $this->team_service->derive_team_details( $fixture->get_home_team() );
                }
            }

            $away_team = null;
            if ( ! empty( $fixture->get_away_team() ) ) {
                if ( is_numeric( $fixture->get_away_team() ) ) {
                    $away_team = $this->team_service->get_team_details( (int) $fixture->get_away_team() );
                } else {
                    $away_team = $this->team_service->derive_team_details( $fixture->get_away_team() );
                }
            }

            $prev_home_match_title = null;
            $prev_away_match_title = null;
            if ( $is_tournament ) {
                if ( ! is_numeric( $fixture->get_home_team() ) ) {
                    $prev_home_match_title = $this->resolve_placeholder_title( $fixture->get_home_team(), $fixture->get_season(), $league, $fixture->get_final() );
                }

                if ( ! is_numeric( $fixture->get_away_team() ) ) {
                    $prev_away_match_title = $this->resolve_placeholder_title( $fixture->get_away_team(), $fixture->get_season(), $league, $fixture->get_final() );
                }
            }
            $is_update_allowed = $this->is_update_allowed( $fixture );

        } catch ( Fixture_Not_Found_Exception|League_Not_Found_Exception|Event_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Fixture_Not_Found_Exception( $e->getMessage() );
        }

        return new Fixture_Details_DTO( $fixture, $league, $event, $competition, $home_team, $away_team, $prev_home_match_title, $prev_away_match_title, $is_update_allowed );

    }

    public function get_tournament_fixture_with_details( int|Fixture|null $fixture_id ): ?Fixture_Details_DTO {
        return $this->get_fixture_with_details( $fixture_id, true );
    }

    /**
     * Resolve placeholder title for fixtures
     *
     * @param string $team_ref
     * @param string $season
     * @param object $league
     * @param string|null $fixture_final
     *
     * @return string|null
     */
    private function resolve_placeholder_title( string $team_ref, string $season, object $league, ?string $fixture_final = null ): ?string {
        $team  = explode( '_', $team_ref );
        $final = $team[1] ?? null;
        if ( empty( $final ) ) {
            // Handle case like 1_semi-final_1 where 1 means Winner, 2 means Loser
            // Or cases where it's not a round key but just something else
            return null;
        }

        $type = match ( $team[0] ) {
            '1' => __( 'Winner', 'racketmanager' ),
            '2' => __( 'Loser', 'racketmanager' ),
            default => null
        };
        $round_name = Util::get_final_name( $final );
        $match_num  = $team[2] ?? '';

        // If it's not the final round, we strictly use "Winner/Loser (Round Name) Match Number"
        if ( 'final' !== $fixture_final ) {
            /* translators: %1$s: type (Winner/Loser), %2$s: round name, %3$s: match number */
            return sprintf( __( '%1$s %2$s %3$s', 'racketmanager' ), $type, $round_name, $match_num );
        }

        $args = [
            'final'   => $final,
            'season'  => $season,
            'orderby' => [ 'id' => 'ASC' ],
        ];

        if ( ! empty( $league->event->current_season['home_away'] ) ) {
            $args['leg'] = '2';
        }

        $prev_matches = $league->get_matches( $args );
        if ( ! $prev_matches ) {
            // If we can't find previous fixtures, we fall back to a descriptive name
            /* translators: %1$s: type (Winner/Loser), %2$s: round name, %3$s: match number */
            return sprintf( __( '%1$s %2$s %3$s', 'racketmanager' ), $type, $round_name, $match_num );
        }

        $match_ref  = (int) ( $team[2] ?? 1 ) - 1;
        $prev_match = $prev_matches[ $match_ref ] ?? null;

        if ( ! $prev_match ) {
            return null;
        }

        // $prev_match is an object with id, date, etc. from League::get_matches
        $prev_fixture = $this->get_fixture( $prev_match->id );
        if ( ! $prev_fixture ) {
            return null;
        }

        $home_team_id = $prev_fixture->get_home_team();
        $away_team_id = $prev_fixture->get_away_team();

        $home_name = __( 'Unknown', 'racketmanager' );
        if ( is_numeric( $home_team_id ) ) {
            $home_team = $this->team_repository->find_by_id( (int) $home_team_id );
            $home_name = $home_team ? $home_team->get_name() : $home_name;
        } else {
            $home_name = $this->resolve_placeholder_title( $home_team_id, $season, $league, $prev_fixture->get_final() );
        }

        $away_name = __( 'Unknown', 'racketmanager' );
        if ( is_numeric( $away_team_id ) ) {
            $away_team = $this->team_repository->find_by_id( (int) $away_team_id );
            $away_name = $away_team ? $away_team->get_name() : $away_name;
        } else {
            $away_name = $this->resolve_placeholder_title( $away_team_id, $season, $league, $prev_fixture->get_final() );
        }

        return sprintf( '%s - %s', $home_name, $away_name );
    }

    /**
     * Check whether match update allowed
     *
     * @param int|Fixture $fixture_id
     *
     * @return object
     */
    public function is_update_allowed( int|Fixture $fixture_id ): object {
        if ( $fixture_id instanceof Fixture ) {
            $fixture = $fixture_id;
        } else {
            $fixture = $this->get_fixture( $fixture_id );
        }
        $home_team_id        = $fixture->get_home_team();
        $away_team_id        = $fixture->get_away_team();
        $home_team           = $this->team_repository->find_by_id( (int) $home_team_id );
        $away_team           = $this->team_repository->find_by_id( (int) $away_team_id );
        $league              = $this->league_repository->find_by_id( $fixture->get_league_id() );
        $competition_type    = $league->event->competition->type;
        $result_status       = $fixture->get_confirmed();
        $user_can_update     = false;
        $user_type           = '';
        $user_team           = '';
        $message             = '';
        $match_approval_mode = false;
        $match_update        = false;

        if ( is_user_logged_in() ) {
            $userid = get_current_user_id();
            if ( $userid ) {
                if ( current_user_can( 'manage_racketmanager' ) ) {
                    $user_type       = 'admin';
                    $user_can_update = true;
                    if ( 'P' === $result_status ) {
                        $match_update = true;
                    }
                } elseif ( empty( $home_team ) || empty( $away_team ) || empty( $home_team->get_club_id() ) || empty( $away_team->get_club_id() ) ) {
                    $message = 'notTeamSet';
                } else {
                    $home_club = $this->club_repository->find( $home_team->get_club_id() );
                    $away_club = $this->club_repository->find( $away_team->get_club_id() );

                    if ( isset( $home_club->match_secretary->id ) && intval( $home_club->match_secretary->id ) === $userid ) {
                        $user_type = 'matchsecretary';
                        $user_team = 'home';
                    } elseif ( isset( $away_club->match_secretary->id ) && intval( $away_club->match_secretary->id ) === $userid ) {
                        $user_type = 'matchsecretary';
                        $user_team = 'away';
                    } elseif ( $fixture->get_home_captain() && intval( $fixture->get_home_captain() ) === $userid ) {
                        $user_type = 'captain';
                        $user_team = 'home';
                    } elseif ( $fixture->get_away_captain() && intval( $fixture->get_away_captain() ) === $userid ) {
                        $user_type = 'captain';
                        $user_team = 'away';
                    } else {
                        $message = 'notCaptain';
                    }

                    $options          = $this->racketmanager->get_options();
                    $match_capability = $options[ $competition_type ]['matchCapability'] ?? 'none';
                    $result_entry     = $options[ $competition_type ]['resultEntry'] ?? 'home';

                    if ( 'none' === $match_capability ) {
                        $message = 'noMatchCapability';
                    } elseif ( 'captain' === $match_capability ) {
                        if ( 'captain' === $user_type || 'matchsecretary' === $user_type ) {
                            if ( 'home' === $user_team ) {
                                if ( 'P' === $result_status || empty( $fixture->get_winner_id() ) ) {
                                    $user_can_update = true;
                                    $match_update    = true;
                                }
                            } elseif ( 'away' === $user_team && 'home' === $result_entry ) {
                                if ( 'P' === $result_status ) {
                                    $user_can_update     = true;
                                    $match_approval_mode = true;
                                }
                            } elseif ( 'either' === $result_entry ) {
                                $user_can_update = true;
                            }
                        }
                    } elseif ( 'player' === $match_capability ) {
                        if ( 'captain' === $user_type || 'matchsecretary' === $user_type ) {
                            if ( 'either' === $result_entry || 'home' === $user_team || ( 'away' === $user_team && 'home' === $result_entry ) ) {
                                if ( 'P' === $result_status ) {
                                    $user_can_update = true;
                                    if ( 'home' === $user_team ) {
                                        if ( empty( $fixture->get_away_captain() ) ) {
                                            $match_update = true;
                                        } elseif ( empty( $fixture->get_home_captain() ) ) {
                                            $match_approval_mode = true;
                                        }
                                    } elseif ( 'away' === $user_team ) {
                                        if ( empty( $fixture->get_home_captain() ) ) {
                                            $match_update = true;
                                        } elseif ( empty( $fixture->get_away_captain() ) ) {
                                            $match_approval_mode = true;
                                        }
                                    }
                                } elseif ( empty( $fixture->get_winner_id() ) ) {
                                    $user_can_update = true;
                                }
                            }
                        } else {
                            $home_club_player = $this->registration_service->is_player_active_in_club( $home_team->get_club_id(), $userid );
                            $away_club_player = $this->registration_service->is_player_active_in_club( $away_team->get_club_id(), $userid );
                            if ( $home_club_player ) {
                                $user_type = 'player';
                                $user_team = 'home';
                            }
                            if ( $away_club_player ) {
                                $user_type = 'player';
                                if ( 'home' === $user_team ) {
                                    $user_team = 'both';
                                } else {
                                    $user_team = 'away';
                                }
                            }
                            if ( $user_team ) {
                                if ( 'home' === $result_entry ) {
                                    if ( empty( $fixture->get_winner_id() ) ) {
                                        if ( 'home' === $user_team || 'both' === $user_team ) {
                                            $user_can_update = true;
                                        }
                                    } elseif ( 'P' === $result_status ) {
                                        if ( 'away' === $user_team || 'both' === $user_team ) {
                                            $user_can_update     = true;
                                            $match_approval_mode = true;
                                        }
                                    }
                                } elseif ( 'either' === $result_entry ) {
                                    if ( 'P' === $result_status ) {
                                        if ( 'home' === $user_team || 'both' === $user_team ) {
                                            if ( empty( $fixture->get_home_captain() ) ) {
                                                $user_can_update     = true;
                                                $match_approval_mode = true;
                                            } elseif ( (int) $fixture->get_home_captain() === $userid ) {
                                                $user_can_update = true;
                                            }
                                        } elseif ( 'away' === $user_team ) {
                                            if ( empty( $fixture->get_away_captain() ) ) {
                                                $user_can_update     = true;
                                                $match_approval_mode = true;
                                            } elseif ( (int) $fixture->get_away_captain() === $userid ) {
                                                $user_can_update = true;
                                            }
                                        }
                                    } elseif ( empty( $fixture->get_winner_id() ) ) {
                                        $user_can_update = true;
                                    }
                                }
                            } else {
                                $message = 'notTeamPlayer';
                            }
                        }
                    }
                }
            } else {
                $message = 'notLoggedIn';
            }
        } else {
            $message = 'notLoggedIn';
        }

        $return                      = new stdClass();
        $return->user_can_update     = $user_can_update;
        $return->user_type           = $user_type;
        $return->user_team           = $user_team;
        $return->message             = $message;
        $return->match_approval_mode = $match_approval_mode;
        $return->match_update        = $match_update;

        return $return;
    }

}
