<?php
declare( strict_types=1 );

namespace Racketmanager;

/**
 * Backward-compatibility class aliases for reorganized domain models and commands.
 *
 * @deprecated 10.0.0 Class aliases are provided for temporary backward compatibility and will be sunset in v11.0.0. Reference PSR-4 classes directly.
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
    \Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Command::class => 'Racketmanager\Domain\DTO\Finance\Set_Purchase_Order_Command',

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

    // Naming-collision fixes (10.1.0)
    \Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler::class => 'Racketmanager\Application\Fixture\Services\Fixture_Detail_Service',
    \Racketmanager\Domain\DTO\Result\Results_Checker_Data::class               => 'Racketmanager\Domain\DTOs\Results_Checker_Data',

    // Relocated request DTOs (10.1.0)
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Date_Update_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Option_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Option_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Reset_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Options_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Status_Options_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Update_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Switch_Teams_Request::class => 'Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Rubber_Status_Options_Request::class => 'Racketmanager\Domain\DTO\Fixture\Rubber_Status_Options_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Confirmation_Request::class => 'Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Update_Request::class => 'Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Championship_Rounds_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Championship_Rounds_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Contact_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Contact_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Entry_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Config_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Information_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Information_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Partner_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Request_DTO::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Payment_Command::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Payment_Command',
    \Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Withdrawal_Command::class => 'Racketmanager\Domain\DTO\Tournament\Tournament_Withdrawal_Command',
    \Racketmanager\Infrastructure\Wordpress\Request\Competition\Cup_Entry_Request_DTO::class => 'Racketmanager\Domain\DTO\Competition\Cup_Entry_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Competition\League_Entry_Request_DTO::class => 'Racketmanager\Domain\DTO\Competition\League_Entry_Request_DTO',
    \Racketmanager\Infrastructure\Wordpress\Request\Player\Search_Players_Command::class => 'Racketmanager\Domain\DTO\Player\Search_Players_Command',

];

foreach ( $aliases as $target => $alias ) {
    if ( ! class_exists( $alias, false ) && class_exists( $target ) ) {
        class_alias( $target, $alias );
    }
}

require_once __DIR__ . '/Admin/View_Models/Legacy_View_Model_Aliases.php';
