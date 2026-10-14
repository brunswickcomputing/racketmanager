<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use function get_current_user_id;
use function is_array;
use function is_numeric;
use function is_user_logged_in;
use function sanitize_text_field;
use function shortcode_atts;
use function wp_unslash;

class Get_Message_Query {
    public function __construct(
        public readonly ?int $message_id = null,
        public readonly string $template = '',
        public readonly bool $is_logged_in = false,
        public readonly ?int $user_id = null
    ) {}

    public static function from_atts(
        array|string $atts = [],
        array $query_vars = [],
        array $get_params = []
    ): self {
        $atts_array = is_array( $atts ) ? $atts : [];
        $defaults   = [
            'id'       => 0,
            'template' => '',
        ];
        $parsed     = shortcode_atts( $defaults, $atts_array );

        $raw_id = null;
        if ( ! empty( $parsed['id'] ) ) {
            $raw_id = $parsed['id'];
        } elseif ( ! empty( $query_vars['message_id'] ) ) {
            $raw_id = $query_vars['message_id'];
        } elseif ( ! empty( $query_vars['id'] ) ) {
            $raw_id = $query_vars['id'];
        } elseif ( ! empty( $get_params['id'] ) ) {
            $raw_id = $get_params['id'];
        } elseif ( ! empty( $get_params['message_id'] ) ) {
            $raw_id = $get_params['message_id'];
        } elseif ( ! empty( $get_params['message'] ) ) {
            $raw_id = $get_params['message'];
        }

        $message_id = ( null !== $raw_id && is_numeric( $raw_id ) ) ? (int) $raw_id : null;
        $template   = isset( $parsed['template'] ) ? sanitize_text_field( wp_unslash( (string) $parsed['template'] ) ) : '';

        $is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
        $user_id      = $is_logged_in && function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null;

        return new self(
            message_id: $message_id,
            template: $template,
            is_logged_in: $is_logged_in,
            user_id: $user_id
        );
    }
}
