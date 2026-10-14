<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

use stdClass;

/**
 * Command DTO for club profile updates from AJAX POST.
 */
readonly class Update_Club_Command {
    public function __construct(
        public ?int $club_id,
        public ?string $contact_no,
        public ?string $facilities,
        public ?int $founded,
        public ?string $website,
        public ?string $address,
        public ?int $match_secretary_id,
        public ?string $match_secretary_contact_no,
        public ?string $match_secretary_email
    ) {
    }

    /**
     * @param array<string, mixed> $post
     */
    public static function from_post( array $post ): self {
        return new self(
            club_id: isset( $post['club_id'] ) ? intval( $post['club_id'] ) : null,
            contact_no: isset( $post['clubContactNo'] ) ? sanitize_text_field( wp_unslash( $post['clubContactNo'] ) ) : null,
            facilities: isset( $post['facilities'] ) ? sanitize_text_field( wp_unslash( $post['facilities'] ) ) : null,
            founded: isset( $post['founded'] ) ? intval( $post['founded'] ) : null,
            website: isset( $post['website'] ) ? sanitize_text_field( wp_unslash( $post['website'] ) ) : null,
            address: isset( $post['address'] ) ? sanitize_text_field( wp_unslash( $post['address'] ) ) : null,
            match_secretary_id: isset( $post['matchSecretaryId'] ) ? intval( $post['matchSecretaryId'] ) : null,
            match_secretary_contact_no: isset( $post['matchSecretaryContactNo'] ) ? sanitize_text_field( wp_unslash( $post['matchSecretaryContactNo'] ) ) : null,
            match_secretary_email: isset( $post['matchSecretaryEmail'] ) ? sanitize_text_field( wp_unslash( $post['matchSecretaryEmail'] ) ) : null
        );
    }

    public function match_secretary(): stdClass {
        $match_secretary            = new stdClass();
        $match_secretary->id        = $this->match_secretary_id;
        $match_secretary->contactno = $this->match_secretary_contact_no;
        $match_secretary->email     = $this->match_secretary_email;

        return $match_secretary;
    }
}
