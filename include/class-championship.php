<?php
/**
 * Championship object
 *
 * @package Racketmanager/Classes
 */

namespace Racketmanager;

/**
* Implement Championship mode
*
* @author   Kolja Schleich
* @author  Paul Moffat
* @package  RacketManager
* @subpackage Championship
*/
final class Championship {
    /**
     * League ID
     *
     * @var int
     */
    public int $league_id = 0;

    /**
     * Preliminary groups
     *
     * @var array
     */
    public array $groups = array();

    /**
     * Number of preliminary groups
     *
     * @var int
     */
    public int $num_group = 0;

    /**
     * Number of teams per group
     *
     * @var int
     */
    public int $team_per_group = 0;

    /**
     * Number of teams to advance to final rounds
     *
     * @var int
     */
    public mixed $num_advance = 0;

    /**
     * Number of final rounds
     *
     * @var int
     */
    public mixed $num_rounds = 0;

    /**
     * Number of teams in first round
     *
     * @var int
     */
    public int $num_teams_first_round = 0;

    /**
     * Final keys indexed by round
     *
     * @var array
     */
    private array $keys = array();

    /**
     * Finals indexed by key
     *
     * @var array
     */
    public array $finals = array();

    /**
     * Current final key
     *
     * @var string
     */
    public string $current_final = '';

    /**
     * Array of final team names
     *
     * @var array
     */
    public array $final_teams = array();

    /**
     * Image of cup icon
     *
     * @var string
     */
    public string $cup_icon = '';
    /**
     * Number of teams per group
     *
     * @var int
     */
    public int $teams_per_group;
    /**
     * Number of groups
     *
     * @var int
     */
    public int $num_groups;
    /**
     * Number of teams
     *
     * @var int
     */
    public int $num_teams;
    /**
     * Is consolation
     *
     * @var boolean
     */
    public bool $is_consolation;
    /**
     * Number of seeds
     *
     * @var int
     */
    public int $num_seeds;
    /**
     * Initialize Championship Mode
     *
     * @param object $league league object.
     * @param array $settings array of settings.
     */
    public function __construct( object $league, array $settings ) {
        $this->league_id      = $league->id;
        $this->is_consolation = false;
        if ( ! empty( $league->event->primary_league ) && intval( $this->league_id ) !== intval( $league->event->primary_league ) ) {
            $this->is_consolation = true;
        }
        if ( isset( $settings['groups'] ) && is_array( $settings['groups'] ) ) {
            $this->groups = $settings['groups'];
        }
        $this->teams_per_group = isset( $settings['teams_per_group'] ) ? intval( $settings['teams_per_group'] ) : 4;
        $this->num_groups      = count( $this->groups );
        if ( $this->num_groups > 0 ) {
            $this->num_advance           = $settings['num_advance'] ?? 0;
            $this->num_teams_first_round = $this->num_groups * $this->num_advance;
            $this->num_rounds            = log( $this->num_teams_first_round, 2 );
        } else {
            $num_teams       = $league->num_teams_total;
            $this->num_teams = $num_teams;
            if ( $this->is_consolation ) {
                $primary_league        = get_league( $league->event->primary_league );
                $max_rounds            = $primary_league->championship->num_rounds - 1;
                $max_teams_first_round = pow( 2, $max_rounds );
                $first_round           = $primary_league->championship->get_final_keys( 1 );
                $outstanding_matches   = $primary_league->get_matches(
                    array(
                        'pending'          => true,
                        'final'            => $first_round,
                        'count'            => true,
                        'season'           => $league->current_season['name'],
                        'reset_query_args' => true,
                    )
                );
                if ( $outstanding_matches || $num_teams > $max_teams_first_round ) {
                    $this->num_teams  = 0;
                    $this->num_rounds = $max_rounds;
                } else {
                    $this->num_rounds = ceil( log( $num_teams, 2 ) );
                }
                $this->num_teams_first_round = pow( 2, $this->num_rounds );
                $this->num_advance           = $this->num_teams_first_round;
            } else {
                $this->num_advance = pow( 2, $league->current_season['num_match_days'] );
                if ( $league->event->competition->is_active || $league->event->competition->is_complete ) {
                    $use_teams = true;
                } elseif ( $num_teams > $this->num_advance ) {
                    $use_teams = true;
                } else {
                    $use_teams = false;
                }
                if ( $use_teams ) {
                    $this->num_rounds            = ceil( log( $num_teams, 2 ) );
                    $this->num_teams_first_round = pow( 2, $this->num_rounds );
                } else {
                    $this->num_teams_first_round = $this->num_advance;
                    $this->num_rounds            = $league->current_season['num_match_days'];
                }
            }
        }
        if ( $this->is_consolation ) {
            $this->num_seeds = 0;
        } elseif ( $league->num_teams_total <= 10 ) {
            $this->num_seeds = 2;
        } elseif ( $league->num_teams_total <= 20 ) {
            $this->num_seeds = 4;
        } elseif ( $league->num_teams_total <= 40 ) {
            $this->num_seeds = 8;
        } elseif ( $league->num_teams_total <= 80 ) {
            $this->num_seeds = 16;
        } elseif ( $league->num_teams_total <= 132 ) {
            $this->num_seeds = 32;
        } else {
            $this->num_seeds = 0;
        }
        $num_teams = 2;
        $i         = $this->num_rounds;
        while ( $num_teams <= $this->num_teams_first_round ) {
            $finalkey                  = $this->get_final_key( $num_teams );
            $num_matches               = $num_teams / 2;
            $is_final                  = 'final' === $finalkey;
            $this->finals[ $finalkey ] = array(
                'key'         => $finalkey,
                'is_final'    => $is_final,
                'name'        => Racketmanager_Util::get_final_name( $finalkey ),
                'num_matches' => $num_matches,
                'num_teams'   => $num_teams,
                'colspan'     => ( $this->num_teams_first_round / 2 >= 4 ) ? ceil( 4 / $num_matches ) : ceil( ( $this->num_teams_first_round / 2 ) / $num_matches ),
                'round'       => $i,
            );

            // Separately add match for third place.
            if ( 2 === $num_teams && ( isset( $settings['match_place3'] ) && 1 === $settings['match_place3'] ) ) {
                $finalkey                  = 'third';
                $this->finals[ $finalkey ] = array(
                    'key'         => $finalkey,
                    'name'        => Racketmanager_Util::get_final_name( $finalkey ),
                    'num_matches' => $num_matches,
                    'num_teams'   => $num_teams,
                    'colspan'     => ( $this->num_teams_first_round / 2 >= 4 ) ? ceil( 4 / $num_matches ) : ceil( ( $this->num_teams_first_round / 2 ) / $num_matches ),
                    'round'       => $i,
                );
            }

            $this->keys[ $i ] = $finalkey;

            --$i;
            $num_teams = $num_teams * 2;
        }
        $this->set_current_final();
        $this->set_final_teams();

        $this->cup_icon = '<img style="vertical-align: middle;" src="' . RACKETMANAGER_URL . 'admin/icons/cup.png" />';
    }
    /**
     * Get groups
     *
     * @return array
     */
    public function get_groups(): array {
        return $this->groups;
    }

    /**
     * Get final key
     *
     * @param false|int $round round name.
     *
     * @return false|array|string
     */
    public function get_final_keys( false|int $round = false ): false|array|string {
        if ( $round ) {
            return $this->keys[$round] ?? false;
        } else {
            return $this->keys;
        }
    }

    /**
     * Get final data
     *
     * @param false|int|string $key final key.
     *
     * @return mixed
     */
    public function get_finals( false|int|string $key = false ): mixed {
        if ( 'current' === $key ) {
            $key = $this->current_final;
        }
        if ( $key ) {
            return $this->finals[ $key ];
        } else {
            return $this->finals;
        }
    }
    /**
     * Get key of final depending on number of teams
     *
     * @param int $num_teams number of teams in round.
     *
     * @return string key
     */
    private function get_final_key( int $num_teams ): string {
        if ( 2 === $num_teams ) {
            $key = 'final';
        } elseif ( 4 === $num_teams ) {
            $key = 'semi';
        } elseif ( 8 === $num_teams ) {
            $key = 'quarter';
        } else {
            $key = 'last-' . $num_teams;
        }
        return $key;
    }

    /**
     * Set current final key
     */
    private function set_current_final(): void {
        if ( isset( $_GET['final'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $key = sanitize_text_field( wp_unslash( $_GET['final'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } else {
            $key = $this->get_final_keys( 1 );
        }
        $this->current_final = $key;
    }

    /**
     * Get current final key
     *
     * @return array|string
     */
    public function get_current_final_key(): array|string {
        return $this->current_final;
    }

    /**
     * Set general names for final rounds
     */
    private function set_final_teams(): void {
        // Final Rounds.
        foreach ( $this->get_finals() as $k => $data ) {
            $this->final_teams[ $k ] = array();

            if ( $data['round'] > 1 ) {
                // get data of previous round.
                $final = $this->get_finals( $this->get_final_keys( $data['round'] - 1 ) );
                if ( isset( $final['num_matches'] ) ) {
                    for ( $x = 1; $x <= $final['num_matches']; $x++ ) {
                        if ( 'third' === $k ) {
                            /* translators: %1$s: round %2$d: match */
                            $title = sprintf( __( 'Loser %1$s %2$d', 'racketmanager' ), $final['name'], $x );
                            $key   = '2_' . $final['key'] . '_' . $x;
                        } else {
                            /* translators: %1$s: round %2$d: match */
                            $title = sprintf( __( 'Winner %1$s %2$d', 'racketmanager' ), $final['name'], $x );
                            $key   = '1_' . $final['key'] . '_' . $x;
                        }

                        $this->final_teams[ $k ][ $key ] = (object) array(
                            'id'    => $key,
                            'title' => $title,
                            'home'  => 0,
                        );
                    }
                }
            } elseif ( ! empty( $this->groups ) ) {
                foreach ( $this->groups as $group ) {
                    for ( $a = 1; $a <= $this->num_advance; $a++ ) {
                        $this->final_teams[ $k ][ $a . '_' . $group ] = (object) array(
                            'id'    => $a . '_' . $group,
                            /* translators: %1$d: team rank %2$s: group */
                            'title' => sprintf( __( '%1$d. Group %2$s', 'racketmanager' ), $a, $group ),
                            'home'  => 0,
                        );
                    }
                }
            } else {
                $num_teams = $this->num_teams_first_round;
                for ( $a = 1; $a <= $num_teams; $a++ ) {
                    $this->final_teams[ $k ][ $a . '_' ] = (object) array(
                        'id'    => $a . '_',
                        /* translators: $d: rank number */
                        'title' => sprintf( __( 'Team Rank %d', 'racketmanager' ), $a ),
                        'home'  => 0,
                    );
                }
            }
        }
    }

    /**
     * Get final team names
     *
     * @param string $final_round final reference.
     *
     * @return array|null
     */
    public function get_final_teams( string $final_round ): ?array {
        return $this->final_teams[$final_round] ?? null;
    }

    /**
     * Update final rounds results
     *
     * @param array $matches array of matches.
     * @param array $home_points home points.
     * @param array $away_points away points.
     * @param array $custom custom.
     * @param int $round round.
     * @param string $season season.
     */
    public function update_final_results( array $matches, array $home_points, array $away_points, array $custom, int $round, string $season ): void {
        global $racketmanager;

        $league = get_league( $this->league_id );
        $league->set_finals();
        $num_matches = $league->update_match_results( $matches, $home_points, $away_points, $custom, $season, $round );

        if ( $round < $this->num_rounds ) {
            $this->proceed( $round );
        }
        /* translators: %d: number of matches */
        $racketmanager->set_message( sprintf( __( 'Updated Results of %d matches', 'racketmanager' ), $num_matches ) );
    }

    /**
     * Set teams for match function
     *
     * @param object $match match object.
     * @param string|null $home_id home team.
     * @param string|null $away_id away team.
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
     * Proceed to next final round
     *
     * @param int $round round number.
     *
     * @return void
     */
    public function proceed( int $round ): void {
        if ( $round >= $this->num_rounds ) {
            return;
        }
        $current    = $this->get_final_keys( $round );
        $next       = $this->get_final_keys( $round + 1 );
        $legs       = false;
        $prev_home  = null;
        $prev_away  = null;
        $league     = get_league( $this->league_id );
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
                // get matches of current round.
                $match_args = array(
                    'final'   => $current,
                    'limit'   => false,
                    'orderby' => array(
                        'id' => 'ASC',
                    ),
                );
                if ( $legs ) {
                    $match_args['leg'] = 2;
                }
                $prev      = $league->get_matches( $match_args );
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
                    $this->set_teams( $match, $home_team, $away_team );
                    if ( ! empty( $league->event->primary_league ) && $league->event->primary_league === $league->id && $round < 3 ) {
                        if ( ! empty( $prev_home ) ) {
                            $this->set_consolation_team( $prev_home, $current, $league );
                        }
                        if ( ! empty( $prev_away ) ) {
                            $this->set_consolation_team( $prev_away, $current, $league );
                        }
                    }
                    // Set winners on final.
                    if ( 'third' === $next ) {
                        $match     = $league->get_matches(
                            array(
                                'final'   => 'final',
                                'limit'   => false,
                                'orderby' => array(
                                    'id' => 'ASC',
                                ),
                            )
                        );
                        $match     = $match[0];
                        $home_team = $prev_home->loser_id;
                        $away_team = $prev_away->loser_id;
                        $match->set_teams( $home_team, $away_team );
                    }
                }
            }
        }
    }
    /**
     * Set consolation teams function
     *
     * @param object $match match.
     * @param string $round round name.
     * @param object $league league.
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
            if ( count( $matches ) === 2 ) {
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
