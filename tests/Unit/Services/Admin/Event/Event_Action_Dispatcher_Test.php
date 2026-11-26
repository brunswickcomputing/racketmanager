<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Event;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Event_Action_Dispatcher_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wpdb']              = new class( 'test', 'test', 'test', 'localhost' ) extends \wpdb {
            public $insert_id = 5;
            public $racketmanager_events = 'events';
            public $racketmanager = 'leagues';
            public $prepared_queries = array();

            public function prepare( $q, ...$a ) {
                $this->prepared_queries[] = array(
                    'query' => $q,
                    'args'  => $a,
                );
                return $q;
            }

            public function query( $q ) {
                return 1;
            }

            public function get_results( $q = null, $output = 'OBJECT' ) {
                return array();
            }

            public function get_var( $q = null, $x = 0, $y = 0 ) {
                return 0;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function make_league( int $id = 7 ): \Racketmanager\Domain\Competition\League {
        $league = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        League_Test_Factory::set_property( $league, 'id', $id );
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
        $type = $is_tournament ? Competition_Type::TOURNAMENT : Competition_Type::LEAGUE;
        $competition = Competition_Test_Factory::create(
            id: $id,
            type: $type,
            seasons: $seasons,
            settings: [ 'mode' => $mode ],
            current_season: $current_season
        );
        $competition->set_is_team_entry( false );
        $competition->set_is_championship( $is_championship );
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

    private function make_event_repository(): Event_Repository_Interface {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->method( 'insert_event' )->willReturn( 5 );
        $event_repository->method( 'update_seasons' )->willReturn( true );
        return $event_repository;
    }

    private function make_dispatcher(
        Competition_Service $competition_service,
        Action_Guard_Interface $guard,
        ?Event_Repository_Interface $event_repository = null,
        ?League_Service $league_service = null,
        ?Competition_Season_Service $competition_season_service = null,
        ?Event_Query_Service_Interface $event_query_service = null
    ): Event_Action_Dispatcher {
        return new Event_Action_Dispatcher(
            $competition_service,
            $competition_season_service ?? $this->createStub( Competition_Season_Service::class ),
            $guard,
            $event_repository ?? $this->make_event_repository(),
            $league_service ?? $this->createStub( League_Service::class ),
            $event_query_service ?? $this->createStub( Event_Query_Service_Interface::class )
        );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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
        $dispatcher = $this->make_dispatcher( $competition_service, $guard, league_service: $league_service );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload()
        );

        self::assertSame( 'Event added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_add_event_config_forwards_injected_season_service_to_add_season_to_event(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $season_2025         = array( 'name' => '2025', 'home_away' => true, 'num_match_days' => 10, 'match_dates' => array() );
        $season_2026         = array( 'name' => '2026', 'home_away' => true, 'num_match_days' => 10, 'match_dates' => array() );
        $competition         = $this->make_competition(
            current_season: $season_2025,
            seasons: array( '2025' => $season_2025, '2026' => $season_2026 )
        );
        $competition->set_is_league( true );
        $competition_service->method( 'get_by_id' )->willReturn( $competition );

        $season_names = array();
        $season_service = $this->createMock( Competition_Season_Service::class );
        $season_service->expects( self::exactly( 2 ) )
            ->method( 'add_season_to_event' )
            ->willReturnCallback(
                static function ( Event $event, array $season ) use ( &$season_names ): void {
                    $season_names[] = $season['name'] ?? null;
                }
            );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'add_league_to_event' )->willReturn( $this->make_league() );

        $dispatcher = $this->make_dispatcher(
            $competition_service,
            $guard,
            league_service: $league_service,
            competition_season_service: $season_service
        );
        $result     = $dispatcher->handle( array( 'competition_id' => '1' ), $this->create_event_payload() );

        self::assertSame( array( '2025', '2026' ), $season_names );
        self::assertSame( 'Event added', $result->message );
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

        $captured_settings = null;
        $event_repository  = $this->make_event_repository();
        $event_repository->method( 'update_settings_and_rules' )
            ->willReturnCallback(
                static function ( $id, $settings ) use ( &$captured_settings ): bool {
                    $captured_settings = $settings;
                    return true;
                }
            );
        $dispatcher = $this->make_dispatcher( $competition_service, $guard, event_repository: $event_repository, league_service: $league_service );
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

        self::assertIsArray( $captured_settings );
        self::assertSame( 7, $captured_settings['primary_league'] );
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
        $dispatcher = $this->make_dispatcher( $competition_service, $guard, league_service: $league_service );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload()
        );

        self::assertSame( 'Could not create league for event', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_update_event_config_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'set_config' ) )->getMock();
        $event->set_id( 10 );
        $event->set_competition_id( 1 );
        $event->expects( self::once() )->method( 'set_config' )->willReturn( true );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'set_config' ) )->getMock();
        $event->set_id( 10 );
        $event->set_competition_id( 1 );
        $event->expects( self::once() )->method( 'set_config' )->willReturn( false );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = $this->make_dispatcher( $competition_service, $guard );
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

    public function test_update_event_config_delegates_fallback_resolution_to_query_service_and_persists_primary_league(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'set_config' ) )->getMock();
        $event->set_id( 10 );
        $event->set_competition_id( 1 );
        $event->set_settings( array() );
        $event->set_num_rubbers( 1 );
        $event->set_num_sets( 3 );
        $event->set_type( 'doubles' );
        $event->set_name( 'Men Doubles' );

        $captured_config = null;
        $event->expects( self::once() )
            ->method( 'set_config' )
            ->willReturnCallback(
                static function ( \stdClass $config ) use ( &$captured_config, $event ): bool {
                    $captured_config                   = $config;
                    $event->set_settings( array_merge( $event->settings, array( 'primary_league' => $config->primary_league ) ) );
                    return true;
                }
            );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $event_query_service = $this->createMock( Event_Query_Service_Interface::class );
        $event_query_service->expects( self::once() )
            ->method( 'get_primary_league_id' )
            ->with( $event )
            ->willReturn( 88 );

        $event_repository = $this->make_event_repository();
        $event_repository->expects( self::never() )->method( 'query_leagues' );
        $captured_saved_settings = null;
        $event_repository->expects( self::once() )
            ->method( 'update_settings_and_rules' )
            ->with(
                10,
                self::callback(
                    function ( array $settings ) use ( &$captured_saved_settings ): bool {
                        $captured_saved_settings = $settings;
                        return isset( $settings['primary_league'] ) && 88 === $settings['primary_league'];
                    }
                ),
                self::anything(),
                self::anything(),
                self::anything()
            );

        $dispatcher = $this->make_dispatcher(
            $competition_service,
            $guard,
            event_repository: $event_repository,
            event_query_service: $event_query_service
        );

        $result = $dispatcher->handle(
            array( 'competition_id' => '1', 'event_id' => '10' ),
            $this->create_event_payload(
                array(
                    'event_id'       => '10',
                    'primary_league' => '0',
                ),
                'updateEventConfig'
            )
        );

        self::assertSame( 'Event config updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertNotNull( $captured_config );
        self::assertSame( 88, $captured_config->primary_league );
        self::assertNotNull( $captured_saved_settings );
        self::assertSame( 88, $captured_saved_settings['primary_league'] );
    }

    public function test_update_event_config_bypasses_query_service_when_primary_league_is_explicitly_submitted(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'set_config' ) )->getMock();
        $event->set_id( 10 );
        $event->set_competition_id( 1 );

        $captured_config = null;
        $event->expects( self::once() )
            ->method( 'set_config' )
            ->willReturnCallback(
                static function ( \stdClass $config ) use ( &$captured_config ): bool {
                    $captured_config = $config;
                    return true;
                }
            );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $event_query_service = $this->createMock( Event_Query_Service_Interface::class );
        $event_query_service->expects( self::never() )->method( 'get_primary_league_id' );

        $event_repository = $this->make_event_repository();
        $event_repository->expects( self::never() )->method( 'query_leagues' );

        $dispatcher = $this->make_dispatcher(
            $competition_service,
            $guard,
            event_repository: $event_repository,
            event_query_service: $event_query_service
        );

        $result = $dispatcher->handle(
            array( 'competition_id' => '1', 'event_id' => '10' ),
            $this->create_event_payload(
                array(
                    'event_id'       => '10',
                    'primary_league' => '42',
                ),
                'updateEventConfig'
            )
        );

        self::assertSame( 'Event config updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertNotNull( $captured_config );
        self::assertSame( 42, $captured_config->primary_league );
    }

    public function test_update_event_config_retains_empty_when_fallback_query_service_returns_null(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $event = $this->getMockBuilder( Event::class )->disableOriginalConstructor()->onlyMethods( array( 'set_config' ) )->getMock();
        $event->set_id( 10 );
        $event->set_competition_id( 1 );

        $captured_config = null;
        $event->expects( self::once() )
            ->method( 'set_config' )
            ->willReturnCallback(
                static function ( \stdClass $config ) use ( &$captured_config ): bool {
                    $captured_config = $config;
                    return true;
                }
            );

        $competition_service->expects( self::once() )
            ->method( 'get_event_by_id' )
            ->with( 10 )
            ->willReturn( $event );
        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $event_query_service = $this->createMock( Event_Query_Service_Interface::class );
        $event_query_service->expects( self::once() )
            ->method( 'get_primary_league_id' )
            ->with( $event )
            ->willReturn( null );

        $event_repository = $this->make_event_repository();
        $event_repository->expects( self::never() )->method( 'query_leagues' );

        $dispatcher = $this->make_dispatcher(
            $competition_service,
            $guard,
            event_repository: $event_repository,
            event_query_service: $event_query_service
        );

        $result = $dispatcher->handle(
            array( 'competition_id' => '1', 'event_id' => '10' ),
            $this->create_event_payload(
                array(
                    'event_id'       => '10',
                    'primary_league' => '0',
                ),
                'updateEventConfig'
            )
        );

        self::assertSame( 'Event config updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertNotNull( $captured_config );
        self::assertSame( 0, $captured_config->primary_league );
    }

    public function test_add_event_config_league_creation_failure_returns_error_with_context(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->make_competition();

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $failed_league = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        League_Test_Factory::set_property( $failed_league, 'id', null );
        $league_service    = $this->createMock( League_Service::class );
        $league_service->expects( self::once() )
            ->method( 'add_league_to_event' )
            ->willReturn( $failed_league );
        $captured_settings = null;
        $event_repository  = $this->make_event_repository();
        $event_repository->method( 'update_settings_and_rules' )
            ->willReturnCallback(
                static function ( $id, $settings ) use ( &$captured_settings ): bool {
                    $captured_settings = $settings;
                    return true;
                }
            );
        $dispatcher = $this->make_dispatcher( $competition_service, $guard, event_repository: $event_repository, league_service: $league_service );
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

        self::assertIsArray( $captured_settings );
        self::assertArrayNotHasKey( 'primary_league', $captured_settings );
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

        $failed_plate = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        League_Test_Factory::set_property( $failed_plate, 'id', null );
        $league_service   = $this->createMock( League_Service::class );
        $league_service->expects( self::exactly( 2 ) )
            ->method( 'add_league_to_event' )
            ->willReturnOnConsecutiveCalls( $this->make_league( 7 ), $failed_plate );
        $captured_settings = null;
        $event_repository  = $this->make_event_repository();
        $event_repository->method( 'update_settings_and_rules' )
            ->willReturnCallback(
                static function ( $id, $settings ) use ( &$captured_settings ): bool {
                    $captured_settings = $settings;
                    return true;
                }
            );
        $dispatcher = $this->make_dispatcher( $competition_service, $guard, event_repository: $event_repository, league_service: $league_service );
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

        self::assertIsArray( $captured_settings );
        self::assertSame( 7, $captured_settings['primary_league'] );
    }

    public function test_create_event_returns_error_when_insert_event_fails(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->method( 'get_by_id' )->willReturn( $this->make_competition( 1 ) );
        $guard = $this->createStub( Action_Guard_Interface::class );

        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( self::once() )
            ->method( 'insert_event' )
            ->willReturn( 0 );

        $dispatcher = $this->make_dispatcher( $competition_service, $guard, event_repository: $event_repository );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_event_payload( array( 'event_name' => 'Failed Event' ) )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Could not create event', $result->message );
    }
}
