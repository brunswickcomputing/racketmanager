<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Player\User;

interface User_Repository_Interface extends Repository_Interface {
    public function find( int|string $user_id ): ?User;
    public function find_by_id( int|string|null $id ): ?User;
    public function save( object $entity ): bool|int;
    public function update_contact( User $user, string $contact_no, string $contact_email ): bool;
    public function get_favourites( User $user, ?string $type = null ): array;
    public function delete( int $id ): bool;
}
