<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Query;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Query;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Query;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Query;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Team;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presenters\Club_Presenter;
use Racketmanager\Presenters\League_Standings_Presenter;
use Racketmanager\Presenters\Player_Presenter;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_League;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

class League_Shortcode_Handlers_Test extends TestCase {
    private mixed $original_wp;
    private mixed $original_league;

    protected function setUp(): void {
        parent::setUp();
        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 5 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }
        global $wp, $league;
        $this->original_wp     = $wp ?? null;
        $this->original_league = $league ?? null;
        $wp                    = (object) [ 'query_vars' => [] ];
    }

    protected function tearDown(): void {
        global $wp, $league;
        $wp     = $this->original_wp;
        $league = $this->original_league;
        parent::tearDown();
    }

    private function create_renderer(): Shortcode_Template_Renderer {
        $php_renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        return new Shortcode_Template_Renderer( $php_renderer );
    }

    public function test_championship_handler_success_and_error(): void {
        $league_service = $this->createStub( League_Service::class );
        $fixture_repo   = $this->createStub( Fixture_Repository_Interface::class );
        $comp_service   = $this->createStub( Competition_Service::class );
        $team_service   = $this->createStub( Team_Service::class );
        $link_service   = $this->createStub( Fixture_Link_Service::class );
        $perm_service   = $this->createStub( Fixture_Permission_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Championship_Handler(
            $league_service,
            $fixture_repo,
            $comp_service,
            $team_service,
            $link_service,
            $perm_service,
            $renderer
        );

        // Error path: league not found
        $query_error = new Get_League_Championship_Query( league_id: 999 );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        // Success path
        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test Championship League';
                $this->sport = 'tennis';
            }
            public function get_season(): string { return '2026'; }
            public function set_template( string $type, string $template ): void {}
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $query = new Get_League_Championship_Query( league_id: 1, template: 'custom' );
        $vm    = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'championship-custom', $vm->template );
        $this->assertFalse( $vm->template_type );
    }

    public function test_archive_handler_success_and_by_name(): void {
        $league_service = $this->createStub( League_Service::class );
        $comp_service   = $this->createStub( Competition_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Archive_Handler( $league_service, $comp_service, $renderer );

        // Error path: league not found
        $query_error = new Get_League_Archive_Query( league_name: 'Nonexistent League' );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        // Success path: league by name
        $league_obj = new class extends League {
            public function __construct() {
                $this->id       = 10;
                $this->title    = 'Premier Division';
                $this->sport    = 'tennis';
                $this->event_id = 5;
            }
            public function set_tab( bool $is_archive = false ): void {}
            public function set_templates( array $templates ): void {}
            public function set_template( string $type, string $template ): void {}
        };
        $event_obj = new class extends Event {
            public string $title = 'Main Event';
            public function __construct() {
                $this->id   = 5;
                $this->name = 'Main Event';
            }
            public function get_seasons(): array { return [ '2025', '2026' ]; }
            public function get_leagues( array $args = [] ): array|int { return []; }
        };

        $league_service->method( 'get_league' )->willReturn( $league_obj );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_obj );

        $query = new Get_League_Archive_Query( league_name: 'Premier Division' );
        $vm    = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'archive', $vm->template );
        $this->assertFalse( $vm->template_type );
        $this->assertSame( $league_obj, $vm->vars['league'] );
        $this->assertSame( 'standings', $vm->vars['tab'] );
    }

    public function test_teams_handler_list_and_details_with_rank_guard(): void {
        $league_service = $this->createStub( League_Service::class );
        $team_service   = $this->createStub( Team_Service::class );

        $handler = new Get_League_Teams_Handler( $league_service, $team_service );

        // Error: league not found
        $query_error = new Get_League_Teams_Query( league_id: 999 );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
            }
            public function set_template( string $type, string $template ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
            public function get_team_dtls( int $team_id, ?string $season = null ): object|false { return (object) [ 'name' => 'Team ' . $team_id ]; }
            public function get_league_teams( array $query_args = [] ): array|int { return []; }
            public function get_league_team( int $team_id ): object|false {
                if ( 8 === $team_id ) {
                    return (object) [ 'rank' => '' ];
                }
                return (object) [ 'rank' => 1 ];
            }
            public function get_matches( array $query_args = [] ): array|int|object { return []; }
            public function get_players( array $args = [] ): array { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Teams list
        $query_list = new Get_League_Teams_Query( league_id: 1 );
        $vm_list    = $handler->handle( $query_list );
        $this->assertFalse( $vm_list->is_error() );
        $this->assertSame( 'teams-list', $vm_list->template );
        $this->assertFalse( $vm_list->template_type );

        // Team details success
        $team_obj        = new class extends Team { public function __construct() { $this->id = 7; $this->title = 'Westcliff 1'; } };
        $team_details    = new Team_Details_DTO( team: $team_obj );
        $team_service->method( 'get_team_details' )->willReturnCallback(
            function( int|string|null $team_id ) use ( $team_details ) {
                if ( 8 === (int) $team_id ) {
                    $unranked_team = new class extends Team { public function __construct() { $this->id = 8; $this->title = 'Unranked Team'; } };
                    return new Team_Details_DTO( team: $unranked_team );
                }
                return $team_details;
            }
        );

        $query_detail = new Get_League_Teams_Query( league_id: 1, team_id: '7' );
        $vm_detail    = $handler->handle( $query_detail );
        $this->assertFalse( $vm_detail->is_error() );
        $this->assertSame( 'team-details', $vm_detail->template );
        $this->assertFalse( $vm_detail->template_type );
        $this->assertSame( $team_details, $vm_detail->vars['team_details'] );

        // Team rank guard error (empty rank in league standings)
        $query_unranked = new Get_League_Teams_Query( league_id: 1, team_id: '8' );
        $vm_unranked    = $handler->handle( $query_unranked );
        $this->assertTrue( $vm_unranked->is_error() );
        $this->assertSame( 'Team not found in league', $vm_unranked->error_message );
    }

    public function test_players_handler_numeric_id_and_name(): void {
        $league_service = $this->createStub( League_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Players_Handler( $league_service, $player_service, $renderer );

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
                $this->sport = 'tennis';
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false ): void {}
            public function get_players( array $args = [] ): array { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $player_obj = new class extends Player {
            public function get_matches( ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array { return []; }
            public function get_stats( false|array $stats = false ): array { return []; }
        };

        $player_service->method( 'get_player' )->willReturnCallback(
            function( int $id ) use ( $player_obj ) {
                if ( 999 === $id ) {
                    throw new \Exception( 'Player not found' );
                }
                return $player_obj;
            }
        );
        $player_service->method( 'get_player_by_name' )->willReturn( $player_obj );

        // Player not found error
        $query_error = new Get_League_Players_Query( league_id: 1, player_id: '999' );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'Player not found', $vm_error->error_message );

        // Player by numeric id
        $query_num = new Get_League_Players_Query( league_id: 1, player_id: '12' );
        $vm_num    = $handler->handle( $query_num );
        $this->assertFalse( $vm_num->is_error() );
        $this->assertSame( 'players-tennis', $vm_num->template );
        $this->assertSame( 'league', $vm_num->template_type );

        // Player by name
        $query_name = new Get_League_Players_Query( league_id: 1, player_id: 'John Doe' );
        $vm_name    = $handler->handle( $query_name );
        $this->assertFalse( $vm_name->is_error() );
        $this->assertSame( 'players-tennis', $vm_name->template );

        // All players list
        $query_all = new Get_League_Players_Query( league_id: 1 );
        $vm_all    = $handler->handle( $query_all );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'players-tennis', $vm_all->template );
    }

    public function test_matches_handler_nonce_and_empty_template(): void {
        $league_service = $this->createStub( League_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Matches_Handler( $league_service, $renderer );

        $league_obj = new class extends League {
            public int $set_match_day_calls = 0;
            public mixed $last_match_day    = null;
            public function __construct() {
                $this->id        = 1;
                $this->title     = 'Test League';
                $this->sport     = 'tennis';
                $this->match_day = -1;
            }
            public function set_template( string $type, string $template ): void {}
            public function set_season( false|string $season = false, bool $force_overwrite = false ): void {}
            public function set_match_day( false|string|int $_match_day = false ): void {
                $this->set_match_day_calls++;
                $this->last_match_day = $_match_day;
            }
            public function set_num_matches( bool $total = false ): void {}
            public function get_matches( array $query_args ): array|int|object { return []; }
            public function get_league_teams( array $query_args = [] ): array|int { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Default template (daily matches)
        $query_default = new Get_League_Matches_Query( league_id: 1, template: 'daily' );
        $vm_default    = $handler->handle( $query_default );
        $this->assertFalse( $vm_default->is_error() );
        $this->assertSame( 'matches-daily', $vm_default->template );
        $this->assertFalse( $vm_default->template_type );
        $this->assertSame( 'current', $league_obj->last_match_day );

        // By matchday template (sets match day to -1)
        $query_by_md = new Get_League_Matches_Query( league_id: 1, template: 'by_matchday' );
        $vm_by_md    = $handler->handle( $query_by_md );
        $this->assertFalse( $vm_by_md->is_error() );
        $this->assertSame( -1, $league_obj->last_match_day );

        // Empty template (preserves matches-sport or matches)
        $query_empty = new Get_League_Matches_Query( league_id: 1, template: '' );
        $vm_empty    = $handler->handle( $query_empty );
        $this->assertFalse( $vm_empty->is_error() );
        $this->assertSame( 'matches', $vm_empty->template );

        // Numeric match day
        $query_md = new Get_League_Matches_Query( league_id: 1, matches: '3', template: 'table' );
        $vm_md    = $handler->handle( $query_md );
        $this->assertFalse( $vm_md->is_error() );
        $this->assertSame( 3, $league_obj->last_match_day );
    }

    public function test_crosstable_dropdowns_and_last5_handlers(): void {
        $league_service = $this->createStub( League_Service::class );
        $renderer       = $this->create_renderer();

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
                $this->sport = 'tennis';
            }
            public function set_template( string $type, string $template ): void {}
            public function set_season( false|string $season = false, bool $force_overwrite = false ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
            public function get_league_teams( array $query_args = [] ): array|int { return []; }
            public function get_matches( array $query_args ): array|int|object { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Crosstable
        $crosstable_handler = new Get_League_Crosstable_Handler( $league_service, $renderer );
        $vm_ct             = $crosstable_handler->handle( new Get_League_Crosstable_Query( league_id: 1 ) );
        $this->assertFalse( $vm_ct->is_error() );
        $this->assertSame( 'crosstable', $vm_ct->template );
        $this->assertSame( 'league', $vm_ct->template_type );

        // Season dropdown
        $season_handler = new Get_League_Season_Dropdown_Handler( $league_service, $renderer );
        $vm_sd         = $season_handler->handle( new Get_League_Season_Dropdown_Query( league_id: 1, season: '2026' ) );
        $this->assertFalse( $vm_sd->is_error() );
        $this->assertSame( 'season-dropdown', $vm_sd->template );
        $this->assertSame( 'league', $vm_sd->template_type );

        // Match dropdown
        $match_handler = new Get_League_Match_Dropdown_Handler( $league_service, $renderer );
        $vm_md        = $match_handler->handle( new Get_League_Match_Dropdown_Query( league_id: 1, season: '2026' ) );
        $this->assertFalse( $vm_md->is_error() );
        $this->assertSame( 'match-dropdown', $vm_md->template );
        $this->assertSame( 'league', $vm_md->template_type );

        // Last 5
        $last5_handler = new Get_League_Last_5_Handler( $league_service, $renderer );
        $vm_l5        = $last5_handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 5 ) );
        $this->assertFalse( $vm_l5->is_error() );
        $this->assertSame( 'last-5', $vm_l5->template );
        $this->assertSame( 'league', $vm_l5->template_type );
    }

    public function test_renderer_sets_and_restores_global_league(): void {
        $inner_renderer = new class implements View_Renderer_Interface {
            public ?int $observed_league_id = null;
            public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {}
            public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
                $this->observed_league_id = isset( $GLOBALS['league'] ) && is_object( $GLOBALS['league'] ) ? ( $GLOBALS['league']->id ?? null ) : null;
                return 'rendered-output';
            }
            public function template_exists( string $template_path ): bool {
                return true;
            }
        };

        $renderer   = new Shortcode_Template_Renderer( $inner_renderer );
        $league_obj = (object) [ 'id' => 42, 'title' => 'Global League Test' ];

        // 1. When $league was previously set to another value
        global $league;
        $league = 'previous_value';

        $output = $renderer->render( 'standings', [ 'league' => $league_obj ], false );
        $this->assertSame( 'rendered-output', $output );
        $this->assertSame( 42, $inner_renderer->observed_league_id );
        $this->assertSame( 'previous_value', $GLOBALS['league'] );

        // 2. When $league was not previously set
        unset( $GLOBALS['league'] );
        $output_unset = $renderer->render( 'standings', [ 'league' => $league_obj ], false );
        $this->assertSame( 'rendered-output', $output_unset );
        $this->assertSame( 42, $inner_renderer->observed_league_id );
        $this->assertFalse( array_key_exists( 'league', $GLOBALS ) );
    }

    public function test_shortcodes_league_facade_delegation(): void {
        $container = new Simple_Container();
        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;

        $adapter = $this->createMock( League_Shortcode_Adapter::class );
        $adapter->expects( self::once() )->method( 'show_championship' )->with( [ 'id' => 1 ] )->willReturn( '<div>Champ</div>' );
        $adapter->expects( self::once() )->method( 'show_archive' )->with( [ 'id' => 1 ] )->willReturn( '<div>Archive</div>' );
        $adapter->expects( self::once() )->method( 'show_standings' )->with( [ 'id' => 1 ], false )->willReturn( '<div>Standings</div>' );
        $adapter->expects( self::once() )->method( 'show_crosstable' )->with( [ 'id' => 1 ] )->willReturn( '<div>Crosstable</div>' );
        $adapter->expects( self::once() )->method( 'show_matches' )->with( [ 'id' => 1 ] )->willReturn( '<div>Matches</div>' );
        $adapter->expects( self::once() )->method( 'show_teams' )->with( [ 'id' => 1 ] )->willReturn( '<div>Teams</div>' );
        $adapter->expects( self::once() )->method( 'show_league_players' )->with( [ 'id' => 1 ] )->willReturn( '<div>Players</div>' );
        $adapter->expects( self::once() )->method( 'show_season_dropdown' )->with( [ 'id' => 1 ] )->willReturn( '<select>Season</select>' );
        $adapter->expects( self::once() )->method( 'show_match_dropdown' )->with( [ 'id' => 1 ] )->willReturn( '<select>Match</select>' );
        $adapter->expects( self::once() )->method( 'show_last_5' )->with( [ 'id' => 1 ] )->willReturn( '<div>Last5</div>' );

        $container->set( 'league_shortcode_adapter', fn() => $adapter );

        $facade = new Shortcodes_League( $racketmanager );

        $this->assertSame( '<div>Champ</div>', $facade->show_championship( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Archive</div>', $facade->show_archive( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Standings</div>', $facade->show_standings( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Crosstable</div>', $facade->show_crosstable( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Matches</div>', $facade->show_matches( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Teams</div>', $facade->show_teams( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Players</div>', $facade->show_league_players( [ 'id' => 1 ] ) );
        $this->assertSame( '<select>Season</select>', $facade->show_season_dropdown( [ 'id' => 1 ] ) );
        $this->assertSame( '<select>Match</select>', $facade->show_match_dropdown( [ 'id' => 1 ] ) );
        $this->assertSame( '<div>Last5</div>', $facade->show_last_5( [ 'id' => 1 ] ) );
    }
}
