<?php
/**
 * Tournament_Partner_Request_DTO API: Tournament_Partner_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Request Data Transfer Object
 */
readonly class Tournament_Partner_Request_DTO {
    public ?int $player_id;
    public ?int $partner_id;
    public ?int $tournament_id;
    public ?int $event_id;
    public ?string $modal;
    public ?string $partner_name;

    /**
     * Tournament_Partner_Request_DTO constructor.
     *
     * @param array $data
     */
    public function __construct( array $data ) {
        $this->player_id     = isset( $data['playerId'] ) ? intval( $data['playerId'] ) : null;
        $this->partner_id    = isset( $data['partnerId'] ) ? intval( $data['partnerId'] ) : null;
        $this->modal         = isset( $data['modal'] ) ? sanitize_text_field( wp_unslash( $data['modal'] ) ) : null;
        $this->partner_name  = isset( $data['partner'] ) ? sanitize_text_field( wp_unslash( $data['partner'] ) ) : null;
        $this->event_id      = isset( $data['eventId'] ) ? intval( $data['eventId'] ) : null;
        $this->tournament_id = isset( $data['tournament_id'] ) ? intval( $data['tournament_id'] ) : null;

    }

}
