<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Util\Util;
use function htmlspecialchars;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function trim;
use function wp_strip_all_tags;
use function wp_unslash;

final class Get_Tournament_Query {
    public function __construct(
        public readonly int|string|null $tournament = null,
        public readonly string $template = '',
        public readonly ?string $tab = null,
        public readonly string|int $post_id = '',
        public readonly string $pagename = '',
        public readonly ?string $match_day = null,
        public readonly int|string|null $team_id = null,
        public readonly ?string $event = null
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
        $raw_tab  = ! empty( $get['tab'] ) ? (string) $get['tab'] : ( ! empty( $atts_array['tab'] ) ? (string) $atts_array['tab'] : ( ! empty( $query_vars['tab'] ) ? (string) $query_vars['tab'] : null ) );
        $tab      = null !== $raw_tab ? wp_strip_all_tags( wp_unslash( $raw_tab ) ) : null;

        $post_id = $atts_array['post_id'] ?? ( $query_vars['post_id'] ?? ( function_exists( 'get_the_ID' ) && false !== get_the_ID() ? (int) get_the_ID() : '' ) );
        if ( ! is_int( $post_id ) && ! is_string( $post_id ) ) {
            $post_id = '';
        }

        $pagename = $atts_array['pagename'] ?? ( $query_vars['pagename'] ?? ( function_exists( 'get_query_var' ) ? (string) get_query_var( 'pagename' ) : '' ) );
        if ( ! is_string( $pagename ) && ! is_int( $pagename ) ) {
            $pagename = '';
        }

        $match_day = ! empty( $get['match_day'] ) ? (string) $get['match_day'] : ( ! empty( $query_vars['match_day'] ) ? (string) $query_vars['match_day'] : ( function_exists( 'get_query_var' ) && get_query_var( 'match_day' ) ? (string) get_query_var( 'match_day' ) : null ) );
        $team_id   = $get['team_id'] ?? ( $query_vars['team_id'] ?? null );
        $event     = $atts_array['event'] ?? ( $query_vars['event'] ?? null );

        return new self(
            tournament: $tournament,
            template: $template,
            tab: $tab,
            post_id: $post_id,
            pagename: (string) $pagename,
            match_day: $match_day,
            team_id: $team_id,
            event: $event ? (string) $event : null
        );
    }
}
