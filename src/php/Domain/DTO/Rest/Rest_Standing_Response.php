<?php
/**
 * REST standing response DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Rest
 */

declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rest;

use stdClass;

/**
 * Typed, immutable response DTO for a single row of the REST standings collection.
 *
 * Serialises to the legacy JSON shape: club (only when a club filter applies), league, season,
 * team, rank, status, played, won, drawn, lost, points. Scalar values are kept as supplied so
 * the JSON types (int vs string) are unchanged.
 */
final readonly class Rest_Standing_Response {

    public function __construct(
        public int|float|string|bool|null $league,
        public int|float|string|bool|null $season,
        public int|float|string|bool|null $team,
        public int|float|string|bool|null $rank,
        public int|float|string|bool|null $status,
        public int|float|string|bool|null $played,
        public int|float|string|bool|null $won,
        public int|float|string|bool|null $drawn,
        public int|float|string|bool|null $lost,
        public int|float|string|bool|null $points,
        public ?string $club = null,
    ) {
    }

    /**
     * Serialise to an associative array preserving the legacy key order and optional keys.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        $data = array();
        if ( null !== $this->club ) {
            $data['club'] = $this->club;
        }
        $data['league'] = $this->league;
        $data['season'] = $this->season;
        $data['team']   = $this->team;
        $data['rank']   = $this->rank;
        $data['status'] = $this->status;
        $data['played'] = $this->played;
        $data['won']    = $this->won;
        $data['drawn']  = $this->drawn;
        $data['lost']   = $this->lost;
        $data['points'] = $this->points;

        return $data;
    }

    /**
     * Serialise to the legacy stdClass representation used in WP_REST_Response data.
     *
     * @return stdClass
     */
    public function to_object(): stdClass {
        return (object) $this->to_array();
    }
}
