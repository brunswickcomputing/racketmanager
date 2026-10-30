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

    public function test_relocated_port_aliases_exist_and_resolve(): void {
        $this->assertTrue( interface_exists( 'Racketmanager\Gateways\Mail\Mailer_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Application\Mail\Mailer_Interface' ) );
        $this->assertSame(
            \Racketmanager\Application\Mail\Mailer_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Gateways\Mail\Mailer_Interface' ) )->getName()
        );

        $this->assertTrue( interface_exists( 'Racketmanager\Services\Contracts\Wtn_Api_Client_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Domain\Ports\Rating_Gateway_Interface' ) );
        $this->assertSame(
            \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Services\Contracts\Wtn_Api_Client_Interface' ) )->getName()
        );

        $this->assertTrue( interface_exists( 'Racketmanager\Gateways\Option\Option_Gateway_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Application\Ports\Option_Gateway_Interface' ) );
        $this->assertSame(
            \Racketmanager\Application\Ports\Option_Gateway_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Gateways\Option\Option_Gateway_Interface' ) )->getName()
        );

        $this->assertTrue( interface_exists( 'Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface' ) );
        $this->assertSame(
            \Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface' ) )->getName()
        );

        $this->assertTrue( interface_exists( 'Racketmanager\Gateways\Payment\Payment_Gateway_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface' ) );
        $this->assertSame(
            \Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Gateways\Payment\Payment_Gateway_Interface' ) )->getName()
        );

        $this->assertTrue( class_exists( 'Racketmanager\Gateways\Payment\Payment_Intent_Result' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Application\Payment\Ports\Payment_Intent_Result' ) );
        $this->assertSame(
            \Racketmanager\Application\Payment\Ports\Payment_Intent_Result::class,
            ( new \ReflectionClass( 'Racketmanager\Gateways\Payment\Payment_Intent_Result' ) )->getName()
        );

        $this->assertTrue( interface_exists( 'Racketmanager\Services\Contracts\Data_Exporter_Interface' ) );
        $this->assertTrue( interface_exists( 'Racketmanager\Application\Export\Ports\Data_Exporter_Interface' ) );
        $this->assertSame(
            \Racketmanager\Application\Export\Ports\Data_Exporter_Interface::class,
            ( new \ReflectionClass( 'Racketmanager\Services\Contracts\Data_Exporter_Interface' ) )->getName()
        );

        // Verify behavioral instanceof compatibility for concrete adapters and DTOs
        $mailer = new \Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer();
        $this->assertInstanceOf( 'Racketmanager\Gateways\Mail\Mailer_Interface', $mailer );
        $this->assertInstanceOf( \Racketmanager\Application\Mail\Mailer_Interface::class, $mailer );

        $lta_adapter = new \Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter();
        $this->assertInstanceOf( 'Racketmanager\Services\Contracts\Wtn_Api_Client_Interface', $lta_adapter );
        $this->assertInstanceOf( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class, $lta_adapter );

        $wtn_client = new \Racketmanager\Services\External\Wtn_Api_Client();
        $this->assertInstanceOf( 'Racketmanager\Services\Contracts\Wtn_Api_Client_Interface', $wtn_client );
        $this->assertInstanceOf( \Racketmanager\Domain\Ports\Rating_Gateway_Interface::class, $wtn_client );

        $option_gateway = new \Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway();
        $this->assertInstanceOf( 'Racketmanager\Gateways\Option\Option_Gateway_Interface', $option_gateway );
        $this->assertInstanceOf( \Racketmanager\Application\Ports\Option_Gateway_Interface::class, $option_gateway );

        $favourites_gateway = new \Racketmanager\Infrastructure\Gateways\Account\Wp_User_Favourites_Gateway(
            new \Racketmanager\Infrastructure\Security\Security_Service()
        );
        $this->assertInstanceOf( 'Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface', $favourites_gateway );
        $this->assertInstanceOf( \Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface::class, $favourites_gateway );

        $payment_gateway = new \Racketmanager\Infrastructure\Gateways\Payment\Stripe_Payment_Gateway();
        $this->assertInstanceOf( 'Racketmanager\Gateways\Payment\Payment_Gateway_Interface', $payment_gateway );
        $this->assertInstanceOf( \Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface::class, $payment_gateway );

        $payment_intent = new \Racketmanager\Application\Payment\Ports\Payment_Intent_Result( 'pi_123', 'sec_456', 'succeeded', 2500, 'gbp' );
        $this->assertInstanceOf( 'Racketmanager\Gateways\Payment\Payment_Intent_Result', $payment_intent );
        $this->assertInstanceOf( \Racketmanager\Application\Payment\Ports\Payment_Intent_Result::class, $payment_intent );

        $exporter = new \Racketmanager\Services\Export\Export_Service(
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Services\Result\Result_Reporting_Service::class ),
            $this->createStub( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class )
        );
        $this->assertInstanceOf( 'Racketmanager\Services\Contracts\Data_Exporter_Interface', $exporter );
        $this->assertInstanceOf( \Racketmanager\Application\Export\Ports\Data_Exporter_Interface::class, $exporter );

        $criteria = new \Racketmanager\Application\Export\DTO\Export_Criteria( [ 'league_id' => 10 ] );
        $this->assertInstanceOf( 'Racketmanager\Services\Export\DTO\Export_Criteria', $criteria );
        $this->assertInstanceOf( \Racketmanager\Application\Export\DTO\Export_Criteria::class, $criteria );

        // Verify absence of physical legacy declaration files
        $legacy_mailer_file     = dirname( __DIR__, 2 ) . '/src/php/Gateways/Mail/Mailer_Interface.php';
        $legacy_wtn_file        = dirname( __DIR__, 2 ) . '/src/php/Services/Contracts/Wtn_Api_Client_Interface.php';
        $legacy_option_file     = dirname( __DIR__, 2 ) . '/src/php/Gateways/Option/Option_Gateway_Interface.php';
        $legacy_favourites_file = dirname( __DIR__, 2 ) . '/src/php/Gateways/Account/User_Favourites_Gateway_Interface.php';
        $legacy_payment_file    = dirname( __DIR__, 2 ) . '/src/php/Gateways/Payment/Payment_Gateway_Interface.php';
        $legacy_intent_file     = dirname( __DIR__, 2 ) . '/src/php/Gateways/Payment/Payment_Intent_Result.php';
        $legacy_exporter_file   = dirname( __DIR__, 2 ) . '/src/php/Services/Contracts/Data_Exporter_Interface.php';
        $legacy_criteria_file   = dirname( __DIR__, 2 ) . '/src/php/Services/Export/DTO/Export_Criteria.php';
        $this->assertFileDoesNotExist( $legacy_mailer_file );
        $this->assertFileDoesNotExist( $legacy_wtn_file );
        $this->assertFileDoesNotExist( $legacy_option_file );
        $this->assertFileDoesNotExist( $legacy_favourites_file );
        $this->assertFileDoesNotExist( $legacy_payment_file );
        $this->assertFileDoesNotExist( $legacy_intent_file );
        $this->assertFileDoesNotExist( $legacy_exporter_file );
        $this->assertFileDoesNotExist( $legacy_criteria_file );
    }
}
