<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Public;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Handler;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

#[AllowMockObjectsWithoutExpectations]
class Shortcodes_Competition_Integration_Test extends TestCase {
    use Creates_Competition_Season_Service;
    use \Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

    private RacketManager $plugin;
    private Simple_Container $container;
    private Competition_Shortcode_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->plugin            = $this->createMock( RacketManager::class );
        $this->container         = new Simple_Container();
        $this->plugin->container = $this->container;

        $comp_service   = $this->createStub( Competition_Service::class );
        $club_service   = $this->createStub( Club_Service::class );
        $team_service   = $this->createStub( Team_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $tournament_svc = $this->createStub( Tournament_Service::class );
        $finance_svc    = $this->createStub( Finance_Service::class );
        $reg_service    = $this->createStub( Registration_Service::class );
        $entry_service  = $this->createStub( Competition_Entry_Service::class );

        $season      = [ 'name' => '2026', 'venue' => null, 'orderofplay' => [] ];
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Integration Premier League',
            seasons: [ $season ],
            current_season: $season
        );

        $overview_dto = new Competition_Overview_DTO( (object) [
            'id'          => 10,
            'name'        => 'Integration Premier League',
            'settings'    => '{}',
            'num_events'  => 2,
            'num_teams'   => 8,
            'num_clubs'   => 4,
            'num_players' => 32,
        ] );

        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_by_criteria' )->willReturn( [ $competition ] );
        $comp_service->method( 'get_competition_overview' )->willReturn( $overview_dto );
        $comp_service->method( 'get_events_with_details_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_teams_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_club_details_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_winners_for_competition' )->willReturn( [] );
        $player_service->method( 'get_players_for_competition' )->willReturn( [] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer, new Club_Presenter(), new Player_Presenter() );
        $security          = $this->createStub( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class );
        $settings          = new \Racketmanager\Services\Settings_Service( [] );
        $handler           = new Get_Competition_Handler( $comp_service, $club_service, $player_service, $security, $settings, $this->competition_season_service() );
        $presenter         = new Competition_Shortcode_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );

        $overview_handler = new Get_Competition_Overview_Handler( $comp_service, $this->competition_season_service() );
        $events_handler   = new Get_Competition_Events_Handler( $comp_service, $this->competition_season_service() );
        $teams_handler    = new Get_Competition_Teams_Handler( $comp_service, $this->competition_season_service() );
        $clubs_handler    = new Get_Competition_Clubs_Handler( $comp_service, $club_service, new \Racketmanager\Services\Player_Stats_Service(), $this->competition_season_service() );
        $players_handler  = new Get_Competition_Players_Handler(
            $comp_service,
            $player_service,
            new \Racketmanager\Services\Player_Query_Service( new \Racketmanager\Repositories\Player_Statistics_Repository() ),
            new \Racketmanager\Services\Player_Stats_Service(),
            $this->competition_season_service() );
        $winners_handler  = new Get_Competition_Winners_Handler( $comp_service, $this->competition_season_service() );

        $this->adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            team_service: $team_service,
            player_service: $player_service,
            tournament_service: $tournament_svc,
            finance_service: $finance_svc,
            registration_service: $reg_service,
            competition_entry_service: $entry_service,
            template_renderer: $template_renderer,
            get_competition_handler: $handler,
            competition_shortcode_presenter: $presenter,
            overview_handler: $overview_handler,
            events_handler: $events_handler,
            teams_handler: $teams_handler,
            clubs_handler: $clubs_handler,
            players_handler: $players_handler,
            winners_handler: $winners_handler,
            dropdown_handler: new Get_Competition_Dropdown_Handler( $comp_service ),
            competitions_handler: new Get_Competitions_Handler(
                $comp_service,
                $tournament_svc,
                $player_service,
                new \Racketmanager\Services\Player_Query_Service( new \Racketmanager\Repositories\Player_Statistics_Repository() )
            ),
            view_presenter: $view_presenter,
            racketmanager: $this->plugin
        );

        $this->container->set( 'competition_shortcode_adapter', fn() => $this->adapter );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_real_template_rendering_for_competitions(): void {
        $output = $this->adapter->show_competitions( [ 'type' => 'league' ] );
        $this->assertStringContainsString( 'Integration Premier League', $output );
    }

    public function test_real_template_rendering_for_competition_detail(): void {
        $output = $this->adapter->show_competition( [ 'competition' => '10' ] );
        $this->assertStringContainsString( 'Integration Premier League', $output );
    }

    public function test_real_template_rendering_for_competition_overview(): void {
        $output = $this->adapter->show_competition_overview( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Overview', $output );
        $this->assertStringContainsString( 'Events', $output );
    }

    public function test_real_template_rendering_for_competition_events(): void {
        $output = $this->adapter->show_competition_events( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Events', $output );
    }

    public function test_real_template_rendering_for_competition_teams(): void {
        $output = $this->adapter->show_competition_teams( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Teams', $output );
    }

    public function test_real_template_rendering_for_competition_clubs(): void {
        $output = $this->adapter->show_competition_clubs( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Clubs', $output );
    }

    public function test_real_template_rendering_for_competition_players(): void {
        $output = $this->adapter->show_competition_players( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Players', $output );
    }

    public function test_real_template_rendering_for_competition_winners(): void {
        $output = $this->adapter->show_competition_winners( [ 'id' => '10', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Winners', $output );
    }

    public function test_real_template_rendering_for_dropdown(): void {
        $output = $this->adapter->show_dropdown( [ 'id' => '10' ] );
        $this->assertStringContainsString( 'select', $output );
    }
}
