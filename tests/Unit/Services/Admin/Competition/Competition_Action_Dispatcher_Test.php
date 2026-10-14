<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Action_Dispatcher_Test extends TestCase {
    use Manages_Wordpress_Globals;


    public function test_unhandled_action_returns_empty_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
    }

    public function test_update_competition_config_guard_failure(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-competition-config', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $competition_service->expects( self::never() )->method( 'amend_details' );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '1',
            )
        );

        self::assertSame( 'Forbidden', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_update_competition_config_mismatched_id(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $competition_service->expects( self::never() )->method( 'amend_details' );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '2',
            )
        );

        self::assertSame( 'Invalid competition id', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_update_competition_config_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->with( 1, self::isObject() )
            ->willReturn( 1 );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '1',
                'competition_title'       => 'Summer League',
                'type'                    => 'league',
            )
        );

        self::assertSame( 'Competition updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_update_competition_config_no_updates(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willReturn( 0 );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '1',
            )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_update_competition_config_wp_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $wp_error = new WP_Error( 'name', 'Name is required' );
        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willReturn( $wp_error );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '1',
            )
        );

        self::assertSame( 'Error with competition details', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame(
            array(
                'errors'    => array( 'name' => 'Name is required' ),
                'form_data' => array(),
            ),
            $result->flash_data
        );
    }

    public function test_update_competition_config_whitelists_form_data_and_excludes_nonces_and_secrets(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $wp_error = new WP_Error( 'name', 'Invalid name' );
        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willReturn( $wp_error );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig'   => '1',
                'competition_id'            => '1',
                'racketmanager_nonce'       => 'secret_nonce_value',
                '_wpnonce'                  => 'another_nonce',
                'user_password'             => 'supersecret',
                'random_injected_param'     => 'malicious_payload',
                'competition_title'         => 'Premier League',
                'sport'                     => 'tennis',
                'grade'                     => 'A',
                'max_teams'                 => '16',
                'min_start_time_weekday'    => '18:00',
                'max_start_time_weekday'    => '21:00',
                'rules'                     => array(
                    'rule1' => ' 1 ',
                    'nested' => array(
                        'sub_key' => ' sub_value ',
                    ),
                ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertIsArray( $result->flash_data );
        self::assertArrayHasKey( 'form_data', $result->flash_data );
        $formData = $result->flash_data['form_data'];

        self::assertArrayNotHasKey( 'racketmanager_nonce', $formData );
        self::assertArrayNotHasKey( '_wpnonce', $formData );
        self::assertArrayNotHasKey( 'user_password', $formData );
        self::assertArrayNotHasKey( 'random_injected_param', $formData );
        self::assertArrayNotHasKey( 'updateCompetitionConfig', $formData );

        self::assertSame( 'Premier League', $formData['competition_title'] );
        self::assertSame( 'tennis', $formData['sport'] );
        self::assertSame( 'A', $formData['grade'] );
        self::assertSame( '16', $formData['max_teams'] );
        self::assertSame( '18:00', $formData['min_start_time_weekday'] );
        self::assertSame( '21:00', $formData['max_start_time_weekday'] );
        self::assertSame(
            array(
                'rule1'  => '1',
                'nested' => array(
                    'sub_key' => 'sub_value',
                ),
            ),
            $formData['rules']
        );
    }

    public function test_update_competition_config_exceptions(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willThrowException( new Competition_Not_Updated_Exception( 'Could not update' ) );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'updateCompetitionConfig' => '1',
                'competition_id'          => '1',
            )
        );

        self::assertSame( 'Could not update', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_bulk_delete_events_no_action(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionEvent' => '1',
            )
        );

        self::assertSame( 'No action specified', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'events', $result->tab_override );
    }

    public function test_bulk_delete_events_empty_list(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionEvent' => '1',
                'action'        => 'delete',
                'event'         => array(),
            )
        );

        self::assertSame( 'No events were found to delete', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'events', $result->tab_override );
    }

    public function test_bulk_delete_events_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $this->racketmanager->container->expects( self::once() )->method('get')->with('competition_service')->willReturn($competition_service);

        $competition = $this->createMock(\Racketmanager\Domain\Competition\Competition::class);
        $competition->settings = new \Racketmanager\Domain\Competition\Competition_Settings(['mode' => 'default']);
        $competition_service->method('get_by_id')->willReturn($competition);

        $event = new \Racketmanager\Domain\Competition\Event( (object) array(
            'id'             => 5,
            'name'           => 'Men Singles',
            'type'           => 'singles',
            'competition_id' => 1,
        ) );
        $GLOBALS['wp_stubs_events'][5] = $event;

        $dispatcher = new Competition_Action_Dispatcher( $competition_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionEvent' => '1',
                'action'        => 'delete',
                'event'         => array( '5' ),
            )
        );

        self::assertSame( 'Men Singles has been deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 'events', $result->tab_override );
    }

    private $racketmanager;
    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $GLOBALS['wpdb'] = new class() {
            public string $racketmanager_events = 'events';
            public string $racketmanager = 'leagues';

            public function prepare( string $query, mixed ...$args ): string {
                return $query;
            }

            public function query( string $query ): int {
                return 1;
            }

            public function get_var( string $query ): int {
                return 0;
            }

            public function get_results( string $query ): array {
                return array();
            }
        };

        $this->racketmanager = new \stdClass();
        $this->racketmanager->container = $this->createMock( \Racketmanager\Services\Container\Simple_Container::class );
        $GLOBALS['racketmanager'] = $this->racketmanager;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }
}
