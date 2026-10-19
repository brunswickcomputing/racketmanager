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
    public ?string $tab;
    public bool $is_singular;
    public string|int $post_id;
    public string $pagename;

    public function __construct(
        ?int $competition_id = null,
        ?string $competition_name = null,
        ?string $season = null,
        string $template = '',
        ?string $tab = null,
        bool $is_singular = false,
        string|int $post_id = '',
        string $pagename = ''
    ) {
        $this->competition_id   = ( null !== $competition_id && $competition_id > 0 ) ? $competition_id : null;
        $this->competition_name = ( null !== $competition_name && '' !== trim( $competition_name ) ) ? trim( $competition_name ) : null;
        $this->season           = ( null !== $season && '' !== trim( $season ) && false !== $season ) ? trim( (string) $season ) : null;
        $this->template         = preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '';
        $this->tab              = ( null !== $tab && '' !== trim( $tab ) ) ? trim( $tab ) : null;
        $this->is_singular      = $is_singular;
        $this->post_id          = ( is_int( $post_id ) || is_string( $post_id ) ) ? $post_id : '';
        $this->pagename         = ( is_int( $pagename ) || is_string( $pagename ) ) ? (string) $pagename : '';
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
                $tab         = 'team';
            } else {
                $tab = 'overview';
            }
        }

        $post_id = $atts_array['post_id'] ?? ( $query_vars['post_id'] ?? ( function_exists( 'get_the_ID' ) && false !== get_the_ID() ? (int) get_the_ID() : '' ) );
        if ( ! is_int( $post_id ) && ! is_string( $post_id ) ) {
            $post_id = '';
        }

        $pagename = $atts_array['pagename'] ?? '';
        if ( ! is_string( $pagename ) && ! is_int( $pagename ) ) {
            $pagename = '';
        }

        return new self(
            competition_id: $competition_id,
            competition_name: $competition_name,
            season: $season ? (string) $season : null,
            template: (string) $template,
            tab: $tab,
            is_singular: $is_singular,
            post_id: $post_id,
            pagename: (string) $pagename
        );
    }
}
