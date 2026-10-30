<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\DTOs\Event_Partner_Result;
use Racketmanager\Application\Event\Queries\Event_Allowlist_Guard;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Event_Header_View_Model;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use ReflectionClass;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class Event_Shortcode_Handlers_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET                  = [];
        $_POST                 = [];
        $_REQUEST              = [];
        $GLOBALS['query_vars'] = [];
        unset( $GLOBALS['wp_query'] );
        global $wp;
        $wp = (object) [ 'query_vars' => [] ];
    }

    protected function tearDown(): void {
        $_GET                  = [];
        $_POST                 = [];
        $_REQUEST              = [];
        $GLOBALS['query_vars'] = [];
        unset( $GLOBALS['wp_query'] );
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_renderer(): Shortcode_Template_Renderer {
        $php_renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        return new Shortcode_Template_Renderer( $php_renderer );
    }

    private function competition_presenter(): Competition_Shortcode_Presenter {
        return new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    private function present_partner_error( Event_Partner_Result $result ): Shortcode_View_Model {
        $handler = $this->createStub( Get_Event_Partner_Handler::class );
        $handler->method( 'handle' )->willReturn( $result );
        $vm = null;
        $presenter = $this->createStub( Shortcode_View_Presenter::class );
        $presenter->method( 'render' )->willReturnCallback( function ( Shortcode_View_Model $value ) use ( &$vm ): string {
            $vm = $value;
            return '';
        } );
        ( new Event_Shortcode_Adapter( view_presenter: $presenter, partner_handler: $handler ) )->show_event_partner();

        return $vm;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'season_request_handlers' )]
    public function test_event_handlers_resolve_url_season_before_shortcode_attribute( string $name, string $source ): void {
        $_GET = $_POST = [];
        if ( 'query_var' === $source ) {
            $GLOBALS['wp']->query_vars['season'] = '2025';
        } else {
            $_GET['season'] = '2025';
        }
        $event = $this->create_test_event( 93, 10, $this->create_test_competition( 10, [ 93 ] ) );
        $event->seasons = json_encode( [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] );
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $service = $this->event_query_service();
        $stats = $this->createStub( Player_Stats_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( \Racketmanager\Domain\Club\Club::from_object( (object) [ 'id' => 1 ] ) );
        [ $handler, $query ] = match ( $name ) {
            'event' => [ new Get_Event_Handler( $competition_service, $service ), new Get_Event_Query( event_id: 93, season: '2026' ) ],
            'partner' => [ new Get_Event_Partner_Handler( $competition_service, $this->createStub( Player_Service::class ), $service ), new Get_Event_Partner_Query( event_id: 93, season: '2026' ) ],
            'standings' => [ new Get_Event_Standings_Handler( $competition_service, $service ), new Get_Event_Standings_Query( event_id: 93, season: '2026' ) ],
            'draw' => [ new Get_Event_Draw_Handler( $competition_service, null, $this->createStub( Fixture_Detail_Service::class ), $service, $this->league_query_service() ), new Get_Event_Draw_Query( event_id: 93, season: '2026' ) ],
            'matches' => [ new Get_Event_Matches_Handler( $competition_service, null, $service, $this->league_query_service() ), new Get_Event_Matches_Query( event_id: 93, season: '2026' ) ],
            'clubs' => [ new Get_Event_Clubs_Handler( $competition_service, $club_service, $stats, $service ), new Get_Event_Clubs_Query( event_id: 93, season: '2026' ) ],
            'teams' => [ new Get_Event_Teams_Handler( $competition_service, $stats, null, $service ), new Get_Event_Teams_Query( event_id: 93, season: '2026' ) ],
            'players' => [ new Get_Event_Players_Handler( $competition_service, $this->createStub( Player_Service::class ), $this->createStub( Player_Query_Service::class ), $stats, $service ), new Get_Event_Players_Query( event_id: 93, season: '2026' ) ],
            'team_matches' => [ new Get_Event_Team_Matches_Handler( $competition_service, $service ), new Get_Event_Team_Matches_Query( event_id: 93, team_id: 1, season: '2026' ) ],
            'team_order_players' => [ new Get_Team_Order_Players_Handler( $competition_service, $club_service, $this->createStub( \Racketmanager\Services\Registration_Service::class ), $this->createStub( Settings_Service::class ), $service ), new Get_Team_Order_Players_Query( event_id: 93, club_id: 1, season: '2026' ) ],
            'dropdown' => [ new Get_Event_Dropdown_Handler( $competition_service, $service ), new Get_Event_Dropdown_Query( event_id: 93, season: '2026' ) ],
        };
        self::assertFalse( $handler->handle( $query )->is_error() );
        self::assertSame( '2025', $event->current_season['name'] );
    }

    public static function season_request_handlers(): array {
        $cases = [];
        foreach ( [ 'event', 'partner', 'standings', 'draw', 'matches', 'clubs', 'teams', 'players', 'team_matches', 'team_order_players', 'dropdown' ] as $handler ) {
            foreach ( [ 'get', 'query_var' ] as $source ) {
                $cases[ $handler . '_' . $source ] = [ $handler, $source ];
            }
        }
        return $cases;
    }

    public function test_event_handler_returns_error_for_unknown_season_in_request(): void {
        $_GET = [ 'season' => '1999' ];
        $event = $this->create_test_event( 93, 10, $this->create_test_competition( 10, [ 93 ] ) );
        $event->seasons = json_encode( [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] );
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $handler = new Get_Event_Handler( $competition_service, $this->event_query_service() );
        $query   = new Get_Event_Query( event_id: 93 );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Season not found for event', $result->error_message );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'repository_backed_handlers' )]
    public function test_public_handlers_query_real_events_through_injected_repositories( string $handler_name, string $repository_method, string $template ): void {
        unset( $GLOBALS['racketmanager'] );
        $event = $this->create_test_event( 93, 10, $this->create_test_competition( 10, [ 93 ] ) );
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $repository->expects( $this->once() )->method( $repository_method )->with( 93 )->willReturn( [] );
        $service = $this->event_query_service( $repository );
        $stats = $this->createStub( Player_Stats_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $club = new Club();
        $club->set_id( 50 );
        $club_service->method( 'get_club' )->willReturn( $club );
        [ $handler, $query ] = match ( $handler_name ) {
            'standings' => [ new Get_Event_Standings_Handler( $competition_service, $service ), new Get_Event_Standings_Query( event_id: 93 ) ],
            'draw' => [ new Get_Event_Draw_Handler( $competition_service, null, $this->createStub( Fixture_Detail_Service::class ), $service, $this->league_query_service() ), new Get_Event_Draw_Query( event_id: 93 ) ],
            'matches' => [ new Get_Event_Matches_Handler( $competition_service, null, $service, $this->league_query_service() ), new Get_Event_Matches_Query( event_id: 93 ) ],
            'dropdown' => [ new Get_Event_Dropdown_Handler( $competition_service, $service ), new Get_Event_Dropdown_Query( event_id: 93 ) ],
            'clubs' => [ new Get_Event_Clubs_Handler( $competition_service, $club_service, $stats, $service ), new Get_Event_Clubs_Query( event_id: 93 ) ],
            'teams' => [ new Get_Event_Teams_Handler( $competition_service, $stats, null, $service ), new Get_Event_Teams_Query( event_id: 93 ) ],
            'players' => [ new Get_Event_Players_Handler( $competition_service, $this->createStub( Player_Service::class ), $this->createStub( Player_Query_Service::class ), $stats, $service ), new Get_Event_Players_Query( event_id: 93 ) ],
            'team_matches' => [ new Get_Event_Team_Matches_Handler( $competition_service, $service ), new Get_Event_Team_Matches_Query( event_id: 93, team_id: 5 ) ],
            'order' => [ new Get_Team_Order_Players_Handler( $competition_service, $club_service, $this->createStub( Registration_Service::class ), $this->createStub( Settings_Service::class ), $service ), new Get_Team_Order_Players_Query( event_id: 93, club_id: 50 ) ],
        };

        $result = $handler->handle( $query );
        $this->assertFalse( $result->is_error() );
        $this->assertSame( $template, $result->template );
    }

    public static function repository_backed_handlers(): array {
        return [
            [ 'standings', 'query_leagues', 'standings' ],
            [ 'draw', 'query_leagues', 'draw' ],
            [ 'matches', 'query_leagues', 'matches' ],
            [ 'dropdown', 'query_leagues', 'dropdown' ],
            [ 'clubs', 'query_clubs', 'clubs' ],
            [ 'teams', 'query_teams', 'teams' ],
            [ 'players', 'query_players', 'players' ],
            [ 'team_matches', 'query_matches', 'team-matches' ],
            [ 'order', 'query_teams', 'team-players-list' ],
        ];
    }

    private function create_test_competition(
        int $id = 10,
        ?array $allowed_entry_events = null,
        Competition_Type $type = Competition_Type::LEAGUE
    ): Competition {
        return Competition_Factory::create_from_dto(
            new Competition_Hydration_DTO(
                id: $id,
                name: 'Test Competition ' . $id,
                type: $type,
                age_group: 'Adults',
                seasons: [
                    '2025' => [
                        'name'         => '2025',
                        'entry_events' => $allowed_entry_events,
                    ],
                    '2026' => [
                        'name'         => '2026',
                        'entry_events' => $allowed_entry_events,
                    ],
                ],
                settings: []
            )
        );
    }

    private function attach_test_competition( Event $event, ?Competition $competition = null ): Event {
        $event->competition_id = 10;
        $event->competition    = $competition ?? $this->create_test_competition();
        if ( empty( $event->seasons ) ) {
            $event->seasons = json_encode( [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] );
        } elseif ( is_array( $event->seasons ) ) {
            $seasons_map = [];
            foreach ( $event->seasons as $s ) {
                if ( isset( $s['name'] ) ) {
                    $seasons_map[ $s['name'] ] = $s;
                }
            }
            $event->seasons = json_encode( $seasons_map );
        }
        if ( method_exists( $event, 'method' ) ) {
            $event->method( 'get_season_by_name' )->willReturnCallback( function ( string $name ) use ( $event ) {
                $seasons = json_decode( (string) $event->seasons, true ) ?? [];
                return $seasons[ $name ] ?? null;
            } );
        }
        return $event;
    }

    public function test_event_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $handler      = new Get_Event_Handler( $comp_service, $this->event_query_service() );

        // 1. Missing event id / name
        $vm_missing = $handler->handle( new Get_Event_Query() );
        $this->assertTrue( $vm_missing->is_error() );
        $this->assertSame( 'Event not found', $vm_missing->error_message );

        // 2. Event not found in service
        $comp_service->method( 'get_event_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );
        $vm_not_found = $handler->handle( new Get_Event_Query( event_id: 999 ) );
        $this->assertTrue( $vm_not_found->is_error() );
        $this->assertSame( 'Event not found', $vm_not_found->error_message );

        // 3. Season not found
        $comp_service_success = $this->createStub( Competition_Service::class );
        $event_stub = $this->createStub( Event::class );
        $event_stub->id   = 10;
        $event_stub->name = 'Mens Singles';
        $event_stub->type = 'MS';
        $event_stub->seasons = [ [ 'name' => '2025' ] ];
        $event_stub->current_season = [];
        $this->attach_test_competition( $event_stub );

        $comp_service_success->method( 'get_event_by_id' )->willReturn( $event_stub );
        $handler_success = new Get_Event_Handler( $comp_service_success, $this->event_query_service() );

        $vm_no_season = $handler_success->handle( new Get_Event_Query( event_id: 10, season: '2026' ) );
        $this->assertTrue( $vm_no_season->is_error() );
        $this->assertSame( 'Season not found for event', $vm_no_season->error_message );

        // 4. Success path
        $event_stub->current_season = [ 'name' => '2025' ];
        $result = $handler_success->handle( new Get_Event_Query( event_name: 'Mens Singles', tab: 'draw' ) );
        $vm_success = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_event( $result );
        $this->assertFalse( $vm_success->is_error() );
        $this->assertSame( 'event', $vm_success->template );
        $this->assertFalse( $vm_success->template_type );
        $this->assertSame( 'draw', $vm_success->vars['tab'] );
        $this->assertSame( $event_stub, $vm_success->vars['event'] );
    }

    public function test_event_standings_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $event_repository = $this->event_repository_with_results( [ 'query_leagues' => [ (object) [ 'id' => 1 ] ] ] );
        $handler          = new Get_Event_Standings_Handler( $comp_service, $this->event_query_service( $event_repository ) );

        $vm_err = $handler->handle( new Get_Event_Standings_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 5;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_standings( $handler->handle( new Get_Event_Standings_Query( event_id: 5 ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'standings', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertCount( 1, $event_stub->leagues );
    }

    public function test_event_draw_handler_success_and_error(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $league_service = $this->createStub( League_Service::class );
        $handler        = new Get_Event_Draw_Handler( $comp_service, $league_service, $this->createStub( Fixture_Detail_Service::class ), $this->event_query_service(), $this->league_query_service() );

        $vm_err = $handler->handle( new Get_Event_Draw_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 7;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_draw( $handler->handle( new Get_Event_Draw_Query( event_id: 7 ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'draw', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );

        // Championship template parameter
        $vm_champ = $handler->handle( new Get_Event_Draw_Query( event_id: 7, template: 'championship' ) );
        $this->assertSame( 'draw-championship', $vm_champ->template );
    }

    public function test_event_matches_handler_success_and_error(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $league_service = $this->createStub( League_Service::class );
        $handler        = new Get_Event_Matches_Handler( $comp_service, $league_service, $this->event_query_service(), $this->league_query_service() );

        $vm_err = $handler->handle( new Get_Event_Matches_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 8;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = ( new Event_Shortcode_Adapter( tournament_presenter: new Tournament_Presenter() ) )->present_matches( $handler->handle( new Get_Event_Matches_Query( event_id: 8, template: 'custom' ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'matches-custom', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
    }

    public function test_event_clubs_handler_decorates_nonempty_players(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $event_stub   = $this->createStub( Event::class );
        $event_stub->id             = 12;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $club_stub     = $this->createStub( Club::class );
        $club_stub->id = 101;
        $club_service->method( 'get_club' )->willReturn( $club_stub );

        [ $low, $high, $stats ] = $this->build_stat_players( $event_stub, '2025', 'event' );
        $event_repository = $this->event_repository_with_results(
            [ 'query_players' => [ (object) [ 'player_id' => 31 ], (object) [ 'player_id' => 32 ] ] ]
        );
        $player_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturnMap( [ [ 31, $low ], [ 32, $high ] ] );

        $handler = new Get_Event_Clubs_Handler( $comp_service, $club_service, $stats, $this->event_query_service( $event_repository, player_repository: $player_repository ) );
        $vm      = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_clubs( $handler->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: 101 ) ) );

        $this->assertFalse( $vm->is_error() );
        $this->assert_decorated_and_sorted( $low, $high, $vm->vars['event_club']->players );
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

    public function test_event_clubs_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $handler      = new Get_Event_Clubs_Handler( $comp_service, $club_service, $this->createStub( Player_Stats_Service::class ), $this->event_query_service() );

        $vm_err = $handler->handle( new Get_Event_Clubs_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 12;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // All clubs
        $vm_all = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_clubs( $handler->handle( new Get_Event_Clubs_Query( event_id: 12 ) ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'clubs', $vm_all->template );
        $this->assertSame( 'event', $vm_all->template_type );

        // Specific club found by shortcode
        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 101;
        $club_stub->shortcode = 'ltc';

        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club_stub );

        $vm_club = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_clubs( $handler->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: 'ltc' ) ) );
        $this->assertFalse( $vm_club->is_error() );
        $this->assertSame( $club_stub, $vm_club->vars['event_club'] );
        $this->assertSame( [], $club_stub->players );

        // Specific club not found
        $club_service_err = $this->createStub( Club_Service::class );
        $club_service_err->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );
        $handler_club_err = new Get_Event_Clubs_Handler( $comp_service, $club_service_err, $this->createStub( Player_Stats_Service::class ), $this->event_query_service() );
        $vm_club_err = $handler_club_err->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: 999 ) );
        $this->assertTrue( $vm_club_err->is_error() );
        $this->assertSame( 'Club not found', $vm_club_err->error_message );
    }

    public function test_event_teams_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $team_service = $this->createStub( Team_Service::class );
        $handler      = new Get_Event_Teams_Handler( $comp_service, $this->createStub( Player_Stats_Service::class ), $team_service, $this->event_query_service() );

        $vm_err = $handler->handle( new Get_Event_Teams_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 15;
        $event_stub->name = 'Summer Open';
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // All teams
        $vm_all = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_teams( $handler->handle( new Get_Event_Teams_Query( event_id: 15 ) ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'teams', $vm_all->template );

        // Single team lookup
        $team_stub = $this->createStub( Team::class );
        $team_stub->id = 20;
        $team_stub->title = 'Team 1';

        $team_service->method( 'get_team_by_id' )->willReturn( $team_stub );

        $vm_team = $handler->handle( new Get_Event_Teams_Query( event_id: 15, team: '20' ) );
        $this->assertFalse( $vm_team->is_error() );
        $this->assertSame( $team_stub, $event_stub->team );

        // Championship fallback to 'list'
        $event_stub->competition->is_championship = true;
        $vm_champ = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_teams( $handler->handle( new Get_Event_Teams_Query( event_id: 15, team: null ) ) );
        $this->assertSame( 'teams-list', $vm_champ->template );
    }

    public function test_event_teams_handler_decorates_nonempty_players(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $team_service = $this->createStub( Team_Service::class );
        $event_stub   = $this->createStub( Event::class );
        $event_stub->id             = 15;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $team_stub        = $this->createStub( Team::class );
        $team_stub->id    = 20;
        $team_stub->title = 'Team 1';
        $team_service->method( 'get_team_by_id' )->willReturn( $team_stub );

        [ $low, $high, $stats ] = $this->build_stat_players( $event_stub, '2025', 'event' );
        $event_repository = $this->event_repository_with_results(
            [ 'query_players' => [ (object) [ 'player_id' => 31 ], (object) [ 'player_id' => 32 ] ] ]
        );
        $player_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturnMap( [ [ 31, $low ], [ 32, $high ] ] );

        $handler = new Get_Event_Teams_Handler( $comp_service, $stats, $team_service, $this->event_query_service( $event_repository, player_repository: $player_repository ) );
        $vm      = $handler->handle( new Get_Event_Teams_Query( event_id: 15, team: '20' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assert_decorated_and_sorted( $low, $high, $event_stub->team->players );
    }

    public function test_event_players_handler_numeric_id_and_name_and_all_players(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $query_service = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [] );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );
        $handler = new Get_Event_Players_Handler( $comp_service, $player_service, $query_service, $stats_service, $this->event_query_service() );

        $vm_err = $handler->handle( new Get_Event_Players_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 16;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // 1. All players
        $vm_all = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_players( $handler->handle( new Get_Event_Players_Query( event_id: 16 ) ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'players', $vm_all->template );

        // 2. Numeric player lookup - found
        $player_stub = $this->createStub( Player::class );
        $player_stub->id = 55;
        $player_stub->fullname = 'Roger Federer';

        $player_service->method( 'get_player' )->willReturnCallback( function ( ?int $id ) use ( $player_stub ) {
            if ( 55 === $id ) {
                return $player_stub;
            }
            throw new Player_Not_Found_Exception( 'Player not found' );
        } );

        $vm_player = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 55 ) );
        $this->assertFalse( $vm_player->is_error() );
        $this->assertSame( $player_stub, $event_stub->player );
        $this->assertSame( [], $event_stub->player->matches );
        $this->assertSame( [], $event_stub->player->stats );

        // 3. Numeric player lookup - not found
        $vm_not_found = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 999 ) );
        $this->assertTrue( $vm_not_found->is_error() );
        $this->assertSame( 'Player not found', $vm_not_found->error_message );

        // 4. String player name lookup - found
        $player_service->method( 'get_player_by_name' )->willReturnCallback( function ( string $name ) use ( $player_stub ) {
            if ( 'Roger Federer' === $name ) {
                return $player_stub;
            }
            return null;
        } );

        $vm_name = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 'Roger Federer' ) );
        $this->assertFalse( $vm_name->is_error() );
        $this->assertSame( $player_stub, $event_stub->player );

        // 5. String player name lookup - not found
        $vm_name_not_found = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 'Unknown Player' ) );
        $this->assertTrue( $vm_name_not_found->is_error() );
        $this->assertSame( 'Player not found', $vm_name_not_found->error_message );
    }

    public function test_event_partner_handler_gender_rules_and_modal_errors(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $handler        = new Get_Event_Partner_Handler( $comp_service, $player_service, $this->event_query_service() );

        // 1. Missing event id -> modal error
        $vm_no_event = $handler->handle( new Get_Event_Partner_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'Event not found', $vm_no_event->error_message );
        $this->assertSame( 'modal', $this->present_partner_error( $vm_no_event )->error_variant );

        // 2. Event not found -> modal error
        $comp_service->method( 'get_event_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );
        $vm_event_404 = $handler->handle( new Get_Event_Partner_Query( event_id: 999 ) );
        $this->assertTrue( $vm_event_404->is_error() );
        $this->assertSame( 'Event not found', $vm_event_404->error_message );
        $this->assertSame( 'modal', $this->present_partner_error( $vm_event_404 )->error_variant );

        // 3. Success path with gender rule (male event -> partner male)
        $comp_service_ok = $this->createStub( Competition_Service::class );
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 22;
        $event_stub->type = 'MD';
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service_ok->method( 'get_event_by_id' )->willReturn( $event_stub );

        $partner_player = new Player();
        $partner_player->id = 300;
        $partner_player->fullname = 'Partner Name';
        $player_service->method( 'get_player' )->willReturn( $partner_player );

        $handler_ok = new Get_Event_Partner_Handler( $comp_service_ok, $player_service, $this->event_query_service() );

        $vm_ok = ( new Event_Shortcode_Adapter() )->present_partner( $handler_ok->handle( new Get_Event_Partner_Query( event_id: 22, partner_id: 300, gender: 'M' ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'partner-modal', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertSame( 'M', $vm_ok->vars['partner_gender'] );
        $this->assertSame( 'Partner Name', $vm_ok->vars['partner_name'] );
    }

    public function test_event_team_matches_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $fixture = $this->createStub( \Racketmanager\Domain\Fixture\Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 1 );
        $fixture->method( 'get_league' )->willReturn( null );
        $fixture->method( 'get_title' )->willReturn( 'Match 1' );
        $event_repository = $this->event_repository_with_results( [ 'query_matches' => [ (object) [ 'id' => 1 ] ] ] );
        $fixture_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id_with_relations' )->willReturn( $fixture );
        $handler = new Get_Event_Team_Matches_Handler( $comp_service, $this->event_query_service( $event_repository, fixture_repository: $fixture_repository ) );

        // 1. Missing event id
        $vm_no_event = $handler->handle( new Get_Event_Team_Matches_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'No event id supplied', $vm_no_event->error_message );

        // 2. Missing team id
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 33;
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_no_team = $handler->handle( new Get_Event_Team_Matches_Query( event_id: 33 ) );
        $this->assertTrue( $vm_no_team->is_error() );
        $this->assertSame( 'No team id supplied', $vm_no_team->error_message );

        // 3. Success path
        $vm_ok = ( new Event_Shortcode_Adapter() )->present_team_matches( $handler->handle( new Get_Event_Team_Matches_Query( event_id: 33, team_id: 5, template: 'custom' ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'team-matches-custom', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertCount( 1, $vm_ok->vars['matches'] );
    }

    public function test_team_order_players_handler_roster_and_age_limits(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createStub( Registration_Service::class );
        $settings_service = $this->createStub( Settings_Service::class );
        $rm           = $this->createStub( RacketManager::class );
        $rm->method( 'get_options' )->willReturn( [ 'ageLimitCheck' => 'seniors' ] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_service, $this->event_query_service() );

        // 1. Missing event id
        $vm_no_event = $handler->handle( new Get_Team_Order_Players_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'No event id supplied', $vm_no_event->error_message );

        // 2. Missing club id
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 44;
        $event_stub->type = 'XD';
        $event_stub->age_limit = '45';
        $event_stub->age_offset = '5';
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_no_club = $handler->handle( new Get_Team_Order_Players_Query( event_id: 44 ) );
        $this->assertTrue( $vm_no_club->is_error() );
        $this->assertSame( 'Club id not supplied', $vm_no_club->error_message );

        // 3. Club not found
        $club_service->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );
        $vm_club_404 = $handler->handle( new Get_Team_Order_Players_Query( event_id: 44, club_id: 999 ) );
        $this->assertTrue( $vm_club_404->is_error() );
        $this->assertSame( 'Club not found', $vm_club_404->error_message );

        // 4. Success path with age offset calculation (min_age 45 male, 40 female)
        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 200;
        $club_stub->method( 'get_id' )->willReturn( 200 );

        $club_service_ok = $this->createStub( Club_Service::class );
        $club_service_ok->method( 'get_club' )->willReturn( $club_stub );
        $club_service_ok->method( 'can_user_update_as_captain' )->willReturn( true );

        $reg_service->method( 'get_registered_players_list' )->willReturn( [ (object) [ 'id' => 1 ] ] );

        $handler_ok = new Get_Team_Order_Players_Handler( $comp_service, $club_service_ok, $reg_service, $settings_service, $this->event_query_service() );
        $vm_ok = ( new Event_Shortcode_Adapter() )->present_team_order_players( $handler_ok->handle( new Get_Team_Order_Players_Query( event_id: 44, club_id: 200 ) ) );

        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'team-players-list', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertTrue( $vm_ok->vars['can_update'] );
        $this->assertArrayHasKey( 'm', $vm_ok->vars['club_players'] );
        $this->assertArrayHasKey( 'f', $vm_ok->vars['club_players'] );
    }

    public function test_team_order_players_handler_with_injected_settings_service(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createMock( Registration_Service::class );
        $settings_svc = $this->createMock( Settings_Service::class );
        $settings_svc->expects( $this->once() )
            ->method( 'get_category' )
            ->with( 'rosters', [] )
            ->willReturn( [ 'ageLimitCheck' => 'seniors' ] );

        $competition         = $this->create_test_competition( 1, [ 88 ] );
        $event               = $this->create_test_event( 88, 1, $competition );
        $event->name         = 'Over 45s';
        $event->type         = 'XD';
        $event->age_limit    = '45';
        $event->age_offset   = '5';
        $event->seasons      = [ '2026' => [] ];
        $event->current_season = [ 'name' => '2026' ];

        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 50;
        $club_stub->method( 'get_id' )->willReturn( 50 );
        $club_service->method( 'get_club' )->willReturn( $club_stub );
        $club_service->method( 'can_user_update_as_captain' )->willReturn( true );

        $reg_service->expects( $this->exactly( 2 ) )
            ->method( 'get_registered_players_list' )
            ->willReturnCallback( function( $status, $group, $club_id, $gender, $include_unranked, $max_age, $min_age ) {
                if ( 'm' === $gender ) {
                    $this->assertSame( 45, $min_age );
                    $this->assertNull( $max_age );
                    return [ (object) [ 'id' => 1 ] ];
                }
                if ( 'f' === $gender ) {
                    $this->assertSame( 40, $min_age );
                    $this->assertNull( $max_age );
                    return [ (object) [ 'id' => 2 ] ];
                }
                return [];
            } );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_svc, $this->event_query_service() );
        $vm      = $handler->handle( new Get_Team_Order_Players_Query( event_id: 88, club_id: 50 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertArrayHasKey( 'club_players', $vm->vars );
        $this->assertCount( 1, $vm->vars['club_players']['m'] );
        $this->assertCount( 1, $vm->vars['club_players']['f'] );
    }

    public function test_partner_query_normalises_string_ids_and_handler_accepts_them(): void {
        $query = Get_Event_Partner_Query::from_atts( [ 'id' => '5', 'partner_id' => '123', 'player_id' => '7', 'tournament_id' => '9' ] );
        $this->assertSame( 123, $query->partner_id );
        $this->assertSame( 7, $query->player_id );
        $this->assertSame( 9, $query->tournament_id );

        $comp_service = $this->createStub( Competition_Service::class );
        $event_stub   = $this->createStub( Event::class );
        $event_stub->id = 5;
        $event_stub->type = 'MD';
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $partner = $this->createStub( Player::class );
        $partner->id = 123;
        $partner->fullname = 'Partner Name';
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'get_player' )->with( 123 )->willReturn( $partner );

        $handler = new Get_Event_Partner_Handler( $comp_service, $player_service, $this->event_query_service() );
        $vm      = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'partner-modal', $vm->template );
        $this->assertSame( 123, ( new Event_Shortcode_Adapter() )->present_partner( $vm )->vars['partner_id'] );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'age_limit_provider' )]
    public function test_team_order_players_age_logic( string $age_limit, ?int $exp_max, ?int $exp_min ): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createMock( Registration_Service::class );
        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [ 'ageLimitCheck' => true ] );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 61;
        $event_stub->type = 'BD';
        $event_stub->age_limit = $age_limit;
        $event_stub->age_offset = '5';
        $event_stub->current_season = [ 'name' => '2025' ];
        $this->attach_test_competition( $event_stub );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 200;
        $club_stub->method( 'get_id' )->willReturn( 200 );
        $club_service->method( 'get_club' )->willReturn( $club_stub );
        $club_service->method( 'can_user_update_as_captain' )->willReturn( true );

        $reg_service->expects( $this->once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 200, 'm', true, $exp_max, $exp_min )
            ->willReturn( [] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_service, $this->event_query_service() );
        $vm      = $handler->handle( new Get_Team_Order_Players_Query( event_id: 61, club_id: 200 ) );
        $this->assertFalse( $vm->is_error() );
    }

    public static function age_limit_provider(): array {
        return [
            'junior' => [ '16', 16, null ],
            'senior' => [ '45', null, 45 ],
        ];
    }

    public function test_event_dropdown_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $leagues = [ (object) [ 'id' => 1 ] ];
        $event_repository = $this->event_repository_with_results( [ 'query_leagues' => $leagues ] );
        $handler = new Get_Event_Dropdown_Handler( $comp_service, $this->event_query_service( $event_repository ) );

        $vm_err = $handler->handle( new Get_Event_Dropdown_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 50;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->seasons = [ [ 'name' => '2025' ], [ 'name' => '2024' ] ];
        $this->attach_test_competition( $event_stub );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = ( new Event_Shortcode_Adapter() )->present_dropdown( $handler->handle( new Get_Event_Dropdown_Query( event_id: 50 ) ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'dropdown', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertSame( $leagues, $vm_ok->vars['leagues'] );
    }

    public function test_renderer_sets_and_restores_global_event(): void {
        $inner_renderer = new class implements View_Renderer_Interface {
            public ?int $observed_event_id = null;
            public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {}
            public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
                $this->observed_event_id = isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) ? ( $GLOBALS['event']->id ?? null ) : null;
                return 'rendered-output';
            }
            public function template_exists( string $template_path ): bool {
                return true;
            }
        };

        $renderer  = new Shortcode_Template_Renderer( $inner_renderer );
        $event_obj = (object) [ 'id' => 888, 'name' => 'Global Event Test' ];

        // 1. When $event was previously set to another value
        global $event;
        $event = 'previous_event_value';

        $output = $renderer->render( 'event', [ 'event' => $event_obj ], false );
        $this->assertSame( 'rendered-output', $output );
        $this->assertSame( 888, $inner_renderer->observed_event_id );
        $this->assertSame( 'previous_event_value', $GLOBALS['event'] );

        // 2. When $event was not previously set
        unset( $GLOBALS['event'] );
        $output_unset = $renderer->render( 'event', [ 'event' => $event_obj ], false );
        $this->assertSame( 'rendered-output', $output_unset );
        $this->assertSame( 888, $inner_renderer->observed_event_id );
        $this->assertFalse( array_key_exists( 'event', $GLOBALS ) );
    }

    private function create_test_event( int $id, int $competition_id = 10, ?Competition $competition = null ): Event {
        $event                 = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = 'Event ' . $id;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( [ '2026' => [ 'name' => '2026' ] ] );
        $event->current_season = [ 'name' => '2026' ];
        $event->type           = 'MD';
        if ( $competition ) {
            $event->competition = $competition;
        }
        return $event;
    }

    public function test_event_handlers_enforce_allowlist_for_allowed_and_blocked_events(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $allowed_event = $this->create_test_event( 101, 10, $competition );
        $blocked_event = $this->create_test_event( 103, 10, $competition );

        $comp_service     = $this->createStub( Competition_Service::class );
        $league_service   = $this->createStub( League_Service::class );
        $club_service     = $this->createStub( Club_Service::class );
        $player_service   = $this->createStub( Player_Service::class );
        $reg_service      = $this->createStub( Registration_Service::class );
        $settings_service = $this->createStub( Settings_Service::class );

        // 1. Get_Event_Handler
        $comp_service->method( 'get_event_by_id' )->willReturnCallback(
            fn( $id ) => 101 === (int) $id ? $allowed_event : $blocked_event
        );
        $event_handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $this->assertFalse( $event_handler->handle( new Get_Event_Query( event_id: 101 ) )->is_error() );
        $err = $event_handler->handle( new Get_Event_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 2. Get_Event_Standings_Handler
        $standings_handler = new Get_Event_Standings_Handler( $comp_service, $this->event_query_service() );
        $this->assertFalse( $standings_handler->handle( new Get_Event_Standings_Query( event_id: 101 ) )->is_error() );
        $err = $standings_handler->handle( new Get_Event_Standings_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 3. Get_Event_Draw_Handler
        $draw_handler = new Get_Event_Draw_Handler( $comp_service, $league_service, $this->createStub( Fixture_Detail_Service::class ), $this->event_query_service(), $this->league_query_service() );
        $this->assertFalse( $draw_handler->handle( new Get_Event_Draw_Query( event_id: 101 ) )->is_error() );
        $err = $draw_handler->handle( new Get_Event_Draw_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 4. Get_Event_Matches_Handler
        $matches_handler = new Get_Event_Matches_Handler( $comp_service, $league_service, $this->event_query_service(), $this->league_query_service() );
        $this->assertFalse( $matches_handler->handle( new Get_Event_Matches_Query( event_id: 101 ) )->is_error() );
        $err = $matches_handler->handle( new Get_Event_Matches_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 5. Get_Event_Clubs_Handler
        $clubs_handler = new Get_Event_Clubs_Handler( $comp_service, $club_service, $this->createStub( Player_Stats_Service::class ), $this->event_query_service() );
        $this->assertFalse( $clubs_handler->handle( new Get_Event_Clubs_Query( event_id: 101 ) )->is_error() );
        $err = $clubs_handler->handle( new Get_Event_Clubs_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 6. Get_Event_Teams_Handler
        $teams_handler = new Get_Event_Teams_Handler( $comp_service, $this->createStub( Player_Stats_Service::class ), null, $this->event_query_service() );
        $this->assertFalse( $teams_handler->handle( new Get_Event_Teams_Query( event_id: 101 ) )->is_error() );
        $err = $teams_handler->handle( new Get_Event_Teams_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 7. Get_Event_Players_Handler
        $players_handler = new Get_Event_Players_Handler(
            $comp_service,
            $player_service,
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $this->event_query_service()
        );
        $this->assertFalse( $players_handler->handle( new Get_Event_Players_Query( event_id: 101 ) )->is_error() );
        $err = $players_handler->handle( new Get_Event_Players_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 8. Get_Event_Partner_Handler
        $partner_handler = new Get_Event_Partner_Handler( $comp_service, $player_service, $this->event_query_service() );
        $this->assertFalse( $partner_handler->handle( new Get_Event_Partner_Query( event_id: 101, player_id: 5 ) )->is_error() );
        $err = $partner_handler->handle( new Get_Event_Partner_Query( event_id: 103, player_id: 5 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 9. Get_Event_Team_Matches_Handler
        $team_matches_handler = new Get_Event_Team_Matches_Handler( $comp_service, $this->event_query_service() );
        $this->assertFalse( $team_matches_handler->handle( new Get_Event_Team_Matches_Query( event_id: 101, team_id: 2 ) )->is_error() );
        $err = $team_matches_handler->handle( new Get_Event_Team_Matches_Query( event_id: 103, team_id: 2 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 10. Get_Event_Dropdown_Handler
        $dropdown_handler = new Get_Event_Dropdown_Handler( $comp_service, $this->event_query_service() );
        $this->assertFalse( $dropdown_handler->handle( new Get_Event_Dropdown_Query( event_id: 101 ) )->is_error() );
        $err = $dropdown_handler->handle( new Get_Event_Dropdown_Query( event_id: 103 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // 11. Get_Team_Order_Players_Handler
        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 50;
        $club_stub->method( 'get_id' )->willReturn( 50 );
        $club_service->method( 'get_club' )->willReturn( $club_stub );
        $order_handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $settings_service, $this->event_query_service() );
        $this->assertFalse( $order_handler->handle( new Get_Team_Order_Players_Query( event_id: 101, club_id: 50 ) )->is_error() );
        $err = $order_handler->handle( new Get_Team_Order_Players_Query( event_id: 103, club_id: 50 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );
    }

    public function test_event_allowlist_guard_fails_closed_when_competition_not_found_or_foreign(): void {
        $competition   = $this->create_test_competition( 10, [ 101, 102 ] );
        $foreign_event = $this->create_test_event( 101, 999, $competition );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $foreign_event );

        $handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $err     = $handler->handle( new Get_Event_Query( event_id: 101 ) );
        $this->assertTrue( $err->is_error() );
        $this->assertSame( 'Event not found for competition', $err->error_message );

        // Competition service throwing not found
        $missing_comp_event = $this->create_test_event( 101, 888 ); // no competition object attached
        $comp_service_missing = $this->createStub( Competition_Service::class );
        $comp_service_missing->method( 'get_event_by_id' )->willReturn( $missing_comp_event );
        $comp_service_missing->method( 'get_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $handler_missing = new Get_Event_Handler( $comp_service_missing, $this->event_query_service() );
        $err_missing     = $handler_missing->handle( new Get_Event_Query( event_id: 101 ) );
        $this->assertTrue( $err_missing->is_error() );
        $this->assertSame( 'Event not found for competition', $err_missing->error_message );
    }

    public function test_event_allowlist_guard_handles_integer_season_without_type_error(): void {
        $competition = $this->create_test_competition( 10, [ 101 ] );
        $event       = $this->create_test_event( 101, 10, $competition );
        // Integer season name simulated from numeric database/JSON payloads
        $event->current_season = [ 'name' => 2026 ];

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $result  = $handler->handle( new Get_Event_Query( event_id: 101, season: '2026' ) );

        $this->assertFalse( $result->is_error() );

        // Direct guard invocation with integer season
        $this->assertTrue( Event_Allowlist_Guard::is_event_allowed( $event, 2026, $comp_service ) );
    }

    public function test_event_query_preserves_routing_metadata_and_rejects_nonscalar_post_ids(): void {
        $from_atts = Get_Event_Query::from_atts(
            [ 'id' => 10, 'post_id' => 77, 'pagename' => 'x' ],
            []
        );
        $this->assertSame( 77, $from_atts->post_id );
        $this->assertSame( 'x', $from_atts->pagename );

        $from_query_vars = Get_Event_Query::from_atts(
            [ 'id' => 10 ],
            [ 'pagename' => 'tournament/summer-open/event/mens-singles' ]
        );
        $this->assertSame( 'tournament/summer-open/event/mens-singles', $from_query_vars->pagename );

        $invalid_routing = Get_Event_Query::from_atts(
            [ 'id' => 10, 'post_id' => [ 77 ], 'pagename' => [ 'invalid' ] ],
            []
        );
        $this->assertSame( '', $invalid_routing->post_id );
        $this->assertSame( '', $invalid_routing->pagename );
    }

    public function test_event_handler_renders_routing_metadata_for_tournament_and_league_events(): void {
        $cases = [
            [
                'type'     => Competition_Type::TOURNAMENT,
                'pagename' => 'tournament/summer-open/event/mens-singles',
            ],
            [
                'type'     => Competition_Type::LEAGUE,
                'pagename' => '/leagues/mens-singles/',
            ],
        ];

        foreach ( $cases as $case ) {
            $competition = $this->create_test_competition( 10, [ 101 ], $case['type'] );
            $competition->current_season = [
                'name'       => '2026',
                'date_start' => '2026-06-01',
                'date_end'   => '2026-06-07',
            ];
            $event       = $this->create_test_event( 101, 10, $competition );
            $event->name = 'Mens Singles';

            $competition_service = $this->createStub( Competition_Service::class );
            $competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $handler = new Get_Event_Handler( $competition_service, $this->event_query_service() );
            $query   = Get_Event_Query::from_atts(
                [ 'id' => 101, 'season' => '2026', 'post_id' => 77 ],
                [ 'pagename' => 'tournament/summer-open/event/mens-singles' ]
            );

            $result = $handler->handle( $query );
            $this->assertFalse( $result->is_error() );
            $vm = ( new Event_Shortcode_Adapter( competition_shortcode_presenter: $this->competition_presenter() ) )->present_event( $result );
            $this->assertInstanceOf( Event_Header_View_Model::class, $vm->vars['event_header'] );
            $this->assertSame( 77, $vm->vars['event_header']->post_id );
            $this->assertSame( $case['pagename'], $vm->vars['event_header']->pagename );
            $this->assertSame( 'https://example.test/page/77', $vm->vars['event_header']->form_action );

            if ( Competition_Type::TOURNAMENT === $case['type'] ) {
                $renderer = new Shortcode_View_Presenter( $this->create_renderer() );
                $html     = $renderer->render( $vm );
                $this->assertStringContainsString( 'name="page_id" value="77"', $html );
                $this->assertStringContainsString( 'id="pagename" value="tournament/summer-open/event/mens-singles"', $html );
                $this->assertStringContainsString( 'action="https://example.test/page/77"', $html );
            }
        }
    }
}
