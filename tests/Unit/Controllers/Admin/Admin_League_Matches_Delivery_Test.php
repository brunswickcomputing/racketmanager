<?php
/**
 * Admin league matches delivery test
 *
 * Verifies that the production caller (Admin_League) hands preformatted view models to the template.
 *
 * @package RacketManager
 * @subpackage Unit/Controllers/Admin
 */

declare( strict_types=1 );

namespace Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter;
use Racketmanager\Admin\View_Models\Admin_League_Match_View_Model;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Admin_League_Matches_Delivery_Test extends TestCase {

    private Simple_Container $container;
    private Admin_League $admin_league;
    private mixed $saved_racketmanager = null;

    protected function setUp(): void {
        parent::setUp();
        $this->saved_racketmanager   = $GLOBALS['racketmanager'] ?? null;
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $message_service = new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) );
        $action_adapter  = new Admin_Action_Adapter( $message_service, $this->createStub( Admin_Redirector_Interface::class ) );
        $x               = $this->createStub( \Racketmanager\Services\Competition_Service::class );

        $container = new Simple_Container();
        $container->set( 'admin_action_adapter', fn() => $action_adapter );
        $container->set( 'admin_message_service', fn() => $message_service );
        $container->set( 'competition_service', fn() => $x );
        $container->set( 'season_service', fn() => $this->createStub( \Racketmanager\Services\Season_Service::class ) );
        $container->set( 'results_checker_presenter', fn() => $this->createStub( \Racketmanager\Presentation\Presenters\Results_Checker_Presenter::class ) );
        $container->set( 'club_service', fn() => $this->createStub( \Racketmanager\Services\Club_Service::class ) );
        $container->set( 'player_service', fn() => $this->createStub( \Racketmanager\Services\Player_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( \Racketmanager\Services\Registration_Service::class ) );
        $container->set( 'team_service', fn() => $this->createStub( \Racketmanager\Services\Team_Service::class ) );
        $container->set( 'league_service', fn() => $this->createStub( \Racketmanager\Services\League_Service::class ) );
        $container->set( 'finance_service', fn() => $this->createStub( \Racketmanager\Services\Finance_Service::class ) );
        $container->set( 'tournament_service', fn() => $this->createStub( \Racketmanager\Services\Tournament_Service::class ) );
        $container->set( 'fixture_repository', fn() => $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ) );
        $container->set( 'league_contact_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'event_setup_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'season_delete_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Competition_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_match_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher( $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ), $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_constitution_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_event_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher( $this->createStub( \Racketmanager\Services\League_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'league_schedule_action_dispatcher', fn() => new \Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher( new \Racketmanager\Services\Admin\League\League_Schedule_Service( $this->createStub( \Racketmanager\Services\Fixture\Fixture_Maintenance_Service::class ) ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Player_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_player_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Registration_Service::class ), $this->createStub( \Racketmanager\Services\Player_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_team_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Team_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'club_role_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Club_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'season_action_dispatcher', fn() => new \Racketmanager\Services\Admin\Season\Season_Action_Dispatcher( $this->createStub( \Racketmanager\Services\Season_Service::class ), $this->createStub( \Racketmanager\Services\Admin\Security\Action_Guard_Interface::class ) ) );
        $container->set( 'fixture_detail_service', fn() => $this->createStub( Fixture_Detail_Service::class ) );
        $this->container = $container;

        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $container;

        $this->admin_league = new Admin_League( $plugin );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        $GLOBALS['racketmanager'] = $this->saved_racketmanager;
        parent::tearDown();
    }

    private function make_league( array $matches ): object {
        $competition       = new \stdClass();
        $competition->type = Competition_Type::LEAGUE;
        $event             = new \stdClass();
        $event->competition = $competition;

        return new class( $event, $matches ) {
            public int $id = 4;
            public object $event;
            public array $received_args = array();
            public bool $num_matches_set = false;

            public function __construct( object $event, private array $matches ) {
                $this->event = $event;
            }

            public function get_matches( array $args ): array {
                $this->received_args = $args;
                return $this->matches;
            }

            public function set_num_matches(): void {
                $this->num_matches_set = true;
            }
        };
    }

    public function test_prepare_league_matches_returns_preformatted_view_models_for_enum_competition(): void {
        $this->container->set(
            'admin_league_matches_presenter',
            fn() => new Admin_League_Matches_Presenter( new Admin_Date_Presenter() )
        );

        $match           = new \stdClass();
        $match->id       = 55;
        $match->date     = '2026-04-04 18:30:00';
        $match->hour     = '18';
        $match->minutes  = '30';
        $match->location = 'Court 3';
        $league          = $this->make_league( array( $match ) );

        $result = $this->admin_league->prepare_league_matches( $league, array( 'season' => '2026' ), '2026', 'A' );

        $this->assertTrue( $league->received_args['reset_query_args'] );
        $this->assertSame( '2026', $league->received_args['season'] );
        $this->assertTrue( $league->num_matches_set );
        $this->assertCount( 1, $result );
        $this->assertInstanceOf( Admin_League_Match_View_Model::class, $result[0] );
        $this->assertSame( '4 Apr 2026', $result[0]->formatted_date );
        $this->assertSame( '18:30', $result[0]->formatted_time );
        $this->assertSame( 'Court 3', $result[0]->formatted_location );
        $this->assertStringContainsString( 'page=racketmanager-leagues', $result[0]->edit_url );
        $this->assertStringContainsString( 'edit=55', $result[0]->edit_url );
        $this->assertStringContainsString( 'season=2026', $result[0]->edit_url );
        $this->assertStringContainsString( '&group=A', $result[0]->edit_url );
    }

    public function test_prepare_league_matches_never_returns_raw_rows(): void {
        $this->container->set(
            'admin_league_matches_presenter',
            fn() => new Admin_League_Matches_Presenter( new Admin_Date_Presenter() )
        );
        $first     = new \stdClass();
        $first->id = 9;
        $second    = new \stdClass();
        $second->id = 10;
        $league    = $this->make_league( array( $first, $second ) );

        $result = $this->admin_league->prepare_league_matches( $league, array(), '2026', '' );

        $this->assertCount( 2, $result );
        foreach ( $result as $row ) {
            $this->assertInstanceOf( Admin_League_Match_View_Model::class, $row );
        }
        $this->assertSame( $first, $result[0]->match );
        $this->assertSame( $second, $result[1]->match );
        $this->assertSame( 'N/A', $result[0]->formatted_date );
        $this->assertStringContainsString( 'edit=10', $result[1]->edit_url );
    }

    public function test_prepare_league_matches_falls_back_to_default_presenter_when_not_registered(): void {
        $match     = new \stdClass();
        $match->id = 9;
        $league    = $this->make_league( array( $match ) );

        $result = $this->admin_league->prepare_league_matches( $league, array(), '2026', '' );

        $this->assertCount( 1, $result );
        $this->assertInstanceOf( Admin_League_Match_View_Model::class, $result[0] );
        $this->assertSame( 'N/A', $result[0]->formatted_location );
    }

    public function test_present_league_finals_builds_cup_finals_with_presented_fixtures(): void {
        $this->container->set(
            'admin_championship_finals_presenter',
            fn() => new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() )
        );

        $competition       = $this->createStub( Competition::class );
        $competition->id   = 10;
        $competition->type = Competition_Type::CUP;
        $competition->is_cup = true;

        $fixture_league     = $this->createStub( League::class );
        $fixture_league->id = 4;

        $raw            = new Fixture();
        $raw->id        = 77;
        $raw->league_id = 4;
        $raw->final     = 'final';
        $raw->season    = '2026';
        $raw->date      = '2026-05-09 11:00:00';
        $raw->location  = 'Club Court';

        $home = $this->createStub( Team::class );
        $home->method( 'get_name' )->willReturn( 'Cup Home' );
        $away = $this->createStub( Team::class );
        $away->method( 'get_name' )->willReturn( 'Cup Away' );

        $details = new Fixture_Details_DTO(
            $raw,
            $fixture_league,
            $this->createStub( Event::class ),
            $competition,
            new Team_Details_DTO( $home ),
            new Team_Details_DTO( $away ),
        );

        $detail_service = $this->createMock( Fixture_Detail_Service::class );
        $detail_service->expects( $this->once() )
            ->method( 'get_fixture_with_details' )
            ->with( $raw )
            ->willReturn( $details );
        $this->container->set( 'fixture_detail_service', fn() => $detail_service );

        $league = new class( array( $raw ) ) {
            public int $id = 4;
            public object $championship;
            public array $queries = array();

            public function __construct( private array $final_matches ) {
                $this->championship = new class() {
                    public function get_finals(): array {
                        return array(
                            array( 'key' => 'semi', 'name' => 'Semi Final', 'num_matches' => 2 ),
                            array( 'key' => 'final', 'name' => 'Final', 'num_matches' => 1 ),
                        );
                    }
                };
            }

            public function get_matches( array $args ): array {
                $this->queries[] = $args;
                return 'final' === $args['final'] ? $this->final_matches : array();
            }
        };

        $finals = $this->admin_league->present_league_finals( $league, '2026' );

        $this->assertCount( 2, $league->queries );
        $this->assertSame( 'final', $league->queries[0]['final'], 'rounds are queried in reversed (earliest-last) order' );
        $this->assertSame( '2026', $league->queries[0]['season'] );
        $this->assertCount( 1, $finals, 'rounds without fixtures are omitted' );
        $this->assertSame( 'final', $finals[0]->key );
        $this->assertSame( 1, $finals[0]->num_matches );
        $this->assertCount( 1, $finals[0]->fixtures );

        $fixture = $finals[0]->fixtures[0];
        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $fixture );
        $this->assertSame( '9 May 2026', $fixture->formatted_date );
        $this->assertSame( 'Club Court', $fixture->formatted_location );
        $this->assertSame( 'Cup Home - Cup Away', $fixture->teams_label );
        $this->assertSame(
            'admin.php?page=racketmanager-cups&view=match&competition_id=10&league=4&edit=77&season=2026&final=final',
            $fixture->match_link
        );
    }

    public function test_present_league_finals_returns_empty_list_without_championship(): void {
        $league = new \stdClass();

        $this->assertSame( array(), $this->admin_league->present_league_finals( $league, '2026' ) );
    }
}
