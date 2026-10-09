<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Event_Settings_Test extends TestCase {

    private Simple_Container $container;
    private $fixture_repository;
    private $rubber_repository;
    private $league_repository;
    private Event $event;

    protected function setUp(): void {
        parent::setUp();

        $this->container          = new Simple_Container();
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository  = $this->createMock( Rubber_Repository_Interface::class );
        $this->league_repository  = $this->createMock( League_Repository_Interface::class );

        $this->container->set( 'fixture_repository', $this->fixture_repository );
        $this->container->set( 'rubber_repository', $this->rubber_repository );
        $this->container->set( 'league_repository', $this->league_repository );
        $competition_service = $this->createMock( \Racketmanager\Services\Competition_Service::class );
        $this->container->set( 'competition_service', $competition_service );

        $registration_service = $this->createMock( \Racketmanager\Services\Registration_Service::class );
        $this->container->set( 'registration_service', $registration_service );

        $competition = $this->createMock( \Racketmanager\Domain\Competition\Competition::class );
        $competition_settings = new \Racketmanager\Domain\Competition\Competition_Settings( ['mode' => 'league'] );
        $competition->settings = $competition_settings;
        $competition->type = \Racketmanager\Domain\Competition\Competition_Type::LEAGUE;
        $competition->is_player_entry = false;
        $competition->standings = [];
        $competition_service->method('get_by_id')->willReturn($competition);

        $racketmanager = $this->getMockBuilder( RacketManager::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'get_matches' ] )
            ->getMock();
        $racketmanager->container   = $this->container;
        $racketmanager->time_format = 'H:i';
        $racketmanager->date_format = 'Y-m-d';
        $GLOBALS['racketmanager']   = $racketmanager;

        $GLOBALS['wpdb']->racketmanager_events = 'wp_racketmanager_events';

        $event_raw = new stdClass();
        $event_raw->id = 10;
        $event_raw->name = 'Test Event';
        $event_raw->type = 'doubles';
        $event_raw->competition_id = 1;
        $event_raw->seasons = '[]';
        $event_raw->num_rubbers = 4;
        $this->event = new Event( $event_raw );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['racketmanager'], $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    public function test_set_settings_creates_reverse_rubbers_using_repositories(): void {
        $GLOBALS['racketmanager']->expects( $this->once() )
            ->method( 'get_matches' )
            ->with( [
                'season'   => '2026',
                'event_id' => 10,
            ] )
            ->willReturn( [
                (object) [ 'id' => 100 ],
            ] );

        $fixture_raw            = new stdClass();
        $fixture_raw->id        = 100;
        $fixture_raw->league_id = 50;
        $fixture_raw->date      = '2026-06-01 19:00:00';
        $fixture                = new Fixture( $fixture_raw );

        $this->fixture_repository->method( 'find_by_id' )
            ->with( 100 )
            ->willReturn( $fixture );

        $this->rubber_repository->method( 'count_by_fixture_id' )
            ->with( 100 )
            ->willReturn( 2 );

        $league              = $this->createMock( League::class );
        $league->num_rubbers = 2;
        $this->league_repository->method( 'find_by_id' )
            ->with( 50 )
            ->willReturn( $league );

        // 2 existing rubbers, total = 4 rubbers -> saves 2 new rubbers (ix = 3, 4)
        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Rubber::class ) );

        $this->event->current_season = [ 'name' => '2026' ];
        $this->event->set_settings( [
            'reverse_rubbers' => '1',
        ] );

        $this->assertNotNull( $GLOBALS['wpdb']->last_query );
        $this->assertMatchesRegularExpression(
            '/`num_rubbers`\s*=\s*4/',
            $GLOBALS['wpdb']->last_query
        );
    }
}
