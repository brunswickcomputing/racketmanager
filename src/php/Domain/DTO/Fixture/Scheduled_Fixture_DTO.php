<?php
/**
 * Scheduled_Fixture_DTO API: Scheduled_Fixture_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Class to implement the Scheduled Fixture Data Transfer Object
 */
readonly class Scheduled_Fixture_DTO {
    /**
     * Constructor
     */
    public function __construct(
        public int $fixture_id,
        public int $start_time
    ) {}

}
