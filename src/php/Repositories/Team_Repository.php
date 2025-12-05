<?php

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Team;

class Team_Repository {
    private \QM_DB|\wpdb $wpdb;
    private string $table_name;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_teams';
    }

    public function save( Team $team ): void {
        if ( $team->get_id() === null ) {
            $this->wpdb->insert(
                $this->table_name,
                array(
                    'title'       => $team->get_name(),
                    'stadium'     => $team->get_stadium(),
                    'club_id'     => $team->get_club_id(),
                    'type'        => $team->get_type(),
                    'team_type'   => $team->get_team_type(),
                ),
                array(
                    '%s', // Format for name (string)
                    '%s', // Format for stadium (string)
                    '%d', // Format for club_id (int)
                    '%s', // Format for type (string)
                    '%s', // Format for team_type (string)
                )
            );
            $team->set_id( $this->wpdb->insert_id );
        } else {
            // UPDATE: Use wpdb->update with the prepare logic built-in
            $this->wpdb->update(
                $this->table_name,
                array(
                    'title'       => $team->get_name(),
                    'stadium'     => $team->get_stadium(),
                    'club_id'     => $team->get_club_id(),
                    'type'        => $team->get_type(),
                    'team_type'   => $team->get_team_type(),
                ), // Data to update
                array('id' => $team->get_id() ),            // Where clause
                array( '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                ),                                          // Data format
                array('%d')                                 // Where format
            );
        }
        wp_cache_flush_group( 'teams' );
    }
    /**
     * Checks if a club has teams.
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function has_teams( int $club_id ): bool {
        $count = $this->wpdb->query(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM $this->table_name WHERE `club_id` = %d",
                $club_id
            )
        );
        return $count > 0;
    }

    /**
     * Finds the highest existing sequence number for teams belonging to a club shortcode prefix.
     * Assumes team names follow the format 'SHORTCODE 1', 'SHORTCODE 2', etc.
     *
     * @param string $club_shortcode
     * @param string $type
     *
     * @return int The next available sequence number (1 if no teams exist yet).
     */
    public function find_next_sequence_number( string $club_shortcode, string $type ): int {
        // We look for names starting with the shortcode followed by a space and digits
        $prefix_like = $this->wpdb->esc_like( $club_shortcode ) . ' ' . $this->wpdb->esc_like( $type ) . ' %';
        $query = $this->wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(title, ' ', -1) AS UNSIGNED))
             FROM $this->table_name
             WHERE `title` LIKE %s",
            $prefix_like
        );

        $max_sequence = (int) $this->wpdb->get_var($query);
        // If no teams found, MAX returns NULL/0, so the next number is 1. Otherwise, increment the max.
        return $max_sequence + 1;
    }

}
