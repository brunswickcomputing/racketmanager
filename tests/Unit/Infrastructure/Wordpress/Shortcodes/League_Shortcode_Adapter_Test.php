<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_League;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class League_Shortcode_Adapter_Test extends TestCase {
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

    private function create_adapter( array $overrides = [] ): League_Shortcode_Adapter {
        return new League_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->createStub( RacketManager::class ),
            $overrides['get_league_standings_handler'] ?? $this->createStub( Get_League_Standings_Handler::class ),
            $overrides['league_standings_presenter'] ?? $this->createStub( League_Standings_Presenter::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['championship_handler'] ?? $this->createStub( Get_League_Championship_Handler::class ),
            $overrides['archive_handler'] ?? $this->createStub( Get_League_Archive_Handler::class ),
            $overrides['crosstable_handler'] ?? $this->createStub( Get_League_Crosstable_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_League_Matches_Handler::class ),
            $overrides['teams_handler'] ?? $this->createStub( Get_League_Teams_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_League_Players_Handler::class ),
            $overrides['season_dropdown_handler'] ?? $this->createStub( Get_League_Season_Dropdown_Handler::class ),
            $overrides['match_dropdown_handler'] ?? $this->createStub( Get_League_Match_Dropdown_Handler::class ),
            $overrides['last_5_handler'] ?? $this->createStub( Get_League_Last_5_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    private function create_standings_team_row( array $overrides = [] ): object {
        $defaults = [
            'rank'           => 1,
            'status'         => '',
            'title'          => 'Leigh LTC 1',
            'done_matches'   => 10,
            'won_matches'    => 8,
            'draw_matches'   => 1,
            'lost_matches'   => 1,
            'diff'           => 14,
            'points_plus'    => 25,
            'points_minus'   => 5,
            'table_id'       => 101,
            'points_2_plus'  => 0,
            'points_2_minus' => 0,
            'add_points'     => 0,
        ];

        return (object) array_merge( $defaults, $overrides );
    }

    private function create_standings_league(
        string $title = 'Premier Division',
        array $team_overrides = [],
        array $standings_cols = [ 'rank' => 1, 'status' => 1, 'pld' => 1, 'won' => 1, 'pts' => 1 ]
    ): League {
        $team       = $this->create_standings_team_row( $team_overrides );
        $league_obj = $this->createStub( League::class );
        $league_obj->id        = 1;
        $league_obj->title     = $title;
        $league_obj->sport     = 'tennis';
        $league_obj->standings = $standings_cols;
        $league_obj->teams     = [ $team ];
        $league_obj->method( 'get_league_teams' )->willReturn( [ $team ] );

        return $league_obj;
    }

    private function create_standings_adapter( ?League $league_obj = null, ?League_Standings_Presenter $presenter = null ): League_Shortcode_Adapter {
        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $handler   = new Get_League_Standings_Handler( $league_service );
        $renderer  = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter = $presenter ?? new League_Standings_Presenter( $renderer );

        return $this->create_adapter( [
            'get_league_standings_handler' => $handler,
            'league_standings_presenter'   => $presenter,
        ] );
    }

    public function test_show_standings_renders_standings_via_handler_and_presenter(): void {
        $league_obj = $this->create_standings_league( 'Premier Division', [ 'title' => 'Leigh LTC 1' ] );
        $renderer   = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter  = new League_Standings_Presenter( $renderer, new Club_Presenter(), new Player_Presenter() );
        $adapter    = $this->create_standings_adapter( $league_obj, $presenter );

        $output = $adapter->show_standings( [ 'league_id' => 1 ] );
        $this->assertStringContainsString( 'Premier Division', $output );
        $this->assertStringContainsString( 'Leigh LTC 1', $output );
        $this->assertStringContainsString( 'Pld', $output );
        $this->assertStringContainsString( 'Pts', $output );
    }

    public function test_show_standings_handles_default_attributes_without_season_or_group(): void {
        $league_obj = $this->create_standings_league(
            'Division 1',
            [
                'title'        => 'Westcliff 1',
                'done_matches' => 4,
                'won_matches'  => 3,
                'draw_matches' => 0,
                'lost_matches' => 1,
                'diff'         => 6,
                'points_plus'  => 12,
                'points_minus' => 4,
                'table_id'     => 102,
            ],
            [ 'pld' => 1, 'pts' => 1 ]
        );

        $adapter = $this->create_standings_adapter( $league_obj );

        global $league;
        $league = $league_obj;

        // Calling with empty array (no season, no group)
        $output = $adapter->show_standings( [] );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Westcliff 1', $output );
    }

    public function test_show_standings_handles_missing_league(): void {
        $adapter = $this->create_standings_adapter( null );

        $output = $adapter->show_standings( [ 'league_id' => 99999 ] );
        $this->assertSame( '', $output );
    }

    public function test_handler_forwarding_methods_render_view_models(): void {
        $view_presenter = $this->createMock( Shortcode_View_Presenter::class );
        $view_presenter->expects( self::exactly( 9 ) )->method( 'render' )->willReturnCallback(
            fn( Shortcode_View_Model $vm ) => '<div class="' . $vm->template . '"></div>'
        );

        $championship_handler    = $this->createStub( Get_League_Championship_Handler::class );
        $archive_handler         = $this->createStub( Get_League_Archive_Handler::class );
        $crosstable_handler      = $this->createStub( Get_League_Crosstable_Handler::class );
        $matches_handler         = $this->createStub( Get_League_Matches_Handler::class );
        $teams_handler           = $this->createStub( Get_League_Teams_Handler::class );
        $players_handler         = $this->createStub( Get_League_Players_Handler::class );
        $season_dropdown_handler = $this->createStub( Get_League_Season_Dropdown_Handler::class );
        $match_dropdown_handler  = $this->createStub( Get_League_Match_Dropdown_Handler::class );
        $last_5_handler          = $this->createStub( Get_League_Last_5_Handler::class );

        $championship_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'championship' ) );
        $archive_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'archive' ) );
        $crosstable_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'crosstable' ) );
        $matches_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'matches' ) );
        $teams_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'teams-list' ) );
        $players_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'players' ) );
        $season_dropdown_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'season-dropdown' ) );
        $match_dropdown_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'match-dropdown' ) );
        $last_5_handler->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'last-5' ) );

        $adapter = $this->create_adapter( [
            'view_presenter'          => $view_presenter,
            'championship_handler'    => $championship_handler,
            'archive_handler'         => $archive_handler,
            'crosstable_handler'      => $crosstable_handler,
            'matches_handler'         => $matches_handler,
            'teams_handler'           => $teams_handler,
            'players_handler'         => $players_handler,
            'season_dropdown_handler' => $season_dropdown_handler,
            'match_dropdown_handler'  => $match_dropdown_handler,
            'last_5_handler'          => $last_5_handler,
        ] );

        $this->assertSame( '<div class="championship"></div>', $adapter->show_championship( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="archive"></div>', $adapter->show_archive( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="crosstable"></div>', $adapter->show_crosstable( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="matches"></div>', $adapter->show_matches( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="teams-list"></div>', $adapter->show_teams( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="players"></div>', $adapter->show_league_players( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="season-dropdown"></div>', $adapter->show_season_dropdown( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="match-dropdown"></div>', $adapter->show_match_dropdown( [ 'league_id' => 1 ] ) );
        $this->assertSame( '<div class="last-5"></div>', $adapter->show_last_5( [ 'league_id' => 1 ] ) );
    }
}
