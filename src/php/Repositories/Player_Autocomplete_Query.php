<?php
/**
 * Player autocomplete query builder and executor.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use wpdb;
use const ARRAY_A;

/**
 * Builds, executes, and formats registered player autocomplete queries.
 */
class Player_Autocomplete_Query {
    private const string META_KEY_GENDER = 'gender';
    private const string META_KEY_BTM    = 'btm';
    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private wpdb $wpdb;

    /**
     * Initialise the query with a WordPress database connection.
     *
     * Uses the provided database object when supplied, otherwise falls back to the global WordPress database instance.
     *
     * @param wpdb|null $db Optional WordPress database connection.
     */
    public function __construct( ?wpdb $db = null ) {
        global $wpdb;
        $this->wpdb = $db ?? $wpdb;
    }

    /**
     * Execute registered player autocomplete query.
     *
     * @param string      $term Search query (name or BTM).
     * @param string      $type Lookup type ('name' or 'btm').
     * @param string|null $gender Optional gender filter ('M' or 'F').
     * @param int|null    $club_id Optional club ID filter.
     * @param int         $limit Max results to return.
     *
     * @return list<array{id: int, playerId: int, name: string, user_email: string|null, club_id: int, club: string|null, btm: string|null, contactno: string}>
     */
    public function execute(
        string $term,
        string $type = 'name',
        ?string $gender = null,
        ?int $club_id = null,
        int $limit = 100
    ): array {
        $term = trim( $term );
        if ( strlen( $term ) < 2 ) {
            return [];
        }

        $limit = max( 1, min( $limit, 100 ) );

        [ $sql, $params ] = $this->build_query( $term, $type, $gender, $club_id, $limit );

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $params // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            ),
            ARRAY_A
        );

        if ( empty( $results ) || ! is_array( $results ) ) {
            return [];
        }

        return array_map( fn( array $row ): array => $this->format_row( $row ), $results );
    }

    /**
     * Build SQL query and parameter list for registered player autocomplete.
     *
     * @param string      $term Search query (name or BTM).
     * @param string      $type Lookup type ('name' or 'btm').
     * @param string|null $gender Optional gender filter ('M' or 'F').
     * @param int|null    $club_id Optional club ID filter.
     * @param int         $limit Max results to return.
     *
     * @return array{0: string, 1: list<mixed>} Tuple of [sql, params].
     */
    private function build_query(
        string $term,
        string $type,
        ?string $gender,
        ?int $club_id,
        int $limit
    ): array {
        $users_table        = $this->wpdb->users;
        $usermeta_table     = $this->wpdb->usermeta;
        $club_players_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $clubs_table        = $this->wpdb->prefix . 'racketmanager_clubs';

        $where_clauses = [
            'r.removed_date IS NULL',
            'r.system_record IS NULL',
        ];
        $params        = [];

        $gender_join = '';
        if ( ! empty( $gender ) ) {
            $gender_join = "INNER JOIN $usermeta_table um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = %s AND um_gender.meta_value = %s";
            $params[]    = self::META_KEY_GENDER;
            $params[]    = $gender;
        }

        if ( 'btm' === $type ) {
            $where_clauses[] = 'um_btm.meta_value = %s';
            $params[]        = $term;
            $btm_join_type   = 'INNER JOIN';
        } else {
            $where_clauses[] = 'u.display_name LIKE %s';
            $params[]        = '%' . $this->esc_like( $term ) . '%';
            $btm_join_type   = 'LEFT JOIN';
        }

        if ( ! empty( $club_id ) ) {
            $where_clauses[] = 'r.club_id = %d';
            $params[]        = $club_id;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        $sql = "
            SELECT
                r.id AS id,
                u.ID AS playerId,
                u.display_name AS name,
                u.user_email,
                c.id AS club_id,
                COALESCE(NULLIF(c.shortcode, ''), c.name) AS club,
                um_btm.meta_value AS btm,
                COALESCE(um_phone.meta_value, '') AS contactno
            FROM $users_table u
            INNER JOIN $club_players_table r ON u.ID = r.player_id
            INNER JOIN $clubs_table c ON r.club_id = c.id
            $gender_join
            $btm_join_type $usermeta_table um_btm ON u.ID = um_btm.user_id AND um_btm.meta_key = '" . self::META_KEY_BTM . "'
            LEFT JOIN $usermeta_table um_phone ON u.ID = um_phone.user_id AND um_phone.meta_key = 'contactno'
            WHERE $where_sql
            ORDER BY u.display_name ASC, c.shortcode ASC
            LIMIT %d
        ";

        $params[] = $limit;

        return [ $sql, $params ];
    }


    /**
     * Format a database row into an autocomplete projection array.
     *
     * @param array<string, mixed> $row Database row.
     *
     * @return array{id: int, playerId: int, name: string, user_email: string|null, club_id: int, club: string|null, btm: string|null, contactno: string}
     */
    private function format_row( array $row ): array {
        return [
            'id'         => (int) ( $row['id'] ?? 0 ),
            'playerId'   => (int) ( $row['playerId'] ?? 0 ),
            'name'       => (string) ( $row['name'] ?? '' ),
            'user_email' => $this->normalize_nullable_string( $row['user_email'] ?? null ),
            'club_id'    => (int) ( $row['club_id'] ?? 0 ),
            'club'       => $this->normalize_nullable_string( $row['club'] ?? null ),
            'btm'        => $this->normalize_nullable_string( $row['btm'] ?? null ),
            'contactno'  => (string) ( $row['contactno'] ?? '' ),
        ];
    }


    /**
     * Normalise empty or null values to null, otherwise return as string.
     *
     * @param mixed $value Value to normalize.
     *
     * @return string|null String value or null if empty.
     */
    private function normalize_nullable_string( mixed $value ): ?string {
        if ( null === $value || '' === $value ) {
            return null;
        }

        return (string) $value;
    }

    /**
     * Escape special characters for use in a SQL LIKE clause.
     *
     * @param string $text Text to escape.
     *
     * @return string Escaped text safe for a LIKE pattern.
     */
    private function esc_like( string $text ): string {
        return method_exists( $this->wpdb, 'esc_like' )
            ? $this->wpdb->esc_like( $text )
            : addcslashes( $text, '_%\\' );
    }
}
