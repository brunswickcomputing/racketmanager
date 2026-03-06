<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Information_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Information_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    public function test_get_request_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_id = 123;
        $tournament = $this->create_tournament_instance( $tournament_id, 'Test Tournament' );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( $tournament_id )
            ->willReturn( $tournament );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );

        $dispatcher = new Tournament_Information_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Information_Admin_Controller(
            $tournament_service,
            $dispatcher,
            $guard
        );

        $result = $controller->information_page(
            [ 'tournament' => (string) $tournament_id ],
            []
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Information_Page_View_Model::class, $result['view_model'] );
        self::assertSame( $tournament, $result['view_model']->tournament );
    }

    public function test_post_request_dispatches_action_and_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_id = 456;
        $post_data = [ 
            'tournament_id' => (string) $tournament_id, 
            'setInformation' => '1',
            'parking' => 'Yes',
            'catering' => 'No',
            'photography' => 'Yes',
            'spectators' => 'Yes',
            'referee' => 'John Doe',
            'matchFormat' => 'Best of 3',
        ];
        $query_data = [ 'page' => 'racketmanager-tournaments' ];

        $tournament_service = $this->createMock( Tournament_Service::class );
        // Success case for set_tournament_information
        $tournament_service
            ->method( 'set_tournament_information' )
            ->willReturn( true );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );
        $guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_tournament-information', 'edit_teams' );

        $dispatcher = new Tournament_Information_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Information_Admin_Controller(
            $tournament_service,
            $dispatcher,
            $guard
        );

        $result = $controller->information_page( $query_data, $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=information', $result['redirect'] );
        self::assertStringContainsString( 'tournament=' . $tournament_id, $result['redirect'] );
        self::assertSame( 'Information updated', $result['message'] );
        self::assertFalse( $result['message_type'] ); // SUCCESS maps to false in legacy
    }

    public function test_extract_tournament_id_from_query(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $tournament_id = 789;

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )
            ->with( $tournament_id )
            ->willReturn( $this->create_tournament_instance( $tournament_id, 'Test' ) );
        
        $guard = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = new Tournament_Information_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Information_Admin_Controller( $tournament_service, $dispatcher, $guard );

        // Test 'tournament' key in query
        $result = $controller->information_page( [ 'tournament' => (string) $tournament_id ], [] );
        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( $tournament_id, $result['view_model']->tournament->id );
    }

    public function test_extract_tournament_id_from_post(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $tournament_id = 101;

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service->method( 'notify_finalists_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( true );
        
        $guard = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = new Tournament_Information_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Information_Admin_Controller( $tournament_service, $dispatcher, $guard );

        $result = $controller->information_page( [], [ 'tournament_id' => (string) $tournament_id, 'notifyFinalists' => '1' ] );
        
        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'tournament=' . $tournament_id, $result['redirect'] );
        self::assertSame( 'Finalists notified', $result['message'] );
    }

    private function verify_tournament_id_used( $tournament_service_mock, $expected_id ): void {
        // Not implemented robustly here, but the tests above verify the flow.
    }

    /**
     * @throws ReflectionException
     */
    private function create_tournament_instance( int $id, string $name ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );

        /** @var Tournament $tournament */
        $tournament = $reflection->newInstanceWithoutConstructor();

        $this->set_property( $tournament, 'id', $id );
        $this->set_property( $tournament, 'name', $name );

        return $tournament;
    }

    private function set_property( object $object, string $property_name, mixed $value ): void {
        $reflection = new ReflectionObject( $object );

        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                $property = $reflection->getProperty( $property_name );
                $property->setValue( $object, $value );
                return;
            }

            $reflection = $reflection->getParentClass();
        }

        self::fail( sprintf( 'Property "%s" not found on %s', $property_name, $object::class ) );
    }
}
