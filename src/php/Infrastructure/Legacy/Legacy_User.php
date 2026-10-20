<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Legacy;

use Racketmanager\Domain\Player\User;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;

/**
 * Compatibility subclass for theme and procedural helpers that still call User::get_messages().
 *
 * Note: instances hold a Message_Repository_Interface collaborator and would serialize it,
 * so they must not be stored in persistent/object caches.
 */
class Legacy_User extends User {
    public function __construct(
        ?object $user = null,
        private readonly ?Message_Repository_Interface $message_repository = null,
    ) {
        parent::__construct( $user );
    }

    /**
     * Get user messages.
     *
     * @deprecated Use Message_Repository::find_by_user() or Get_Messages_Handler.
     *
     * @param array $args Query arguments ('count', 'status', 'orderby').
     * @return array|int
     */
    public function get_messages( array $args = array() ): array|int {
        if ( null === $this->message_repository ) {
            throw new \LogicException( 'Legacy_User requires an injected Message_Repository_Interface to load messages.' );
        }

        $user_id = 0;
        if ( isset( $this->id ) ) {
            $user_id = (int) $this->id;
        } elseif ( isset( $this->ID ) ) {
            $user_id = (int) $this->ID;
        }

        return $this->message_repository->find_by_user( $user_id, $args );
    }
}
