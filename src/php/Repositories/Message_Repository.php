<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Message;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use function is_numeric;

class Message_Repository implements Message_Repository_Interface {
    public function find( int|string $message_id ): ?Message {
        if ( ! is_numeric( $message_id ) ) {
            return null;
        }

        $id = (int) $message_id;
        if ( $id <= 0 ) {
            return null;
        }

        $message = Message::get_instance( $id );
        return $message instanceof Message ? $message : null;
    }

    public function find_by_id( int|string|null $id ): ?Message {
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
