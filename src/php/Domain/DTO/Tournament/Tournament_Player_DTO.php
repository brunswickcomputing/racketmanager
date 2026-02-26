<?php
/**
 * Tournament_Player_DTO API: Tournament_Player_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Class to implement the Tournament Player Data Transfer Object
 */
readonly class Tournament_Player_DTO {

    /**
     * Tournament_Player_DTO constructor.
     *
     */
    public function __construct(
        public int $id,
        public string $name
    ) {}

}
