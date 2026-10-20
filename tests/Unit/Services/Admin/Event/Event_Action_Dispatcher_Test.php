<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Event;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Event_Action_Dispatcher_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wpdb']              = new class() {
            public int $insert_id = 5;
            public string $racketmanager_events = 'events';
            public string $racketmanager = 'leagues';
            public array $prepared_queries = array();

            public function prepare( string $q, mixed ...$a ): string {
                $this->prepared_queries[] = array(
                    'query' => $q,
                    'args'  => $a,
                );
                return $q;
            }

            public function query( string $q ): int {
                return 1;
            }

            public function get_results( string $q ): array {
                return array();
            }

            public function get_var( string $q ): int {
                return 0;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function make_league( int $id = 7 ): \Racketmanager\Domain\Competition\League {
        $league     = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league->id = $id;
        return $league;
    }

    private function make_competition(
        int $id = 1,
        bool $is_tournament = false,
        bool $is_championship = false,
        string $mode = 'league',
        ?array $current_season = null,
        array $seasons = []
    ): Competition {
        $competition                  = $this->createStub( Competition::class );
        $competition->id              = $id;
        $competition->is_team_entry   = false;
        $competition->is_tournament   = $is_tournament;
        $competition->is_championship = $is_championship;
        $competition->settings        = new \Racketmanager\Domain\Competition\Competition_Settings( array( 'mode' => $mode ) );
        if ( null !== $current_season ) {
            $competition->current_season = $current_season;
        }
        $competition->method( 'get_seasons' )->willReturn( $seasons );
        return $competition;
    }

    private function create_event_payload( array $overrides = [], string $action = 'addEventConfig' ): array {
        $payload = array(
            'event_name' => 'Men Doubles',
            'type'       => 'doubles',
            'age_limit'  => 'open',
            'age_offset' => '0',
            'scoring'    => 'standard',
            'num_sets'   => '3',
            'offset'     => '0',
        );
        if ( '' !== $action ) {
            $payload[ $action ] = '1';
        }
        return array_merge( $payload, $overrides );
    }

    private function get_settings_update(): ?string {
        foreach ( $GLOBALS['wpdb']->prepared_queries as $prepared_query ) {
            if ( str_contains( $prepared_query['query'], 'SET `settings` = %s' ) ) {
                return $prepared_query['args'][0];
            }
        }
        return null;
    }

    private function set_racketmanager_container( Competition_Service $competition_service, League_Service $league_service ): void {
        $container = new \Racketmanager\Services\Container\Simple_Container();
        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'league_service', fn() => $league_service );
        $racketmanager            = $this->createStub( \Racketmanager\RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
    }

    public function test_add_event_config_guard_failure(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-event-config', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addEventConfig' => '1',
                'event_name'     => 'Mixed Doubles',
            )
        );

        self::assertSame( 'Unauthorized', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_event_config_with_existing_event_id_fails(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addEventConfig' => '1',
                'event_id'       => '5',
                'event_name'     => 'Mixed Doubles',
            )
        );

        self::assertSame( 'Event id invalid for new event', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_event_config_validation_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition( is_tournament: true );

        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        // Missing required fields like type, scoring, num_sets
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addEventConfig' => '1',
                'event_name'     => 'Mixed Doubles',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertIsArray( $result->flash_data['errors'] ?? null );
    }

    public function test_add_event_config_whitelists_form_data_and_excludes_nonces_and_secrets(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition( is_tournament: true );

        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addEventConfig'        => '1',
                'racketmanager_nonce'   => 'secret_nonce_value',
                '_wpnonce'              => 'another_nonce',
                'user_password'         => 'supersecret',
                'unknown_param'         => 'malicious',
                'event_name'            => 'Singles Championship',
                'type'                  => 'MD',
                'age_limit'             => 'open',
                'scoring'               => 'sets',
                'num_sets'              => '3',
                'match_days_allowed'    => array(
                    '1' => ' 1 ',
                    'nested' => array(
                        'day' => ' 2 ',
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
        self::assertArrayNotHasKey( 'unknown_param', $formData );
        self::assertArrayNotHasKey( 'addEventConfig', $formData );

        self::assertSame( 'Singles Championship', $formData['event_name'] );
        self::assertSame( 'MD', $formData['type'] );
        self::assertSame( 'open', $formData['age_limit'] );
        self::assertSame( 'sets', $formData['scoring'] );
        self::assertSame( '3', $formData['num_sets'] );
        self::assertSame(
            array(
                '1'      => '1',
                'nested' => array(
                    'day' => '2',
                ),
            ),
            $formData['match_days_allowed']
        );
    }

    public function test_add_event_config_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $season_data         = array( 'name' => '2025', 'home_away' => true, 'num_match_days' => 10, 'match_dates' => array() );
        $competition         = $this->make_competition(
            current_season: $season_data,
            seasons: array( '2025' => $season_data )
        );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Men Doubles' )
            ->willReturn( $this->make_league() );
        $this->set_racketmanager_container( $competition_service, $league_service );
        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload()
        );

        self::assertSame( 'Event added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_add_event_config_championship_creates_plate_league(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $season_data         = array( 'name' => '2025', 'home_away' => false, 'num_match_days' => 5, 'match_dates' => array() );
        $competition         = $this->make_competition(
            is_tournament: true,
            is_championship: true,
            mode: 'championship',
            current_season: $season_data,
            seasons: array( '2025' => $season_data )
        );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $league_calls   = array();
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::exactly( 2 ) )
            ->method( 'add_league_to_event' )
            ->willReturnCallback(
                function ( int $event_id, ?string $name ) use ( &$league_calls ): \Racketmanager\Domain\Competition\League {
                    $league_calls[] = array( $event_id, $name );
                    return $this->make_league( count( $league_calls ) + 6 );
                }
            );

        $this->set_racketmanager_container( $competition_service, $league_service );
        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload( array(
                'event_name' => 'Men Singles',
                'type'       => 'singles',
            ) )
        );

        self::assertSame( 'Event added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame(
            array(
                array( 5, 'Men Singles' ),
                array( 5, 'Men Singles Plate' ),
            ),
            $league_calls
        );

        $settings_update = $this->get_settings_update();
        self::assertNotNull( $settings_update );
        $settings = unserialize( $settings_update, array( 'allowed_classes' => false ) );
        self::assertSame( 7, $settings['primary_league'] );
    }

    public function test_add_event_config_league_creation_failure_returns_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();
        $competition_service->method( 'get_by_id' )->willReturn( $competition );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Men Doubles' )
            ->willThrowException( new Event_Not_Found_Exception( 'Event not found' ) );
        $this->set_racketmanager_container( $competition_service, $league_service );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload()
        );

        self::assertSame( 'Could not create league for event', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_event_add_league_returns_created_league_id(): void {
        $competition_service = $this->createStub( Competition_Service::class );
        $competition         = $this->make_competition( is_tournament: true, mode: 'tournament' );
        $competition_service->method( 'get_by_id' )->willReturn( $competition );
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Men Singles' )
            ->willReturn( $this->make_league( 23 ) );
        $this->set_racketmanager_container( $competition_service, $league_service );

        $event = new Event( (object) array(
            'id'             => 5,
            'name'           => 'Men Singles',
            'type'           => 'singles',
            'competition_id' => 1,
        ) );

        self::assertSame( 23, $event->add_league( 'Men Singles' ) );
    }

    public function test_event_add_league_returns_false_when_event_is_not_found(): void {
        $competition_service = $this->createStub( Competition_Service::class );
        $competition         = $this->make_competition( is_tournament: true, mode: 'tournament' );
        $competition_service->method( 'get_by_id' )->willReturn( $competition );
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->with( 5, 'Men Singles' )
            ->willThrowException( new Event_Not_Found_Exception( 'Event not found' ) );
        $this->set_racketmanager_container( $competition_service, $league_service );

        $event = new Event( (object) array(
            'id'             => 5,
            'name'           => 'Men Singles',
            'type'           => 'singles',
            'competition_id' => 1,
        ) );

        self::assertFalse( $event->add_league( 'Men Singles' ) );
    }

    public function test_update_event_config_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->competition_id = 1;
        $event->expects( self::once() )->method( 'set_config' )->willReturn( true );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1', 'event_id' => '10' ),
            $this->create_event_payload( array(
                'event_id' => '10',
            ), 'updateEventConfig' )
        );

        self::assertSame( 'Event config updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'event_id' => 10, 'view' => 'event-config' ), $result->query_params );
    }

    public function test_update_event_config_no_updates(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event                 = $this->createMock( Event::class );
        $event->id             = 10;
        $event->competition_id = 1;
        $event->expects( self::once() )->method( 'set_config' )->willReturn( false );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1', 'event_id' => '10' ),
            $this->create_event_payload( array(
                'event_id' => '10',
            ), 'updateEventConfig' )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( array( 'event_id' => 10, 'view' => 'event-config' ), $result->query_params );
    }

    public function test_add_event_config_league_creation_failure_returns_error_with_context(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $failed_league     = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $failed_league->id = null;
        $league_service    = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->willReturn( $failed_league );
        $this->set_racketmanager_container( $competition_service, $league_service );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload( array( 'event_name' => 'Test Event' ) )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Could not create league for event', $result->message );
        self::assertSame( 5, $result->entity_id );
        self::assertSame(
            array(
                'event_id' => 5,
                'view'     => 'event-config',
            ),
            $result->query_params
        );

        $settings_update = $this->get_settings_update();
        self::assertNotNull( $settings_update );
        $settings = unserialize( $settings_update, array( 'allowed_classes' => false ) );
        self::assertIsArray( $settings );
        self::assertArrayNotHasKey( 'primary_league', $settings );
    }

    public function test_add_event_config_plate_league_creation_failure_returns_error_with_context(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition(
            is_tournament: true,
            is_championship: true,
            mode: 'championship'
        );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $failed_plate     = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $failed_plate->id = null;
        $league_service   = $this->createMock( League_Service::class );
        $league_service->expects( self::exactly( 2 ) )
            ->method( 'add_league_to_event' )
            ->willReturnOnConsecutiveCalls( $this->make_league( 7 ), $failed_plate );
        $this->set_racketmanager_container( $competition_service, $league_service );

        $dispatcher = new Event_Action_Dispatcher( $competition_service, $guard, $this->createStub( League_Team_Repository_Interface::class ) );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload( array(
                'event_name' => 'Test Event',
                'type'       => 'singles',
            ) )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Could not create plate league for event', $result->message );
        self::assertSame( 5, $result->entity_id );
        self::assertSame(
            array(
                'event_id' => 5,
                'view'     => 'event-config',
            ),
            $result->query_params
        );

        $settings_update = $this->get_settings_update();
        self::assertNotNull( $settings_update );
        $settings = unserialize( $settings_update, array( 'allowed_classes' => false ) );
        self::assertIsArray( $settings );
        self::assertSame( 7, $settings['primary_league'] );
    }
}
