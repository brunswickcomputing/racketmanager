<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Messages;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Service_Test extends TestCase {

    public function test_bulk_remove_tournaments_success(): void {
        $repo    = $this->createMock( Tournament_Repository_Interface::class );
        $service = $this->get_mocked_service( $repo );

        $service->expects( self::exactly( 2 ) )
                ->method( 'remove_tournament' )
                ->willReturnMap( [
                    [ 123, true ],
                    [ 456, true ],
                ] );

        $result = $service->bulk_remove_tournaments( [ 123, 456 ] );

        $expected_message = Util_Messages::tournament_deleted( 123 ) . '<br>' . Util_Messages::tournament_deleted( 456 );
        self::assertEquals( $expected_message, $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    private function get_mocked_service( $tournament_repository ): MockObject|Tournament_Service {
        return $this->getMockBuilder( Tournament_Service::class )
                    ->setConstructorArgs( [
                        $this->createMock( RacketManager::class ),
                        $tournament_repository,
                        $this->createMock( Charge_Repository::class ),
                        $this->createMock( Event_Repository_Interface::class ),
                        $this->createMock( Fixture_Service::class ),
                        $this->createMock( League_Team_Repository_Interface::class ),
                        $this->createMock( Tournament_Entry_Repository_Interface::class ),
                        $this->createMock( Competition_Service::class ),
                        $this->createMock( Player_Service::class ),
                        $this->createMock( Player_Stats_Service::class ),
                        $this->createMock( Club_Service::class ),
                        $this->createMock( Finance_Service::class ),
                        $this->createMock( League_Service::class )
                    ] )
                    ->onlyMethods( [ 'remove_tournament' ] )
                    ->getMock();
    }

    public function test_bulk_remove_tournaments_with_not_found(): void {
        $repo    = $this->createMock( Tournament_Repository_Interface::class );
        $service = $this->get_mocked_service( $repo );

        $service->expects( self::exactly( 2 ) )
                ->method( 'remove_tournament' )
                ->willReturnCallback( function ( $id ) {
                    if ( $id === 123 ) {
                        return true;
                    }
                    throw new Tournament_Not_Found_Exception( "Not found $id" );
                } );

        $result = $service->bulk_remove_tournaments( [ 123, 456 ] );

        $expected_message = Util_Messages::tournament_deleted( 123 ) . '<br>Not found 456';
        self::assertEquals( $expected_message, $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    private function make_entry_event( int $id, string $name, int $competition_id = 10, array $seasons = [ '2026' => [ 'name' => '2026' ] ] ): \Racketmanager\Domain\Competition\Event {
        $event                 = ( new \ReflectionClass( \Racketmanager\Domain\Competition\Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = $name;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( $seasons );

        return $event;
    }

    private function make_entry_tournament( ?array $information ): \Racketmanager\Domain\Tournament\Tournament {
        $competition = \Racketmanager\Tests\Helpers\Competition_Test_Factory::create(
            id: 10,
            name: 'Tournament Comp',
            type: \Racketmanager\Domain\Competition\Competition_Type::TOURNAMENT,
            seasons: [ '2026' => [ 'name' => '2026' ] ]
        );
        $data                 = new \stdClass();
        $data->id             = 1;
        $data->name           = 'Test Tournament';
        $data->competition_id = 10;
        $data->season         = '2026';
        if ( null !== $information ) {
            $data->information = json_encode( $information );
        }
        $tournament = \Racketmanager\Domain\Tournament\Tournament::from_object( $data );
        $tournament->set_competition( $competition );

        return $tournament;
    }

    private function make_entry_service( $competition_service ): Tournament_Service {
        return new Tournament_Service(
            $this->createMock( RacketManager::class ),
            $this->createMock( Tournament_Repository_Interface::class ),
            $this->createMock( Charge_Repository::class ),
            $this->createMock( Event_Repository_Interface::class ),
            $this->createMock( Fixture_Service::class ),
            $this->createMock( League_Team_Repository_Interface::class ),
            $this->createMock( Tournament_Entry_Repository_Interface::class ),
            $competition_service,
            $this->createMock( Player_Service::class ),
            $this->createMock( Player_Stats_Service::class ),
            $this->createMock( Club_Service::class ),
            $this->createMock( Finance_Service::class ),
            $this->createMock( League_Service::class )
        );
    }

    public function test_get_unavailable_entry_events_resolves_submitted_ids_once_and_rejects_foreign_and_out_of_season(): void {
        $tournament = $this->make_entry_tournament( null );
        $loaded     = [
            $this->make_entry_event( 11, 'Men Doubles' ),
            $this->make_entry_event( 1, 'Foreign Event', 999 ),
            $this->make_entry_event( 12, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] ),
        ];
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_events' )
            ->with( $tournament->get_competition_object() )
            ->willReturn( $loaded );

        $unavailable = $this->make_entry_service( $competition_service )
            ->get_unavailable_entry_events( $tournament, [ 11, 1, 12, 999999, '11' ] );

        self::assertSame( [ 1, 12, 999999 ], $unavailable );
    }

    public function test_get_unavailable_entry_events_applies_tournament_allowlist_after_resolution(): void {
        $tournament = $this->make_entry_tournament( [ 'entry_events' => [ 11 ] ] );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->method( 'get_competition_events' )->willReturn(
            [ $this->make_entry_event( 11, 'Men Doubles' ), $this->make_entry_event( 13, 'Ladies Doubles' ) ]
        );

        $unavailable = $this->make_entry_service( $competition_service )->get_unavailable_entry_events( $tournament, [ 11, 13 ] );

        self::assertSame( [ 13 ], $unavailable );
    }

    public function test_get_unavailable_entry_events_rejects_everything_when_no_events_load_or_no_competition(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->method( 'get_competition_events' )->willReturn( [] );
        $service = $this->make_entry_service( $competition_service );

        self::assertSame( [ 11 ], $service->get_unavailable_entry_events( $this->make_entry_tournament( null ), [ 11 ] ) );

        $orphan = \Racketmanager\Domain\Tournament\Tournament::from_object( (object) [ 'id' => 1, 'name' => 'T', 'competition_id' => 10, 'season' => '2026' ] );
        self::assertSame( [ 11 ], $service->get_unavailable_entry_events( $orphan, [ 11 ] ) );
    }

    public function test_get_unavailable_entry_events_skips_loading_when_nothing_submitted(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition_events' );

        self::assertSame( [], $this->make_entry_service( $competition_service )->get_unavailable_entry_events( $this->make_entry_tournament( null ), [] ) );
    }

    /**
     * Invoke the private save_tournament_and_fees() against a service whose tournament repository
     * answers save_with_charge() with the given result.
     */
    private function save_tournament_and_fees_with( array $composite_result ): bool {
        $tournament        = new Tournament();
        $charge            = new Charge();
        $charge_repository = $this->createMock( Charge_Repository::class );
        $charge_repository->expects( self::never() )->method( 'save' );

        $tournament_repository = $this->createMock( Tournament_Repository_Interface::class );
        $tournament_repository->expects( self::once() )
            ->method( 'save_with_charge' )
            ->with( self::identicalTo( $tournament ), self::identicalTo( $charge ), self::identicalTo( $charge_repository ) )
            ->willReturn( $composite_result );
        $tournament_repository->expects( self::never() )->method( 'save' );

        $service = new Tournament_Service(
            $this->createMock( RacketManager::class ),
            $tournament_repository,
            $charge_repository,
            $this->createMock( Event_Repository_Interface::class ),
            $this->createMock( Fixture_Service::class ),
            $this->createMock( League_Team_Repository_Interface::class ),
            $this->createMock( Tournament_Entry_Repository_Interface::class ),
            $this->createMock( Competition_Service::class ),
            $this->createMock( Player_Service::class ),
            $this->createMock( Player_Stats_Service::class ),
            $this->createMock( Club_Service::class ),
            $this->createMock( Finance_Service::class ),
            $this->createMock( League_Service::class )
        );

        $method = new \ReflectionMethod( $service, 'save_tournament_and_fees' );

        return $method->invoke( $service, $tournament, $charge );
    }

    public function test_save_tournament_and_fees_throws_tournament_message_when_tournament_save_fails(): void {
        $this->expectException( Database_Operation_Exception::class );
        $this->expectExceptionMessage( Util_Messages::tournament_failed_to_save() );

        $this->save_tournament_and_fees_with( [ 'tournament' => false, 'charge' => null ] );
    }

    public function test_save_tournament_and_fees_throws_charge_message_when_charge_save_fails(): void {
        $this->expectException( Database_Operation_Exception::class );
        $this->expectExceptionMessage( Util_Messages::charge_failed_to_save() );

        $this->save_tournament_and_fees_with( [ 'tournament' => 7, 'charge' => false ] );
    }

    public function test_save_tournament_and_fees_reports_updates_when_either_record_changed(): void {
        self::assertTrue( $this->save_tournament_and_fees_with( [ 'tournament' => 7, 'charge' => 9 ] ) );
        self::assertTrue( $this->save_tournament_and_fees_with( [ 'tournament' => 0, 'charge' => 9 ] ) );
        self::assertTrue( $this->save_tournament_and_fees_with( [ 'tournament' => true, 'charge' => 0 ] ) );
        self::assertTrue( $this->save_tournament_and_fees_with( [ 'tournament' => 7, 'charge' => 0 ] ) );
    }

    public function test_save_tournament_and_fees_reports_no_updates_when_both_records_unchanged(): void {
        self::assertFalse( $this->save_tournament_and_fees_with( [ 'tournament' => 0, 'charge' => 0 ] ) );
    }
}
