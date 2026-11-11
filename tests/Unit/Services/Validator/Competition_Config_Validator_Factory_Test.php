<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Competition_Config_Validator_Factory_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Validator\Competition_Config_Validator_Factory;
use Racketmanager\Services\Validator\Validator_Config;
use ReflectionProperty;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Config_Validator_Factory_Test extends TestCase {

    public function test_implements_interface(): void {
        $factory = new Competition_Config_Validator_Factory();
        $this->assertInstanceOf( Competition_Config_Validator_Factory_Interface::class, $factory );
    }

    public function test_create_returns_validator_config_instance(): void {
        $factory = new Competition_Config_Validator_Factory();
        $validator = $factory->create();

        $this->assertInstanceOf( Validator_Config::class, $validator );
    }

    public function test_create_passes_injected_competition_service(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $factory = new Competition_Config_Validator_Factory( $competition_service );

        $validator = $factory->create();
        $this->assertInstanceOf( Validator_Config::class, $validator );

        $property = new ReflectionProperty( $validator, 'competition_service' );
        $this->assertSame( $competition_service, $property->getValue( $validator ) );
    }

    public function test_create_with_null_competition_service(): void {
        $factory = new Competition_Config_Validator_Factory( null );

        $validator = $factory->create();
        $this->assertInstanceOf( Validator_Config::class, $validator );

        $property = new ReflectionProperty( $validator, 'competition_service' );
        $this->assertNull( $property->getValue( $validator ) );
    }
}
