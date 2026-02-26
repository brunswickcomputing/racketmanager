<?php
/**
 * Tournament_Event_Entry_DTO API: Tournament_Event_Entry_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Class to implement the Tournament Event Entry Data Transfer Object
 */
readonly class Tournament_Event_Entry_DTO {

    /**
     * Tournament_Event_Entry_DTO constructor.
     *
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?int $rank,
        public ?float $rating,
        /** @var Tournament_Player_DTO[] */
        public array $players
    ) {}

}
