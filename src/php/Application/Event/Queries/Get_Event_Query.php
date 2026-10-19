<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\Queries;

use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function str_replace;

use function wp_strip_all_tags;
use function wp_unslash;

final readonly class Get_Event_Query {
    public function __construct(
        public ?int $event_id = null,
        public ?string $event_name = null,
        public ?string $season = null,
        public ?string $tab = null,
        public string|int $post_id = '',
        public string $pagename = '',
        public bool $is_singular = false
    ) {}

    public static function from_atts( array|string $atts = [], array $query_vars = [], array $get = [] ): self {
        $atts_array = is_array( $atts ) ? $atts : [];

        $event_id = null;
        if ( isset( $atts_array['id'] ) && is_numeric( $atts_array['id'] ) && (int) $atts_array['id'] > 0 ) {
            $event_id = (int) $atts_array['id'];
        } elseif ( isset( $atts_array['event_id'] ) && is_numeric( $atts_array['event_id'] ) && (int) $atts_array['event_id'] > 0 ) {
            $event_id = (int) $atts_array['event_id'];
        }

        $event_name = null;
        if ( ! $event_id ) {
            if ( ! empty( $atts_array['event_name'] ) ) {
                $event_name = (string) $atts_array['event_name'];
            } elseif ( ! empty( $atts_array['event'] ) ) {
                $event_name = (string) $atts_array['event'];
            } elseif ( ! empty( $query_vars['event'] ) ) {
                $event_name = str_replace( '-', ' ', (string) $query_vars['event'] );
            }
        }

        $season      = ! empty( $atts_array['season'] ) ? (string) $atts_array['season'] : null;
        $is_singular = false;
        $raw_tab     = ! empty( $get['tab'] ) ? (string) $get['tab'] : ( ! empty( $atts_array['tab'] ) ? (string) $atts_array['tab'] : ( ! empty( $query_vars['tab'] ) ? (string) $query_vars['tab'] : null ) );
        $tab         = null !== $raw_tab ? wp_strip_all_tags( wp_unslash( $raw_tab ) ) : null;
        if ( empty( $tab ) ) {
            if ( isset( $query_vars['player_id'] ) || isset( $get['player_id'] ) ) {
                $is_singular = true;
                $tab         = 'players';
            } elseif ( isset( $query_vars['club_name'] ) || isset( $get['club_name'] ) ) {
                $is_singular = true;
                $tab         = 'clubs';
            } elseif ( isset( $query_vars['team'] ) || isset( $get['team'] ) ) {
                $is_singular = true;
                $tab         = 'teams';
            }
        }

        $post_id = $atts_array['post_id'] ?? ( $query_vars['post_id'] ?? ( function_exists( 'get_the_ID' ) ? get_the_ID() : '' ) );
        if ( ! is_int( $post_id ) && ! is_string( $post_id ) ) {
            $post_id = '';
        }

        $pagename = $atts_array['pagename'] ?? ( $query_vars['pagename'] ?? ( function_exists( 'get_query_var' ) ? get_query_var( 'pagename' ) : '' ) );
        if ( ! is_string( $pagename ) && ! is_int( $pagename ) ) {
            $pagename = '';
        }

        return new self(
            event_id: $event_id,
            event_name: $event_name,
            season: $season,
            tab: $tab,
            post_id: $post_id,
            pagename: (string) $pagename,
            is_singular: $is_singular
        );
    }
}
