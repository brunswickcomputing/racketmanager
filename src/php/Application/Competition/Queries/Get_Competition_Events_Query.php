<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use function htmlspecialchars;
use function is_array;
use function is_numeric;
use function preg_replace;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

/**
 * Query for retrieving competition events data for the [competition-events] shortcode.
 */
final readonly class Get_Competition_Events_Query {
    public ?int $competition_id;
    public ?string $competition_name;
    public ?int $season;
    public string $template;

    public function __construct(
        ?int $competition_id = null,
        ?string $competition_name = null,
        ?int $season = null,
        string $template = ''
    ) {
        $this->competition_id   = ( null !== $competition_id && $competition_id > 0 ) ? $competition_id : null;
        $this->competition_name = ( null !== $competition_name && '' !== trim( $competition_name ) ) ? trim( $competition_name ) : null;
        $this->season           = ( null !== $season && $season > 0 ) ? $season : null;
        $this->template         = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
    }

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $comp_val   = $atts_array['id'] ?? $atts_array['competition'] ?? null;

        if ( ! $comp_val ) {
            if ( ! empty( $get['competition'] ) ) {
                $comp_val = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['competition'] ) ) );
            } elseif ( ! empty( $get['id'] ) ) {
                $comp_val = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['id'] ) ) );
            } elseif ( isset( $query_vars['competition'] ) ) {
                $comp_val = $query_vars['competition'];
            } elseif ( isset( $query_vars['competition_id'] ) ) {
                $comp_val = $query_vars['competition_id'];
            } elseif ( isset( $query_vars['competition_name'] ) ) {
                $comp_val = $query_vars['competition_name'];
            }
        }

        $competition_id   = is_numeric( $comp_val ) ? (int) $comp_val : null;
        $competition_name = ! is_numeric( $comp_val ) && ! empty( $comp_val ) ? (string) $comp_val : null;

        $season_val = $atts_array['season'] ?? ( $query_vars['season'] ?? null );
        $season     = ( null !== $season_val && '' !== trim( (string) $season_val ) && false !== $season_val && is_numeric( $season_val ) ) ? (int) $season_val : null;
        $template   = $atts_array['template'] ?? '';

        return new self(
            competition_id: $competition_id,
            competition_name: $competition_name,
            season: $season,
            template: (string) $template
        );
    }
}
