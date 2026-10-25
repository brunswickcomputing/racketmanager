<?php
/**
 * Championship Manager API: Championship_Manager class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result_Factory;
use Racketmanager\Services\Result_Service;

final class Championship_Manager {

    public function __construct(
        private Result_Service $result_service,
        private Fixture_Repository_Interface $fixture_repository,
        private League_Service $league_service,
        private Event_Repository_Interface $event_repository,
        private ?Notification_Service $notification_service = null
    ) {
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
     * @return int Number of updated fixtures.
     */
    public function update_final_results(
        Championship $championship,
        array $matches,
        array $home_points,
        array $away_points,
        array $custom,
        int $round
    ): int {
        $num_updated = 0;

        foreach ( $matches as $match_id ) {
            $fixture = $this->fixture_repository->find_by_id( (int) $match_id );
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

        return $num_updated;
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
            $league = $this->league_service->get_league( $championship->league_id() );
        }

        $matches = $this->fixture_repository->find_by_league_and_final(
            $league->get_id(),
            $league->get_season(),
            $next,
            ! empty( $league->current_season['home_away'] ) && 'final' !== $next ? 1 : null
        );

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

                $prev = $this->fixture_repository->find_by_league_and_final(
                    $league->get_id(),
                    $league->get_season(),
                    $current,
                    ! empty( $league->current_season['home_away'] ) ? 2 : null
                );
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
                        $final_matches = $this->fixture_repository->find_by_league_and_final(
                            $league->get_id(),
                            $league->get_season(),
                            'final'
                        );

                        if ( ! empty( $final_matches[0] ) && ! empty( $prev_home ) && ! empty( $prev_away ) ) {
                            $final_match = $final_matches[0];
                            $this->set_teams( $final_match, (string) $prev_home->loser_id, (string) $prev_away->loser_id );
                        }
                    }
                }
            }
        }
    }

    /**
     * Set teams for a match and linked match.
     *
     * @param object|int $match match object or fixture ID.
     * @param string|null $home_id home team id.
     * @param string|null $away_id away team id.
     *
     * @return void
     */
    public function set_teams( object|int $match, ?string $home_id, ?string $away_id ): void {
        $fixture_id = is_object( $match ) ? (int) $match->id : (int) $match;
        $fixture    = $this->fixture_repository->find_by_id( $fixture_id );
        if ( ! $fixture ) {
            return;
        }

        if ( null !== $home_id && '' !== $home_id ) {
            $fixture->set_home_team( $home_id );
        }
        if ( null !== $away_id && '' !== $away_id ) {
            $fixture->set_away_team( $away_id );
        }

        $this->fixture_repository->save( $fixture );

        if ( is_numeric( $fixture->get_home_team() ) && is_numeric( $fixture->get_away_team() ) ) {
            if ( $this->notification_service ) {
                $this->notification_service->send_next_fixture_notification( $fixture );
            }
        }

        $linked_fixture_id = $fixture->get_linked_fixture();
        if ( ! empty( $linked_fixture_id ) ) {
            $linked_fixture = $this->fixture_repository->find_by_id( (int) $linked_fixture_id );
            if ( $linked_fixture ) {
                if ( null !== $home_id && '' !== $home_id ) {
                    $linked_fixture->set_home_team( $home_id );
                }
                if ( null !== $away_id && '' !== $away_id ) {
                    $linked_fixture->set_away_team( $away_id );
                }

                $this->fixture_repository->save( $linked_fixture );

                if ( is_numeric( $linked_fixture->get_home_team() ) && is_numeric( $linked_fixture->get_away_team() ) ) {
                    if ( $this->notification_service ) {
                        $this->notification_service->send_next_fixture_notification( $linked_fixture );
                    }
                }
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
            $team_switch = $match->loser_id;
            $matches = $this->fixture_repository->find_by_league_and_team(
                $league->get_id(),
                $league->get_season(),
                (string)$match->loser_id
            );

            if ( 2 === count( $matches ) ) {
                if ( $matches[0]->get_id() === $match->id ) {
                    $first_match = $matches[1];
                } else {
                    $first_match = $matches[0];
                }

                if ( '-1' !== $first_match->get_home_team() && '-1' !== $first_match->get_away_team() ) {
                    $team_switch = '-1';
                }
            }
        }

        $team_ref = '2_' . $round . '_' . $match->id;
        $event_id = isset( $league->event->id ) ? (int) $league->event->id : (int) ( method_exists( $league, 'get_event_id' ) ? $league->get_event_id() : 0 );
        $event = $this->event_repository->find_by_id( $event_id );

        if ( $event ) {
            $event_leagues = $this->event_repository->query_leagues( $event_id, array( 'consolation' => true ) );

            if ( is_array( $event_leagues ) ) {
                foreach ( $event_leagues as $event_league ) {
                    $consolation_league = $this->league_service->get_league( (int) ( $event_league->id ?? $event_league ) );
                    if ( ! $consolation_league ) {
                        continue;
                    }

                    if ( '-1' !== $team_switch ) {
                        $switch_teams = \Racketmanager\get_league_query_service()->get_league_teams( $consolation_league,
                            array(
                                'team_id'          => $team_switch,
                                'reset_query_args' => true,
                            )
                        );

                        if ( ! $switch_teams ) {
                            \Racketmanager\get_league_query_service()->add_team( $consolation_league, $team_switch, $consolation_league->current_season['name'] );
                        }
                    }

                    $consolation_teams = \Racketmanager\get_league_query_service()->get_league_teams( $consolation_league,
                        array(
                            'team_name'        => $team_ref,
                            'reset_query_args' => true,
                        )
                    );

                    if ( $consolation_teams ) {
                        $consolation_team    = $consolation_teams[0];
                        $consolation_matches = $this->fixture_repository->find_by_league_and_team(
                            $consolation_league->get_id(),
                            $league->get_season(),
                            (string)$consolation_team->id
                        );

                        if ( $consolation_matches ) {
                            foreach ( $consolation_matches as $consolation_match ) {
                                if ( $consolation_match->get_home_team() === (string)$consolation_team->id ) {
                                    $this->set_teams( $consolation_match, (string)$team_switch, null );
                                } elseif ( $consolation_match->get_away_team() === (string)$consolation_team->id ) {
                                    $this->set_teams( $consolation_match, null, (string)$team_switch );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
