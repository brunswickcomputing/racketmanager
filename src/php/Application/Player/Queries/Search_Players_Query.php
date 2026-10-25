<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Player\Queries;

/**
 * Query DTO for searching players by text string.
 */
readonly class Search_Players_Query {
    public function __construct(
        public ?string $search_string = null
    ) {
    }

    /**
     * @param array<string, mixed> $request
     * @return self
     */
    public static function from_request( array $request ): self {
        $search = isset( $request['search_string'] ) ? sanitize_text_field( wp_unslash( (string) $request['search_string'] ) ) : null;
        if ( ! $search && isset( $request['name'] ) ) {
            $search = sanitize_text_field( wp_unslash( (string) $request['name'] ) );
        }

        return new self(
            search_string: $search ?: null
        );
    }
}
