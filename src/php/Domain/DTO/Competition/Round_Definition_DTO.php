<?php
/**
 * Round_Definition_DTO API: Round_Definition_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Competition;

/**
 * Class to implement the Round Definition Data Transfer Object
 */
readonly class Round_Definition_DTO {
    /**
     * Constructor
     */
    public function __construct(
        public string $key,
        public int $num_matches,
        public int $level,
        public string $date
    ) {}

}
