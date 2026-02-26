<?php
/**
 * Championship_Rounds_Request_DTO API: Championship_Rounds_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use Racketmanager\Domain\DTO\Competition\Round_Definition_DTO;

/**
 * Class to implement the Championship Rounds Request Data Transfer Object
 */
readonly class Championship_Rounds_Request_DTO {
    public int $season;
    /** @var array<int, Round_Definition_DTO> Keyed by index */
    public array $rounds;

    public function __construct(array $data) {
        $this->season       = (int) ($data['season'] ?? 0);
        $this->rounds       = self::map_rounds($data['rounds'] ?? []);
    }
    /**
     * Maps the nested rounds array into DTOs
     */
    private static function map_rounds(array $rounds_data): array {
        $mapped = [];

        foreach ($rounds_data as $idx => $round) {
            $mapped[(int)$idx] = new Round_Definition_DTO(
                key:        sanitize_text_field($round['key'] ?? ''),
                num_matches: (int) ($round['num_matches'] ?? 0),
                level: (int) ($round['round'] ?? 0),
                date:  sanitize_text_field($round['match_date'] ?? '')
            );
        }

        return $mapped;
    }

}
