<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Query;
use Racketmanager\Domain\Charge;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Domain\Player;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use Racketmanager\Presenters\Shortcode_View_Presenter;

class Competition_Entry_Flow_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['query_vars']                 = [];
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $GLOBALS['wp_stubs_current_user_can']  = false;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_captain_and_admin_club_selection_render_a_real_club_link(): void {
        $competition = Competition_Test_Factory::create( seasons: Competition_Test_Factory::season_map() );
        $GLOBALS['query_vars'] = [ 'competition_name' => 'Summer League', 'season' => '2026' ];
        $club = (object) [ 'id' => 4, 'shortcode' => 'WST', 'name' => 'Westcliff Tennis' ];
        $plugin = $this->createStub( RacketManager::class );
        $plugin->method( 'get_options' )->willReturnCallback( static fn( false|string $type = false ): array =>
            ( 'league' === $type || 'cup' === $type )
                ? [ 'entry_level' => 'captain' ]
                : [ 'league' => [ 'entry_level' => 'captain' ] ]
        );
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'find_all_associated_clubs' )
            ->with( 1, 'captain', null )->willReturn( [ $club ] );
        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( $this->once() )->method( 'get_clubs' )->willReturn( [ $club ] );
        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $service,
            club_service: $club_service,
            team_service: $this->createStub( Team_Service::class ),
            player_service: $player_service,
            tournament_service: $this->createStub( Tournament_Service::class ),
            finance_service: $this->createStub( Finance_Service::class ),
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            template_renderer: new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ),
            racketmanager: $plugin,
            club_entry_selection: new Club_Entry_Selection_Service( $club_service, $player_service )
        );

        $output = $adapter->show_competition_entry();
        $this->assertStringContainsString( 'Select club for entry', $output );
        $this->assertStringContainsString( 'WST', $output );
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $this->assertStringContainsString( 'WST', $adapter->show_competition_entry() );
    }

    public function test_league_and_cup_with_authorised_club_select_their_entry_templates(): void {
        $GLOBALS['query_vars'] = [ 'competition_name' => 'Summer League', 'season' => '2026', 'club_name' => 'WST' ];
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( 1 );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $this->club() );
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_teams_for_club' )->willReturn( [] );
        $player_service = $this->createStub( Player_Service::class );
        $renderer = $this->createMock( Shortcode_Template_Renderer::class );
        $renderer->expects( $this->exactly( 2 ) )->method( 'render' )
            ->willReturnCallback( static function ( string $template, array $vars, $type ): string {
                TestCase::assertSame( 'entry', $type );
                TestCase::assertSame( '2026', $vars['season'] );
                TestCase::assertSame( 4, $vars['club']->id );
                return '<form id="form-entry">' . $template . '</form>';
            } );
        foreach ( [ Competition_Type::LEAGUE, Competition_Type::CUP ] as $type ) {
            $service = $this->createStub( Competition_Service::class );
            $service->method( 'get_by_id' )->willReturn( Competition_Test_Factory::create( type: $type, seasons: Competition_Test_Factory::season_map() ) );
            $adapter = new Competition_Shortcode_Adapter(
                competition_service: $service, club_service: $club_service,
                team_service: $team_service, player_service: $player_service,
                tournament_service: $this->createStub( Tournament_Service::class ),
                registration_service: $this->createStub( Registration_Service::class ),
                competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
                finance_service: $this->createStub( Finance_Service::class ),
                template_renderer: $renderer,
                club_entry_selection: new Club_Entry_Selection_Service( $club_service, $player_service )
            );
            $this->assertStringContainsString( 'entry-' . $type->value, $adapter->show_competition_entry() );
        }
    }

    public function test_missing_season_and_unauthorised_club_are_rejected(): void {
        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( Competition_Test_Factory::create( type: Competition_Type::CUP, seasons: Competition_Test_Factory::season_map() ) );
        $renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'find_all_associated_clubs' )
            ->with( 1, 'captain', 4 )->willReturn( [] );
        $plugin = $this->createStub( RacketManager::class );
        $plugin->method( 'get_options' )->willReturnCallback( static fn( false|string $type = false ): array =>
            ( 'league' === $type || 'cup' === $type )
                ? [ 'entry_level' => 'captain' ]
                : [ 'cup' => [ 'entry_level' => 'captain' ] ]
        );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $this->club() );
        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $service, club_service: $club_service,
            team_service: $this->createStub( Team_Service::class ),
            player_service: $player_service,
            tournament_service: $this->createStub( Tournament_Service::class ),
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            finance_service: $this->createStub( Finance_Service::class ),
            template_renderer: $renderer, racketmanager: $plugin,
            club_entry_selection: new Club_Entry_Selection_Service(
                $club_service, $player_service
            )
        );
        $GLOBALS['query_vars'] = [ 'competition_name' => 'Summer League' ];
        $this->assertStringContainsString( 'No season specified', $adapter->show_competition_entry() );
        $GLOBALS['query_vars']['season'] = '2025';
        $this->assertStringContainsString( 'Season not found', $adapter->show_competition_entry() );
        $GLOBALS['query_vars']['season'] = '2026';
        $GLOBALS['query_vars']['club_name'] = 'WST';
        $this->assertStringContainsString( 'User not authorised', $adapter->show_competition_entry() );
    }

    public function test_tournament_entry_provides_player_season_and_fees_to_the_entry_template(): void {
        $GLOBALS['query_vars'] = [ 'tournament' => 'Autumn Open' ];
        $tournament = $this->tournament();
        $competition = Competition_Test_Factory::create( type: Competition_Type::TOURNAMENT, seasons: Competition_Test_Factory::season_map() );
        $player = $this->player();
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $tournament_service->method( 'get_fees' )->willReturn( (object) [ 'competition' => 10 ] );
        $tournament_service->method( 'get_events_for_tournament' )->willReturn( [] );
        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );
        $finance = $this->createStub( Finance_Service::class );
        $finance->method( 'get_tournament_paid_total_for_player' )->willReturn( 0 );
        $renderer = $this->createMock( Shortcode_Template_Renderer::class );
        $renderer->expects( $this->once() )->method( 'render' )
            ->willReturnCallback( static function ( string $template, array $vars, $type ) use ( $tournament, $player ): string {
                TestCase::assertSame( 'entry-tournament', $template );
                TestCase::assertSame( 'entry', $type );
                TestCase::assertSame( $tournament, $vars['tournament'] );
                TestCase::assertSame( $player, $vars['player'] );
                TestCase::assertSame( '2026', (string) $vars['season'] );
                return '<form id="form-entry">Autumn Open</form>';
            } );
        $club_service = $this->createStub( Club_Service::class );
        $adapter = new Competition_Shortcode_Adapter(
            competition_service: $service, tournament_service: $tournament_service,
            player_service: $player_service, finance_service: $finance,
            club_service: $club_service,
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            template_renderer: $renderer,
            club_entry_selection: new Club_Entry_Selection_Service( $club_service, $player_service )
        );
        $this->assertStringContainsString( 'Autumn Open', $adapter->show_competition_entry() );
    }

    public function test_payment_and_completion_use_typed_lookup_and_render_the_correct_views(): void {
        $GLOBALS['query_vars'] = [ 'competition_type' => 'tournament', 'tournament' => 'Autumn Open' ];
        $tournament = $this->tournament();
        $player = $this->player();
        $entry = new Tournament_Entry( (object) [
            'id' => 42, 'tournament_id' => 8, 'player_id' => 1, 'status' => 1, 'fee' => 30,
        ] );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );
        $entry_service = $this->createMock( Competition_Entry_Service::class );
        $entry_service->expects( $this->exactly( 2 ) )->method( 'get_tournament_entry_by_tournament_and_player' )
            ->with( 8, 1 )->willReturn( $entry );
        $charge = new Charge();
        $charge->id = 6;
        $finance = $this->createMock( Finance_Service::class );
        $finance->expects( $this->once() )->method( 'get_charge' )->with( '5_2026' )->willReturn( $charge );
        $finance->expects( $this->once() )->method( 'get_invoices_by_criteria' )
            ->with( [ 'charge' => 6, 'player' => 1, 'status' => 'open' ] )
            ->willReturn( [ (object) [ 'id' => 2, 'amount' => 30 ], (object) [ 'id' => 3, 'amount' => 5 ] ] );
        $renderer = $this->createMock( Shortcode_Template_Renderer::class );
        $renderer->expects( $this->exactly( 2 ) )->method( 'render' )
            ->willReturnCallback( static function ( string $template, array $vars, $type ) use ( $entry ): string {
                TestCase::assertSame( 'entry', $type );
                TestCase::assertSame( $entry, $vars['tournament_entry'] );
                if ( 'tournament-payment' === $template ) {
                    TestCase::assertSame( 35, $vars['total_due'] );
                    TestCase::assertSame( 3, $vars['invoice_id'] );
                } else {
                    TestCase::assertSame( 'tournament-payment-complete', $template );
                }
                return '<div class="payment">' . $template . '</div>';
            } );
        $handler = new Get_Competition_Payment_Handler( $tournament_service, $player_service, $finance, $entry_service );
        $adapter = new Competition_Shortcode_Adapter(
            payment_handler: $handler, view_presenter: new Shortcode_View_Presenter( $renderer )
        );
        $this->assertStringContainsString( 'tournament-payment</div>', $adapter->show_competition_entry_payment() );
        $this->assertStringContainsString( 'tournament-payment-complete</div>', $adapter->show_competition_entry_payment_complete() );
        $this->assertSame( 'foo', Get_Competition_Payment_Query::from_atts(
            [ 'template' => 'foo' ], $GLOBALS['query_vars'], 1
        )->template );
    }

    public function test_payment_rejects_non_tournament_and_unknown_tournament(): void {
        $renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_by_name' )->willThrowException(
            new \Racketmanager\Exceptions\Tournament_Not_Found_Exception( 'Tournament not found' )
        );
        $handler = new Get_Competition_Payment_Handler(
            $tournament_service, $this->createStub( Player_Service::class ),
            $this->createStub( Finance_Service::class ), $this->createStub( Competition_Entry_Service::class )
        );
        $adapter = new Competition_Shortcode_Adapter(
            payment_handler: $handler, template_renderer: $renderer,
            view_presenter: new Shortcode_View_Presenter( $renderer )
        );
        $this->assertStringContainsString( 'Tournament not found', $adapter->show_competition_entry_payment_complete() );
        $GLOBALS['query_vars'] = [ 'competition_type' => 'tournament', 'tournament' => 'unknown' ];
        $this->assertStringContainsString( 'Tournament not found', $adapter->show_competition_entry_payment() );
    }

    public function test_payment_and_completion_render_real_no_entry_markup(): void {
        $GLOBALS['query_vars'] = [ 'competition_type' => 'tournament', 'tournament' => 'Autumn Open' ];
        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $this->tournament() );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $this->player() );
        $charge = new Charge();
        $charge->id = 6;
        $finance = $this->createStub( Finance_Service::class );
        $finance->method( 'get_charge' )->willReturn( $charge );
        $finance->method( 'get_invoices_by_criteria' )->willReturn( [] );
        $handler = new Get_Competition_Payment_Handler(
            $tournament_service, $player_service, $finance, $this->createStub( Competition_Entry_Service::class )
        );
        $renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $adapter = new Competition_Shortcode_Adapter(
            payment_handler: $handler, view_presenter: new Shortcode_View_Presenter( $renderer )
        );

        $this->assertStringContainsString( 'You have not yet entered the tournament', $adapter->show_competition_entry_payment() );
        $this->assertStringContainsString( 'Payment status', $adapter->show_competition_entry_payment_complete() );
    }

    public function test_entry_query_parsing_and_fallbacks(): void {
        $query = \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query::from_atts(
            [ 'template' => 'custom' ],
            [
                'competition_name' => 'summer-league',
                'competition_type' => 'league',
                'season'           => '2026',
                'club_name'        => 'westcliff-tennis',
                'player_id'        => 'jane-doe',
            ],
            10,
            true,
            'captain'
        );

        $this->assertSame( 'summer league', $query->competition_name );
        $this->assertNull( $query->tournament_name );
        $this->assertSame( 'league', $query->competition_type );
        $this->assertSame( '2026', $query->season );
        $this->assertSame( 'westcliff tennis', $query->club_name );
        $this->assertSame( 'jane doe', $query->player_id );
        $this->assertSame( 10, $query->user_id );
        $this->assertTrue( $query->is_admin );
        $this->assertSame( 'captain', $query->entry_level );
        $this->assertSame( 'custom', $query->template );

        $tournament_query = \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query::from_atts(
            [],
            [
                'competition_name' => 'autumn-open',
                'competition_type' => 'tournament',
            ]
        );
        $this->assertSame( 'autumn open', $tournament_query->tournament_name );
    }

    public function test_entry_handler_handles_error_cases(): void {
        $club_entry_selection = new Club_Entry_Selection_Service(
            $this->createStub( Club_Service::class ),
            $this->createStub( Player_Service::class )
        );
        $handler = new \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $this->createStub( Competition_Service::class ),
            tournament_service: $this->createStub( Tournament_Service::class ),
            player_service: $this->createStub( Player_Service::class ),
            club_service: $this->createStub( Club_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            finance_service: $this->createStub( Finance_Service::class )
        );
        $vm = $handler->handle( new \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query() );
        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Competition not found', $vm->error_message );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_by_name' )->willThrowException(
            new \Racketmanager\Exceptions\Tournament_Not_Found_Exception( 'Tournament not found' )
        );
        $handler = new \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $this->createStub( Competition_Service::class ),
            tournament_service: $tournament_service,
            player_service: $this->createStub( Player_Service::class ),
            club_service: $this->createStub( Club_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            finance_service: $this->createStub( Finance_Service::class )
        );
        $vm = $handler->handle( new \Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query( tournament_name: 'Unknown' ) );
        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Tournament not found', $vm->error_message );
    }

    private function tournament(): Tournament {
        $tournament = new Tournament();
        $tournament->id = 8;
        $tournament->name = 'Autumn Open';
        $tournament->competition_id = 5;
        $tournament->season = '2026';
        $tournament->date_end = '2026-10-20';
        return $tournament;
    }

    private function player(): Player {
        return Player_Test_Factory::create_sparse( [ 'id' => 1, 'display_name' => 'Jane Doe', 'gender' => 'F' ] );
    }

    private function club(): Club {
        return Club_Test_Factory::create( id: 4, shortcode: 'WST', name: 'Westcliff Tennis' );
    }
}