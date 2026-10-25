<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Queries;

/**
 * Query DTO for retrieving player lists based on lookup criteria.
 */
readonly class Get_Player_Details_Query {
    public function __construct(
        public ?string $type = null,
        public ?string $name = null,
        public ?string $gender = null,
        public ?int $club_id = null
    ) {
    }

    /**
     * @param array<string, mixed> $request
     * @return self
     */
    public static function from_request( array $request ): self {
        $type    = isset( $request['type'] ) ? sanitize_text_field( wp_unslash( (string) $request['type'] ) ) : null;
        $name    = isset( $request['name'] ) ? sanitize_text_field( wp_unslash( (string) $request['name'] ) ) : null;
        $gender  = ! empty( $request['partnerGender'] ) ? sanitize_text_field( wp_unslash( (string) $request['partnerGender'] ) ) : ( ! empty( $request['gender'] ) ? sanitize_text_field( wp_unslash( (string) $request['gender'] ) ) : null );
        $club_id = empty( $request['club'] ) ? ( ! empty( $request['club_id'] ) ? (int) $request['club_id'] : null ) : (int) $request['club'];

        return new self(
            type: $type ?: null,
            name: $name ?: null,
            gender: $gender ?: null,
            club_id: $club_id > 0 ? $club_id : null
        );
    }
}
