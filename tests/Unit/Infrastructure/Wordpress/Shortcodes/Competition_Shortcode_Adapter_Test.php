<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Dropdown_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Query;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_Competition;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;

class Competition_Shortcode_Adapter_Test extends TestCase {
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        global $wp;
        $wp = (object) [ 'query_vars' => [] ];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function competition_shortcode_presenter(): Competition_Shortcode_Presenter {
        return new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    public function test_show_competition_renders_competition_via_handler_and_presenter(): void {
        $competition = Competition_Test_Factory::create(
            seasons: Competition_Test_Factory::season_list( '2026', [ 'venue' => 12, 'orderofplay' => [] ] )
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $venue_club   = Club_Test_Factory::create( id: 12, name: 'Venue Club', shortcode: 'VC' );
        $club_service->method( 'get_club' )->willReturn( $venue_club );

        $security       = $this->createStub( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class );
        $settings       = new \Racketmanager\Services\Settings_Service( [] );
        $player_service = $this->createStub( Player_Service::class );
        $handler        = new Get_Competition_Handler( $comp_service, $club_service, $player_service, $security, $settings );
        $renderer       = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter      = new Competition_Shortcode_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            get_competition_handler: $handler,
            competition_shortcode_presenter: $presenter
        );

        $output = $adapter->show_competition( [ 'competition' => '5' ] );
        $this->assertStringContainsString( 'Summer League', $output );
    }

    public function test_show_competition_handles_missing_venue_club_gracefully(): void {
        $competition = Competition_Test_Factory::create(
            seasons: Competition_Test_Factory::season_list( '2026', [ 'venue' => 999, 'orderofplay' => [] ] )
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $security       = $this->createStub( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class );
        $settings       = new \Racketmanager\Services\Settings_Service( [] );
        $player_service = $this->createStub( Player_Service::class );
        $handler        = new Get_Competition_Handler( $comp_service, $club_service, $player_service, $security, $settings );
        $renderer       = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter      = new Competition_Shortcode_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            get_competition_handler: $handler,
            competition_shortcode_presenter: $presenter
        );

        $output = $adapter->show_competition( [ 'competition' => '5' ] );
        $this->assertStringContainsString( 'Summer League', $output );
    }

    public function test_show_competition_returns_danger_alert_when_not_found(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $club_service   = $this->createStub( Club_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $security       = $this->createStub( \Racketmanager\Infrastructure\Security\Security_Service_Interface::class );
        $settings       = new \Racketmanager\Services\Settings_Service( [] );
        $handler        = new Get_Competition_Handler( $comp_service, $club_service, $player_service, $security, $settings );
        $renderer       = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter      = new Competition_Shortcode_Presenter( $renderer );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            get_competition_handler: $handler,
            competition_shortcode_presenter: $presenter
        );

        $output = $adapter->show_competition( [ 'competition' => '99999' ] );
        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'Competition not found', $output );
    }

    public function test_show_competitions_renders_competition_list(): void {
        $competition = Competition_Test_Factory::create( id: 1, name: 'Division 1' );
        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_criteria' )->willReturn( [ $competition ] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $player_service = $this->createStub( Player_Service::class );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            competitions_handler: new Get_Competitions_Handler( $comp_service, $tournament_service, $player_service, $this->createStub( Player_Query_Service::class ), $this->competition_shortcode_presenter() ),
            view_presenter: new Shortcode_View_Presenter( $template_renderer )
        );

        $output = $adapter->show_competitions( [ 'type' => 'league' ] );
        $this->assertStringContainsString( 'Division 1', $output );
    }

    public function test_show_competition_overview_renders_overview(): void {
        $competition = Competition_Test_Factory::create( id: 2, name: 'Premier League', seasons: Competition_Test_Factory::season_list() );

        $dto = new Competition_Overview_DTO( (object) [
            'id'          => 2,
            'name'        => 'Premier League',
            'settings'    => '{}',
            'num_events'  => 1,
            'num_teams'   => 6,
            'num_clubs'   => 4,
            'num_players' => 24,
        ] );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_competition_overview' )->willReturn( $dto );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $overview_handler  = new Get_Competition_Overview_Handler( $comp_service, $this->competition_shortcode_presenter() );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            overview_handler: $overview_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_overview( [ 'id' => '2', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Overview', $output );
    }

    public function test_show_competition_events_renders_events(): void {
        $competition = Competition_Test_Factory::create( id: 3, name: 'Cup Event', type: Competition_Type::CUP, seasons: Competition_Test_Factory::season_list() );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_events_with_details_for_competition' )->willReturn( [] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $events_handler    = new Get_Competition_Events_Handler( $comp_service );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            events_handler: $events_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_events( [ 'id' => '3', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Events', $output );
    }

    public function test_show_competition_teams_renders_teams(): void {
        $competition = Competition_Test_Factory::create( id: 4, name: 'Team League', seasons: Competition_Test_Factory::season_list() );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_teams_for_competition' )->willReturn( [] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $teams_handler     = new Get_Competition_Teams_Handler( $comp_service );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            teams_handler: $teams_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_teams( [ 'id' => '4', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Teams', $output );
    }

    public function test_show_competition_clubs_renders_clubs(): void {
        $competition = Competition_Test_Factory::create( name: 'Club League', seasons: Competition_Test_Factory::season_list() );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_club_details_for_competition' )->willReturn( [] );

        $club_service      = $this->createStub( Club_Service::class );
        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $clubs_handler     = new Get_Competition_Clubs_Handler( $comp_service, $club_service, $this->createStub( Player_Stats_Service::class ), new Tournament_Presenter() );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            template_renderer: $template_renderer,
            clubs_handler: $clubs_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_clubs( [ 'id' => '5', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Clubs', $output );
    }

    public function test_show_competition_clubs_single_club_detail(): void {
        $competition = Competition_Test_Factory::create( name: 'Club League', seasons: Competition_Test_Factory::season_list() );

        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $relation_loader    = new Fixture_Relation_Loader( $this->createStub( League_Repository_Interface::class ) );
        $GLOBALS['racketmanager'] = $this->create_app_with_services( [
            'fixture_repository'      => $fixture_repository,
            'fixture_relation_loader' => $relation_loader,
        ] );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $club         = Club_Test_Factory::create( id: 10, name: 'Westcliff Club', shortcode: 'WST' );
        $club_service->method( 'get_club' )->willReturn( $club );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $clubs_handler     = new Get_Competition_Clubs_Handler( $comp_service, $club_service, $this->createStub( Player_Stats_Service::class ), new Tournament_Presenter() );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            club_service: $club_service,
            template_renderer: $template_renderer,
            clubs_handler: $clubs_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_clubs( [ 'id' => '5', 'clubs' => 'WST', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Results', $output );
        $this->assertStringContainsString( 'Teams', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
    }

    public function test_competition_clubs_handler_decorates_nonempty_players(): void {
        $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
        $competition->current_season = [ 'name' => '2026' ];
        $competition->method( 'get_id' )->willReturn( 5 );
        $competition->method( 'get_teams' )->willReturn( [] );
        $competition->method( 'get_matches' )->willReturn( [] );

        [ $low, $high, $stats ] = $this->build_stat_players( $competition, '2026', 'competition' );
        $competition->method( 'get_players' )->willReturn( [ $low, $high ] );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $club_service = $this->createStub( Club_Service::class );
        $club         = Club_Test_Factory::create( id: 10, name: 'Westcliff Club', shortcode: 'WST' );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );

        $handler = new Get_Competition_Clubs_Handler( $comp_service, $club_service, $stats, new Tournament_Presenter() );
        $vm      = $handler->handle( new \Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Query( competition_id: 5, club_identifier: 'WST', season: 2026 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assert_decorated_and_sorted( $low, $high, $vm->vars['competition_club']->players );
    }


    /**
     * Build two players (low wins first) and a real stats service over a mocked query service.
     *
     * @return array{0: Player, 1: Player, 2: Player_Stats_Service, 3: \PHPUnit\Framework\MockObject\MockObject}
     */
    private function build_stat_players( object $grouping, string $season, string $source ): array {
        $low             = new Player();
        $low->id         = 31;
        $low->statistics = [ 's' => [ 'played' => [ 'winner' => 1, 'loser' => 2 ] ] ];
        $high             = new Player();
        $high->id         = 32;
        $high->statistics = [ 's' => [ 'played' => [ 'winner' => 4, 'loser' => 0 ] ] ];

        $query_service = $this->createMock( Player_Query_Service::class );
        $query_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches' )
            ->with( $this->isInstanceOf( Player::class ), $grouping, $season, $source )
            ->willReturn( [] );

        return [ $low, $high, new Player_Stats_Service( $query_service ), $query_service ];
    }

    private function assert_decorated_and_sorted( Player $low, Player $high, array $players ): void {
        $this->assertSame( [ $high, $low ], $players );
        $this->assertSame( 4, $high->matches_won );
        $this->assertSame( 4, $high->played );
        $this->assertSame( 1, $low->matches_won );
        $this->assertSame( 3, $low->played );
        $this->assertArrayHasKey( 'total', $high->stats );
        $this->assertSame( [], $high->matches );
    }

    public function test_show_competition_players_renders_players(): void {
        $competition = Competition_Test_Factory::create( id: 6, name: 'Player League', seasons: Competition_Test_Factory::season_list() );

        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $player_service->method( 'get_players_for_competition' )->willReturn( [] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $players_handler   = new Get_Competition_Players_Handler(
            $comp_service,
            $player_service,
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class )
        );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            player_service: $player_service,
            template_renderer: $template_renderer,
            players_handler: $players_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_players( [ 'id' => '6', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Players', $output );
    }

    public function test_show_competition_winners_renders_winners(): void {
        $competition = Competition_Test_Factory::create( id: 7, name: 'Championship', seasons: Competition_Test_Factory::season_list() );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_winners_for_competition' )->willReturn( [] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $winners_handler   = new Get_Competition_Winners_Handler( $comp_service );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            winners_handler: $winners_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_winners( [ 'id' => '7', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Winners', $output );
    }

    public function test_winners_and_overview_without_a_current_season_show_alerts(): void {
        $competition = Competition_Test_Factory::create( id: 7, name: 'No Season League' );
        $service     = $this->createMock( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->expects( $this->never() )->method( 'get_winners_for_competition' );
        $service->expects( $this->never() )->method( 'get_competition_overview' );
        $renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $adapter  = new Competition_Shortcode_Adapter(
            template_renderer: $renderer,
            winners_handler: new Get_Competition_Winners_Handler( $service ),
            overview_handler: new Get_Competition_Overview_Handler( $service, $this->competition_shortcode_presenter() ),
            view_presenter: new Shortcode_View_Presenter( $renderer )
        );

        $this->assertStringContainsString( 'Season not found', $adapter->show_competition_winners( [ 'id' => '7' ] ) );
        $this->assertStringContainsString( 'Season not found', $adapter->show_competition_overview( [ 'id' => '7' ] ) );
    }

    public function test_non_admin_club_selection_passes_null_for_unspecified_club(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $competition = Competition_Test_Factory::create( id: 7 );
        $plugin      = $this->createStub( RacketManager::class );
        $plugin->method( 'get_options' )->willReturn( [ 'entry_level' => 'captain' ] );
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'find_all_associated_clubs' )
            ->with( 1, 'captain', null )->willReturn( [ (object) [ 'shortcode' => 'WST' ] ] );
        $adapter = new Competition_Shortcode_Adapter(
            player_service: $player_service,
            racketmanager: $plugin,
            club_entry_selection: new Club_Entry_Selection_Service( $this->createStub( Club_Service::class ), $player_service )
        );

        $this->assertCount( 1, $adapter->club_selection_available( $competition ) );
    }

    public function test_show_dropdown_renders_dropdown(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [ (object) [ 'id' => 11, 'name' => 'Singles' ] ] );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            dropdown_handler: new Get_Competition_Dropdown_Handler( $comp_service ),
            view_presenter: new Shortcode_View_Presenter( $template_renderer )
        );

        $output = $adapter->show_dropdown( [ 'id' => '8' ] );
        $this->assertStringContainsString( '<select', $output );
        $this->assertStringContainsString( 'Singles', $output );
        $this->assertSame( 8, Get_Competition_Dropdown_Query::from_atts( [ 'id' => '8' ] )->competition_id );
    }

    public function test_show_competition_entry_requires_login(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );

        $adapter = new Competition_Shortcode_Adapter(
            template_renderer: $template_renderer
        );

        $output = $adapter->show_competition_entry( [] );
        $this->assertStringContainsString( 'alert--danger', $output );
        $this->assertStringContainsString( 'You need to be logged in to enter', $output );
        unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
    }

    public function test_show_competition_players_single_player_detail(): void {
        $competition = Competition_Test_Factory::create( id: 6, name: 'Player League', seasons: Competition_Test_Factory::season_list() );

        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $player         = Player_Test_Factory::create_sparse( [
            'id'           => 42,
            'display_name' => 'Jane Doe',
            'firstname'    => 'Jane',
            'surname'      => 'Doe',
            'user_login'   => 'jdoe',
        ] );
        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_matches' )->willReturn( [] );
        $player_query_service->method( 'get_competitions' )->willReturn( [] );

        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_stats' )->willReturn( [] );
        $player_stats_service->method( 'get_career_stats' )->willReturn( [] );

        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $player_service->method( 'get_player' )->willReturn( $player );
        $player_service->method( 'get_player_by_name' )->willReturn( $player );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );
        $view_presenter    = new Shortcode_View_Presenter( $template_renderer );
        $players_handler   = new Get_Competition_Players_Handler( $comp_service, $player_service, $player_query_service, $player_stats_service );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            player_service: $player_service,
            template_renderer: $template_renderer,
            players_handler: $players_handler,
            view_presenter: $view_presenter
        );

        $output = $adapter->show_competition_players( [ 'id' => '6', 'players' => '42', 'season' => '2026' ] );
        $this->assertStringContainsString( 'Jane Doe', $output );
        $this->assertStringNotContainsString( 'alert--danger', $output );
        $this->assertSame( [], $competition->player->matches );
        $this->assertSame( [], $competition->player->stats );
    }

    public function test_get_competitions_handler_reads_user_competitions_from_query_service(): void {
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_by_criteria' )->willReturn( [] );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $player = new Player();
        $player->id = 42;
        $player_service->method( 'get_player' )->willReturn( $player );
        $query_service = $this->createMock( Player_Query_Service::class );
        $player_competition = (object) [ 'name' => 'Player League', 'season' => '2026', 'current_season' => [ 'name' => '2026' ] ];
        $query_service->expects( $this->once() )
            ->method( 'get_competitions' )
            ->with( $player, [ 'type' => 'league' ] )
            ->willReturn( [ $player_competition ] );

        $handler = new Get_Competitions_Handler( $competition_service, $tournament_service, $player_service, $query_service, $this->competition_shortcode_presenter() );
        $view_model = $handler->handle( new Get_Competitions_Query( type: 'league', user_id: 42 ) );

        $this->assertSame( [ $player_competition ], $view_model->vars['user_competitions'] );
    }

    public function test_show_competition_entry_missing_competition_returns_alert(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        global $wp;
        $wp->query_vars['competition_name'] = 'Unknown League';
        $wp->query_vars['competition_type'] = 'league';

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer
        );

        $output = $adapter->show_competition_entry( [] );
        $this->assertStringContainsString( 'Competition not found', $output );
        unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
    }

    public function test_shortcodes_competition_facade_delegates_to_container_adapter(): void {
        $renderer          = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $renderer );

        $comp_service = $this->createStub( Competition_Service::class );
        $competition  = Competition_Test_Factory::create( id: 9, name: 'Facade League', seasons: Competition_Test_Factory::season_list() );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_by_criteria' )->willReturn( [ $competition ] );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [] );
        $comp_service->method( 'get_winners_for_competition' )->willReturn( [] );

        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $comp_service,
            template_renderer: $template_renderer,
            competitions_handler: new Get_Competitions_Handler(
                $comp_service,
                $this->createStub( Tournament_Service::class ),
                $this->createStub( Player_Service::class ),
                $this->createStub( Player_Query_Service::class ),
                $this->competition_shortcode_presenter()
            ),
            dropdown_handler: new Get_Competition_Dropdown_Handler( $comp_service ),
            view_presenter: new Shortcode_View_Presenter( $template_renderer )
        );

        $racketmanager = $this->create_app_with_services( [
            'competition_shortcode_adapter' => $adapter,
        ] );

        $facade = new Shortcodes_Competition( $racketmanager );

        $this->assertStringContainsString( 'Facade League', $facade->show_competitions( [ 'type' => 'league' ] ) );
        $this->assertStringContainsString( '<select', $facade->show_dropdown( [ 'id' => '9' ] ) );
    }

    public function test_competitions_query_preserves_attributes_and_legacy_fallbacks(): void {
        $from_atts = Get_Competitions_Query::from_atts( [ 'type' => 'cup', 'age_group' => 'junior' ], [ 'type' => 'league' ] );
        $this->assertSame( 'cup', $from_atts->type );
        $this->assertSame( 'junior', $from_atts->age_group );
        $from_url = Get_Competitions_Query::from_atts( [], [ 'type' => 'league', 'age_group' => 'senior' ], [ 'competition_type' => 'cup', 'type' => '<b>cup</b>' ] );
        $this->assertSame( 'cup', $from_url->type );
        $this->assertSame( 'senior', $from_url->age_group );
    }

    public function test_competition_shortcode_presenter_header_date_boundaries(): void {
        $renderer  = $this->createStub( \Racketmanager\Services\View\View_Renderer_Interface::class );
        $presenter = new Competition_Shortcode_Presenter( $renderer );

        $competition = (object) [
            'id'             => 10,
            'name'           => 'Summer League 2026',
            'type'           => 'league',
            'current_season' => [ 'name' => '2026' ],
        ];

        // 1. Valid range
        $season_valid = [
            'name'       => '2026',
            'date_start' => '2026-05-01',
            'date_end'   => '2026-08-31',
        ];
        $vm_valid = $presenter->present_header( $competition, $season_valid, '2026', [], '', '', 'd/m/Y' );
        $this->assertTrue( $vm_valid->has_date_range );
        $this->assertSame( '01/05/2026', $vm_valid->date_start_display );
        $this->assertSame( '31/08/2026', $vm_valid->date_end_display );

        // 2. Partial dates: missing end date
        $season_partial = [
            'name'       => '2026',
            'date_start' => '2026-05-01',
            'date_end'   => null,
        ];
        $vm_partial = $presenter->present_header( $competition, $season_partial, '2026', [], '', '', 'd/m/Y' );
        $this->assertFalse( $vm_partial->has_date_range );
        $this->assertNull( $vm_partial->date_start_display );
        $this->assertNull( $vm_partial->date_end_display );

        // 3. Reversed dates
        $season_reversed = [
            'name'       => '2026',
            'date_start' => '2026-09-01',
            'date_end'   => '2026-05-01',
        ];
        $vm_reversed = $presenter->present_header( $competition, $season_reversed, '2026', [], '', '', 'd/m/Y' );
        $this->assertTrue( $vm_reversed->has_date_range );
        $this->assertSame( '01/09/2026', $vm_reversed->date_start_display );
        $this->assertSame( '01/05/2026', $vm_reversed->date_end_display );

        // 4. Empty / zero dates
        $season_empty = [
            'name'       => '2026',
            'date_start' => '0000-00-00',
            'date_end'   => '',
        ];
        $vm_empty = $presenter->present_header( $competition, $season_empty, '2026', [], '', '', 'd/m/Y' );
        $this->assertFalse( $vm_empty->has_date_range );
        $this->assertNull( $vm_empty->date_start_display );
        $this->assertNull( $vm_empty->date_end_display );
    }
}
