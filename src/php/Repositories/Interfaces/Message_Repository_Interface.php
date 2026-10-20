<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Communication\Message;

interface Message_Repository_Interface extends Repository_Interface {
    public function find( int|string $message_id ): ?Message;
    public function find_by_id( int|string|null $id ): ?Message;
    public function find_by_user( int $user_id, array $args = [] ): array|int;
    public function delete_by_user_and_type( int $user_id, string $type ): int|false;
    public function save( object $entity ): bool|int;
    public function delete( int $id ): bool;
}
