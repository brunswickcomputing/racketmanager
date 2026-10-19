<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Championship;
use Racketmanager\Domain\Championship_Settings;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Championship_Manager_Test extends TestCase {

    private Fixture_Repository_Interface $fixture_repository;
    private Notification_Service $notification_service;
    private Result_Service $result_service;
    private Championship_Manager $manager;

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_repository   = $this->createMock( Fixture_Repository_Interface::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->result_service       = $this->createMock( Result_Service::class );

        $this->manager = new Championship_Manager(
            $this->result_service,
            $this->fixture_repository,
            $this->createStub( League_Service::class ),
            $this->createStub( Event_Repository_Interface::class ),
            $this->notification_service
        );
    }

    public function test_set_teams_updates_teams_saves_and_notifies(): void {
        $primary_raw = new stdClass();
        $primary_raw->id = 10;
        $primary_raw->home_team = '100';
        $primary_raw->away_team = null;
        $primary_raw->linked_match = 20;
        $primary_fixture = Fixture_Factory::from_object( $primary_raw );

        $linked_raw = new stdClass();
        $linked_raw->id = 20;
        $linked_raw->home_team = '100';
        $linked_raw->away_team = null;
        $linked_fixture = Fixture_Factory::from_object( $linked_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->willReturnMap( [
                [ 10, $primary_fixture ],
                [ 20, $linked_fixture ],
            ] );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' );

        $this->notification_service->expects( $this->exactly( 2 ) )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( (object) [ 'id' => 10 ], '101', '202' );

        $this->assertSame( '101', $primary_fixture->get_home_team() );
        $this->assertSame( '202', $primary_fixture->get_away_team() );
        $this->assertSame( '101', $linked_fixture->get_home_team() );
        $this->assertSame( '202', $linked_fixture->get_away_team() );
    }

    public function test_set_teams_when_fixture_not_found_does_nothing(): void {
        $this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $this->fixture_repository->expects( $this->never() )
            ->method( 'save' );
        $this->notification_service->expects( $this->never() )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( 999, '100', '200' );
    }

    public function test_set_teams_with_non_numeric_teams_does_not_notify(): void {
        $primary_raw = new stdClass();
        $primary_raw->id = 10;
        $primary_raw->home_team = null;
        $primary_raw->away_team = null;
        $primary_fixture = Fixture_Factory::from_object( $primary_raw );

        $this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $primary_fixture );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $primary_fixture );

        $this->notification_service->expects( $this->never() )
            ->method( 'send_next_fixture_notification' );

        $this->manager->set_teams( 10, 'Winner Semi 1', 'Winner Semi 2' );

        $this->assertSame( 'Winner Semi 1', $primary_fixture->get_home_team() );
        $this->assertSame( 'Winner Semi 2', $primary_fixture->get_away_team() );
    }

    public function test_update_final_results_with_found_fixture_applies_results_and_proceeds(): void {
        $settings     = new Championship_Settings();
        $championship = new Championship(
            league_id: 10,
            is_consolation: false,
            settings: $settings,
            num_advance: 4,
            num_rounds: 2,
            num_teams: 4,
            num_teams_first_round: 4,
            num_seeds: 0,
            keys_by_round: array( 1 => 'semi', 2 => 'final' ),
            finals_by_key: array(
                'semi'  => array( 'key' => 'semi', 'name' => 'Semi Final' ),
                'final' => array( 'key' => 'final', 'name' => 'Final' ),
            ),
            final_teams_by_round: array(),
            current_final: 'semi'
        );

        $fixture_raw = new stdClass();
        $fixture_raw->id = 101;
        $fixture_raw->home_team = '1';
        $fixture_raw->away_team = '2';
        $fixture = Fixture_Factory::from_object( $fixture_raw );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 101 )
            ->willReturn( $fixture );

        $this->result_service->expects( $this->once() )
            ->method( 'apply_to_fixture' )
            ->with(
                $fixture,
                $this->isInstanceOf( Result::class )
            );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_season' )->willReturn( '2026' );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( $this->once() )
            ->method( 'get_league' )
            ->with( 10 )
            ->willReturn( $league );

        $next_fixture_raw = new stdClass();
        $next_fixture_raw->id = 201;
        $next_fixture_raw->home_team = 'Winner Semi 1';
        $next_fixture_raw->away_team = 'Winner Semi 2';
        $next_fixture = Fixture_Factory::from_object( $next_fixture_raw );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_league_and_final' )
            ->willReturnCallback( function ( $league_id, $season, $final_key, $args = null ) use ( $next_fixture, $fixture ) {
                if ( 'final' === $final_key ) {
                    return [ $next_fixture ];
                }
                if ( 'semi' === $final_key ) {
                    return [ $fixture ];
                }
                return [];
            } );

        $manager = new Championship_Manager(
            $this->result_service,
            $this->fixture_repository,
            $league_service,
            $this->createStub( Event_Repository_Interface::class ),
            $this->notification_service
        );

        $count = $manager->update_final_results(
            $championship,
            [ 101 ],
            [ 101 => 6 ],
            [ 101 => 3 ],
            [ 101 => [] ],
            1
        );

        $this->assertSame( 1, $count );
    }

    public function test_update_final_results_on_last_round_does_not_proceed(): void {
        $settings     = new Championship_Settings();
        $championship = new Championship(
            league_id: 10,
            is_consolation: false,
            settings: $settings,
            num_advance: 2,
            num_rounds: 1,
            num_teams: 2,
            num_teams_first_round: 2,
            num_seeds: 0,
            keys_by_round: array( 1 => 'final' ),
            finals_by_key: array(
                'final' => array( 'key' => 'final', 'name' => 'Final' ),
            ),
            final_teams_by_round: array(),
            current_final: 'final'
        );

        $fixture_raw = new stdClass();
        $fixture_raw->id = 101;
        $fixture = Fixture_Factory::from_object( $fixture_raw );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 101 )
            ->willReturn( $fixture );

        $this->result_service->expects( $this->once() )
            ->method( 'apply_to_fixture' )
            ->with( $fixture, $this->isInstanceOf( Result::class ) );

        $this->fixture_repository->expects( $this->never() )
            ->method( 'find_by_league_and_final' );

        $count = $this->manager->update_final_results(
            $championship,
            [ 101 ],
            [ 101 => 6 ],
            [ 101 => 3 ],
            [],
            1
        );

        $this->assertSame( 1, $count );
    }

    public function test_proceed_uses_injected_league_service_and_find_by_league_and_final(): void {
        $settings     = new Championship_Settings();
        $championship = new Championship(
            league_id: 10,
            is_consolation: false,
            settings: $settings,
            num_advance: 4,
            num_rounds: 2,
            num_teams: 4,
            num_teams_first_round: 4,
            num_seeds: 0,
            keys_by_round: array( 1 => 'semi', 2 => 'final' ),
            finals_by_key: array(
                'semi'  => array( 'key' => 'semi', 'name' => 'Semi Final' ),
                'final' => array( 'key' => 'final', 'name' => 'Final' ),
            ),
            final_teams_by_round: array(),
            current_final: 'semi'
        );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_season' )->willReturn( '2026' );

        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( $this->once() )
            ->method( 'get_league' )
            ->with( 10 )
            ->willReturn( $league );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_league_and_final' )
            ->with( 10, '2026', 'final', $this->anything() )
            ->willReturn( [] );

        $manager = new Championship_Manager(
            $this->result_service,
            $this->fixture_repository,
            $league_service,
            $this->createStub( Event_Repository_Interface::class ),
            $this->notification_service
        );

        $manager->proceed( $championship, 1 );
    }

    public function test_set_consolation_team_resolves_each_league_through_injected_services(): void {
        $event = $this->createMock( Event::class );
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 55 )
            ->willReturn( $event );
        $event_repository->expects( $this->once() )
            ->method( 'query_leagues' )
            ->with( 55, [ 'consolation' => true ] )
            ->willReturn( [ (object) [ 'id' => 31 ], (object) [ 'id' => 32 ] ] );

        $consolation_leagues = [
            31 => $this->createMock( League::class ),
            32 => $this->createMock( League::class ),
        ];
        foreach ( $consolation_leagues as $consolation_league ) {
            $consolation_league->expects( $this->once() )
                ->method( 'get_league_teams' )
                ->with( [ 'team_name' => '2_semi_101', 'reset_query_args' => true ] )
                ->willReturn( [] );
        }

        $resolved_ids = [];
        $league_service = $this->createMock( League_Service::class );
        $league_service->expects( $this->exactly( 2 ) )
            ->method( 'get_league' )
            ->willReturnCallback( function ( int $league_id ) use ( &$resolved_ids, $consolation_leagues ) {
                $resolved_ids[] = $league_id;
                return $consolation_leagues[ $league_id ];
            } );

        $manager = new Championship_Manager(
            $this->result_service,
            $this->fixture_repository,
            $league_service,
            $event_repository,
            $this->notification_service
        );
        $league = $this->createMock( League::class );
        $league->method( 'get_event_id' )->willReturn( 55 );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_season' )->willReturn( '2026' );

        $method = new \ReflectionMethod( Championship_Manager::class, 'set_consolation_team' );
        $method->invoke( $manager, (object) [ 'loser_id' => 20, 'is_walkover' => true, 'id' => 101 ], 'semi', $league );

        $this->assertSame( [ 31, 32 ], $resolved_ids );
    }
}
