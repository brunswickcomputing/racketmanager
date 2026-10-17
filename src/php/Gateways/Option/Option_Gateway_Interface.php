<?php
declare( strict_types=1 );

namespace Racketmanager\Gateways\Option;

interface Option_Gateway_Interface {
    public function get_option( string $option, mixed $default_value = false ): mixed;
    public function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool;
    public function delete_option( string $option ): bool;
}
