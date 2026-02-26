<?php
/**
 * Tournament_Event_DTO API: Tournament_Event_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Class to implement the Tournament Event Data Transfer Object
 */
readonly class Tournament_Event_DTO {

    /**
     * Tournament_Event_Entry_DTO constructor.
     *
     */
    public function __construct(
        public int $id,
        public string $name,
        /** @var Tournament_Event_Entry_DTO[] */
        public array $entries,
        public ?int $num_seeds
    ) {}

}
