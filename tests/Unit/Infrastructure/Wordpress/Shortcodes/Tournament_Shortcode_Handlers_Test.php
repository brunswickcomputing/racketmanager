<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Query;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Entry_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Public\Shortcodes_Tournament;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

class Tournament_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;

    private Tournament_Service $tournament_service;

    protected function setUp(): void {
        parent::setUp();
        $this->tournament_service = $this->createStub( Tournament_Service::class );
    }

    // -------------------------------------------------------------------------
    // 1. Get_Tournament_Handler
    // -------------------------------------------------------------------------

    public function test_tournament_handler_missing_tournament_delegates_to_latest_with_active(): void {
        $active_tournament = $this->createStub( Tournament::class );
        $active_tournament->method( 'get_name' )->willReturn( 'Summer Open' );

        $this->tournament_service->method( 'get_active_tournament' )->willReturn( $active_tournament );

        $latest_handler = new Get_Latest_Tournament_Handler( $this->tournament_service );
        $handler        = new Get_Tournament_Handler( $this->tournament_service, $latest_handler );

        $vm = $handler->handle( new Get_Tournament_Query() );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'location.href = "/tournament/summer-open/"', $vm->raw_html );
    }

    public function test_tournament_handler_missing_tournament_delegates_to_latest_without_active(): void {
        $this->tournament_service->method( 'get_active_tournament' )->willReturn( null );

        $latest_handler = new Get_Latest_Tournament_Handler( $this->tournament_service );
        $handler        = new Get_Tournament_Handler( $this->tournament_service, $latest_handler );

        $vm = $handler->handle( new Get_Tournament_Query() );
        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'location.href = "/tournaments/"', $vm->raw_html );
    }

    public function test_tournament_handler_success_with_details(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';

        $competition = $this->createStub( Competition::class );
        $competition->age_group = 'seniors';

        $club = $this->createStub( Club::class );
        $club->method( 'get_shortcode' )->willReturn( 'WTC' );

        $details = new Tournament_Details_DTO( $tournament, $competition, $club );

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $details );

        $this->tournament_service->method( 'get_tournaments' )
            ->willReturn( [ $details ] );

        $handler = new Get_Tournament_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Query( tournament: 'Summer Open', template: 'custom' ) );

        $this->assertSame( 'tournament-custom', $vm->template );
        $this->assertSame( $details, $vm->vars['tournament_details'] );
        $this->assertFalse( $vm->template_type );
    }

    public function test_tournament_handler_not_found_exception(): void {
        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $handler = new Get_Tournament_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Query( tournament: 'Missing Tourn' ) );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Tournament not found', $vm->error_message );
    }

    public function test_tournament_query_from_atts_and_globals(): void {
        $query = Get_Tournament_Query::from_atts(
            [ 'tournament' => 'Summer Open', 'template' => 'view' ]
        );
        $this->assertSame( 'summer open', $query->tournament );
        $this->assertSame( 'view', $query->template );

        $query2 = Get_Tournament_Query::from_atts(
            [],
            [ 'tournament' => 'winter-cup' ]
        );
        $this->assertSame( 'winter cup', $query2->tournament );
    }

    // -------------------------------------------------------------------------
    // 2. Get_Tournament_Overview_Handler
    // -------------------------------------------------------------------------

    public function test_overview_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Overview_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Overview_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_overview_handler_success(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 10 );

        $overview = (object) [
            'tournament' => $tournament,
            'events'     => [],
            'draws'      => [],
        ];

        $this->tournament_service->method( 'get_tournament_overview' )
            ->willReturn( $overview );

        $handler = new Get_Tournament_Overview_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Overview_Query( tournament_id: 10 ) );

        $this->assertSame( 'overview', $vm->template );
        $this->assertSame( 'tournament', $vm->template_type );
        $this->assertSame( $overview, $vm->vars['tournament'] );
    }

    public function test_overview_handler_not_found(): void {
        $this->tournament_service->method( 'get_tournament_overview' )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Overview not found' ) );

        $handler = new Get_Tournament_Overview_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Overview_Query( tournament_id: 99 ) );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Overview not found', $vm->error_message );
    }

    // -------------------------------------------------------------------------
    // 3. Get_Tournament_Events_Handler
    // -------------------------------------------------------------------------

    public function test_events_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Events_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Events_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_events_handler_all_events(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 5 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_events_with_details_for_tournament' )->willReturn( [] );

        $handler = new Get_Tournament_Events_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Events_Query( tournament_id: 5 ) );

        $this->assertSame( 'events', $vm->template );
        $this->assertSame( 'tournament', $vm->template_type );
    }

    public function test_events_handler_single_event(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 5 );
        $event = new Tournament_Event_DTO( id: 2, name: 'Mens Singles', entries: [], num_seeds: null );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_event_details_for_tournament' )->willReturn( $event );

        $handler = new Get_Tournament_Events_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Events_Query( tournament_id: 5, event_id: 'Mens Singles' ) );

        $this->assertSame( 'event', $vm->template );
        $this->assertSame( $event, $vm->vars['event'] );
    }

    public function test_events_handler_event_not_found(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 5 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_event_details_for_tournament' )
            ->willThrowException( new Event_Not_Found_Exception( 'Event missing' ) );

        $handler = new Get_Tournament_Events_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Events_Query( tournament_id: 5, event_id: 'Bad' ) );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Event missing', $vm->error_message );
    }

    // -------------------------------------------------------------------------
    // 4. Get_Tournament_Draws_Handler
    // -------------------------------------------------------------------------

    public function test_draws_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Draws_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Draws_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_draws_handler_all_draws(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 7 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_events_with_details_for_tournament' )->willReturn( [] );

        $handler = new Get_Tournament_Draws_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Draws_Query( tournament_id: 7 ) );

        $this->assertSame( 'draws', $vm->template );
    }

    public function test_draws_handler_single_draw(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 7 );
        $draw = $this->createStub( Event::class );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_draw_details_for_tournament' )->willReturn( $draw );

        $handler = new Get_Tournament_Draws_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Draws_Query( tournament_id: 7, draw_id: 'Draw 1' ) );

        $this->assertSame( 'draw', $vm->template );
        $this->assertSame( $draw, $vm->vars['draw'] );
    }

    // -------------------------------------------------------------------------
    // 5. Get_Tournament_Players_Handler & Slug Decoding
    // -------------------------------------------------------------------------

    public function test_players_query_decodes_seo_slug_from_query_vars_and_get(): void {
        $query1 = Get_Tournament_Players_Query::from_atts(
            [ 'id' => '12' ],
            [ 'player' => 'john-smith' ]
        );
        $this->assertSame( 12, $query1->tournament_id );
        $this->assertSame( 'john smith', $query1->player_id );

        $query2 = Get_Tournament_Players_Query::from_atts(
            [ 'id' => '12' ],
            [],
            [ 'player' => 'alice-cooper' ]
        );
        $this->assertSame( 'alice cooper', $query2->player_id );

        $query3 = Get_Tournament_Players_Query::from_atts(
            [ 'id' => '12', 'players' => '345' ]
        );
        $this->assertSame( 345, $query3->player_id );
    }

    public function test_players_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Players_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Players_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_players_handler_all_players(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 3 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_players_for_tournament' )->willReturn( [] );

        $handler = new Get_Tournament_Players_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Players_Query( tournament_id: 3 ) );

        $this->assertSame( 'players', $vm->template );
        $this->assertSame( 'tournament', $vm->template_type );
    }

    public function test_players_handler_single_player(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 3 );
        $player = $this->createStub( Player::class );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_player_details_for_tournament' )
            ->willReturn( $player );

        $handler = new Get_Tournament_Players_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Players_Query( tournament_id: 3, player_id: 'john smith' ) );

        $this->assertSame( 'player', $vm->template );
        $this->assertSame( $player, $vm->vars['tournament_player'] );
    }

    public function test_players_handler_player_not_found(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 3 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_player_details_for_tournament' )
            ->willThrowException( new Player_Not_Found_Exception( 'Player missing' ) );

        $handler = new Get_Tournament_Players_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Players_Query( tournament_id: 3, player_id: 99 ) );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Player missing', $vm->error_message );
    }

    // -------------------------------------------------------------------------
    // 6. Get_Tournament_Winners_Handler
    // -------------------------------------------------------------------------

    public function test_winners_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Winners_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Winners_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_winners_handler_success(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 8 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_winners_for_tournament' )->willReturn( [] );

        $handler = new Get_Tournament_Winners_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Winners_Query( tournament_id: '8' ) );

        $this->assertSame( 'winners', $vm->template );
    }

    // -------------------------------------------------------------------------
    // 7. Get_Tournament_Matches_Handler
    // -------------------------------------------------------------------------

    public function test_matches_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Matches_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Matches_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_matches_handler_success(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 9 );

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_fixtures_by_date_for_tournament' )->willReturn( [] );

        $handler = new Get_Tournament_Matches_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Matches_Query( tournament_id: 9 ) );

        $this->assertSame( 'matches', $vm->template );
    }

    // -------------------------------------------------------------------------
    // 8. Get_Tournament_Match_Handler
    // -------------------------------------------------------------------------

    public function test_match_handler_missing_id_returns_error(): void {
        $fixture_service = $this->createStub( Fixture_Service::class );
        $handler         = new Get_Tournament_Match_Handler( $this->tournament_service, $fixture_service );
        $vm              = $handler->handle( new Get_Tournament_Match_Query() );

        $this->assertTrue( $vm->is_error() );
    }

    public function test_match_handler_success(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';

        $competition = $this->createStub( Competition::class );
        $competition->age_group = 'seniors';

        $club = $this->createStub( Club::class );
        $club->method( 'get_shortcode' )->willReturn( 'WTC' );

        $details = new Tournament_Details_DTO( $tournament, $competition, $club );

        $fixture = $this->createStub( Fixture::class );
        $fixture_details = new Fixture_Details_DTO(
            $fixture,
            $this->createStub( League::class ),
            $this->createStub( Event::class ),
            $competition
        );

        $fixture_service = $this->createStub( Fixture_Service::class );
        $fixture_service->method( 'get_tournament_fixture_with_details' )->willReturn( $fixture_details );
        $this->tournament_service->method( 'get_tournament_with_details_by_name' )->willReturn( $details );

        $handler = new Get_Tournament_Match_Handler( $this->tournament_service, $fixture_service );
        $vm      = $handler->handle( new Get_Tournament_Match_Query( tournament: 'summer open', fixture_id: 100 ) );

        $this->assertSame( 'match-tournament', $vm->template );
        $this->assertSame( $fixture_details, $vm->vars['fixture_details'] );
    }

    // -------------------------------------------------------------------------
    // 9. Get_Order_Of_Play_Handler
    // -------------------------------------------------------------------------

    public function test_order_of_play_handler_missing_args_returns_error(): void {
        $comp_repo   = $this->createStub( Competition_Repository::class );
        $fix_repo    = $this->createStub( Fixture_Repository::class );
        $comp_svc    = $this->createStub( Competition_Service::class );
        $team_svc    = $this->createStub( Team_Service::class );

        $handler = new Get_Order_Of_Play_Handler(
            $this->tournament_service,
            $comp_repo,
            $fix_repo,
            $comp_svc,
            $team_svc
        );

        $vm = $handler->handle( new Get_Order_Of_Play_Query() );
        $this->assertTrue( $vm->is_error() );
    }

    // -------------------------------------------------------------------------
    // 10. Get_Latest_Tournament_Handler & URL Escaping
    // -------------------------------------------------------------------------

    public function test_latest_tournament_handler_active_tournament_and_escaping(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_name' )->willReturn( 'Open <2026>' );

        $this->tournament_service->method( 'get_active_tournament' )
            ->willReturn( $tournament );

        $handler = new Get_Latest_Tournament_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Latest_Tournament_Query( age_group: 'seniors' ) );

        $this->assertTrue( $vm->is_raw_html() );
        $html = $vm->raw_html;
        $this->assertStringContainsString( 'location.href = "', $html );
        $this->assertStringNotContainsString( '<2026>', $html );
    }

    public function test_latest_tournament_handler_with_age_group_fallback(): void {
        $this->tournament_service->method( 'get_active_tournament' )->willReturn( null );

        $handler = new Get_Latest_Tournament_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Latest_Tournament_Query( age_group: 'juniors' ) );

        $this->assertTrue( $vm->is_raw_html() );
        $this->assertStringContainsString( 'location.href = "/tournaments/juniors/"', $vm->raw_html );
    }

    // -------------------------------------------------------------------------
    // 11. Get_Tournament_Withdrawal_Handler
    // -------------------------------------------------------------------------

    public function test_withdrawal_handler_missing_id_returns_error(): void {
        $handler = new Get_Tournament_Withdrawal_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Withdrawal_Query() );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'modal', $vm->error_variant );
    }

    public function test_withdrawal_handler_success(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $this->tournament_service->method( 'get_tournament_event_entry_details_for_player' )
            ->willReturn( [] );

        $handler = new Get_Tournament_Withdrawal_Handler( $this->tournament_service );
        $vm      = $handler->handle( new Get_Tournament_Withdrawal_Query(
            tournament_id: '44',
            player_id: '77',
            modal: 'true'
        ) );

        $this->assertSame( 'withdrawal-modal', $vm->template );
        $this->assertSame( 'tournament', $vm->template_type );
        $this->assertSame( '44', $vm->vars['tournament_id'] );
        $this->assertSame( '77', $vm->vars['player_id'] );
    }

    // -------------------------------------------------------------------------
    // 12. Real Presenter Render Test
    // -------------------------------------------------------------------------

    public function test_real_presenter_render_with_view_model(): void {
        $view_renderer = new class implements View_Renderer_Interface {
            public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {
            }

            public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
                return '<div class="real-rendered-modal">Modal HTML</div>';
            }

            public function template_exists( string $template_path ): bool {
                return true;
            }
        };

        $template_renderer = new Shortcode_Template_Renderer(
            $view_renderer,
            $this->createStub( Club_Presenter::class ),
            $this->createStub( Player_Presenter::class )
        );

        $presenter = new Shortcode_View_Presenter( $template_renderer );

        $vm     = Shortcode_View_Model::for_template( 'withdrawal-modal', [ 'foo' => 'bar' ], 'tournament' );
        $output = $presenter->render( $vm );

        $this->assertSame( '<div class="real-rendered-modal">Modal HTML</div>', $output );
    }

    // -------------------------------------------------------------------------
    // 13. Shortcodes_Tournament Facade Delegation Test
    // -------------------------------------------------------------------------

    public function test_shortcodes_tournament_facade_delegates_to_adapter(): void {
        $adapter = $this->createMock( Tournament_Shortcode_Adapter::class );
        $adapter->expects( $this->once() )->method( 'show_tournament' )->willReturn( 'facade_tournament' );
        $adapter->expects( $this->once() )->method( 'show_tournament_overview' )->willReturn( 'facade_overview' );
        $adapter->expects( $this->exactly( 2 ) )->method( 'show_tournament_events' )->willReturn( 'facade_events' );
        $adapter->expects( $this->exactly( 2 ) )->method( 'show_draws' )->willReturn( 'facade_draws' );
        $adapter->expects( $this->exactly( 2 ) )->method( 'show_tournament_players' )->willReturn( 'facade_players' );
        $adapter->expects( $this->once() )->method( 'show_tournament_winners' )->willReturn( 'facade_winners' );
        $adapter->expects( $this->once() )->method( 'show_tournament_matches' )->willReturn( 'facade_matches' );
        $adapter->expects( $this->once() )->method( 'show_tournament_match' )->willReturn( 'facade_match' );
        $adapter->expects( $this->once() )->method( 'show_order_of_play' )->willReturn( 'facade_order_of_play' );
        $adapter->expects( $this->once() )->method( 'show_latest_tournament' )->willReturn( 'facade_latest' );
        $adapter->expects( $this->once() )->method( 'show_tournament_withdrawal_modal' )->willReturn( 'facade_withdrawal_modal' );
        $adapter->expects( $this->once() )->method( 'show_tournament_withdrawal' )->willReturn( 'facade_withdrawal' );

        $racketmanager = $this->create_app_with_services( [ 'tournament_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes_Tournament( $racketmanager );

        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 1 );

        $this->assertSame( 'facade_tournament', $facade->show_tournament() );
        $this->assertSame( 'facade_overview', $facade->show_tournament_overview() );
        $this->assertSame( 'facade_events', $facade->show_tournament_events() );
        $this->assertSame( 'facade_events', $facade->show_tournament_event( $tournament, 2 ) );
        $this->assertSame( 'facade_draws', $facade->show_draws() );
        $this->assertSame( 'facade_draws', $facade->show_draw( $tournament, 3 ) );
        $this->assertSame( 'facade_players', $facade->show_tournament_players() );
        $this->assertSame( 'facade_players', $facade->show_tournament_player( $tournament, 4 ) );
        $this->assertSame( 'facade_winners', $facade->show_tournament_winners() );
        $this->assertSame( 'facade_matches', $facade->show_tournament_matches() );
        $this->assertSame( 'facade_match', $facade->show_tournament_match() );
        $this->assertSame( 'facade_order_of_play', $facade->show_order_of_play() );
        $this->assertSame( 'facade_latest', $facade->show_latest_tournament() );
        $this->assertSame( 'facade_withdrawal_modal', $facade->show_tournament_withdrawal_modal() );
        $this->assertSame( 'facade_withdrawal', $facade->show_tournament_withdrawal() );
    }
}
