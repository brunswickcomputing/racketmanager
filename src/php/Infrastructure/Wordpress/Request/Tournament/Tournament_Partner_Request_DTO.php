<?php
declare( strict_types=1 );
/**
 * Tournament_Partner_Request_DTO API: Tournament_Partner_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Request
 */

namespace Racketmanager\Infrastructure\Wordpress\Request\Tournament;

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
     * @param int|null $player_id
     * @param int|null $partner_id
     * @param int|null $tournament_id
     * @param int|null $event_id
     * @param string|null $modal
     * @param string|null $partner_name
     */
    public function __construct(
        array $data = [],
        ?int $player_id = null,
        ?int $partner_id = null,
        ?int $tournament_id = null,
        ?int $event_id = null,
        ?string $modal = null,
        ?string $partner_name = null
    ) {
        if ( ! empty( $data ) ) {
            $this->player_id     = self::parse_int_fallback( $data['playerId'] ?? null, $data['player_id'] ?? null ) ?? $player_id;
            $this->partner_id    = self::parse_int_fallback( $data['partnerId'] ?? null, $data['partner_id'] ?? null ) ?? $partner_id;
            $this->modal         = isset( $data['modal'] ) ? sanitize_text_field( wp_unslash( $data['modal'] ) ) : $modal;
            $this->partner_name  = isset( $data['partner'] ) ? sanitize_text_field( wp_unslash( $data['partner'] ) ) : ( isset( $data['partner_name'] ) ? sanitize_text_field( wp_unslash( $data['partner_name'] ) ) : $partner_name );
            $this->event_id      = self::parse_int_fallback( $data['eventId'] ?? null, $data['event_id'] ?? null ) ?? $event_id;
            $this->tournament_id = self::parse_int_fallback( $data['tournamentId'] ?? null, $data['tournament_id'] ?? null, $data['tournament'] ?? null ) ?? $tournament_id;
        } else {
            $this->player_id     = $player_id;
            $this->partner_id    = $partner_id;
            $this->tournament_id = $tournament_id;
            $this->event_id      = $event_id;
            $this->modal         = $modal;
            $this->partner_name  = $partner_name;
        }
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
