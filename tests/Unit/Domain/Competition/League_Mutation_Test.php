<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Notification\Notification_Service;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class League_Mutation_Test extends TestCase {

    private Simple_Container $container;
    private $fixture_repository;
    private $fixture_result_manager;
    private $notification_service;
    private League $league;

    protected function setUp(): void {
        parent::setUp();

        if ( ! isset( $GLOBALS['wpdb'] ) ) {
            $GLOBALS['wpdb'] = new \wpdb( 'user', 'password', 'dbname', 'localhost');
        }

        $this->container              = new Simple_Container();
        $this->fixture_repository     = $this->createMock( Fixture_Repository_Interface::class );
        $this->fixture_result_manager = $this->createMock( Fixture_Result_Manager::class );
        $this->notification_service   = $this->createMock( Notification_Service::class );

        $this->container->set( 'fixture_repository', $this->fixture_repository );
        $this->container->set( 'fixture_result_manager', $this->fixture_result_manager );
        $this->container->set( 'notification_service', $this->notification_service );

        $GLOBALS['racketmanager']            = new stdClass();
        $GLOBALS['racketmanager']->container = $this->container;

        $this->league = $this->getMockBuilder( League::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'update_standings', 'get_matches', 'notify_league_team_withdrawn' ] )
            ->getMock();
        $this->league->id = 5;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['racketmanager'] );
        parent::tearDown();
    }

    public function test_update_match_updates_linked_fixture_via_repository(): void {
        $primary_raw               = new stdClass();
        $primary_raw->id           = 10;
        $primary_raw->home_team    = '100';
        $primary_raw->away_team    = '200';
        $primary_raw->date         = '2026-06-01 19:00:00';
        $primary_raw->host         = 'home';
        $primary_raw->linked_match = 20;
        $primary_fixture           = new Fixture( $primary_raw );

        $linked_raw         = new stdClass();
        $linked_raw->id     = 20;
        $linked_raw->host   = 'away';
        $linked_fixture     = new Fixture( $linked_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 20 )
            ->willReturn( $linked_fixture );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' );

        $this->league->update_match( $primary_fixture );

        $this->assertSame( '100', $linked_fixture->get_home_team() );
        $this->assertSame( '200', $linked_fixture->get_away_team() );
        $this->assertSame( 'away', $linked_fixture->get_host() );
        $this->assertSame( '2026-06-15 19:00:00', $linked_fixture->get_date() );
    }

    public function test_update_match_results_uses_fixture_repository_and_result_manager(): void {
        $fixture_raw            = new stdClass();
        $fixture_raw->id        = 15;
        $fixture_raw->home_team = '100';
        $fixture_raw->away_team = '200';
        $fixture_raw->status    = 1;
        $fixture_raw->custom    = [ 'sets' => [ [ 'home' => 6, 'away' => 3 ] ] ];
        $fixture                = new Fixture( $fixture_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 15 )
            ->willReturn( $fixture );

        $this->fixture_result_manager->expects( $this->once() )
            ->method( 'confirm_result' )
            ->with(
                $fixture,
                '',
                null,
                $this->isInstanceOf( Result::class )
            );

        $matches     = [ 15 ];
        $home_points = [ 15 => '5.0' ];
        $away_points = [ 15 => '3.0' ];
        $custom      = [ 15 => [ 'notes' => 'Great match' ] ];

        $result_count = $this->league->update_match_results(
            $matches,
            $home_points,
            $away_points,
            $custom,
            '2026',
            'Final', // prevent update_standings db call
            'Y'
        );

        $this->assertSame( 1, $result_count );
    }

    public function test_delete_team_deletes_fixtures_via_repository(): void {
        $this->league->method( 'get_matches' )
            ->with( [
                'team_id' => 50,
                'season'  => '2026',
                'final'   => 'all',
            ] )
            ->willReturn( [
                (object) [ 'id' => 101 ],
                (object) [ 'id' => 102 ],
            ] );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'delete' )
            ->willReturn( true );

        $this->league->delete_team( 50, '2026' );

        $this->assertStringContainsString( 'DELETE FROM wp_racketmanager_league_teams', $GLOBALS['wpdb']->last_query );
    }

    public function test_withdraw_team_championship_updates_result_and_notifies(): void {
        $this->league->is_championship = true;

        $fixture_raw           = new stdClass();
        $fixture_raw->id       = 101;
        $fixture_raw->home_team = '50';
        $fixture_raw->away_team = '60';
        $fixture_raw->leg      = 2;
        $fixture_raw->confirmed = '1';
        $fixture_raw->status   = 0;
        $fixture               = new Fixture( $fixture_raw );

        $this->league->method( 'get_matches' )
            ->willReturn( [ (object) [ 'id' => 101 ] ] );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 101 )
            ->willReturn( $fixture );

        $this->notification_service->expects( $this->once() )
            ->method( 'notify_team_withdrawal' )
            ->with( $fixture, 50 );

        $this->fixture_result_manager->expects( $this->once() )
            ->method( 'handle_fixture_result_update' )
            ->with(
                $fixture,
                $this->callback( function ( Fixture_Result_Update_Request $req ) {
                    return 101 === $req->fixture_id && 'walkover_player2' === $req->match_status && '1' === $req->confirmed;
                } )
            );

        $this->league->withdraw_team( 50, '2026' );

        $this->assertStringContainsString( "UPDATE wp_racketmanager_league_teams SET `status` = 'W'", $GLOBALS['wpdb']->last_query );
    }

    public function test_withdraw_team_league_confirms_cancelled_result_and_updates_standings(): void {
        $this->league->is_championship = false;

        $fixture_raw           = new stdClass();
        $fixture_raw->id       = 102;
        $fixture_raw->home_team = '50';
        $fixture_raw->away_team = '60';
        $fixture_raw->status   = 0;
        $fixture_raw->custom   = [ 'some' => 'meta' ];
        $fixture               = new Fixture( $fixture_raw );

        $this->league->method( 'get_matches' )
            ->willReturn( [ (object) [ 'id' => 102 ] ] );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 102 )
            ->willReturn( $fixture );

        $this->fixture_result_manager->expects( $this->once() )
            ->method( 'confirm_result' )
            ->with(
                $fixture,
                '',
                null,
                $this->callback( function ( Result $result ) {
                    return 0.0 === (float) $result->get_home_points()
                        && 0.0 === (float) $result->get_away_points()
                        && 8 === $result->get_status(); // cancelled code
                } )
            );

        $this->league->expects( $this->once() )
            ->method( 'update_standings' )
            ->with( '2026' );

        $this->league->expects( $this->once() )
            ->method( 'notify_league_team_withdrawn' )
            ->with( 50, '2026' );

        $this->league->withdraw_team( 50, '2026' );

        $this->assertStringContainsString( "UPDATE wp_racketmanager_league_teams SET `status` = 'W'", $GLOBALS['wpdb']->last_query );
    }
}
