<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Util\Util;
use function htmlspecialchars;
use function is_array;
use function is_numeric;
use function is_string;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Tournament_Query {
    public function __construct(
        public readonly int|string|null $tournament = null,
        public readonly string $template = ''
    ) {}

    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $tournament = $atts_array['tournament'] ?? null;
        if ( ! $tournament ) {
            if ( ! empty( $get['tournament'] ) ) {
                $tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['tournament'] ) ) );
            } elseif ( isset( $query_vars['tournament'] ) ) {
                $tournament = $query_vars['tournament'];
            }
        }

        if ( is_string( $tournament ) && '' !== trim( $tournament ) ) {
            $tournament = Util::un_seo_url( trim( $tournament ) );
        } elseif ( is_numeric( $tournament ) ) {
            $tournament = (int) $tournament;
        } else {
            $tournament = null;
        }

        $template = isset( $atts_array['template'] ) ? (string) $atts_array['template'] : '';

        return new self( $tournament, $template );
    }
}
