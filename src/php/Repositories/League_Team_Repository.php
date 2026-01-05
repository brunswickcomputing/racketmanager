<?php
/**
 * League_Team_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\League_Team;
use wpdb;

/**
 * Class to implement the League team repository
 */
class League_Team_Repository {
    private wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_league_teams';
    }

    public function save( League_Team $league_team ): void {
        if ( empty( $league_team->get_id() ) ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'team_id'        => $league_team->get_team_id(),
                    'league_id'      => $league_team->get_league_id(),
                    'season'         => $league_team->get_season(),
                    'captain'        => $league_team->get_captain(),
                    'match_day'      => $league_team->get_match_day(),
                    'match_time'     => $league_team->get_match_time(),
                    'points_plus'    => $league_team->get_points_plus(),
                    'points_minus'   => $league_team->get_points_minus(),
                    'points_2_plus'  => $league_team->get_points_2_plus(),
                    'points_2_minus' => $league_team->get_points_2_minus(),
                    'add_points'     => $league_team->get_add_points(),
                    'done_matches'   => $league_team->get_done_matches(),
                    'won_matches'    => $league_team->get_won_matches(),
                    'draw_matches'   => $league_team->get_drawn_matches(),
                    'lost_matches'   => $league_team->get_lost_matches(),
                    'diff'           => $league_team->get_diff(),
                    'group'          => $league_team->get_group(),
                    'rank'           => $league_team->get_rank(),
                    'profile'        => $league_team->get_profile(),
                    'status'         => $league_team->get_status(),
                    'rating'         => $league_team->get_rating(),
                    'custom'         => maybe_serialize( $league_team->get_custom() ),
                ),
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%f',
                    '%f',
                    '%d',
                    '%d',
                    '%f',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%f',
                    '%s',
                )
            );
            $league_team->set_id( $this->wpdb->insert_id );
        } else {
            $this->wpdb->update(
                $this->table_name,
                array(
                    'team_id'        => $league_team->get_team_id(),
                    'league_id'      => $league_team->get_league_id(),
                    'season'         => $league_team->get_season(),
                    'captain'        => $league_team->get_captain(),
                    'match_day'      => $league_team->get_match_day(),
                    'match_time'     => $league_team->get_match_time(),
                    'points_plus'    => $league_team->get_points_plus(),
                    'points_minus'   => $league_team->get_points_minus(),
                    'points_2_plus'  => $league_team->get_points_2_plus(),
                    'points_2_minus' => $league_team->get_points_2_minus(),
                    'add_points'     => $league_team->get_add_points(),
                    'done_matches'   => $league_team->get_done_matches(),
                    'won_matches'    => $league_team->get_won_matches(),
                    'draw_matches'   => $league_team->get_drawn_matches(),
                    'lost_matches'   => $league_team->get_lost_matches(),
                    'diff'           => $league_team->get_diff(),
                    'group'          => $league_team->get_group(),
                    'rank'           => $league_team->get_rank(),
                    'profile'        => $league_team->get_profile(),
                    'status'         => $league_team->get_status(),
                    'rating'         => $league_team->get_rating(),
                    'custom'         => maybe_serialize( $league_team->get_custom() ),
                ), // Data to update
                array(
                    'id' => $league_team->get_id()
                ), // Where clause
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%f',
                    '%f',
                    '%d',
                    '%d',
                    '%f',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%f',
                    '%s',
                ),
                array(
                    '%d'
                ) // Where format
            );
        }
    }

    public function find_by_id( $league_team_id ): ?League_Team {
        if ( empty( $league_team_id ) ) {
            return null;
        }
        $league_team = wp_cache_get( $league_team_id, 'league-teams' );

        if ( ! $league_team ) {
            $league_team = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $league_team_id
                )
            );

            if ( ! $league_team ) {
                return null;
            }
            $league_team = new League_Team( $league_team );

            wp_cache_set( $league_team->id, $league_team, 'league-teams' );
        }

        return $league_team;
    }

    /**
     * Finds all Club IDs where the given player is assigned as a team captain.
     *
     * @param int $player_id
     * @param int|null $club_id
     *
     * @return int[] Array of Club IDs.
     */
    public function find_club_ids_where_player_is_captain( int $player_id, ?int $club_id ):array {
        // Need to join the league team entry table with the main teams table to get the club_id
        $teams_table = $this->wpdb->prefix . 'racketmanager_teams';
        $sql = "SELECT DISTINCT t.club_id FROM $this->table_name lte INNER JOIN $teams_table t ON lte.team_id = t.id WHERE lte.captain = %d";
        $params = array( $player_id );
        if ( $club_id ) {
            $sql .= " AND club_id = %d";
            $params[] = $club_id;
        }
        $query = $this->wpdb->prepare(
            $sql,
            $params
        );
        return $this->wpdb->get_col($query);
    }
}
