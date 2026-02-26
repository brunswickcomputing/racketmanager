<?php
/**
 * Tournament_Information_Request_DTO API: Tournament_Information_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Information Request Data Transfer Object
 */
final class Tournament_Information_Request_DTO {
    public string $parking;
    public string $catering;
    public string $photography;
    public string $spectators;
    public string $referee;
    public string $match_format;

    /**
     * Tournament_Entry_Request_DTO constructor.
     *
     * @param array $data
     */
    public function __construct( array $data ) {
        $this->parking       = sanitize_text_field( wp_unslash( $data['parking'] ?? null ) );
        $this->catering      = sanitize_text_field( wp_unslash( $data['catering'] ?? null ) );
        $this->photography   = sanitize_text_field( wp_unslash( $data['photography'] ?? null ) );
        $this->spectators    = sanitize_text_field( wp_unslash( $data['spectators'] ?? null ) );
        $this->referee       = sanitize_text_field( wp_unslash( $data['referee'] ?? null ) );
        $this->match_format  = sanitize_text_field( wp_unslash( $data['matchFormat'] ?? null ) );
    }

}
