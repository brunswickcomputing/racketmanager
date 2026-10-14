<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Club\Queries;

/**
 * Query for retrieving club details for the [club] shortcode.
 */
final readonly class Get_Club_Query {
    public ?string $club_name;
    public string $template;

    public function __construct(
        ?string $club_name = null,
        string $template = ''
    ) {
        $this->club_name = ( null !== $club_name && '' !== trim( $club_name ) ) ? trim( $club_name ) : null;
        $this->template  = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, array $query_vars = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $club_name  = $query_vars['club_name'] ?? null;
        if ( ! empty( $club_name ) ) {
            $club_name = str_replace( '-', ' ', (string) $club_name );
        }

        $template = $atts_array['template'] ?? '';

        return new self(
            club_name: $club_name ? (string) $club_name : null,
            template: (string) $template
        );
    }
}
