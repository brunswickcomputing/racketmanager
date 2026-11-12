<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Validator;

use Racketmanager\Application\Club\Ports\Club_Validator_Factory_Interface;
use Racketmanager\Application\Club\Ports\Club_Validator_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;

/**
 * Club validator factory.
 */
final readonly class Club_Validator_Factory implements Club_Validator_Factory_Interface {
    /**
     * Create a club validator factory.
     *
     * @param Club_Role_Repository_Interface|null $club_role_repository The club role repository.
     * @param Player_Repository_Interface|null    $player_repository The player repository.
     * @param User_Repository_Interface|null      $user_repository The user repository.
     * @param Club_Repository_Interface|null      $club_repository The club repository.
     */
    public function __construct(
        private ?Club_Role_Repository_Interface $club_role_repository = null,
        private ?Player_Repository_Interface $player_repository = null,
        private ?User_Repository_Interface $user_repository = null,
        private ?Club_Repository_Interface $club_repository = null,
    ) {
    }

    /**
     * Create a club validator.
     *
     * @return Club_Validator_Interface The club validator.
     */
    public function create(): Club_Validator_Interface {
        return new Club_Validator_Adapter(
            new Validator_Club(
                club_role_repository: $this->club_role_repository,
                player_repository: $this->player_repository,
                user_repository: $this->user_repository,
                club_repository: $this->club_repository,
            )
        );
    }
}
