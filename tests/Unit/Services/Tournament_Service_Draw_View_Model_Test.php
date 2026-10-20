<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Tournament_Service_Draw_View_Model_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_options'] = array(
            'date_format'   => 'j M Y',
            'time_format'   => 'H:i',
            'racketmanager' => array(),
        );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'], $GLOBALS['wp_stubs_leagues'] );
        parent::tearDown();
    }

    public function test_get_draw_view_model_presents_championship_finals_with_enum_competition_data(): void {
        $container = new Simple_Container();
        $container->set(
            'admin_championship_finals_presenter',
            fn () => new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() )
        );

        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $container;

        $tournament_repo     = $this->createMock( Tournament_Repository_Interface::class );
        $league_service      = $this->createMock( League_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $fixture_service     = $this->createMock( Fixture_Service::class );

        $service = new Tournament_Service(
            plugin_instance: $plugin,
            tournament_repository: $tournament_repo,
            charge_repository: $this->createStub( Charge_Repository_Interface::class ),
            event_repository: $this->createStub( Event_Repository_Interface::class ),
            fixture_service: $fixture_service,
            league_team_repository: $this->createStub( League_Team_Repository_Interface::class ),
            tournament_entry_repository: $this->createStub( Tournament_Entry_Repository_Interface::class ),
            competition_service: $competition_service,
            player_service: $this->createStub( Player_Service::class ),
            player_stats_service: $this->createStub( Player_Stats_Service::class ),
            club_service: $this->createStub( Club_Service::class ),
            finance_service: $this->createStub( Finance_Service::class ),
            league_service: $league_service,
        );

        $tournament     = $this->createStub( Tournament::class );
        $tournament->id = 99;
        $tournament->method( 'get_id' )->willReturn( 99 );

        $league_entity = new class() {
            public int $id = 55;
            public string $season = '2026';
            public array $finals = array();
            public object $championship;
            public function __construct() {
                $this->championship = new class() {
                    public function get_finals(): array {
                        return array(
                            array( 'key' => 'semi', 'name' => 'Semi Final' ),
                            array( 'key' => 'final', 'name' => 'Championship Final' ),
                        );
                    }
                };
            }
            public function get_id(): int {
                return 55;
            }
            public function get_event_id(): int {
                return 12;
            }
        };
        $GLOBALS['wp_stubs_leagues'] = array( 55 => $league_entity );

        $competition                = $this->createStub( Competition::class );
        $competition->id            = 1;
        $competition->type          = Competition_Type::TOURNAMENT;
        $competition->is_tournament = true;
        $competition->is_cup        = false;

        $fixture_league     = $this->createStub( League::class );
        $fixture_league->id = 55;

        $raw_fixture            = new Fixture();
        $raw_fixture->id        = 101;
        $raw_fixture->league_id = 55;
        $raw_fixture->final     = 'final';
        $raw_fixture->season    = '2026';
        $raw_fixture->date      = '2026-07-25 15:30:00';
        $raw_fixture->location  = 'Court 1';

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Carlos Alcaraz' );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Jannik Sinner' );

        $fixture_details = new Fixture_Details_DTO(
            $raw_fixture,
            $fixture_league,
            $this->createStub( Event::class ),
            $competition,
            new Team_Details_DTO( $home_team ),
            new Team_Details_DTO( $away_team ),
        );

        $event = $this->createStub( Event::class );
        $event->method( 'get_id' )->willReturn( 12 );

        $tournament_repo->expects( $this->once() )
            ->method( 'find_event_for_tournament' )
            ->with( 99, 12 )
            ->willReturn( $event );
        $tournament_repo->expects( $this->once() )
            ->method( 'find_matches_by_event_for_tournament' )
            ->with( 99, 12 )
            ->willReturn( array( $raw_fixture ) );

        $competition_service->expects( $this->once() )
            ->method( 'get_leagues_for_event' )
            ->with( 12 )
            ->willReturn( array( (object) array( 'id' => 55 ) ) );

        $fixture_service->expects( $this->atLeastOnce() )
            ->method( 'get_tournament_fixture_with_details' )
            ->with( $raw_fixture )
            ->willReturn( $fixture_details );

        $league_service->expects( $this->once() )
            ->method( 'get_league_standings' )
            ->with( 55, '2026' )
            ->willReturn( array() );

        $vm = $service->get_draw_view_model( $tournament, $league_entity, array( 'tab' => 'fixtures' ), array() );

        $this->assertInstanceOf( Tournament_Draw_Page_View_Model::class, $vm );
        $this->assertSame( 'fixtures', $vm->tab );
        $this->assertSame( '2026', $vm->season );
        $this->assertCount( 1, $vm->finals );
        $this->assertArrayHasKey( 0, $vm->finals );

        $presented_final = $vm->finals[0];
        $this->assertSame( 'final', $presented_final->key );
        $this->assertCount( 1, $presented_final->fixtures );

        $presented_fixture = $presented_final->fixtures[0];
        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $presented_fixture );
        $this->assertSame( '25 Jul 2026', $presented_fixture->formatted_date );
        // Fixture entities carry no hour/minutes properties, so time is N/A (same as legacy behaviour).
        $this->assertSame( 'N/A', $presented_fixture->formatted_time );
        $this->assertSame( 'Court 1', $presented_fixture->formatted_location );
        $this->assertSame( 'Carlos Alcaraz - Jannik Sinner', $presented_fixture->teams_label );
        $this->assertSame(
            'admin.php?page=racketmanager-tournaments&view=fixture&tournament=99&league=55&edit=101&season=2026&final=final',
            $presented_fixture->match_link
        );
    }
}
