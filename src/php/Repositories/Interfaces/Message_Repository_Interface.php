<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Message;

interface Message_Repository_Interface extends Repository_Interface {
    public function find( int|string $message_id ): ?Message;
    public function find_by_id( int|string|null $id ): ?Message;
    public function save( object $entity ): bool|int;
    public function delete( int $id ): bool;
}
