<?php
declare( strict_types=1 );
/**
 * Player_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repository
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Update_Exception;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Util\Util;
use WP_User;
use wpdb;

/**
 * Class to implement the Player repository
 */
class Player_Repository implements Player_Repository_Interface {
    const string META_KEY_GENDER = 'gender';
    const string META_KEY_BTM    = 'btm';
    const string META_KEY_YOB    = 'year_of_birth';
    private wpdb $wpdb;

    /**
     * Player metadata hydrator collaborator.
     *
     * @var Player_Metadata_Hydrator
     */
    private Player_Metadata_Hydrator $hydrator;

    /**
     * Player user resolver collaborator.
     *
     * @var Player_User_Resolver
     */
    private Player_User_Resolver $user_resolver;

    /**
     * Player autocomplete query collaborator.
     *
     * @var Player_Autocomplete_Query
     */
    private Player_Autocomplete_Query $autocomplete_query;

    /**
     * Player summary query collaborator.
     *
     * @var Player_Summary_Query
     */
    private Player_Summary_Query $summary_query;

    /**
     * Player titles query collaborator.
     *
     * @var Player_Titles_Query
     */
    private Player_Titles_Query $titles_query;

    /**
     * Initialise the instance with a WordPress database connection.
     *
     * Uses the provided database object when supplied, otherwise falls back to the global WordPress database instance.
     *
     * @param wpdb|null                      $db                 Optional WordPress database connection.
     * @param Player_Metadata_Hydrator|null  $hydrator           Optional player metadata hydrator collaborator.
     * @param Player_User_Resolver|null      $user_resolver      Optional player user resolver collaborator.
     * @param Player_Autocomplete_Query|null $autocomplete_query Optional player autocomplete query collaborator.
     * @param Player_Summary_Query|null      $summary_query      Optional player summary query collaborator.
     * @param Player_Titles_Query|null       $titles_query       Optional player titles query collaborator.
     *
     * @return void
     */
    public function __construct(
        ?wpdb $db = null,
        ?Player_Metadata_Hydrator $hydrator = null,
        ?Player_User_Resolver $user_resolver = null,
        ?Player_Autocomplete_Query $autocomplete_query = null,
        ?Player_Summary_Query $summary_query = null,
        ?Player_Titles_Query $titles_query = null
    ) {
        global $wpdb;
        $this->wpdb               = $db ?? $wpdb;
        $this->hydrator           = $hydrator ?? new Player_Metadata_Hydrator();
        $this->user_resolver      = $user_resolver ?? new Player_User_Resolver();
        $this->autocomplete_query = $autocomplete_query ?? new Player_Autocomplete_Query( $this->wpdb );
        $this->summary_query      = $summary_query ?? new Player_Summary_Query( $this->wpdb );
        $this->titles_query       = $titles_query ?? new Player_Titles_Query( $this->wpdb );
    }

    /**
     * Add a player as a WordPress user and save related player metadata.
     *
     * @param Player $player Player to add.
     *
     * @return bool|int User ID on success, false on failure.
     */
    public function add( Player $player ): bool|int {
        $userdata                    = array();
        $userdata['first_name']      = $player->get_firstname();
        $userdata['last_name']       = $player->get_surname();
        $userdata['display_name']    = $player->get_fullname();
        $userdata['user_login']      = $player->get_login();
        $userdata['user_pass']       = $player->get_password();
        $userdata['user_registered'] = $player->get_date_registered();
        if ( $player->email ) {
            $userdata['user_email'] = $player->email;
        }
        $user_id = wp_insert_user( $userdata );
        if ( ! is_wp_error( $user_id ) ) {
            $player->set_id( $user_id );
            update_user_meta( $user_id, 'show_admin_bar_front', false );
            update_user_meta( $user_id, self::META_KEY_GENDER, $player->get_gender() );
            if ( isset( $player->btm ) && $player->btm > '' ) {
                update_user_meta( $user_id, self::META_KEY_BTM, $player->get_btm() );
            }
            if ( isset( $player->contactno ) && $player->contactno > '' ) {
                update_user_meta( $user_id, 'contactno', $player->get_contactno() );
            }
            if ( isset( $player->year_of_birth ) && $player->year_of_birth > '' ) {
                update_user_meta( $user_id, 'year_of_birth', $player->get_year_of_birth() );
            }
            return $user_id;
        }

        return false;
    }

    /**
     * Save an opt-in value for a player if it has not already been recorded.
     *
     * @param int    $player_id Player user ID.
     * @param string $opt_in Opt-in value to save.
     *
     * @return bool True if the opt-in already exists or was saved successfully, false otherwise.
     */
    public function save_opt_in( int $player_id, string $opt_in ): bool {
        if ( ! $player_id || ! $opt_in ) {
            return false;
        }
        $existing = get_user_meta( $player_id, 'racketmanager_opt_in', true );
        if ( is_array( $existing ) && in_array( $opt_in, $existing, true ) ) {
            return true;
        }
        return (bool) add_user_meta( $player_id, 'racketmanager_opt_in', $opt_in, true );
    }

    /**
     * Update a player's WordPress user data and selected player metadata.
     *
     * Applies only the fields indicated by the updates array, refreshes the relevant user meta values,
     * and clears the cached player record after saving.
     *
     * @param Player $player Player entity containing the latest values to save.
     * @param array  $updates List of fields to update, keyed by update identifier.
     *
     * @return bool True when the update succeeds, false otherwise.
     * @throws Player_Update_Exception If the core WordPress user update fails.
     */
    public function update( Player $player, array $updates ): bool {
        $success = true;
        if ( isset( $updates['core'] ) ) {
            $user_data                  = array();
            $user_data['first_name']    = $player->get_firstname();
            $user_data['last_name']     = $player->get_surname();
            $user_data['display_name']  = $player->get_fullname();
            $user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
            $user_data['user_email']    = $player->get_email();
            $user_data['ID']            = $player->get_id();
            $user_id                    = wp_update_user( $user_data );
            if ( is_wp_error( $user_id ) ) {
                throw new Player_Update_Exception( esc_html( $user_id->get_error_message() ) );
            }
            $success = 0 !== $user_id;
        }
        $user_id = $player->get_id();
        if ( isset( $updates[ self::META_KEY_GENDER ] ) ) {
            update_user_meta( $user_id, self::META_KEY_GENDER, $player->get_gender() );
        }
        if ( isset( $updates[ self::META_KEY_BTM ] ) ) {
            update_user_meta( $user_id, self::META_KEY_BTM, $player->get_btm() );
        }
        if ( isset( $updates['contactno'] ) ) {
            update_user_meta( $user_id, 'contactno', $player->get_contactno() );
        }
        if ( isset( $updates['dob'] ) ) {
            update_user_meta( $user_id, self::META_KEY_YOB, $player->get_year_of_birth() );
        }
        if ( isset( $updates['locked'] ) ) {
            if ( $player->get_locked() ) {
                update_user_meta( $user_id, 'locked', $player->get_locked() );
                update_user_meta( $user_id, 'locked_date', $player->get_locked_date() );
                update_user_meta( $user_id, 'locked_user', $player->get_locked_user() );
            } else {
                delete_user_meta( $user_id, 'locked' );
                delete_user_meta( $user_id, 'locked_date' );
                delete_user_meta( $user_id, 'locked_user' );
            }
        }
        if ( isset( $updates['removed'] ) ) {
            update_user_meta( $user_id, 'remove', $player->get_removed_date() );
            update_user_meta( $user_id, 'remove_user', $player->get_removed_user() );
        }
        if ( isset( $updates['wtn'] ) ) {
            foreach ( $player->get_wtn() as $match_type => $wtn ) {
                $wtn_type = 'wtn_' . $match_type;
                update_user_meta( $user_id, $wtn_type, $wtn );
            }
        }
        wp_cache_delete( $player->get_id(), 'players' );
        return $success;
    }

    /**
     * Find players matching the supplied criteria.
     *
     * Returns active players when requested, otherwise searches WordPress users by
     * gender metadata and optionally filters by player name or numeric BTM value.
     *
     * @param array $args Optional arguments used to find players.
     *
     * @return array
     */
    public function find_all( array $args = array() ): array {
        $defaults       = array(
            'active' => false,
            'name'   => false,
        );
        $args           = array_merge( $defaults, $args );
        $active         = $args['active'];
        $name           = $args['name'];
        $orderby_string = 'display_name';
        $order          = 'ASC';
        if ( $active ) {
            return $this->get_active_players();
        }
        $user_args                 = array();
        $user_args['meta_key']     = self::META_KEY_GENDER; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $user_args['meta_value']   = 'M,F'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $user_args['meta_compare'] = 'IN';
        $user_args['orderby']      = $orderby_string;
        $user_args['order']        = $order;
        if ( $name ) {
            if ( is_numeric( $name ) ) {
                $user_args['meta_key']   = self::META_KEY_BTM; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                $user_args['meta_value'] = $name; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            } else {
                $user_args['search']         = '*' . $name . '*';
                $user_args['search_columns'] = array( 'display_name' );
            }
        }
        $user_search = wp_json_encode( $user_args );
        $players     = wp_cache_get( md5( $user_search ), 'players' );
        if ( ! $players ) {
            $user_args['fields'] = array( 'ID', 'display_name' );
            $players             = get_users( $user_args );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( (int) $player->ID );
                    $players[ $i ] = $player;
                    ++$i;
                }
            }
            wp_cache_set( md5( $user_search ), $players, 'players' );
        }

        return $players;
    }

    /**
     * Find player summaries matching the supplied filters.
     *
     * Supports filtering to active players only, searching by BTM number when the
     * name argument is numeric, or searching by display name when it is a string.
     * Results are cached using the normalised argument set.
     *
     * @param array $args Optional arguments used to filter player summaries.
     *
     * @return array
     */
    public function find_players_summary( array $args = array() ): array {
        return $this->summary_query->execute( $args );
    }

    /**
     * Get active players who have played in a rubber or have tournament entries.
     *
     * @return array
     */
    public function get_active_players(): array {
        $sql     = "SELECT DISTINCT `player_id` FROM {$this->wpdb->prefix}racketmanager_rubber_players UNION SELECT DISTINCT `player_id` FROM {$this->wpdb->prefix}racketmanager_tournament_entries ORDER BY `player_id`";
        $players = wp_cache_get( md5( $sql ), 'players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results(
                $sql // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( (int) $player->player_id );
                    $players[ $i ] = $player;
                    ++$i;
                }
            }
            wp_cache_set( md5( $sql ), $players, 'players' );
        }

        return $players;
    }

    /**
     * Get players who have played in a rubber (legacy "active" player list).
     *
     * Unlike get_active_players() this excludes players who only have tournament entries.
     *
     * @return array
     */
    public function get_active_rubber_players(): array {
        $sql     = "SELECT DISTINCT `player_id` FROM {$this->wpdb->prefix}racketmanager_rubber_players ORDER BY `player_id`";
        $players = wp_cache_get( md5( $sql ), 'players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results(
                $sql // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( (int) $player->player_id );
                    $players[ $i ] = $player;
                    ++$i;
                }
            }
            wp_cache_set( md5( $sql ), $players, 'players' );
        }

        return $players;
    }

    /**
     * Find a player by ID or by the specified search type.
     *
     * Retrieves a player from cache when available, otherwise looks up the
     * underlying WordPress user record, loads the associated player metadata,
     * and returns a Player instance.
     *
     * @param int|string $player_id Player identifier or lookup value.
     * @param string     $search_type Search type to use. Accepts 'id', 'btm', 'email', 'login', or 'name'.
     *
     * @return Player|null Player instance when found, or null when no matching player exists.
     * @throws Player_Not_Found_Exception When the player identifier is empty.
     */
    public function find( int|string $player_id, string $search_type = 'id' ): ?Player {
        if ( empty( $player_id ) ) {
            throw new Player_Not_Found_Exception( esc_html__( 'Player ID cannot be empty.', 'racketmanager' ) );
        }
        $player = wp_cache_get( $player_id, 'players' );
        if ( $player ) {
            return $player;
        }

        $user = $this->user_resolver->resolve( $player_id, $search_type );
        if ( ! $user ) {
            return null;
        }

        $this->hydrator->hydrate( $user->data );

        // Create a new Player object from the retrieved user data.
        $player = Player_Factory::from_object(
            $user->data
        );
        wp_cache_set( $player_id, $player, 'players' );

        return $player;
    }

    /**
     * Find a user by BTM player ID.
     *
     * @param mixed $player_id BTM player ID to search for.
     *
     * @return mixed User object when found, or false when no matching user exists.
     */
    public function find_by_btm( mixed $player_id ): mixed {
        return $this->user_resolver->find_by_btm( $player_id );
    }

    /**
     * Find a user by email address.
     *
     * @param string $player_id Email address to search for.
     *
     * @return false|WP_User User object if found, false otherwise.
     */
    public function find_by_email( $player_id ): false|WP_User {
        return $this->user_resolver->find_by_email( (string) $player_id );
    }

    /**
     * Check whether a player has any club role or club player associations.
     *
     * @param int $player_id Player ID to check.
     *
     * @return bool True if the player has one or more club associations, false otherwise.
     */
    public function has_club_associations( int $player_id ): bool {
        $roles_table        = $this->wpdb->prefix . 'racketmanager_club_roles';
        $club_players_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $query              = $this->wpdb->prepare( "SELECT ((SELECT COUNT(id) FROM $roles_table WHERE user_id = %d) + (SELECT COUNT(id) FROM $club_players_table WHERE player_id = %d)) AS association_count", $player_id, $player_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $count              = $this->wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return $count > 0;
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id User ID to delete.
     *
     * @return bool True on successful deletion, false on failure.
     */
    public function delete( int $id ): bool {
        return wp_delete_user( $id );
    }

    /**
     * Find player IDs currently registered to a club.
     *
     * @param int $club_id Club ID.
     *
     * @return array
     */
    public function find_player_ids_by_club( int $club_id ): array {
        $registration_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $query              = $this->wpdb->prepare( "SELECT player_id FROM $registration_table WHERE club_id = %d AND `removed_date` IS NULL", $club_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        return $this->wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Finds players and their registration details based on various filters.
     *
     * @param int|null    $club_id Optional Club ID filter.
     * @param string|null $status Optional status filter ('pending', 'approved').
     * @param string|null $gender Optional gender filter.
     * @param string|null $active Optional active filter.
     * @param bool        $system Optional system filter.
     * @param int|null    $max_age Optional maximum age filter.
     * @param int|null    $min_age Optional minimum age filter.
     *
     * @return array
     */
    public function find_club_players_with_details( ?int $club_id = null, ?string $status = null, ?string $gender = null, ?string $active = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array {
        $registration_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $club_table         = $this->wpdb->prefix . 'racketmanager_clubs';
        $user_table         = $this->wpdb->users;
        $user_meta_table    = $this->wpdb->usermeta;

        // Base query: Join users, registrations, and clubs tables.
        $query = "SELECT r.id as registration_id, u.ID as user_id, u.display_name as display_name, u.user_email as email, c.id as club_id, c.shortcode as club_name, r.requested_date as registration_date, r.created_date as approval_date, r.removed_date as removal_date, r.requested_user as registered_by_user_id, r.created_user as approved_by_user_id, r.removed_user as removed_by_user_id, MAX(IF(um_gender.meta_key = %s, um_gender.meta_value, NULL)) as gender, MAX(IF(um_yob.meta_key = %s, um_yob.meta_value, NULL)) as year_of_birth FROM $user_table u INNER JOIN $registration_table r ON u.ID = r.player_id INNER JOIN $club_table c ON r.club_id = c.id LEFT JOIN $user_meta_table um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = %s LEFT JOIN $user_meta_table um_yob ON u.ID = um_yob.user_id AND um_yob.meta_key = %s";

        $params       = [ self::META_KEY_GENDER, self::META_KEY_YOB, self::META_KEY_GENDER, self::META_KEY_YOB ];
        $search_terms = [];
        if ( $club_id ) {
            $search_terms[] = $this->wpdb->prepare( 'r.club_id = %d', $club_id );
        }
        if ( ! $system ) {
            $search_terms[] = 'r.system_record IS NULL';
        }
        if ( $active ) {
            $search_terms[] = 'r.removed_date IS NULL';
        }
        switch ( $status ) {
            case 'outstanding':
                $search_terms[] = 'r.created_date IS NULL';
                break;
            case 'all':
            default:
                break;
        }
        // Age filtering.
        $current_year = (int) gmdate( 'Y' );
        if ( is_numeric( $min_age ) ) {
            // A player who is at least $minAge must have a YOB <= ($currentYear - $minAge).
            $max_year_of_birth = $current_year - (int) $min_age;
            // We need to add an explicit JOIN condition for the YOB meta-key for the WHERE clause to work efficiently.
            $search_terms[] = $this->wpdb->prepare( 'CAST(um_yob.meta_value AS UNSIGNED) <= %d', $max_year_of_birth );
        }
        if ( is_numeric( $max_age ) ) {
            // A player who is at most $maxAge must have a YOB >= ($currentYear - $maxAge).
            $min_year_of_birth = $current_year - (int) $max_age;
            $search_terms[]    = $this->wpdb->prepare( 'CAST(um_yob.meta_value AS UNSIGNED) >= %d', $min_year_of_birth );
        }
        $search = Util::search_string( $search_terms, true );
        $query .= " $search GROUP BY u.ID, c.id, r.id ";

        if ( ! empty( $gender ) ) {
            // Filter happens in the HAVING clause because gender is aggregated
            // OR we use an INNER JOIN for meta-query optimisation if performance is key.
            // Using HAVING for simplicity here.
            $query   .= ' HAVING gender = %s';
            $params[] = $gender;
        }

        $query  .= ' ORDER BY u.display_name ASC';
        $results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return array_map(
            function ( $row ) {
                return (object) [
                    'registration_id' => (int) $row->registration_id,
                    'user_id'         => (int) $row->user_id,
                ];
            },
            $results
        );
    }

    /**
     * Get final match title records for a player, grouped by season and tournament.
     *
     * Retrieves finals involving teams associated with the player and organises the
     * results by season, then by tournament name.
     *
     * @param int|string $player_id Player ID.
     *
     * @return array
     */
    public function get_titles( $player_id ): array {
        return $this->titles_query->execute( $player_id );
    }

    /**
     * Find active players who have played in a competition during a season.
     *
     * Returns distinct non-system players from rubbers linked to fixtures in the
     * specified competition and season, ordered by surname and first name.
     *
     * @param int    $competition_id Competition ID.
     * @param string $season Season identifier.
     *
     * @return array
     */
    public function find_active_players_by_competition_and_season( int $competition_id, string $season ): array {
        $user_table           = $this->wpdb->base_prefix . 'users';
        $usermeta_table       = $this->wpdb->base_prefix . 'usermeta';
        $rubber_players_table = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $rubbers_table        = $this->wpdb->prefix . 'racketmanager_rubbers';
        $fixtures_table       = $this->wpdb->prefix . 'racketmanager_matches';
        $events_table         = $this->wpdb->prefix . 'racketmanager_events';
        $leagues_table        = $this->wpdb->prefix . 'racketmanager_leagues';
        $registrations_table  = $this->wpdb->prefix . 'racketmanager_club_players';

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $this->wpdb->prepare(
            "
                SELECT
                    base.player_id,
                    u.display_name,
                    u.user_email as  email_address,
                    m1.meta_value AS firstName,
                    m2.meta_value AS surname
                FROM (
                    -- Filter down to unique player IDs first to avoid joining meta for duplicates
                    SELECT DISTINCT rp.player_id AS player_id
                    FROM `$rubber_players_table` rp
                        JOIN `$registrations_table` rg ON rp.club_player_id = rg.id
                    JOIN `$rubbers_table` r ON rp.rubber_id = r.id
                    JOIN `$fixtures_table` f ON r.match_id = f.id
                    JOIN `$leagues_table` l ON f.league_id = l.id
                    JOIN `$events_table` e ON l.event_id = e.id
                    WHERE f.season = %s
                      AND e.competition_id = %d
                      AND rg.system_record IS NULL
                ) AS base
                    JOIN `$user_table` u ON base.player_id = u.id
                LEFT JOIN `$usermeta_table` m1 ON base.player_id = m1.user_id AND m1.meta_key = 'first_name'
                LEFT JOIN `$usermeta_table` m2 ON base.player_id = m2.user_id AND m2.meta_key = 'last_name'
                ORDER BY surname, firstName;
                ",
            $season,
            $competition_id,
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $results = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return array_map(
            fn( $row ) => new Players_List_DTO( $row ),
            $results
        );
    }

    /**
     * Find players assigned to a team.
     *
     * @param int $team_id Team ID.
     *
     * @return array
     */
    public function find_by_team( int $team_id ): array {
        $team_players_table = $this->wpdb->prefix . 'racketmanager_team_players';

        $player_ids = $this->wpdb->get_col(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT player_id FROM $team_players_table WHERE team_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $team_id // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            )
        );

        $players = [];
        foreach ( $player_ids as $player_id ) {
            $player = $this->find( $player_id );
            if ( $player ) {
                $players[] = $player;
            }
        }

        return $players;
    }

    /**
     * Find a player by ID.
     *
     * Returns null when the ID is empty or no matching player is found.
     *
     * @param int|string|null $id Player ID to find.
     *
     * @return object|null Player object if found, otherwise null.
     */
    public function find_by_id( int|string|null $id ): ?object {
        if ( empty( $id ) ) {
            return null;
        }
        try {
            return $this->find( (int) $id );
        } catch ( Player_Not_Found_Exception ) {
            return null;
        }
    }

    /**
     * Find registered player autocomplete projections.
     *
     * @param string      $term Search query (name or BTM).
     * @param string      $type Lookup type ('name' or 'btm').
     * @param string|null $gender Optional gender filter ('M' or 'F').
     * @param int|null    $club_id Optional club ID filter.
     * @param int         $limit Max results to return.
     *
     * @return list<array{id: int, playerId: int, name: string, user_email: string|null, club_id: int, club: string|null, btm: string|null, contactno: string}>
     */
    public function find_registered_player_autocomplete(
        string $term,
        string $type = 'name',
        ?string $gender = null,
        ?int $club_id = null,
        int $limit = 100
    ): array {
        return $this->autocomplete_query->execute( $term, $type, $gender, $club_id, $limit );
    }
}
