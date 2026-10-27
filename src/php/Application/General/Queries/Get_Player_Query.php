<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Domain\Shared\Seo_Slug;
use function function_exists;
use function get_query_var;
use function is_array;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Player_Query {
    public function __construct(
        public readonly ?string $template = null,
        public readonly ?string $player_name = null,
        public readonly ?string $btm = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [ 'template' => '' ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $template = ! empty( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : null;

        $raw_player_name = null;
        if ( isset( $query_vars['player_id'] ) && '' !== $query_vars['player_id'] ) {
            $raw_player_name = (string) $query_vars['player_id'];
        } elseif ( isset( $get_params['player_id'] ) && '' !== $get_params['player_id'] ) {
            $raw_player_name = (string) $get_params['player_id'];
        } elseif ( function_exists( 'get_query_var' ) ) {
            $var = get_query_var( 'player_id' );
            if ( $var && '' !== $var ) {
                $raw_player_name = (string) $var;
            }
        }

        $player_name = null;
        if ( null !== $raw_player_name ) {
            $player_name = Seo_Slug::decode( $raw_player_name );
        }

        $btm = null;
        if ( isset( $query_vars['btm'] ) && '' !== $query_vars['btm'] ) {
            $btm = (string) $query_vars['btm'];
        } elseif ( isset( $get_params['btm'] ) && '' !== $get_params['btm'] ) {
            $btm = (string) $get_params['btm'];
        } elseif ( function_exists( 'get_query_var' ) ) {
            $var = get_query_var( 'btm' );
            if ( $var && '' !== $var ) {
                $btm = (string) $var;
            }
        }

        return new self(
            template: $template,
            player_name: $player_name,
            btm: $btm
        );
    }
}
