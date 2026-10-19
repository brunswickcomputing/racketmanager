<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\User;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use function get_user_by;
use function is_numeric;
use function is_string;

class User_Repository implements User_Repository_Interface {
    public function find( int|string $user_id ): ?User {
        $id = 0;
        if ( is_numeric( $user_id ) ) {
            $id = (int) $user_id;
        } elseif ( is_string( $user_id ) && '' !== $user_id ) {
            $user_obj = get_user_by( 'login', $user_id );
            if ( $user_obj && isset( $user_obj->ID ) ) {
                $id = (int) $user_obj->ID;
            }
        }

        if ( $id <= 0 ) {
            return null;
        }

        $user = User::get_instance( $id );
        return $user instanceof User ? $user : null;
    }

    public function find_by_id( int|string|null $id ): ?User {
        if ( null === $id ) {
            return null;
        }
        return $this->find( $id );
    }

    public function save( object $entity ): bool|int {
        return false;
    }

    public function delete( int $id ): bool {
        return false;
    }
}
