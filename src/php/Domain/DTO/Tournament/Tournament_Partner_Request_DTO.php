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
        $this->player_id     = self::parse_int_fallback( $data['playerId'] ?? null, $data['player_id'] ?? null );
        $this->partner_id    = self::parse_int_fallback( $data['partnerId'] ?? null, $data['partner_id'] ?? null );
        $this->modal         = isset( $data['modal'] ) ? sanitize_text_field( wp_unslash( $data['modal'] ) ) : null;
        $this->partner_name  = isset( $data['partner'] ) ? sanitize_text_field( wp_unslash( $data['partner'] ) ) : null;
        $this->event_id      = self::parse_int_fallback( $data['eventId'] ?? null, $data['event_id'] ?? null );
        $this->tournament_id = self::parse_int_fallback( $data['tournamentId'] ?? null, $data['tournament_id'] ?? null, $data['tournament'] ?? null );
    }

    /**
     * Return the integer value of the first non-null and non-empty string argument.
     *
     * @param mixed ...$values
     * @return int|null
     */
    private static function parse_int_fallback( mixed ...$values ): ?int {
        foreach ( $values as $value ) {
            if ( null !== $value && '' !== $value ) {
                return intval( $value );
            }
        }

        return null;
    }

}
