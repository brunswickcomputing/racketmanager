<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Queries;

use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;

/**
 * Handler for retrieving player autocomplete suggestions based on lookup criteria.
 *
 * Supported autocomplete types (`name`, `btm`, or null/empty) return a projected
 * read model of jQuery UI autocomplete items matching registered club players.
 *
 * All unsupported lookup types return empty arrays to prevent leaking sensitive
 * player entities or unverified records.
 */
class Get_Player_Details_Handler {
    private const int MIN_AUTOCOMPLETE_TERM_LENGTH = 2;
    private const int MAX_AUTOCOMPLETE_RESULTS = 100;

    public function __construct(
        private readonly Player_Repository_Interface $player_repository
    ) {
    }

    /**
     * Handle player details lookup.
     *
     * Supported types:
     * - 'name' (or null/empty): Autocomplete search by player name for registered club players.
     * - 'btm': Autocomplete search by LTA tennis / BTM number.
     *
     * All other types (including 'club', 'registered', 'non_club', 'championship', 'user', 'all')
     * are unsupported on this public endpoint and return an empty array.
     *
     * @param Get_Player_Details_Query $query
     * @return list<array{label: string, value: string, name: string, id: int|null, playerId: int, btm: string|null, contactno: string, user_email: string|null, club_id: int|null, club: string|null}>
     */
    public function handle( Get_Player_Details_Query $query ): array {
        $type = $query->type ? strtolower( trim( $query->type ) ) : 'name';

        if ( 'btm' !== $type && 'name' !== $type ) {
            return [];
        }

        $term = trim( (string) $query->name );
        if ( strlen( $term ) < self::MIN_AUTOCOMPLETE_TERM_LENGTH ) {
            return [];
        }

        $records = $this->player_repository->find_registered_player_autocomplete(
            term: $term,
            type: $type,
            gender: $query->gender,
            club_id: $query->club_id,
            limit: self::MAX_AUTOCOMPLETE_RESULTS
        );

        if ( empty( $records ) ) {
            return [];
        }

        $items = [];
        foreach ( $records as $row ) {
            $club_name = $row['club'];
            $name      = $row['name'];
            $label     = $name;
            if ( ! empty( $club_name ) ) {
                $label .= ' - ' . $club_name;
            }

            $value = ( 'btm' === $type ) ? ( $row['btm'] ?: $term ) : $name;

            $items[] = [
                'label'      => $label,
                'value'      => $value,
                'name'       => $name,
                'id'         => $row['id'],
                'playerId'   => $row['playerId'],
                'btm'        => $row['btm'],
                'contactno'  => $row['contactno'],
                'user_email' => $row['user_email'],
                'club_id'    => $row['club_id'],
                'club'       => $club_name,
            ];
        }

        return $items;
    }
}
