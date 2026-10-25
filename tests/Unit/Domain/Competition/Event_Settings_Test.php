<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Event_Settings_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $event_repository;
    private $fixture_repository;
    private $rubber_repository;
    private $league_repository;
    private Event $event;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );

        $this->event_repository   = $this->createMock( Event_Repository_Interface::class );
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository  = $this->createMock( Rubber_Repository_Interface::class );
        $this->league_repository  = $this->createMock( League_Repository_Interface::class );

        $competition = $this->createMock( \Racketmanager\Domain\Competition\Competition::class );
        $competition_settings = new \Racketmanager\Domain\Competition\Competition_Settings( ['mode' => 'league'] );
        $competition->settings = $competition_settings;
        $competition->type = \Racketmanager\Domain\Competition\Competition_Type::LEAGUE;
        $competition->is_player_entry = false;
        $competition->standings = [];

        $event_raw = new stdClass();
        $event_raw->id = 10;
        $event_raw->name = 'Test Event';
        $event_raw->type = 'doubles';
        $event_raw->competition_id = 1;
        $event_raw->seasons = '[]';
        $event_raw->num_rubbers = 4;
        $this->event = new Event( $event_raw, $competition );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_set_settings_creates_reverse_rubbers_using_repositories(): void {
        $this->event_repository->expects( $this->once() )
            ->method( 'query_matches' )
            ->with(
                10,
                $this->callback(
                    static function ( array $args ): bool {
                        return 10 === ( $args['event_id'] ?? null ) && '2026' === ( $args['season'] ?? null );
                    }
                )
            )
            ->willReturn( [
                (object) [ 'id' => 100 ],
            ] );

        $fixture_raw            = new stdClass();
        $fixture_raw->id        = 100;
        $fixture_raw->league_id = 50;
        $fixture_raw->date      = '2026-06-01 19:00:00';
        $fixture                = Fixture_Factory::from_object( $fixture_raw );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_id_with_relations' )
            ->with( 100 )
            ->willReturn( $fixture );

        $this->rubber_repository->expects( $this->once() )
            ->method( 'count_by_fixture_id' )
            ->with( 100 )
            ->willReturn( 2 );

        $league              = $this->createMock( League::class );
        $league->num_rubbers = 2;
        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 50 )
            ->willReturn( $league );

        // 2 existing rubbers, total = 4 rubbers -> saves 2 new rubbers (ix = 3, 4)
        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Rubber::class ) );

        $this->event_repository->expects( $this->once() )
            ->method( 'update_settings_and_rules' )
            ->with( 10, [ 'reverse_rubbers' => '1' ], 4, null, 'doubles' )
            ->willReturn( true );

        $this->event->current_season = [ 'name' => '2026' ];
        $query_service = new \Racketmanager\Services\Competition\Event_Query_Service(
            $this->event_repository,
            $this->fixture_repository,
            $this->rubber_repository,
            $this->league_repository
        );

        $query_service->set_settings(
            $this->event,
            [
                'reverse_rubbers' => '1',
            ]
        );
        $this->assertSame( [ 'reverse_rubbers' => '1' ], $this->event->settings );
    }

    public function test_event_set_settings_updates_property_in_memory(): void {
        $this->event->set_settings( [ 'test_setting' => 'abc' ] );
        $this->assertSame( [ 'test_setting' => 'abc' ], $this->event->settings );
    }
}
