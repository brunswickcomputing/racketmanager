<?php
/**
 * Player_Stats_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

declare( strict_types=1 );

namespace Racketmanager\Services;

use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Domain\Player\Player;
use stdClass;

/**
 * Service for calculating, aggregating and formatting player match and career statistics.
 */
class Player_Stats_Service implements Player_Stats_Service_Interface {
    private Player_Query_Service $query_service;

    public function __construct( Player_Query_Service $query_service ) {
        $this->query_service = $query_service;
    }

    /**
     * Populate calculated stats and match breakdown on a Player entity.
     *
     * @param Player $player
     * @param object|null $grouping
     * @param string|int|null $season
     * @param string $match_source
     */
    public function populate_player_stats( Player $player, ?object $grouping, string|int|null $season, string $match_source ): void {
        $season_str = null !== $season ? (string) $season : null;
        $player->set_matches( $this->query_service->get_matches( $player, $grouping, $season_str, $match_source ) );
        $stats = $this->get_stats( $player );
        $player->set_stats( $stats );
        $total = $stats['total'] ?? null;
        $player->set_stats_summary(
            $total->matches_won ?? 0,
            $total->matches_lost ?? 0,
            (float) ( $total->win_pct ?? 0 ),
            $total->played ?? 0
        );
    }

    /**
     * Populate calculated stats for an array of Player entities and sort them by matches won / played.
     *
     * @param array $players
     * @param object|null $grouping
     * @param string|int|null $season
     * @param string $match_source
     * @return array
     */
    public function populate_players_stats( array $players, ?object $grouping, string|int|null $season, string $match_source ): array {
        foreach ( $players as $player ) {
            if ( $player instanceof Player ) {
                $this->populate_player_stats( $player, $grouping, $season, $match_source );
            }
        }
        $won    = array_column( $players, 'matches_won' );
        $played = array_column( $players, 'played' );
        if ( ! empty( $won ) && ! empty( $played ) ) {
            array_multisort( $won, SORT_DESC, $played, SORT_ASC, $players );
        }
        return $players;
    }

    /**
     * Get player statistics function.
     *
     * @param Player $player
     * @param false|array $stats optional array of statistics to use.
     * @return array of statistics
     */
    public function get_stats( Player $player, false|array $stats = false ): array {
        if ( ! $stats ) {
            $stats = $player->statistics;
        }
        if ( ! is_array( $stats ) ) {
            $stats = array();
        }
        ksort( $stats );
        $statistics           = array();
        $statistics['detail'] = $stats;
        $total_stats          = array();
        $total_stats_sets     = array();
        $total_stats_games    = array();
        $stat_types           = array( 'winner', 'loser', 'draw' );
        foreach ( $stat_types as $stat_type ) {
            $total_stats[ $stat_type ]       = 0;
            $total_stats_sets[ $stat_type ]  = 0;
            $total_stats_games[ $stat_type ] = 0;
        }
        $total_stats_walkover = 0;
        foreach ( $stats as $statistics_item ) {
            if ( ! empty( $statistics_item['played'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics_item['played'][ $stat_type ] ) ) {
                        $total_stats[ $stat_type ] += $statistics_item['played'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics_item['sets'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics_item['sets'][ $stat_type ] ) ) {
                        $total_stats_sets[ $stat_type ] += $statistics_item['sets'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics_item['games'] ) ) {
                foreach ( $stat_types as $stat_type ) {
                    if ( ! empty( $statistics_item['games'][ $stat_type ] ) ) {
                        $total_stats_games[ $stat_type ] += $statistics_item['games'][ $stat_type ];
                    }
                }
            }
            if ( ! empty( $statistics_item['walkover'] ) ) {
                $total_stats_walkover += $statistics_item['walkover'];
            }
        }
        $statistics['total']               = new stdClass();
        $statistics['total']->matches_won  = $total_stats['winner'];
        $statistics['total']->matches_lost = $total_stats['loser'];
        $statistics['total']->matches_tie  = $total_stats['draw'];
        $statistics['total']->played       = $statistics['total']->matches_won + $statistics['total']->matches_lost + $statistics['total']->matches_tie;
        if ( $statistics['total']->played ) {
            $statistics['total']->win_pct = ceil( ( $statistics['total']->matches_won / $statistics['total']->played ) * 100 );
        }
        $statistics['total']->sets_won   = $total_stats_sets['winner'];
        $statistics['total']->sets_lost  = $total_stats_sets['loser'];
        $statistics['total']->games_won  = $total_stats_games['winner'];
        $statistics['total']->games_lost = $total_stats_games['loser'];
        $statistics['total']->walkover   = empty( $total_stats_walkover ) ? '' : $total_stats_walkover;
        $player->set_statistics( $statistics );
        return $player->statistics;
    }

    /**
     * Get player career statistics function.
     *
     * @param Player $player
     * @return array of statistics
     */
    public function get_career_stats( Player $player ): array {
        $team_matches   = $this->query_service->get_stats_teams( $player );
        $player_matches = $this->query_service->get_stats_players( $player );

        return $this->calculate_career_stats( $team_matches, $player_matches );
    }

    /**
     * Calculate career statistics from match lists.
     *
     * @param array $team_matches
     * @param array $player_matches
     * @return array
     */
    public function calculate_career_stats( array $team_matches, array $player_matches ): array {
        $result         = null;
        $types          = array( 'S', 'D', 'X' );
        $stat_types     = array( 'win', 'loss', 'tie' );
        $matches        = array_merge( $team_matches, $player_matches );
        $seasons        = array();
        foreach ( $matches as $match ) {
            if ( 'teams' === $match->stat_type ) {
                if ( $match->winner_id ) {
                    if ( 'home' === $match->player_team ) {
                        if ( $match->winner_id === $match->home_team ) {
                            $result = 'win';
                        } elseif ( $match->winner_id === $match->away_team ) {
                            $result = 'loss';
                        } else {
                            $result = 'tie';
                        }
                    } elseif ( 'away' === $match->player_team ) {
                        if ( $match->winner_id === $match->away_team ) {
                            $result = 'win';
                        } elseif ( $match->winner_id === $match->home_team ) {
                            $result = 'loss';
                        } else {
                            $result = 'tie';
                        }
                    }
                }
            } elseif ( 'players' === $match->stat_type ) {
                if ( $match->winner_id ) {
                    if ( $match->winner_id === $match->team_id ) {
                        $result = 'win';
                    } elseif ( $match->loser_id === $match->team_id ) {
                        $result = 'loss';
                    } else {
                        $result = 'tie';
                    }
                }
            }
            if ( isset( $match->type ) && ( 'XD' === $match->type || 'LD' === $match->type ) ) {
                $type = 'X';
            } else {
                $type = isset( $match->type ) ? substr( (string) $match->type, 1, 1 ) : 'S';
            }
            $season = $match->season ?? '';
            if ( false === array_key_exists( $season, $seasons ) ) {
                $seasons[ $season ] = array();
                foreach ( $types as $match_type ) {
                    $seasons[ $season ][ $match_type ] = array();
                    foreach ( $stat_types as $stat_type ) {
                        $seasons[ $season ][ $match_type ][ $stat_type ] = 0;
                    }
                }
            }
            if ( false === array_key_exists( $type, $seasons[ $season ] ) ) {
                $seasons[ $season ][ $type ] = array();
            }
            if ( false === array_key_exists( $result, $seasons[ $season ][ $type ] ) ) {
                $seasons[ $season ][ $type ][ $result ] = 0;
            }
            ++$seasons[ $season ][ $type ][ $result ];
        }
        krsort( $seasons );
        $totals = array();
        foreach ( $stat_types as $stat_type ) {
            $totals['total'][ $stat_type ] = 0;
        }
        foreach ( $seasons as $season => $season_types ) {
            unset( $seasons[ $season ] );
            $seasons[ $season ]['breakdown'] = $season_types;
            $seasons[ $season ]['total']     = array();
            foreach ( $stat_types as $stat_type ) {
                $seasons[ $season ]['total'][ $stat_type ] = 0;
            }
            foreach ( $season_types as $match_type => $results ) {
                foreach ( $results as $match_result => $count ) {
                    if ( ! isset( $totals['breakdown'][ $match_type ][ $match_result ] ) ) {
                        $totals['breakdown'][ $match_type ][ $match_result ] = 0;
                    }
                    $totals['breakdown'][ $match_type ][ $match_result ] += $count;
                    $totals['total'][ $match_result ]                    += $count;
                    $seasons[ $season ]['total'][ $match_result ]        += $count;
                }
            }
        }
        $statistics            = array();
        $statistics['totals']  = $totals;
        $statistics['seasons'] = $seasons;
        return $statistics;
    }
}
