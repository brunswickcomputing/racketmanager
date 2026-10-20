<?php
declare( strict_types=1 );

namespace Racketmanager;

/**
 * Backward-compatibility class aliases for reorganized domain models and commands.
 */
$aliases = [
    // Club Context
    \Racketmanager\Domain\Club\Club::class                        => 'Racketmanager\Domain\Club',
    \Racketmanager\Domain\Club\Club_Player::class                 => 'Racketmanager\Domain\Club_Player',
    \Racketmanager\Domain\Club\Club_Role::class                   => 'Racketmanager\Domain\Club_Role',
    \Racketmanager\Domain\Club\Club_Factory::class                => 'Racketmanager\Domain\Club_Factory',

    // Player Context
    \Racketmanager\Domain\Player\Player::class                    => 'Racketmanager\Domain\Player',
    \Racketmanager\Domain\Player\Player_Error::class              => 'Racketmanager\Domain\Player_Error',
    \Racketmanager\Domain\Player\Player_Ranking::class            => 'Racketmanager\Domain\Player_Ranking',
    \Racketmanager\Domain\Player\User::class                      => 'Racketmanager\Domain\User',

    // Tournament Context
    \Racketmanager\Domain\Tournament\Tournament::class            => 'Racketmanager\Domain\Tournament',
    \Racketmanager\Domain\Tournament\Tournament_Entry::class      => 'Racketmanager\Domain\Tournament_Entry',
    \Racketmanager\Domain\Tournament\Championship::class          => 'Racketmanager\Domain\Championship',
    \Racketmanager\Domain\Tournament\Championship_Settings::class => 'Racketmanager\Domain\Championship_Settings',

    // Finance Context
    \Racketmanager\Domain\Finance\Charge::class                   => 'Racketmanager\Domain\Charge',
    \Racketmanager\Domain\Finance\Invoice::class                  => 'Racketmanager\Domain\Invoice',

    // Communication Context
    \Racketmanager\Domain\Communication\Message::class            => 'Racketmanager\Domain\Message',
    \Racketmanager\Domain\Communication\Results_Report::class     => 'Racketmanager\Domain\Results_Report',

    // Team & Season Contexts
    \Racketmanager\Domain\Team\Team::class                        => 'Racketmanager\Domain\Team',
    \Racketmanager\Domain\Season\Season::class                    => 'Racketmanager\Domain\Season',

    // Result Context
    \Racketmanager\Domain\Result\Results_Checker::class           => 'Racketmanager\Domain\Results_Checker',

    // Account Commands
    \Racketmanager\Application\Account\Commands\Add_Favourite_Command::class   => 'Racketmanager\Domain\DTO\Account\Add_Favourite_Command',
    \Racketmanager\Application\Account\Commands\Delete_Messages_Command::class => 'Racketmanager\Domain\DTO\Account\Delete_Messages_Command',
    \Racketmanager\Application\Account\Commands\Login_Command::class           => 'Racketmanager\Domain\DTO\Account\Login_Command',
    \Racketmanager\Application\Account\Commands\Reset_Password_Command::class   => 'Racketmanager\Domain\DTO\Account\Reset_Password_Command',
    \Racketmanager\Application\Account\Commands\Update_Account_Command::class   => 'Racketmanager\Domain\DTO\Account\Update_Account_Command',

    // Club Commands
    \Racketmanager\Application\Club\Commands\Club_Player_Request_Command::class => 'Racketmanager\Domain\DTO\Club\Club_Player_Request_Command',
    \Racketmanager\Application\Club\Commands\Club_Players_Remove_Command::class => 'Racketmanager\Domain\DTO\Club\Club_Players_Remove_Command',
    \Racketmanager\Application\Club\Commands\Set_Club_Role_Command::class       => 'Racketmanager\Domain\DTO\Club\Set_Club_Role_Command',
    \Racketmanager\Application\Club\Commands\Update_Club_Command::class         => 'Racketmanager\Domain\DTO\Club\Update_Club_Command',
];

foreach ( $aliases as $target => $alias ) {
    if ( ! class_exists( $alias, false ) && class_exists( $target ) ) {
        class_alias( $target, $alias );
    }
}
