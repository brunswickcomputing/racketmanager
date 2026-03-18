<?php
/**
 * Championship Manager API: Championship_Manager class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Championship;
use Racketmanager\Domain\League;
use Racketmanager\Repositories\Fixture_Repository;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;

final class Championship_Manager {
    /**
     * @var Result_Service
     */
    private Result_Service $result_service;

    public function __construct( ?Result_Service $result_service = null ) {
        $this->result_service = $result_service ?? new Result_Service( new Fixture_Repository() );
    }

    /**
     * Update final round results and progress competition if needed.
     *
     * @param Championship $championship championship domain object.
     * @param array $matches fixtures.
     * @param array $home_points home points.
     * @param array $away_points away points.
     * @param array $custom custom values.
     * @param int $round round.
     *
     * @return void
     */
    public function update_final_results(
        Championship $championship,
        array $matches,
        array $home_points,
        array $away_points,
        array $custom,
        int $round
    ): void {
        global $racketmanager;

        $fixture_repository = new Fixture_Repository();
        $num_updated = 0;

        foreach ( $matches as $match_id ) {
            $fixture = $fixture_repository->find_by_id( (int) $match_id );
            if ( ! $fixture ) {
                continue;
            }

            $result_data = [
                'home_points' => $home_points[ $match_id ] ?? 0,
                'away_points' => $away_points[ $match_id ] ?? 0,
                'custom'      => $custom[ $match_id ] ?? [],
                'status'      => 0, // Assuming 0 for completed if points are provided
            ];

            $result = Result_Factory::from_array( $result_data );
            $this->result_service->apply_to_fixture( $fixture, $result );
            $num_updated++;
        }

        if ( $round < $championship->num_rounds() ) {
            $this->proceed( $championship, $round );
        }

        /* translators: %d: number of fixtures */
        $racketmanager->set_message( sprintf( __( 'Updated Results of %d fixtures', 'racketmanager' ), $num_updated ) );
    }

    /**
     * Proceed to the next round.
     *
     * @param Championship $championship championship domain object.
     * @param int $round round number.
     * @param League|null $league Optional league object to avoid legacy get_league call.
     *
     * @return void
     */
    public function proceed( Championship $championship, int $round, ?League $league = null ): void {
        if ( $round >= $championship->num_rounds() ) {
            return;
        }

        $current    = $championship->final_key_for_round( $round );
        $next       = $championship->final_key_for_round( $round + 1 );
        $legs       = false;
        $prev_home  = null;
        $prev_away  = null;
        if ( ! $league ) {
            $league = get_league( $championship->league_id() );
        }
        $match_args = array(
            'final' => $next,
            'limit' => false,
        );

        if ( ! empty( $league->current_season['home_away'] ) ) {
            $legs = true;
            if ( 'final' !== $next ) {
                $match_args['leg'] = 1;
            }
        }

        $matches = $league->get_matches( $match_args );

        foreach ( $matches as $match ) {
            $update = true;
            $home   = explode( '_', $match->home_team );
            $away   = explode( '_', $match->away_team );

            if ( is_array( $home ) && is_array( $away ) ) {
                if ( $legs ) {
                    $winner_col = 'winner_id_tie';
                    $loser_col  = 'loser_id_tie';
                } else {
                    $winner_col = 'winner_id';
                    $loser_col  = 'loser_id';
                }

                if ( isset( $home[1] ) ) {
                    $col  = ( '1' === $home[0] ) ? $winner_col : $loser_col;
                    $home = array(
                        'col'      => $col,
                        'finalkey' => $home[1],
                        'no'       => $home[2],
                    );
                } else {
                    $home['no'] = 0;
                }

                if ( isset( $away[1] ) ) {
                    $col  = ( '1' === $away[0] ) ? $winner_col : $loser_col;
                    $away = array(
                        'col'      => $col,
                        'finalkey' => $away[1],
                        'no'       => $away[2],
                    );
                } else {
                    $away['no'] = 0;
                }

                $prev_match_args = array(
                    'final'   => $current,
                    'limit'   => false,
                    'orderby' => array(
                        'id' => 'ASC',
                    ),
                );

                if ( $legs ) {
                    $prev_match_args['leg'] = 2;
                }

                $prev      = $league->get_matches( $prev_match_args );
                $home_team = 0;
                $away_team = 0;

                if ( isset( $prev[ $home['no'] - 1 ] ) ) {
                    $prev_home = $prev[ $home['no'] - 1 ];
                    $home_team = $prev_home->{$home['col']};
                }

                if ( isset( $prev[ $away['no'] - 1 ] ) ) {
                    $prev_away = $prev[ $away['no'] - 1 ];
                    $away_team = $prev_away->{$away['col']};
                }

                if ( empty( $home_team ) && empty( $away_team ) ) {
                    $update = false;
                }

                if ( $update ) {
                    $this->set_teams( $match, (string) $home_team, (string) $away_team );

                    if ( ! empty( $league->event->primary_league ) && $league->event->primary_league === $league->id && $round < 3 ) {
                        if ( ! empty( $prev_home ) ) {
                            $this->set_consolation_team( $prev_home, $current, $league );
                        }
                        if ( ! empty( $prev_away ) ) {
                            $this->set_consolation_team( $prev_away, $current, $league );
                        }
                    }

                    if ( 'third' === $next ) {
                        $final_matches = $league->get_matches(
                            array(
                                'final'   => 'final',
                                'limit'   => false,
                                'orderby' => array(
                                    'id' => 'ASC',
                                ),
                            )
                        );

                        if ( ! empty( $final_matches[0] ) && ! empty( $prev_home ) && ! empty( $prev_away ) ) {
                            $final_match = $final_matches[0];
                            $final_match->set_teams( $prev_home->loser_id, $prev_away->loser_id );
                        }
                    }
                }
            }
        }
    }

    /**
     * Set teams for a match and linked match.
     *
     * @param object $match match object.
     * @param string|null $home_id home team id.
     * @param string|null $away_id away team id.
     *
     * @return void
     */
    public function set_teams( object $match, ?string $home_id, ?string $away_id ): void {
        $match = get_match( $match );
        $match = $match->set_teams( $home_id, $away_id );

        if ( is_numeric( $match->home_team ) && is_numeric( $match->away_team ) ) {
            $match->notify_next_match_teams();
        }

        if ( ! empty( $match->linked_match ) ) {
            $linked_match = get_match( $match->linked_match );
            $linked_match = $linked_match->set_teams( $home_id, $away_id );

            if ( is_numeric( $linked_match->home_team ) && is_numeric( $linked_match->away_team ) ) {
                $linked_match->notify_next_match_teams();
            }
        }
    }

    /**
     * Set consolation teams.
     *
     * @param object $match source match.
     * @param string $round round name.
     * @param object $league league object.
     *
     * @return void
     */
    private function set_consolation_team( object $match, string $round, object $league ): void {
        if ( empty( $match->loser_id ) ) {
            return;
        }

        if ( $match->is_walkover ) {
            $team_switch = '-1';
        } else {
            $team_switch                     = $match->loser_id;
            $match_array                     = array();
            $match_array['team_id']          = $match->loser_id;
            $match_array['final']            = 'all';
            $match_array['reset_query_args'] = true;
            $matches                         = $league->get_matches( $match_array );

            if ( 2 === count( $matches ) ) {
                if ( $matches[0]->id === $match->id ) {
                    $first_match = $matches[1];
                } else {
                    $first_match = $matches[0];
                }

                if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) {
                    $team_switch = '-1';
                }
            }
        }

        $team_ref = '2_' . $round . '_' . $match->id;
        $event    = get_event( $league->event->id );

        if ( $event ) {
            $event_leagues = $event->get_leagues( array( 'consolation' => true ) );

            if ( $event_leagues ) {
                foreach ( $event_leagues as $event_league ) {
                    $consolation_league = get_league( $event_league );

                    if ( '-1' !== $team_switch ) {
                        $switch_teams = $consolation_league->get_league_teams(
                            array(
                                'team_id'          => $team_switch,
                                'reset_query_args' => true,
                            )
                        );

                        if ( ! $switch_teams ) {
                            $consolation_league->add_team( $team_switch, $consolation_league->current_season['name'] );
                        }
                    }

                    $consolation_teams = $consolation_league->get_league_teams(
                        array(
                            'team_name'        => $team_ref,
                            'reset_query_args' => true,
                        )
                    );

                    if ( $consolation_teams ) {
                        $consolation_team    = $consolation_teams[0];
                        $consolation_matches = $consolation_league->get_matches(
                            array(
                                'team_id' => $consolation_team->id,
                                'final'   => 'all',
                            )
                        );

                        if ( $consolation_matches ) {
                            foreach ( $consolation_matches as $consolation_match ) {
                                if ( $consolation_match->home_team === $consolation_team->id ) {
                                    $this->set_teams( $consolation_match, $team_switch, null );
                                } elseif ( $consolation_match->away_team === $consolation_team->id ) {
                                    $this->set_teams( $consolation_match, null, $team_switch );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
