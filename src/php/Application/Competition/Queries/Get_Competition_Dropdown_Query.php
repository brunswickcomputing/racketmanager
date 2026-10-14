<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

final readonly class Get_Competition_Dropdown_Query {
    public function __construct(
        public ?int $competition_id,
        public string $template = ''
    ) {}

    public static function from_atts( array|string $atts ): self {
        $atts     = is_array( $atts ) ? $atts : [];
        $id       = $atts['id'] ?? null;
        $template = $atts['template'] ?? '';

        return new self(
            is_scalar( $id ) && is_numeric( $id ) && (int) $id > 0 ? (int) $id : null,
            is_string( $template ) ? ( preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '' ) : ''
        );
    }
}