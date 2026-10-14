<?php
/**
 * REST match response DTO
 *
 * @package RacketManager
 * @subpackage Domain/DTO/Rest
 */

declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Rest;

use stdClass;

/**
 * Typed, immutable response DTO for a single item of the REST fixtures / results collections.
 *
 * Serialises to the legacy JSON shape: league, home_team, away_team, match_date, match_time
 * and, only when the match has a result (winner set), score and status.
 */
final readonly class Rest_Match_Response {

    public function __construct(
        public string $league,
        public string $home_team,
        public string $away_team,
        public string $match_date,
        public int|float|string|bool|null $match_time,
        public bool $has_result = false,
        public ?string $score = null,
        public ?string $status = null,
    ) {
    }

    /**
     * Serialise to an associative array preserving the legacy key order and optional keys.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        $data = array(
            'league'     => $this->league,
            'home_team'  => $this->home_team,
            'away_team'  => $this->away_team,
            'match_date' => $this->match_date,
            'match_time' => $this->match_time,
        );
        if ( $this->has_result ) {
            $data['score']  = $this->score;
            $data['status'] = $this->status;
        }

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
