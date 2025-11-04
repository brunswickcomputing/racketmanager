<?php
/**
 * League_Tennis class
 *
 * @package Racketmanager/Classes/Sports/Tennis
 */

namespace Racketmanager\sports;

use Racketmanager\Domain\League;
use Racketmanager\util\Util;
use function Racketmanager\get_league;
use function Racketmanager\get_match;

/**
 * Tennis league class
 */
class League_Tennis extends League {

    /**
     * Sports key
     *
     * @var string
     */
    public string $sport = 'tennis';

    /**
     * Default number of sets
     *
     * @var int
     */
    public int $num_sets = 3;

    /**
     * Create class instance
     *
     * @param object $league league object.
     *
     * @return void
     */
    public function __construct( object $league ) {
        parent::__construct( $league );
        add_filter( 'racketmanager_team_points_' . $this->sport, array( &$this, 'calculate_team_points' ), 10, 4 );
    }

    /**
     * Calculate Points: add match score
     *
     * @param array $points points.
     * @param int $team_id team.
     * @param array $point_rule rule.
     * @param array $matches matches.
     *
     * @return array
     */
    public function calculate_team_points( array $points, int $team_id, array $point_rule, array $matches ): array {
        if ( $matches ) {
            $points_for     = 0;
            $points_against = 0;
            foreach ( $matches as $match ) {
                if ( $match->home_team === strval( $team_id ) ) {
                    $points_for     += $match->home_points;
                    $points_against += $match->away_points;
                } elseif ( $match->away_team === strval( $team_id ) ) {
                    $points_for     += $match->away_points;
                    $points_against += $match->home_points;
                }
            }
            $points['plus']  = $points_for;
            $points['minus'] = $points_against;
        } else {
            $forwin             = empty( $point_rule['forwin'] ) ? 0 : $point_rule['forwin'];
            $forwin_split       = empty( $point_rule['forwin_split'] ) ? 0 : $point_rule['forwin_split'];
            $forloss_split      = empty( $point_rule['forloss_split'] ) ? 0 : $point_rule['forloss_split'];
            $forshare           = empty( $point_rule['forshare'] ) ? 0 : $point_rule['forshare'];
            $forwalkover_rubber = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
            $walkover_penalty   = empty( $point_rule['forwalkover_match'] ) ? 0 : $point_rule['forwalkover_match'];
            $rubber_win         = empty( $point_rule['rubber_win'] ) ? 0 : $point_rule['rubber_win'];
            $rubber_draw        = empty( $point_rule['rubber_draw'] ) ? 0 : $point_rule['rubber_draw'];
            $matches_win        = empty( $point_rule['matches_win'] ) ? 0 : $point_rule['matches_win'];
            $matches_draw       = empty( $point_rule['matches_draw'] ) ? 0 : $point_rule['matches_draw'];
            $shared_match       = empty( $point_rule['shared_match'] ) ? 0 : $point_rule['shared_match'];
            $data               = $this->get_standings_data( $team_id, array(), $matches );
            if ( ! empty( $point_rule['match_result'] ) ) {
                if ( 'rubber_count' === $point_rule['match_result'] ) {
                    $points['plus'] = $data['rubbers_won'] * $rubber_win + $data['rubbers_shared'] * $rubber_draw - ( $data['no_player'] * $forwalkover_rubber ) - $data['no_team'] * $walkover_penalty + $data['matches_shared'] * $shared_match;
                } elseif ( 'games' === $point_rule['match_result'] ) {
                    $points['plus'] = $data['games_won'];
                }
            } else {
                $points['plus']  = $data['sets_won'] + ( $data['straight_set']['win'] * $forwin ) + ( $data['split_set']['win'] * $forwin_split ) + ( $data['split_set']['lost'] * $forloss_split ) + ( $data['sets_shared'] * $forshare ) - ( $data['no_player'] * $forwalkover_rubber ) - ( $data['no_team'] * $walkover_penalty ) + ( $data['matches_won'] * $matches_win ) + ( $data['matches_shared'] * $matches_draw );
                $points['minus'] = $data['sets_allowed'] + ( $data['straight_set']['lost'] * $forwin ) + ( $data['split_set']['win'] * $forloss_split ) + ( $data['split_set']['lost'] * $forwin_split ) + ( $data['sets_shared'] * $forshare );
            }
        }
        return $points;
    }

    /**
     * Rank Teams
     *
     * @param array $teams team array.
     *
     * @return array of teams
     */
    protected function rank_teams( array $teams ): array {
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
            $points[ $key ]        = $team->points['plus'];
            $sets_diff[ $key ]     = $team_sets_won - $team_sets_allowed;
            $sets_won[ $key ]      = $team_sets_won;
            $sets_allowed[ $key ]  = $team_sets_allowed;
            $games_diff[ $key ]    = $team_games_won - $team_games_allowed;
            $games_won[ $key ]     = $team_games_won;
            $games_allowed[ $key ] = $team_games_allowed;
            if ( 'W' === $team->status ) {
                $status[ $key ] = $team->status;
            } else {
                $status[ $key ] = null;
            }
            $title[ $key ] = $team->title;
        }
        array_multisort( $status, SORT_ASC, $points, SORT_DESC, $sets_diff, SORT_DESC, $games_diff, SORT_DESC, $sets_won, SORT_DESC, $sets_allowed, SORT_ASC, $games_won, SORT_DESC, $games_allowed, SORT_ASC, $title, SORT_ASC, $teams );

        return $teams;
    }

    /**
     * Get standings data for given team
     *
     * @param int   $team_id team.
     * @param array $data data.
     * @param array $matches matches.
     *
     * @return array number of runs for and against as associative array
     */
    protected function get_standings_data( int $team_id, array $data, array $matches = array() ): array {
        global $league;

        $data['straight_set']   = array(
            'win'  => 0,
            'lost' => 0,
        );
        $data['split_set']      = $data['straight_set'];
        $data['games_allowed']  = 0;
        $data['games_won']      = 0;
        $data['sets_won']       = 0;
        $data['sets_allowed']   = 0;
        $data['sets_shared']    = 0;
        $data['no_player']      = 0;
        $data['no_team']        = 0;
        $data['rubbers_won']    = 0;
        $data['rubbers_shared'] = 0;
        $data['matches_won']    = 0;
        $data['matches_shared'] = 0;

        $league         = get_league( $this->id );
        $walkover_sets  = $league->num_sets_to_win;
        $set_type       = Util::get_set_type( $league->scoring );
        $set_info       = Util::get_set_info( $set_type );
        $games_to_win   = $set_info->min_win;
        $walkover_games = $walkover_sets * $games_to_win;

        $season = $league->get_season();

        if ( ! $matches ) {
            $matches = $this->get_matches_for_standings( $season, $team_id );
        }
        foreach ( $matches as $match ) {
            $team_ref       = strval( $team_id ) === $match->home_team ? 'home' : 'away';
            $team_ref_alt   = 'home' === $team_ref ? 'away' : 'home';
            $player_ref     = strval( $team_id ) === $match->home_team ? 'player1' : 'player2';
            $player_ref_alt = 'player1' === $player_ref ? 'player2' : 'player1';
            $match          = get_match( $match );
            if ( ! empty( $match->winner_id ) && ! empty( $match->loser_id ) && 'W' !== $match->teams['home']->status && 'W' !== $match->teams['away']->status && ! $match->is_cancelled ) {
                if ( ! empty( $match->status ) && 3 === $match->status ) {
                    ++$data['matches_shared'];
                }
                if ( ! empty( $league->num_rubbers ) ) {
                    $rubbers_won    = 0;
                    $rubbers_lost   = 0;
                    $rubbers_shared = 0;
                    $rubbers        = $match->get_rubbers();
                    $walkovers      = 0;
                    foreach ( $rubbers as $rubber ) {
                        if ( ! $rubber->is_walkover && ! $rubber->is_shared ) {
                            $num_sets    = count( $rubber->sets );
                            $set_retired = null;
                            if ( $rubber->is_retired || $rubber->is_abandoned ) {
                                for ( $s1 = $num_sets; $s1 > 0; $s1-- ) {
                                    if ( '' !== $rubber->sets[ $s1 ]['player1'] || '' !== $rubber->sets[ $s1 ]['player2'] ) {
                                        $set_retired = $s1;
                                        break;
                                    }
                                }
                            }
                            for ( $j = 1; $j <= $league->num_sets; $j++ ) {
                                $set_type = Util::get_set_type( $league->scoring, null, $league->num_sets, $j );
                                if ( isset( $rubber->sets[ $j ]['player1'] ) && null !== $rubber->sets[ $j ]['player1'] ) {
                                    $set        = $rubber->sets[ $j ];
                                    $set_winner = null;
                                    if ( $rubber->is_retired && $set_retired === $j ) {
                                        if ( $team_ref === $rubber->custom['retired'] ) {
                                            $set_winner = $team_ref_alt;
                                        } else {
                                            $set_winner = $team_ref;
                                        }
                                    }
                                    if ( $rubber->is_abandoned && $set_retired === $j ) {
                                        $set_winner = 'abandoned';
                                    }
                                    if ( is_numeric( trim( $set[ $player_ref_alt ] ) ) ) {
                                        if ( 'MTB' === $set_type ) {
                                            if ( $rubber->loser_id === strval( $team_id ) ) {
                                                ++$data['games_allowed'];
                                            }
                                        } else {
                                            $data['games_allowed'] += intval( $set[ $player_ref_alt ] );
                                        }
                                    }
                                    if ( is_numeric( trim( $set[ $player_ref ] ) ) ) {
                                        if ( 'MTB' === $set_type ) {
                                            if ( $rubber->winner_id === strval( $team_id ) ) {
                                                ++$data['games_won'];
                                            }
                                        } else {
                                            $data['games_won'] += intval( $set[ $player_ref ] );
                                        }
                                    }
                                    if ( ( $set[ $player_ref ] > $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref === $set_winner ) {
                                        $data['sets_won'] += 1;
                                    } elseif ( ( $set[ $player_ref ] < $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
                                        $data['sets_allowed'] += 1;
                                    } elseif ( 'S' === strtoupper( $set[ $player_ref ] ) || $rubber->is_abandoned ) {
                                        $data['sets_shared'] += 1;
                                    }
                                }
                            }
                        } elseif ( $rubber->is_shared ) {
                            $data['sets_shared'] += $league->num_sets;
                            ++$data['rubbers_shared'];
                            ++$rubbers_shared;
                        }
                        if ( $rubber->winner_id === strval( $team_id ) || '-1' === $rubber->winner_id ) { // winning team.
                            if ( $rubber->winner_id === strval( $team_id ) ) {
                                ++$data['rubbers_won'];
                                ++$rubbers_won;
                            }
                            if ( $rubber->is_walkover ) {
                                $data['sets_won']            += $walkover_sets;
                                $data['games_won']           += $walkover_games;
                                $data['straight_set']['win'] += 1;
                            } elseif ( $match->home_team === strval( $team_id ) ) {   // home team.
                                if ( $data['sets_won'] > '0' ) {
                                    if ( $rubber->away_points > '0' ) {
                                        $data['split_set']['win'] += 1;
                                    } else {
                                        $data['straight_set']['win'] += 1;
                                    }
                                }
                            } elseif ( $rubber->home_points > '0' ) { // away team split set win.
                                if ( $data['sets_won'] > '0' ) {
                                    $data['split_set']['win'] += 1;       // home team got a set.
                                }
                            } elseif ( $data['sets_won'] > '0' ) {                                  // home team straight set win.
                                $data['straight_set']['win'] += 1;
                            }
                        } elseif ( $rubber->loser_id === strval( $team_id ) ) { // losing team.
                            ++$rubbers_lost;
                            if ( $rubber->is_walkover ) {
                                $data['sets_allowed']         += $walkover_sets;
                                $data['games_allowed']        += $walkover_games;
                                $data['no_player']            += 1;
                                $data['straight_set']['lost'] += 1;
                                ++$walkovers;
                            } elseif ( $match->home_team === strval( $team_id ) ) {   // team loss.
                                if ( $rubber->home_points > '0' ) {
                                    $data['split_set']['lost'] += 1;
                                } else {
                                    $data['straight_set']['lost'] += 1;
                                }
                            } elseif ( $rubber->away_points > '0' ) { // team split set loss.
                                $data['split_set']['lost'] += 1;
                            } else {                                 // team straight set loss.
                                $data['straight_set']['lost'] += 1;
                            }
                        }
                    }
                    if ( intval( $match->league->num_rubbers ) === $walkovers ) {
                        $data['no_team'] += $walkovers;
                    }
                    if ( $rubbers_shared ) {
                        if ( $rubbers_won === $rubbers_lost ) {
                            ++$data['matches_shared'];
                        } elseif ( $rubbers_won > $rubbers_lost ) {
                            ++$data['matches_won'];
                        }
                    } elseif ( $rubbers_won > $rubbers_lost ) {
                        ++$data['matches_won'];
                    }
                } else {
                    $set_winner = null;
                    for ( $j = 1; $j <= $league->num_sets; $j++ ) {
                        $set = $match->sets[ $j ];
                        if ( ( $set[ $player_ref ] > $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref === $set_winner ) {
                            $data['sets_won'] += 1;
                        } elseif ( ( $set[ $player_ref ] < $set[ $player_ref_alt ] && empty( $set_winner ) ) || $team_ref_alt === $set_winner ) {
                            $data['sets_allowed'] += 1;
                        } elseif ( 'S' === strtoupper( $set[ $player_ref ] ) ) {
                            $data['sets_shared'] += 1;
                        }
                        $data['games_allowed'] += $match->sets[ $j ][ $player_ref_alt ];
                        $data['games_won']     += $match->sets[ $j ][ $player_ref ];
                    }
                    if ( $league->num_sets > 1 && '' !== $match->sets[ $league->num_sets ]['player1'] && '' !== $match->sets[ $league->num_sets ]['player2'] ) {
                        if ( $match->winner_id === strval( $team_id ) ) {
                            $data['split_set']['win'] += 1;
                        } elseif ( $match->loser_id === strval( $team_id ) ) {
                            $data['split_set']['lost'] += 1;
                        }
                    } elseif ( $match->winner_id === strval( $team_id ) ) {
                        $data['straight_set']['win'] += 1;
                    } elseif ( $match->loser_id === strval( $team_id ) ) {
                        $data['straight_set']['lost'] += 1;
                    }
                }
            }
        }
        return $data;
    }
    /**
     * Get matches for standings function
     *
     * @param string $season season.
     * @param int $team team id.
     *
     * @return array of matches.
     */
    private function get_matches_for_standings( string $season, int $team ): array {
        global $league;
        return $league->get_matches(
            array(
                'season'           => $season,
                'team_id'          => $team,
                'final'            => '',
                'limit'            => false,
                'cache'            => false,
                'home_points'      => 'not null',
                'away_points'      => 'not null',
                'reset_query_args' => true,
                'confirmed'        => true,
                'withdrawn'        => false,
            )
        );
    }
    /**
     * Update match results and automatically calculate score
     *
     * @param object $match match details.
     *
     * @return object $match
     */
    protected function update_results( object $match ): object {
        $match = get_match( $match );

        // exit if only one team is set.
        if ( '-1' === $match->home_team || '-1' === $match->away_team ) {
            return $match;
        }

        if ( empty( $match->home_points ) && empty( $match->away_points ) ) {
            $score = array(
                'home' => '0',
                'away' => '0',
            );
            if ( isset( $match->league->num_rubbers ) && $match->league->num_rubbers > 0 ) {
                $rubbers = $match->get_rubbers();

                foreach ( $rubbers as $rubber ) {
                    if ( is_numeric( $rubber->home_points ) ) {
                        $score['home'] += intval( $rubber->home_points );
                    }
                    if ( is_numeric( $rubber->away_points ) ) {
                        $score['away'] += intval( $rubber->away_points );
                    }
                }
            } else {
                foreach ( $match->sets as $set ) {
                    if ( isset( $set['player1'] ) && isset( $set['player2'] ) ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            $score['home'] += 1;
                        } else {
                            $score['away'] += 1;
                        }
                    }
                }
            }
            $match->home_points = $score['home'];
            $match->away_points = $score['away'];
            $match->get_result( $match->home_points, $match->away_points, $match->custom );
        }
        return $match;
    }

    /**
     * Determine if two teams are tied based on
     *
     * 1) Primary points
     * 2) sets difference
     * 3) games difference
     * 4) sets won
     *
     * @param object $team1 first team.
     * @param object $team2 second team.
     *
     * @return boolean
     */
    protected function is_tie( object $team1, object $team2 ): bool {
        // initialize results array.

        $res = array(
            'primary'    => false,
            'sets_diff'  => false,
            'games_diff' => false,
            'sets_won'   => false,
        );

        if ( $team1->points['plus'] === $team2->points['plus'] ) {
            $res['primary'] = true;
        }
        if ( ( $team1->sets_won - $team1->sets_allowed ) === ( $team2->sets_won - $team2->sets_allowed ) ) {
            $res['sets_diff'] = true;
        }
        if ( ( $team1->games_won - $team1->games_allowed ) === ( $team2->games_won - $team2->games_allowed ) ) {
            $res['sets_diff'] = true;
        }
        if ( $team1->sets_won === $team2->sets_won ) {
            $res['sets_won'] = true;
        }

        // get unique results.
        $res = array_values( array_unique( $res ) );

        // more than one results, i.e. not tied.
        if ( count( $res ) > 1 ) {
            return false;
        }

        return $res[0];
    }
}
