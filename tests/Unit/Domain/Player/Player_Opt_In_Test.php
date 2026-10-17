<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player_Factory;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Player_Service;

class Player_Opt_In_Test extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_user_meta'] );
        parent::tearDown();
    }

    public function test_player_set_opt_in_in_memory(): void {
        $data = [
            'ID' => 123,
            'firstname' => 'Roger',
            'surname' => 'Federer',
            'email' => 'roger@tennis.test',
        ];
        $player = Player_Factory::from_array( $data );
        $this->assertEmpty( $player->opt_ins );

        $player->set_opt_in( '1' );
        $this->assertContains( '1', $player->opt_ins );

        // Setting again should not duplicate
        $player->set_opt_in( '1' );
        $this->assertCount( 1, $player->opt_ins );
    }

    public function test_player_factory_and_dto_opt_in_normalization(): void {
        // Test with false
        $dto_false = Player_Hydration_DTO::from_object( (object) [ 'ID' => 1, 'opt_ins' => false ] );
        $this->assertSame( [], $dto_false->opt_ins );
        $player_false = Player_Factory::create_from_dto( $dto_false );
        $this->assertSame( [], $player_false->opt_ins );

        // Test with null
        $dto_null = Player_Hydration_DTO::from_object( (object) [ 'ID' => 2, 'opt_ins' => null ] );
        $this->assertSame( [], $dto_null->opt_ins );
        $player_null = Player_Factory::create_from_dto( $dto_null );
        $this->assertSame( [], $player_null->opt_ins );

        // Test with single string
        $dto_str = Player_Hydration_DTO::from_object( (object) [ 'ID' => 3, 'opt_ins' => '1' ] );
        $this->assertSame( [ '1' ], $dto_str->opt_ins );
        $player_str = Player_Factory::create_from_dto( $dto_str );
        $this->assertSame( [ '1' ], $player_str->opt_ins );

        // Test with array
        $dto_arr = Player_Hydration_DTO::from_object( (object) [ 'ID' => 4, 'opt_ins' => [ 'k1' => '1', 'k2' => '2' ] ] );
        $this->assertSame( [ '1', '2' ], $dto_arr->opt_ins );
        $player_arr = Player_Factory::create_from_dto( $dto_arr );
        $this->assertSame( [ '1', '2' ], $player_arr->opt_ins );
    }

    public function test_player_service_save_opt_in_delegates_to_repository(): void {
        $repo = $this->createMock( Player_Repository::class );
        $repo->expects( $this->once() )
            ->method( 'save_opt_in' )
            ->with( 45, '1' )
            ->willReturn( true );

        $service = $this->make_player_service( $repo );

        $this->assertTrue( $service->save_opt_in( 45, '1' ) );
    }

    public function test_player_service_save_opt_in_handles_failure(): void {
        $repo = $this->createMock( Player_Repository::class );
        $repo->expects( $this->once() )
            ->method( 'save_opt_in' )
            ->with( 0, '' )
            ->willReturn( false );

        $service = $this->make_player_service( $repo );

        $this->assertFalse( $service->save_opt_in( 0, '' ) );
    }

    public function test_player_repository_save_opt_in_guards_and_idempotence(): void {
        $repo = new Player_Repository();

        // Invalid inputs
        $this->assertFalse( $repo->save_opt_in( 0, '1' ) );
        $this->assertFalse( $repo->save_opt_in( 10, '' ) );

        // Existing opt in in user meta
        $GLOBALS['wp_stubs_user_meta'][100]['racketmanager_opt_in'] = [ '1' ];
        $this->assertTrue( $repo->save_opt_in( 100, '1' ) );

        // New opt in
        $GLOBALS['wp_stubs_user_meta'][101]['racketmanager_opt_in'] = [];
        $this->assertTrue( $repo->save_opt_in( 101, '1' ) );
        $this->assertContains( '1', $GLOBALS['wp_stubs_user_meta'][101]['racketmanager_opt_in'] );
    }

    private function make_player_service( Player_Repository $repo ): Player_Service {
        return new Player_Service(
            $this->createStub( RacketManager::class ),
            $repo,
            $this->createStub( Player_Error_Repository_Interface::class ),
            $this->createStub( Club_Role_Repository_Interface::class ),
            $this->createStub( Rating_Gateway_Interface::class ),
            $this->createStub( League_Team_Repository::class ),
            $this->createStub( Club_Repository::class ),
            $this->createStub( Registration_Repository_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );
    }
}
