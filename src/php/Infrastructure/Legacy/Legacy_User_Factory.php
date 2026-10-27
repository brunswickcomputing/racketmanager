<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Legacy;

use Racketmanager\Domain\DTO\Player\User_Hydration_DTO;
use Racketmanager\Domain\Player\User;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;

/**
 * Wraps a domain User as a Legacy_User with an injected message repository.
 */
final class Legacy_User_Factory {
    public function __construct(
        private readonly Message_Repository_Interface $message_repository,
    ) {
    }

    public function from_user( User $user ): Legacy_User {
        return new Legacy_User( User_Hydration_DTO::from_object( $user ), $this->message_repository );
    }
}
