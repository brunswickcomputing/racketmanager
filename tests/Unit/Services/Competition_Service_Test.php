<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Competition_Service_Test extends TestCase {
    private $service;
    private $competition_repository;
    private $app;

    protected function setUp(): void {
        parent::setUp();
        
        $this->app = $this->createMock( RacketManager::class );
        $this->competition_repository = $this->createMock( Competition_Repository_Interface::class );
        
        $this->service = new Competition_Service(
            $this->app,
            $this->competition_repository,
            $this->createMock( Club_Repository_Interface::class ),
            $this->createMock( Event_Repository_Interface::class ),
            $this->createMock( League_Repository_Interface::class ),
            $this->createMock( League_Team_Repository_Interface::class ),
            $this->createMock( Season_Repository_Interface::class ),
            $this->createMock( Team_Repository_Interface::class )
        );

        // Clear global competition before each test
        unset( $GLOBALS['competition'] );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['competition'] );
        parent::tearDown();
    }

    public function test_get_competition_returns_object_if_passed() {
        $competition = $this->createStub( Competition::class );
        $result = $this->service->get_competition( $competition );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_does_not_use_global_if_no_id_passed(): void {
        $GLOBALS['competition'] = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 0 )->willReturn( null );

        $this->expectException( Competition_Not_Found_Exception::class );
        $this->service->get_competition();
    }

    public function test_get_competition_hydrates_from_stdclass() {
        $data = new stdClass();
        $data->id = 123;
        $data->name = 'Test Comp';
        $data->type = 'league';
        $data->seasons = json_encode([]);
        $data->settings = json_encode([]);

        $result = $this->service->get_competition( $data );
        
        $this->assertInstanceOf( Competition::class, $result );
        $this->assertEquals( 123, $result->id );
        $this->assertEquals( 'Test Comp', $result->name );
    }

    public function test_get_competition_finds_by_id() {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 123 )
            ->willReturn( $competition );

        $result = $this->service->get_competition( 123 );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_finds_by_name() {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_name' )
            ->with( 'Test Comp' )
            ->willReturn( $competition );

        $result = $this->service->get_competition( 'Test Comp', 'name' );
        $this->assertSame( $competition, $result );
    }

    public function test_get_competition_preserves_hyphenated_name(): void {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_name' )
            ->with( 'Under-18 Cup' )
            ->willReturn( $competition );

        $this->assertSame( $competition, $this->service->get_competition( 'Under-18 Cup', 'name' ) );
    }

    public function test_get_competition_treats_non_numeric_id_string_as_verbatim_name(): void {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_name' )
            ->with( 'Under-18 Cup' )
            ->willReturn( $competition );

        $this->assertSame( $competition, $this->service->get_competition( 'Under-18 Cup' ) );
    }

    public function test_get_competition_numeric_string_looks_up_id_even_with_global(): void {
        $GLOBALS['competition'] = 'under_18-cup';
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 12 )
            ->willReturn( $competition );

        $this->assertSame( $competition, $this->service->get_competition( '12' ) );
    }

    public function test_get_competition_does_not_read_global_for_empty_input(): void {
        $GLOBALS['competition'] = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->exactly( 2 ) )->method( 'find_by_id' )
            ->with( 0 )
            ->willReturn( null );

        foreach ( [ 0, '' ] as $input ) {
            try {
                $this->service->get_competition( $input );
                $this->fail( 'Empty input must not resolve from the global competition.' );
            } catch ( Competition_Not_Found_Exception $e ) {
            }
        }
    }

    public function test_get_competition_throws_exception_if_not_found() {
        $this->competition_repository->method( 'find_by_id' )->willReturn( null );
        
        $this->expectException( Competition_Not_Found_Exception::class );
        $this->service->get_competition( 999 );
    }

    public function test_save_delegates_to_competition_repository() {
        $competition = $this->createStub( Competition::class );
        $this->competition_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $competition )
            ->willReturn( true );

        $this->assertTrue( $this->service->save( $competition ) );
    }

    public function test_update_seasons_persists_when_changed() {
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Test Comp',
            seasons: [ '2024' => [ 'name' => '2024' ] ]
        );

        $this->competition_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $competition )
            ->willReturn( true );

        $result = $this->service->update_seasons( $competition, [ '2025' => [ 'name' => '2025' ] ] );
        $this->assertTrue( $result );
    }

    public function test_update_seasons_does_not_persist_when_unchanged() {
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Test Comp',
            seasons: [ '2025' => [ 'name' => '2025' ] ]
        );

        $this->competition_repository->expects( $this->never() )
            ->method( 'save' );

        $result = $this->service->update_seasons( $competition, [ '2025' => [ 'name' => '2025' ] ] );
        $this->assertFalse( $result );
    }

    public function test_add_season_persists_when_added() {
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Test Comp',
            seasons: [ '2024' => [ 'name' => '2024' ] ]
        );

        $this->competition_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $competition )
            ->willReturn( true );

        $result = $this->service->add_season( $competition, (object) [ 'name' => '2025' ] );
        $this->assertTrue( $result );
    }

    public function test_update_season_persists_when_updated() {
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Test Comp',
            seasons: [ '2024' => [ 'name' => '2024', 'date_start' => '2024-05-01' ] ]
        );

        $this->competition_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $competition )
            ->willReturn( true );

        $result = $this->service->update_season( $competition, [ 'name' => '2024', 'date_start' => '2024-06-01' ] );
        $this->assertTrue( $result );
    }

    public function test_get_matches_for_competition_delegates_to_repository() {
        $competition = Competition_Test_Factory::create( id: 10, name: 'Test Comp' );
        $args        = [ 'season' => '2025' ];

        $this->competition_repository->expects( $this->once() )
            ->method( 'find_matches' )
            ->with( 10, $args )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $result = $this->service->get_matches_for_competition( $competition, $args );
        $this->assertCount( 1, $result );
    }

    public function test_get_events_by_query_delegates_to_repository() {
        $competition = Competition_Test_Factory::create( id: 10, name: 'Test Comp' );
        $args        = [ 'season' => '2025' ];

        $this->competition_repository->expects( $this->once() )
            ->method( 'find_events' )
            ->with( 10, $args )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $result = $this->service->get_events_by_query( $competition, $args );
        $this->assertCount( 1, $result );
    }

    public function test_get_teams_by_query_delegates_to_repository() {
        $competition = Competition_Test_Factory::create( id: 10, name: 'Test Comp' );
        $args        = [ 'season' => '2025' ];

        $this->competition_repository->expects( $this->once() )
            ->method( 'find_teams' )
            ->with( 10, $args )
            ->willReturn( [ (object) [ 'id' => 201 ] ] );

        $result = $this->service->get_teams_by_query( $competition, $args );
        $this->assertCount( 1, $result );
    }

    public function test_get_players_for_competition_query_delegates_to_repository() {
        $competition = Competition_Test_Factory::create( id: 10, name: 'Test Comp' );
        $args        = [ 'season' => '2025' ];

        $this->competition_repository->expects( $this->once() )
            ->method( 'find_players' )
            ->with( 10, $args )
            ->willReturn( [ 301, 302 ] );

        $result = $this->service->get_players_for_competition_query( $competition, $args );
        $this->assertSame( [ 301, 302 ], $result );
    }

    private function make_event( int $id, string $name = 'Men Doubles', int $competition_id = 10, array $seasons = [ '2026' => [ 'name' => '2026' ] ] ): \Racketmanager\Domain\Competition\Event {
        $event                 = ( new \ReflectionClass( \Racketmanager\Domain\Competition\Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = $name;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( $seasons );

        return $event;
    }

    private function make_season_competition( array $seasons = [ '2026' => [ 'name' => '2026' ] ] ): Competition {
        return Competition_Test_Factory::create( id: 10, name: 'Test Comp', seasons: $seasons );
    }

    public function test_get_competition_events_loads_events_from_repository(): void {
        $competition = $this->make_season_competition();
        $events      = [ $this->make_event( 1 ) ];
        $this->competition_repository->expects( $this->once() )->method( 'find_events' )->with( 10 )->willReturn( $events );

        $this->assertSame( $events, $this->service->get_competition_events( $competition ) );
    }

    public function test_resolve_competition_event_resolves_local_id_slug_and_name_with_one_query_each(): void {
        $competition = $this->make_season_competition();
        $local       = $this->make_event( 11, 'Men Doubles' );
        $this->competition_repository->expects( $this->exactly( 3 ) )->method( 'find_events' )->with( 10 )->willReturn( [ $local ] );

        $this->assertSame( $local, $this->service->resolve_competition_event( $competition, 11, '2026' ) );
        $this->assertSame( $local, $this->service->resolve_competition_event( $competition, 'men-doubles', '2026' ) );
        $this->assertSame( $local, $this->service->resolve_competition_event( $competition, 'Men Doubles', '2026' ) );
    }

    public function test_resolve_competition_event_rejects_foreign_out_of_season_and_unknown_events(): void {
        $competition = $this->make_season_competition();
        $foreign     = $this->make_event( 1, 'Foreign Event', 999 );
        $old         = $this->make_event( 12, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] );
        $this->competition_repository->method( 'find_events' )->willReturn( [ $foreign, $old ] );

        $this->assertNull( $this->service->resolve_competition_event( $competition, 1, '2026' ) );
        $this->assertNull( $this->service->resolve_competition_event( $competition, 'foreign-event', '2026' ) );
        $this->assertNull( $this->service->resolve_competition_event( $competition, 12, '2026' ) );
        $this->assertNull( $this->service->resolve_competition_event( $competition, 999999, '2026' ) );
    }

    public function test_resolve_competition_event_with_event_instance_skips_repository_but_still_checks_scope(): void {
        $competition = $this->make_season_competition();
        $local       = $this->make_event( 11 );
        $foreign     = $this->make_event( 1, 'Foreign', 999 );
        $this->competition_repository->expects( $this->never() )->method( 'find_events' );

        $this->assertSame( $local, $this->service->resolve_competition_event( $competition, $local, '2026' ) );
        $this->assertNull( $this->service->resolve_competition_event( $competition, $foreign, '2026' ) );
    }

    public function test_get_entry_events_and_frontend_events_use_loaded_candidates_and_season_allowlist(): void {
        $competition = $this->make_season_competition( [ '2026' => [ 'name' => '2026', 'entry_events' => [ 11 ] ] ] );
        $events      = [ $this->make_event( 11 ), $this->make_event( 12, 'Ladies Doubles' ), $this->make_event( 1, 'Foreign', 999 ) ];
        $this->competition_repository->expects( $this->exactly( 2 ) )->method( 'find_events' )->with( 10 )->willReturn( $events );

        $entry    = $this->service->get_entry_events( $competition, '2026' );
        $frontend = $this->service->get_frontend_events( $competition, '2026' );

        $this->assertSame( [ 11 ], array_map( static fn( $event ) => $event->id, $entry ) );
        $this->assertSame( [ 11 ], array_map( static fn( $event ) => $event->id, $frontend ) );
    }
}
