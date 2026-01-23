<?php
/**
 * Team_Entry_DTO API: Team_Entry_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain\DTO;

/**
 * Class to implement the Team Entry Data Transfer Object
 */
readonly class Team_Entry_DTO {

    public function __construct(
        public int $id,
        public string $team_name,
        public int $match_day,
        public string $match_time,
        public int $captain_id,
        public string $captain,
        public string $telephone,
        public string $email,
        public bool $existing,
        public int $league_id,
    ) {}

    /**
     * Factory to satisfy linting rules against long parameter lists
     */
    public static function from_array( array $data ): self {
        return new self(
            id:         (int) ($data['id'] ?? 0),
            team_name:  (string) ($data['team_name'] ?? ''),
            match_day:  (int) ($data['match_day'] ?? 0),
            match_time: (string) ($data['match_time'] ?? ''),
            captain_id: (int) ($data['captain_id'] ?? 0),
            captain:    (string) ($data['captain'] ?? ''),
            telephone:  (string) ($data['telephone'] ?? ''),
            email:      (string) ($data['email'] ?? ''),
            existing:   (bool) ($data['existing'] ?? false),
            league_id:  (int) ($data['league_id'] ?? 0),
        );
    }

}
