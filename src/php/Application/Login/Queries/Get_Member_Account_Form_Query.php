<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\Queries;

use function get_current_user_id;
use function is_user_logged_in;
use function wp_get_current_user;

class Get_Member_Account_Form_Query {
    public function __construct(
        public readonly bool $is_logged_in = false,
        public readonly ?int $user_id = null
    ) {}

    public static function from_atts(
        array|string $atts = []
    ): self {
        $is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
        $user_id      = null;
        if ( $is_logged_in ) {
            if ( function_exists( 'wp_get_current_user' ) ) {
                $user = wp_get_current_user();
                $user_id = isset( $user->ID ) ? (int) $user->ID : ( function_exists( 'get_current_user_id' ) ? (int) get_current_user_id() : null );
            } elseif ( function_exists( 'get_current_user_id' ) ) {
                $user_id = (int) get_current_user_id();
            }
        }

        return new self(
            is_logged_in: $is_logged_in,
            user_id: $user_id
        );
    }
}
