<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Ports;

interface Query_Vars_Interface {
    public function get( string $key, mixed $default = null ): mixed;

    public function has( string $key ): bool;

    public function set( string $key, mixed $value ): void;

    public function all(): array;
}
