<?php
/**
 * Tournament_Finals_Config_Request_DTO API: Tournament_Finals_Config_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Finals Config Request Data Transfer Object
 */
readonly class Tournament_Finals_Config_Request_DTO {

    public ?string $start_time;
    public ?string $time_increment;
    public ?int $num_courts;

    /**
     * Tournament_Finals_Config_Request_DTO constructor.
     *
     * @param array $data
     */
    public function __construct( array $data ) {
        $this->num_courts     = isset( $data['numCourtsAvailable'] ) ? intval( $data['numCourtsAvailable'] ) : null;
        $this->start_time     = isset( $data['start_time'] ) ? sanitize_text_field( wp_unslash( $data['start_time'] ) ) : null;
        $this->time_increment = isset( $data['timeIncrement'] ) ? sanitize_text_field( wp_unslash( $data['timeIncrement'] ) ) : null;
    }

}
