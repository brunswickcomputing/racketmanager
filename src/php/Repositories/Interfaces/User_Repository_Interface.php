<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\User;

interface User_Repository_Interface extends Repository_Interface {
    public function find( int|string $user_id ): ?User;
    public function find_by_id( int|string|null $id ): ?User;
    public function save( object $entity ): bool|int;
    public function delete( int $id ): bool;
}
