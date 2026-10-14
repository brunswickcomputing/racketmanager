<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Command for searching players.
 */
readonly class Search_Players_Command {
    public function __construct(
        public ?string $search_string = null,
        public ?string $type = null,
        public ?string $gender = null,
        public ?int $club_id = null
    ) {}

    public static function from_request( array $data ): self {
        $search = isset( $data['search_string'] ) ? sanitize_text_field( wp_unslash( (string) $data['search_string'] ) ) : null;
        if ( ! $search && isset( $data['name'] ) ) {
            $search = sanitize_text_field( wp_unslash( (string) $data['name'] ) );
        }
        $type = isset( $data['type'] ) ? sanitize_text_field( wp_unslash( (string) $data['type'] ) ) : null;
        $gender = ! empty( $data['partnerGender'] ) ? sanitize_text_field( wp_unslash( (string) $data['partnerGender'] ) ) : ( ! empty( $data['gender'] ) ? sanitize_text_field( wp_unslash( (string) $data['gender'] ) ) : null );
        $club_id = ! empty( $data['club'] ) ? (int) $data['club'] : ( ! empty( $data['club_id'] ) ? (int) $data['club_id'] : null );

        return new self(
            search_string: $search ?: null,
            type: $type ?: null,
            gender: $gender ?: null,
            club_id: $club_id ?: null
        );
    }
}
