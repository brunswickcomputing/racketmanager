<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

/**
 * Query for retrieving competition details for the [competition] shortcode.
 */
final readonly class Get_Competition_Query {
    public ?int $competition_id;
    public ?string $competition_name;
    public ?string $season;
    public string $template;

    public function __construct(
        ?int $competition_id = null,
        ?string $competition_name = null,
        ?string $season = null,
        string $template = ''
    ) {
        $this->competition_id   = ( null !== $competition_id && $competition_id > 0 ) ? $competition_id : null;
        $this->competition_name = ( null !== $competition_name && '' !== trim( $competition_name ) ) ? trim( $competition_name ) : null;
        $this->season           = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->template         = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $comp_val   = $atts_array['competition'] ?? null;

        if ( ! $comp_val ) {
            if ( ! empty( $get['competition'] ) ) {
                $comp_val = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['competition'] ) ) );
            } elseif ( isset( $query_vars['competition'] ) ) {
                $comp_val = $query_vars['competition'];
            }
        }

        $competition_id   = is_numeric( $comp_val ) ? (int) $comp_val : null;
        $competition_name = ! is_numeric( $comp_val ) && ! empty( $comp_val ) ? (string) $comp_val : null;

        $season   = $atts_array['season'] ?? null;
        $template = $atts_array['template'] ?? '';

        return new self(
            competition_id: $competition_id,
            competition_name: $competition_name,
            season: $season ? (string) $season : null,
            template: (string) $template
        );
    }
}
