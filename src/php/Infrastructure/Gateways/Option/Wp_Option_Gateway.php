<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Gateways\Option;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;

class Wp_Option_Gateway implements Option_Gateway_Interface {
    public function get_option( string $option, mixed $default_value = false ): mixed {
        return get_option( $option, $default_value );
    }

    public function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool {
        return update_option( $option, $value, $autoload );
    }

    public function delete_option( string $option ): bool {
        return delete_option( $option );
    }
}
