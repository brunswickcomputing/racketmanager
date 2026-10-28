<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Account\Ports;

interface User_Favourites_Gateway_Interface {
    /** @return array<string, list<int|string>>|null Null for an unauthenticated visitor. */
    public function get_current_user_favourites(): ?array;
}
