<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class Aliases_Test extends TestCase {
    public function test_compatibility_aliases_exist(): void {
        $this->assertTrue( class_exists( 'Racketmanager\Domain\User' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Player' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Tournament' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Team' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Season' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Message' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Results_Report' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Charge' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Invoice' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club_Player' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club_Role' ) );
    }

    public function test_command_aliases_exist(): void {
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Account\Login_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Account\Add_Favourite_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Club\Update_Club_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Club\Set_Club_Role_Command' ) );
    }

    public function test_naming_collision_aliases_exist(): void {
        $this->assertTrue( class_exists( 'Racketmanager\Application\Fixture\Services\Fixture_Detail_Service' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTOs\Results_Checker_Data' ) );
        $this->assertSame(
            \Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler::class,
            ( new \ReflectionClass( 'Racketmanager\Application\Fixture\Services\Fixture_Detail_Service' ) )->getName()
        );
        $this->assertSame(
            \Racketmanager\Domain\DTO\Result\Results_Checker_Data::class,
            ( new \ReflectionClass( 'Racketmanager\Domain\DTOs\Results_Checker_Data' ) )->getName()
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function relocated_request_dtos(): iterable {
        $contexts = [
            'Fixture'     => [
                'Fixture_Date_Update_Request',
                'Fixture_Option_Request',
                'Fixture_Reset_Request',
                'Fixture_Result_Update_Request',
                'Fixture_Status_Options_Request',
                'Fixture_Status_Update_Request',
                'Fixture_Switch_Teams_Request',
                'Rubber_Status_Options_Request',
                'Team_Result_Confirmation_Request',
                'Team_Result_Update_Request',
            ],
            'Tournament'  => [
                'Championship_Rounds_Request_DTO',
                'Tournament_Contact_Request_DTO',
                'Tournament_Entry_Request_DTO',
                'Tournament_Finals_Config_Request_DTO',
                'Tournament_Finals_Request_DTO',
                'Tournament_Information_Request_DTO',
                'Tournament_Partner_Request_DTO',
                'Tournament_Request_DTO',
                'Tournament_Payment_Command',
                'Tournament_Withdrawal_Command',
            ],
            'Competition' => [ 'Cup_Entry_Request_DTO', 'League_Entry_Request_DTO' ],
            'Player'      => [ 'Search_Players_Command' ],
        ];
        foreach ( $contexts as $context => $classes ) {
            foreach ( $classes as $class ) {
                yield $class => [
                    'Racketmanager\Domain\DTO\\' . $context . '\\' . $class,
                    'Racketmanager\Infrastructure\Wordpress\Request\\' . $context . '\\' . $class,
                ];
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'relocated_request_dtos' )]
    public function test_relocated_request_dto_aliases_resolve_to_infrastructure_classes( string $old_fqcn, string $new_fqcn ): void {
        $this->assertTrue( class_exists( $new_fqcn ), "$new_fqcn must exist under Infrastructure/Wordpress/Request" );
        $this->assertTrue( class_exists( $old_fqcn ), "$old_fqcn must remain available as a compatibility alias" );
        $this->assertSame( $new_fqcn, ( new \ReflectionClass( $old_fqcn ) )->getName() );
    }

    public function test_relocated_request_dtos_count(): void {
        $this->assertCount( 23, iterator_to_array( self::relocated_request_dtos() ) );
    }
}
