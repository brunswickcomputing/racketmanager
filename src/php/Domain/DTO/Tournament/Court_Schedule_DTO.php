<?php
/**
 * Court_Schedule_DTO API: Court_Schedule_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use Racketmanager\Domain\DTO\Fixture\Scheduled_Fixture_DTO;

/**
 * Class to implement the Court Schedule Data Transfer Object
 */
readonly class Court_Schedule_DTO {
    /**
     * Constructor
     */
    public function __construct(
        public string $name,
        public string $start_time,
        /** @var array<int, Scheduled_Fixture_DTO> Keyed by Slot Index */
        public array $slots
    ) {}

}
