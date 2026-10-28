<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Events_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Payment_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Players_Query;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Teams_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\DTO\Club\Club_Competition_DTO;
use Racketmanager\Domain\DTO\Competition\Event_Details_DTO;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\DTO\Team\Team_Competition_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_Entry_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Player_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use stdClass;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

final class Presentation_Modernization_Templates_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;

    private Php_View_Renderer $view_renderer;
    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $template_path           = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
        $this->view_renderer     = new Php_View_Renderer( $template_path );
        $this->template_renderer = new Shortcode_Template_Renderer( $this->view_renderer );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_login_form_renders_with_real_handler_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback( fn( string $key, mixed $default = null ) => match ( $key ) {
            'users_can_register' => 1,
            default              => $default,
        } );

        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [] );

        $handler = new Get_Login_Form_Handler( $option_gateway, $settings_service );
        $query   = new Get_Login_Form_Query( redirect_to: 'https://example.com/custom-redirect' );
        $vm      = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-login', $vm->template );
        $this->assertTrue( $vm->vars['users_can_register'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering login form must produce zero warnings' );
        $this->assertStringContainsString( 'Sign Up', $output );
        $this->assertStringContainsString( 'value="https://example.com/custom-redirect"', $output );
    }

    public function test_login_form_renders_without_registration_tab_when_disabled(): void {
        unset( $GLOBALS['racketmanager'] );

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback( fn( string $key, mixed $default = null ) => match ( $key ) {
            'users_can_register' => 0,
            default              => $default,
        } );

        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [] );

        $handler = new Get_Login_Form_Handler( $option_gateway, $settings_service );
        $query   = new Get_Login_Form_Query();
        $vm      = $handler->handle( $query );

        $this->assertFalse( $vm->vars['users_can_register'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings );
        $this->assertStringNotContainsString( 'Sign Up', $output );
    }

    public function test_tournament_payment_renders_with_presenter_url_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $finance_service    = $this->createStub( Finance_Service::class );
        $player_service     = $this->createStub( Player_Service::class );
        $comp_entry_service = $this->createStub( Competition_Entry_Service::class );
        $presenter          = new Tournament_Presenter();

        $handler = new Get_Competition_Payment_Handler(
            tournament_service: $tournament_service,
            player_service: $player_service,
            finance_service: $finance_service,
            entry_service: $comp_entry_service,
            tournament_presenter: $presenter
        );

        $tournament = new Tournament();
        $tournament->id   = 7;
        $tournament->name = 'Autumn Closed';

        $player = Player_Test_Factory::create( id: 42, display_name: 'Test Player' );

        $entry = new Tournament_Entry();
        $entry->id = 10;

        $charge = Charge::from_object( (object) [ 'id' => 1 ] );

        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $player_service->method( 'get_player' )->willReturn( $player );
        $comp_entry_service->method( 'get_tournament_entry_by_tournament_and_player' )->willReturn( $entry );
        $finance_service->method( 'get_charge' )->willReturn( $charge );
        $finance_service->method( 'get_invoices_by_criteria' )->willReturn( [
            (object) [ 'id' => 101, 'amount' => 25.00 ],
        ] );

        $query = new Get_Competition_Payment_Query(
            competition_type: 'tournament',
            tournament_name: 'Autumn Closed',
            player_id: 42
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'tournament-payment', $vm->template );
        $this->assertStringContainsString( 'entry-form/autumn-closed-tournament/payment-complete/', $vm->vars['payment_complete_url'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-payment must produce zero warnings' );
        $this->assertStringContainsString( 'You now need to pay your entry fee', $output );
        $this->assertStringContainsString( 'id="paymentCompleteUrl"', $output );
        $this->assertStringContainsString( 'entry-form/autumn-closed-tournament/payment-complete/', $output );
    }

    public function test_tournament_events_and_single_event_render_with_presenter_urls_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $presenter          = new Tournament_Presenter();

        $handler = new Get_Tournament_Events_Handler(
            tournament_service: $tournament_service,
            tournament_presenter: $presenter
        );

        $tournament       = new Tournament();
        $tournament->id   = 10;
        $tournament->name = 'Spring Open';

        $event1            = new stdClass();
        $event1->id        = 101;
        $event1->name      = "Men's Singles";
        $event1->num_teams = 16;

        $event2            = new stdClass();
        $event2->id        = 102;
        $event2->name      = "Ladies Doubles";
        $event2->num_teams = 8;

        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_events_with_details_for_tournament' )->willReturn( [ $event1, $event2 ] );

        // 1. Test events list
        $list_query = new Get_Tournament_Events_Query( tournament_id: 10 );
        $list_vm    = $handler->handle( $list_query );

        $this->assertSame( 'events', $list_vm->template );
        $this->assertSame( '/tournament/spring-open/event/mens-singles/', $list_vm->vars['events'][0]->url_link );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $list_vm->template, $list_vm->vars, $list_vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament events list must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/spring-open/event/mens-singles/"', $output );
        $this->assertStringContainsString( 'href="/tournament/spring-open/event/ladies-doubles/"', $output );

        // 2. Test single event with real readonly DTOs
        $player1 = new Tournament_Player_DTO( id: 51, name: 'Roger Federer' );
        $entry1  = new Tournament_Event_Entry_DTO(
            id: 801,
            name: 'Roger Federer',
            rank: 1,
            rating: 1.0,
            players: [ $player1 ]
        );

        $single_event = new Tournament_Event_DTO(
            id: 101,
            name: "Men's Singles",
            entries: [ $entry1 ],
            num_seeds: 2
        );

        $tournament_service->method( 'get_event_details_for_tournament' )->willReturn( $single_event );

        $single_query = new Get_Tournament_Events_Query( tournament_id: 10, event_id: 101 );
        $single_vm    = $handler->handle( $single_query );

        $this->assertSame( 'event', $single_vm->template );
        $this->assertSame( $single_event, $single_vm->vars['event'] );
        $this->assertSame( '/tournament/spring-open/draw/mens-singles/', $single_vm->vars['draw_link'] );
        $this->assertSame( '/tournament/spring-open/player/roger-federer/', $single_vm->vars['player_links'][51] );

        $single_warnings = [];
        $single_output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $single_vm->template, $single_vm->vars, $single_vm->template_type ),
            $single_warnings
        );

        $this->assertSame( [], $single_warnings, 'Rendering tournament single event must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/spring-open/draw/mens-singles/"', $single_output );
        $this->assertStringContainsString( 'href="/tournament/spring-open/player/roger-federer/"', $single_output );
    }

    public function test_tournament_draws_renders_with_presenter_urls_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $presenter          = new Tournament_Presenter();

        $handler = new Get_Tournament_Draws_Handler(
            tournament_service: $tournament_service,
            tournament_presenter: $presenter
        );

        $tournament       = new Tournament();
        $tournament->id   = 12;
        $tournament->name = 'Winter Cup';

        $event1       = new stdClass();
        $event1->id   = 201;
        $event1->name = 'Men Singles Draw';

        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_events_with_details_for_tournament' )->willReturn( [ $event1 ] );

        $query = new Get_Tournament_Draws_Query( tournament_id: 12 );
        $vm    = $handler->handle( $query );

        $this->assertSame( 'draws', $vm->template );
        $this->assertSame( '/tournament/winter-cup/draw/men-singles-draw/', $vm->vars['draws'][0]->draw_link );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament draws must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/winter-cup/draw/men-singles-draw/"', $output );
    }

    public function test_tournament_players_and_single_player_render_with_presenter_urls_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $presenter          = new Tournament_Presenter();
        $player_presenter   = new Player_Presenter();

        $handler = new Get_Tournament_Players_Handler(
            tournament_service: $tournament_service,
            tournament_presenter: $presenter,
            player_presenter: $player_presenter
        );

        $tournament       = new Tournament();
        $tournament->id   = 15;
        $tournament->name = 'Championship Open';

        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        // 1. Test players list
        $player_item              = new stdClass();
        $player_item->id          = 101;
        $player_item->display_name = 'Rafael Nadal';
        $player_item->index       = 'Nadal, Rafael';
        $player_item->club        = (object) [ 'shortcode' => 'LTC' ];

        $tournament_service->method( 'get_players_for_tournament' )->willReturn( [
            'N' => [ $player_item ],
        ] );

        $list_query = new Get_Tournament_Players_Query( tournament_id: 15 );
        $list_vm    = $handler->handle( $list_query );

        $this->assertSame( 'players', $list_vm->template );
        $this->assertSame( '/tournament/championship-open/player/', $list_vm->vars['player_link'] );
        $this->assertSame( '/tournament/championship-open/player/rafael-nadal/', $list_vm->vars['player_links'][101] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $list_vm->template, $list_vm->vars, $list_vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament players list must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/championship-open/player/rafael-nadal/"', $output );
        $this->assertStringContainsString( 'Nadal, Rafael', $output );

        // 2. Test single player
        $t_player = Player_Test_Factory::create(
            id: 101,
            display_name: 'Rafael Nadal',
            email: 'rafa@tennis.local',
            overrides: [ 'contactno' => '0123456789' ]
        );
        $t_player->matches = [];

        $entry1               = new stdClass();
        $entry1->event_id     = 501;
        $entry1->event_name   = 'Men Doubles';
        $entry1->partner_id   = 102;
        $entry1->partner_name = 'Carlos Alcaraz';

        $t_player->teams = [ $entry1 ];

        $tournament_service->method( 'get_player_details_for_tournament' )->willReturn( $t_player );

        $GLOBALS['wp_stubs_is_user_logged_in'] = true;

        $single_query = new Get_Tournament_Players_Query( tournament_id: 15, player_id: 101 );
        $single_vm    = $handler->handle( $single_query );

        $this->assertSame( 'player', $single_vm->template );
        $this->assertFalse( isset( $single_vm->vars['tournament_player']->teams[0]->event_link ) );
        $this->assertSame( '/tournament/championship-open/event/men-doubles/', $single_vm->vars['player_entry_rows'][0]->event_link );
        $this->assertSame( '/tournament/championship-open/player/carlos-alcaraz/', $single_vm->vars['player_entry_rows'][0]->partner_link );

        $single_warnings = [];
        $single_output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $single_vm->template, $single_vm->vars, $single_vm->template_type ),
            $single_warnings
        );

        $this->assertSame( [], $single_warnings, 'Rendering tournament single player must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/championship-open/event/men-doubles/"', $single_output );
        $this->assertStringContainsString( 'href="/tournament/championship-open/player/carlos-alcaraz/"', $single_output );
        $this->assertStringContainsString( 'Carlos Alcaraz', $single_output );
    }

    public function test_cup_and_league_entry_templates_render_with_presenter_urls_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $club_entry_selection = $this->createStub( Club_Entry_Selection_Service::class );
        $comp_service       = $this->createStub( Competition_Service::class );
        $tourn_service      = $this->createStub( Tournament_Service::class );
        $player_service     = $this->createStub( Player_Service::class );
        $club_service       = $this->createStub( Club_Service::class );
        $team_service       = $this->createStub( Team_Service::class );
        $reg_service        = $this->createStub( Registration_Service::class );
        $comp_entry_service = $this->createStub( Competition_Entry_Service::class );
        $finance_service    = $this->createStub( Finance_Service::class );
        $comp_presenter     = new Competition_Shortcode_Presenter( renderer: $this->view_renderer );
        $tourn_presenter    = new Tournament_Presenter();

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $comp_service,
            tournament_service: $tourn_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $reg_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $finance_service,
            competition_presenter: $comp_presenter,
            tournament_presenter: $tourn_presenter,
            event_query_service: $this->event_query_service()
        );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Premier Cup',
            type: Competition_Type::CUP,
            seasons: [
                '2025' => [
                    'name'         => '2025',
                    'date_start'   => '2025-05-01',
                    'date_end'     => '2025-08-01',
                    'date_closing' => '2025-04-15',
                ],
            ]
        );

        $league_competition = Competition_Test_Factory::create(
            id: 2,
            name: 'Premier League',
            type: Competition_Type::LEAGUE,
            seasons: [
                '2025' => [
                    'name'         => '2025',
                    'date_start'   => '2025-05-01',
                    'date_end'     => '2025-08-01',
                    'date_closing' => '2025-04-15',
                ],
            ]
        );

        $club = Club::from_object( (object) [
            'id'        => 5,
            'name'      => 'Leigh Tennis Club',
            'shortcode' => 'LTC',
        ] );

        $comp_service->method( 'get_by_id' )->willReturnCallback( fn( $id ) => match ( (string) $id ) {
            '1', 'Premier Cup'    => $competition,
            '2', 'Premier League' => $league_competition,
            default               => null,
        } );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $club_entry_selection->method( 'available_clubs' )->willReturn( true );
        $team_service->method( 'get_teams_for_club' )->willReturn( [] );

        $query = new Get_Competition_Entry_Query(
            competition_name: 'Premier Cup',
            season: '2025',
            user_id: 1,
            club_name: 'LTC'
        );

        $vm = $handler->handle( $query );
        $this->assertSame( 'entry-cup', $vm->template );
        $this->assertSame( '/clubs/ltc/', $vm->vars['back_url'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering cup entry must produce zero warnings' );
        $this->assertStringContainsString( 'href="/clubs/ltc/"', $output );

        // 2. League entry
        $league_query = new Get_Competition_Entry_Query(
            competition_name: 'Premier League',
            season: '2025',
            user_id: 1,
            club_name: 'LTC'
        );

        $league_vm = $handler->handle( $league_query );
        $this->assertSame( 'entry-league', $league_vm->template );
        $this->assertSame( '/clubs/ltc/', $league_vm->vars['back_url'] );
        $this->assertSame( '/rules/premier-league-rules/', $league_vm->vars['rules_url'] );

        $league_warnings = [];
        $league_output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $league_vm->template, $league_vm->vars, $league_vm->template_type ),
            $league_warnings
        );

        $this->assertSame( [], $league_warnings, 'Rendering league entry must produce zero warnings' );
        $this->assertStringContainsString( 'href="/clubs/ltc/"', $league_output );
        $this->assertStringContainsString( 'href="/rules/premier-league-rules/"', $league_output );
    }

    public function test_tournament_payment_complete_renders_with_presenter_url_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $finance_service    = $this->createStub( Finance_Service::class );
        $player_service     = $this->createStub( Player_Service::class );
        $comp_entry_service = $this->createStub( Competition_Entry_Service::class );
        $presenter          = new Tournament_Presenter();

        $handler = new Get_Competition_Payment_Handler(
            tournament_service: $tournament_service,
            player_service: $player_service,
            finance_service: $finance_service,
            entry_service: $comp_entry_service,
            tournament_presenter: $presenter
        );

        $tournament                  = new Tournament();
        $tournament->id              = 8;
        $tournament->name            = 'Spring Open';
        $tournament->competition_id  = 3;
        $tournament->season          = '2026';

        $player = Player_Test_Factory::create( id: 55, display_name: 'Payment Player' );

        $entry     = new Tournament_Entry();
        $entry->id = 22;

        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $player_service->method( 'get_player' )->willReturn( $player );
        $comp_entry_service->method( 'get_tournament_entry_by_tournament_and_player' )->willReturn( $entry );

        $query = new Get_Competition_Payment_Query(
            competition_type: 'tournament',
            tournament_name: 'Spring Open',
            player_id: 55,
            complete: true
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'tournament-payment-complete', $vm->template );
        $this->assertNull( $vm->vars['total_due'] );
        $this->assertStringContainsString( 'entry-form/spring-open-tournament/payment-complete/', $vm->vars['payment_complete_url'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-payment-complete must produce zero warnings' );
        $this->assertStringContainsString( 'Payment status', $output );
        $this->assertStringContainsString( 'You have been entered into the tournament.', $output );
        $this->assertStringContainsString( 'There are no outstanding entry fees.', $output );
        $this->assertStringNotContainsString( 'id="paymentCompleteUrl"', $output );
    }

    public function test_tournament_entry_renders_with_presenter_back_url_and_zero_warnings(): void {
        unset( $GLOBALS['racketmanager'] );
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $club_service         = $this->createStub( Club_Service::class );
        $player_service       = $this->createStub( Player_Service::class );
        $comp_service         = $this->createStub( Competition_Service::class );
        $tournament_service   = $this->createStub( Tournament_Service::class );
        $team_service         = $this->createStub( Team_Service::class );
        $registration_service = $this->createStub( Registration_Service::class );
        $comp_entry_service   = $this->createStub( Competition_Entry_Service::class );
        $finance_service      = $this->createStub( Finance_Service::class );
        $club_entry_selection = $this->createStub( Club_Entry_Selection_Service::class );

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_entry_selection,
            competition_service: $comp_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $team_service,
            registration_service: $registration_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $finance_service,
            competition_presenter: new Competition_Shortcode_Presenter( renderer: $this->view_renderer ),
            tournament_presenter: new Tournament_Presenter( 'd/m/Y' ),
            event_query_service: $this->event_query_service()
        );

        $today        = new \DateTimeImmutable( 'today', new \DateTimeZone( 'UTC' ) );
        $date_open    = $today->modify( '-5 days' )->format( 'Y-m-d' );
        $date_closing = $today->modify( '+10 days' )->format( 'Y-m-d' );
        $date_start   = $today->modify( '+30 days' )->format( 'Y-m-d' );
        $date_end     = $today->modify( '+37 days' )->format( 'Y-m-d' );

        $competition = Competition_Test_Factory::create(
            id: 8,
            name: 'Summer Open',
            type: Competition_Type::TOURNAMENT,
            seasons: [
                '2026' => [
                    'name'         => '2026',
                    'date_closing' => $date_closing,
                ],
            ]
        );

        $tournament = Tournament::from_object( (object) [
            'id'             => 1,
            'competition_id' => 8,
            'name'           => 'Summer Open',
            'season'         => '2026',
            'date_open'      => $date_open,
            'date_start'     => $date_start,
            'date_end'       => $date_end,
            'date_closing'   => $date_closing,
            'fees'           => (object) [
                'competition' => 5,
                'event'       => 10,
            ],
        ] );

        $player = Player_Test_Factory::create(
            id: 42,
            firstname: 'Roger',
            surname: 'Federer',
            display_name: 'Roger Federer',
            gender: 'M'
        );

        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $tournament_service->method( 'get_fees' )->willReturn( (object) [
            'competition' => 5,
            'event'       => 10,
        ] );
        $tournament_service->method( 'get_events_for_tournament' )->willReturn( [] );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $player_service->method( 'get_player' )->willReturn( $player );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [] );
        $comp_entry_service->method( 'get_tournament_entry_by_tournament_and_player' )->willReturn( null );

        $query = new Get_Competition_Entry_Query(
            tournament_name: 'Summer Open',
            user_id: 42
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'entry-tournament', $vm->template );
        $this->assertSame( '/tournament/summer-open/', $vm->vars['back_url'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament entry must produce zero warnings' );
        $this->assertStringContainsString( 'href="/tournament/summer-open/"', $output );
    }

    private function render_view_model( Shortcode_View_Model $vm, array &$warnings ): string {
        return $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );
    }

    private function make_competition_stub( string $type = 'league', bool $team_entry = true, bool $championship = false ): Competition {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 5 );
        $competition->method( 'get_type' )->willReturn( $type );
        $competition->method( 'is_event_allowed' )->willReturn( true );
        $competition->id              = 5;
        $competition->name            = 'Summer League';
        $competition->type            = Competition_Type::from( $type );
        $competition->current_season  = [ 'name' => '2026' ];
        $competition->is_team_entry   = $team_entry;
        $competition->is_championship = $championship;

        return $competition;
    }

    private function make_event_stub( Competition $competition ): Event {
        $event = $this->createStub( Event::class );
        $event->method( 'get_id' )->willReturn( 77 );
        $event->id             = 77;
        $event->name           = "Men's Doubles";
        $event->competition    = $competition;
        $event->current_season = [ 'name' => '2026' ];
        $event->is_box         = false;

        return $event;
    }

    private function event_team_row( int $id, int $club_id, string $name, string $league_title ): object {
        return (object) [
            'id'           => $id,
            'team_id'      => $id,
            'club_id'      => $club_id,
            'name'         => $name,
            'league_title' => $league_title,
            'roster'       => null,
            'team_type'    => 'D',
        ];
    }

    /**
     * @param array<int, Club> $clubs Clubs keyed by club id.
     */
    private function club_repository_with( array $clubs ): Club_Repository_Interface {
        $repository = $this->createStub( Club_Repository_Interface::class );
        $repository->method( 'find_by_id' )->willReturnCallback( static fn( $id ) => $clubs[ (int) $id ] ?? null );

        return $repository;
    }

    private function assert_no_unresolved_fallbacks( string $output ): void {
        $this->assertStringNotContainsString( 'href="/league/"', $output );
        $this->assertStringNotContainsString( 'href=""', $output );
    }

    public function test_competition_events_list_renders_presenter_links_keyed_by_event_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $service     = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_events_with_details_for_competition' )->willReturn(
            [
                new Event_Details_DTO( (object) [ 'event_id' => 101, 'event_name' => "Men's Singles", 'format' => 'MS', 'settings' => [], 'num_leagues' => 2, 'num_teams' => 8, 'num_clubs' => 4, 'num_players' => 20 ] ),
                new Event_Details_DTO( (object) [ 'event_id' => 102, 'event_name' => 'Ladies Doubles', 'format' => 'LD', 'settings' => [], 'num_leagues' => 1, 'num_teams' => 6, 'num_clubs' => 3, 'num_players' => 12 ] ),
            ]
        );

        $handler = new Get_Competition_Events_Handler( $service, new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $vm      = $handler->handle( new Get_Competition_Events_Query( competition_id: 5 ) );

        $this->assertSame( 'events', $vm->template );
        $this->assertSame(
            [ 101 => '/leagues/mens-singles/2026/', 102 => '/leagues/ladies-doubles/2026/' ],
            $vm->vars['event_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );

        $this->assertSame( [], $warnings, 'Rendering competition events must produce zero warnings' );
        $this->assertStringContainsString( 'href="/leagues/mens-singles/2026/"', $output );
        $this->assertStringContainsString( 'href="/leagues/ladies-doubles/2026/"', $output );
        $this->assertStringContainsString( 'Ladies Doubles', $output );
    }

    public function test_competition_teams_list_renders_presenter_links_keyed_by_team_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $service     = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_teams_for_competition' )->willReturn(
            [
                new Team_Competition_DTO( (object) [ 'team_id' => 11, 'team_name' => 'Westcliff 1', 'league_name' => 'Division One', 'club_id' => 3, 'club_shortcode' => 'Westcliff TC', 'num_players' => 6 ] ),
                new Team_Competition_DTO( (object) [ 'team_id' => 12, 'team_name' => 'Leigh 2', 'league_name' => 'Division Two', 'club_id' => 4, 'club_shortcode' => 'Leigh LTC', 'num_players' => 5 ] ),
            ]
        );

        $handler = new Get_Competition_Teams_Handler( $service, new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $vm      = $handler->handle( new Get_Competition_Teams_Query( competition_id: 5 ) );

        $this->assertSame( 'teams', $vm->template );
        $this->assertSame( [ 11, 12 ], array_keys( $vm->vars['team_links'] ) );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );

        $this->assertSame( [], $warnings, 'Rendering competition teams must produce zero warnings' );
        $this->assertStringContainsString( 'href="/league/division-one/2026/team/westcliff-1/"', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/club/westcliff-tc/"', $output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/"', $output );
        $this->assertStringContainsString( 'href="/league/division-two/2026/team/leigh-2/"', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/club/leigh-ltc/"', $output );
        $this->assert_no_unresolved_fallbacks( $output );
    }

    public function test_competition_clubs_list_and_detail_render_presenter_links(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $club        = Club_Test_Factory::create( 3, 'Westcliff Tennis Club', 'Westcliff TC' );
        $player      = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );
        $team        = (object) [ 'id' => 11, 'name' => 'Westcliff 1', 'league_title' => 'Division One', 'event_name' => "Men's Doubles", 'rank' => 1 ];

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_teams_by_query' )->willReturn( [ $team ] );
        $service->method( 'get_matches_for_competition' )->willReturn( [] );
        $service->method( 'get_players_for_competition_query' )->willReturn( [ $player ] );
        $service->method( 'get_club_details_for_competition' )->willReturn(
            [
                new Club_Competition_DTO( (object) [ 'club_id' => 3, 'club_name' => 'Westcliff Tennis Club', 'club_shortcode' => 'Westcliff TC', 'num_teams' => 2, 'num_players' => 9 ] ),
                new Club_Competition_DTO( (object) [ 'club_id' => 4, 'club_name' => 'Leigh Lawn', 'club_shortcode' => 'Leigh LTC', 'num_teams' => 1, 'num_players' => 4 ] ),
            ]
        );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturnArgument( 0 );

        $handler = new Get_Competition_Clubs_Handler( $service, $club_service, $stats, new Tournament_Presenter(), new Competition_Shortcode_Presenter(), $this->competition_season_service() );

        // List.
        $list_vm = $handler->handle( new Get_Competition_Clubs_Query( competition_id: 5 ) );
        $this->assertSame( 'clubs', $list_vm->template );
        $this->assertSame(
            [ 3 => '/summer-league/2026/club/westcliff-tc/', 4 => '/summer-league/2026/club/leigh-ltc/' ],
            $list_vm->vars['club_links']
        );
        $warnings = [];
        $output   = $this->render_view_model( $list_vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering competition clubs list must produce zero warnings' );
        $this->assertStringContainsString( 'href="/summer-league/2026/club/westcliff-tc/"', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/club/leigh-ltc/"', $output );

        // Detail.
        $detail_vm = $handler->handle( new Get_Competition_Clubs_Query( competition_id: 5, club_identifier: 'westcliff-tc' ) );
        $this->assertFalse( $detail_vm->is_error() );
        $this->assertSame( '/clubs/westcliff-tc/', $detail_vm->vars['club_url'] );
        $this->assertSame( [ 11 ], array_keys( $detail_vm->vars['team_links'] ) );
        $this->assertSame( [ 51 ], array_keys( $detail_vm->vars['player_links'] ) );

        $detail_warnings = [];
        $detail_output   = $this->render_view_model( $detail_vm, $detail_warnings );
        $this->assertSame( [], $detail_warnings, 'Rendering competition club detail must produce zero warnings' );
        $this->assertStringContainsString( 'href="/clubs/westcliff-tc/"', $detail_output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/team/westcliff-1/"', $detail_output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/"', $detail_output );
        $this->assertStringContainsString( 'href="/summer-league/2026/player/roger-federer/"', $detail_output );
    }

    public function test_competition_players_list_renders_player_links_keyed_by_player_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $service     = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_players_for_competition' )->willReturn(
            [
                new Players_List_DTO( (object) [ 'player_id' => 51, 'display_name' => 'Roger Federer', 'firstName' => 'Roger', 'surname' => 'Federer' ] ),
                new Players_List_DTO( (object) [ 'player_id' => 52, 'display_name' => 'Rafael Nadal', 'firstName' => 'Rafael', 'surname' => 'Nadal' ] ),
            ]
        );

        $handler = new Get_Competition_Players_Handler(
            $service,
            $player_service,
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $vm      = $handler->handle( new Get_Competition_Players_Query( competition_id: 5 ) );

        $this->assertSame( 'players', $vm->template );
        $this->assertSame(
            [ 51 => '/summer-league/2026/player/roger-federer/', 52 => '/summer-league/2026/player/rafael-nadal/' ],
            $vm->vars['player_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering competition players must produce zero warnings' );
        $this->assertStringContainsString( 'href="/summer-league/2026/player/roger-federer/"', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/player/rafael-nadal/"', $output );
    }

    public function test_event_standings_renders_league_links_keyed_by_league_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );
        $league_one  = (object) [ 'id' => 201, 'title' => 'Division One' ];
        $league_two  = (object) [ 'id' => 202, 'title' => 'Division Two' ];

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $query_service = $this->event_query_service( $this->event_repository_with_results( [ 'query_leagues' => [ $league_one, $league_two ] ] ) );
        $handler       = new Get_Event_Standings_Handler( $service, new Competition_Shortcode_Presenter(), $query_service );
        $vm      = $handler->handle( new Get_Event_Standings_Query( event_id: 77 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'standings', $vm->template );
        $this->assertSame(
            [ 201 => '/league/division-one/2026/', 202 => '/league/division-two/2026/' ],
            $vm->vars['league_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering event standings must produce zero warnings' );
        $this->assertStringContainsString( 'href="/league/division-one/2026/"', $output );
        $this->assertStringContainsString( 'href="/league/division-two/2026/"', $output );
        $this->assertStringContainsString( 'Division One', $output );
        $this->assertStringContainsString( 'Division Two', $output );
    }

    public function test_event_clubs_list_and_detail_render_presenter_links(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );

        $listed_club_a = Club_Test_Factory::create( 3, 'Westcliff Tennis Club', 'Westcliff TC', null, null, null, null, null, null, [ 'team_count' => 2, 'player_count' => 9 ] );
        $listed_club_b = Club_Test_Factory::create( 4, 'Leigh Lawn', 'Leigh LTC', null, null, null, null, null, null, [ 'team_count' => 1, 'player_count' => 4 ] );
        $event_club    = Club_Test_Factory::create(
            3,
            'Westcliff Tennis Club',
            'Westcliff TC',
            null,
            null,
            null,
            null,
            null,
            null,
            [
                'teams'   => [ (object) [ 'id' => 11, 'name' => 'Westcliff 1', 'league_title' => 'Division One', 'rank' => 1 ] ],
                'players' => [ Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' ) ],
            ]
        );
        $event_player = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );

        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $event_repository->method( 'query_clubs' )->willReturn(
            [
                (object) [ 'club_id' => 3, 'team_count' => 2 ],
                (object) [ 'club_id' => 4, 'team_count' => 1 ],
            ]
        );
        $event_repository->method( 'query_teams' )->willReturn(
            [
                (object) [
                    'id'           => 11,
                    'team_id'      => 11,
                    'club_id'      => 3,
                    'name'         => 'Westcliff 1',
                    'league_title' => 'Division One',
                    'rank'         => 1,
                    'roster'       => null,
                    'team_type'    => 'D',
                ],
            ]
        );
        $event_repository->method( 'query_players' )->willReturnCallback(
            static fn( int $event_id, bool $is_player_entry, array $args = [] ) => ! empty( $args['count'] ) ? 9 : [ (object) [ 'player_id' => 51 ] ]
        );
        $event_repository->method( 'query_matches' )->willReturn( [] );

        $club_repository = $this->createStub( Club_Repository_Interface::class );
        $club_repository->method( 'find_by_id' )->willReturnCallback(
            static fn( $id ) => match ( (int) $id ) {
                3       => $listed_club_a,
                4       => $listed_club_b,
                default => null,
            }
        );
        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturn( $event_player );

        $query_service = $this->event_query_service( $event_repository, null, null, null, null, $club_repository, $player_repository );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $event_club );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturnArgument( 0 );

        $handler = new Get_Event_Clubs_Handler( $service, $club_service, $stats, new Tournament_Presenter(), new Competition_Shortcode_Presenter(), $query_service );

        // List.
        $list_vm = $handler->handle( new Get_Event_Clubs_Query( event_id: 77 ) );
        $this->assertFalse( $list_vm->is_error() );
        $this->assertSame(
            [ 3 => '/leagues/mens-doubles/2026/club/westcliff-tc/', 4 => '/leagues/mens-doubles/2026/club/leigh-ltc/' ],
            $list_vm->vars['club_links']
        );
        $warnings = [];
        $output   = $this->render_view_model( $list_vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering event clubs list must produce zero warnings' );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/club/westcliff-tc/"', $output );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/club/leigh-ltc/"', $output );
        $this->assertStringContainsString( 'Westcliff Tennis Club', $output );
        $this->assertStringContainsString( 'Leigh Lawn', $output );

        // Detail.
        $detail_vm = $handler->handle( new Get_Event_Clubs_Query( event_id: 77, club_id: 'westcliff-tc' ) );
        $this->assertFalse( $detail_vm->is_error() );
        $this->assertSame( '/clubs/westcliff-tc/', $detail_vm->vars['club_url'] );
        $this->assertSame(
            [ 11 => [ 'team_link' => '/league/division-one/2026/team/westcliff-1/', 'league_link' => '/league/division-one/2026/' ] ],
            $detail_vm->vars['team_links']
        );
        $this->assertSame(
            [ 51 => '/leagues/mens-doubles/2026/player/roger-federer/' ],
            $detail_vm->vars['player_links']
        );

        $detail_warnings = [];
        $detail_output   = $this->render_view_model( $detail_vm, $detail_warnings );
        $this->assertSame( [], $detail_warnings, 'Rendering event club detail must produce zero warnings' );
        $this->assertStringContainsString( 'href="/clubs/westcliff-tc/"', $detail_output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/team/westcliff-1/"', $detail_output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/"', $detail_output );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/player/roger-federer/"', $detail_output );
    }

    public function test_event_teams_list_renders_presenter_links_keyed_by_team_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );
        $club_a      = Club_Test_Factory::create( 3, 'Westcliff Tennis Club', 'Westcliff TC' );
        $club_b      = Club_Test_Factory::create( 4, 'Leigh Lawn', 'Leigh LTC' );
        $event_repository = $this->event_repository_with_results(
            [
                'query_teams'   => [
                    $this->event_team_row( 11, 3, 'Westcliff 1', 'Division One' ),
                    $this->event_team_row( 12, 4, 'Leigh 2', 'Division Two' ),
                ],
                'query_players' => 6,
            ]
        );
        $query_service   = $this->event_query_service(
            $event_repository,
            null,
            null,
            null,
            $this->createStub( Team_Repository_Interface::class ),
            $this->club_repository_with( [ 3 => $club_a, 4 => $club_b ] ),
            $this->createStub( Player_Repository_Interface::class )
        );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Teams_Handler(
            $service,
            $this->createStub( Player_Stats_Service::class ),
            new Tournament_Presenter(),
            null,
            new Team_Details_Presenter( $this->createStub( Option_Gateway_Interface::class ), $this->createStub( Settings_Service::class ) ),
            new Competition_Shortcode_Presenter(),
            $query_service
        );
        $vm      = $handler->handle( new Get_Event_Teams_Query( event_id: 77, template: 'x' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'teams-x', $vm->template );
        $this->assertSame(
            [
                11 => [
                    'team_link'   => '/league/division-one/2026/team/westcliff-1/',
                    'club_link'   => '/leagues/mens-doubles/2026/club/westcliff-tc/',
                    'league_link' => '/league/division-one/2026/',
                ],
                12 => [
                    'team_link'   => '/league/division-two/2026/team/leigh-2/',
                    'club_link'   => '/leagues/mens-doubles/2026/club/leigh-ltc/',
                    'league_link' => '/league/division-two/2026/',
                ],
            ],
            $vm->vars['team_links']
        );

        // Render the default list template with the same link map.
        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'teams', $vm->vars, $vm->template_type ),
            $warnings
        );
        $this->assertSame( [], $warnings, 'Rendering event teams must produce zero warnings' );
        $this->assertStringContainsString( 'href="/league/division-one/2026/team/westcliff-1/"', $output );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/club/westcliff-tc/"', $output );
        $this->assertStringContainsString( 'href="/league/division-one/2026/"', $output );
        $this->assertStringContainsString( 'href="/league/division-two/2026/team/leigh-2/"', $output );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/club/leigh-ltc/"', $output );
        $this->assertStringContainsString( 'href="/league/division-two/2026/"', $output );
    }

    public function test_event_players_list_renders_player_links_keyed_by_player_id(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );
        $roger  = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );
        $rafael = Player_Test_Factory::create( 52, 'Rafael Nadal', null, 'Rafael', 'Nadal' );

        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturnCallback( static fn( $id ) => 51 === (int) $id ? $roger : $rafael );
        $event_repository  = $this->event_repository_with_results(
            [
                'query_players' => [ (object) [ 'player_id' => 51 ], (object) [ 'player_id' => 52 ] ],
            ]
        );
        $query_service     = $this->event_query_service( $event_repository, null, null, null, null, null, $player_repository );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Players_Handler(
            $service,
            $this->createStub( Player_Service::class ),
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(),
            $query_service
        );
        $vm      = $handler->handle( new Get_Event_Players_Query( event_id: 77 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players', $vm->template );
        $this->assertSame( [ 51, 52 ], array_keys( $vm->vars['player_links'] ) );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering event players must produce zero warnings' );
        $this->assertStringContainsString( 'href="' . $vm->vars['player_links'][51] . '"', $output );
        $this->assertStringContainsString( 'href="' . $vm->vars['player_links'][52] . '"', $output );
        $this->assertStringContainsString( 'roger-federer', $vm->vars['player_links'][51] );
        $this->assertStringContainsString( 'rafael-nadal', $vm->vars['player_links'][52] );
    }

    public function test_competition_and_event_single_player_render_match_group_links_keyed_by_group(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );
        $player      = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );

        $league_event_competition       = new stdClass();
        $league_event_competition->type = Competition_Type::LEAGUE;
        $league_event                   = new stdClass();
        $league_event->name             = "Men's Doubles";
        $league_event->competition      = $league_event_competition;
        $league                         = new stdClass();
        $league->title                  = 'Division One';
        $league->season                 = '2026';
        $league->event                  = $league_event;

        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_matches' )->willReturn( [ 'g1' => [ 'league' => $league, 'matches' => [] ] ] );
        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_stats' )->willReturn( [] );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $competition_handler = new Get_Competition_Players_Handler(
            $service,
            $player_service,
            $player_query_service,
            $player_stats_service,
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $event_handler       = new Get_Event_Players_Handler(
            $service,
            $player_service,
            $player_query_service,
            $player_stats_service,
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(),
            $this->event_query_service()
        );

        $vms = [
            'competition' => $competition_handler->handle( new Get_Competition_Players_Query( competition_id: 5, player_identifier: '51' ) ),
            'event'       => $event_handler->handle( new Get_Event_Players_Query( event_id: 77, player_id: 51 ) ),
        ];

        foreach ( $vms as $context => $vm ) {
            $this->assertFalse( $vm->is_error(), $context );
            $this->assertSame( [ 'g1' ], array_keys( $vm->vars['match_group_links'] ), $context );
            $this->assertSame( '/leagues/mens-doubles/2026/', $vm->vars['match_group_links']['g1']['event_link'], $context );
            $this->assertSame( '/league/division-one/2026/', $vm->vars['match_group_links']['g1']['league_link'], $context );

            $warnings = [];
            $output   = $this->render_view_model( $vm, $warnings );
            $this->assertSame( [], $warnings, "Rendering $context single player must produce zero warnings" );
            $this->assertStringContainsString( 'href="/leagues/mens-doubles/2026/"', $output, $context );
            $this->assertStringContainsString( 'href="/league/division-one/2026/"', $output, $context );
        }
    }

    public function test_cup_championship_competition_club_detail_renders_exact_championship_draw_and_team_urls(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
        $competition->id              = 9;
        $competition->name            = 'Summer Cup';
        $competition->type            = Competition_Type::CUP;
        $competition->is_championship = true;
        $competition->current_season  = [ 'name' => '2026', 'date_start' => '2026-05-01', 'date_end' => '2026-09-01' ];
        $competition->method( 'get_id' )->willReturn( 9 );
        $competition->method( 'get_type' )->willReturn( 'cup' );

        $club = Club_Test_Factory::create(
            id: 3,
            name: 'Westcliff Tennis Club',
            shortcode: 'Westcliff TC'
        );
        $player = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );
        $team = (object) [
            'id'         => 11,
            'name'       => 'Westcliff 1',
            'event_name' => "Men's Jubilee Cup",
            'rank'       => 1,
        ];

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_teams_by_query' )->willReturn( [ $team ] );
        $service->method( 'get_matches_for_competition' )->willReturn( [] );
        $service->method( 'get_players_for_competition_query' )->willReturn( [ $player ] );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturnArgument( 0 );

        $handler = new Get_Competition_Clubs_Handler( $service, $club_service, $stats, new Tournament_Presenter(), new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $vm      = $handler->handle( new Get_Competition_Clubs_Query( competition_id: 9, club_identifier: 'westcliff-tc' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( '/clubs/westcliff-tc/', $vm->vars['club_url'] );
        $this->assertSame(
            [
                11 => [
                    'team_link'   => '/cup/mens-jubilee-cup/2026/team/westcliff-1/',
                    'league_link' => '/cups/mens-jubilee-cup/2026/',
                ],
            ],
            $vm->vars['team_links']
        );
        $this->assertSame(
            [ 51 => '/summer-cup/2026/player/roger-federer/' ],
            $vm->vars['player_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering championship cup club detail must produce zero warnings' );
        $this->assertStringContainsString( 'href="/cup/mens-jubilee-cup/2026/team/westcliff-1/"', $output );
        $this->assertStringContainsString( 'href="/cups/mens-jubilee-cup/2026/"', $output );
        $this->assertStringContainsString( 'href="/summer-cup/2026/player/roger-federer/"', $output );
    }

    public function test_box_round_event_teams_and_standings_render_exact_round_season_urls(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition = $this->make_competition_stub();
        $event       = $this->make_event_stub( $competition );
        $event->is_box = true;

        $club_a = Club_Test_Factory::create( 3, 'Westcliff Tennis Club', 'Westcliff TC' );
        $team   = $this->event_team_row( 11, 3, 'Westcliff 1', 'Box One' );

        $league = (object) [ 'id' => 301, 'title' => 'Box One' ];

        $event_repository = $this->event_repository_with_results(
            [
                'query_teams'   => [ $team ],
                'query_players' => 4,
                'query_leagues' => [ $league ],
            ]
        );
        $query_service    = $this->event_query_service(
            $event_repository,
            null,
            null,
            null,
            $this->createStub( Team_Repository_Interface::class ),
            $this->club_repository_with( [ 3 => $club_a ] ),
            $this->createStub( Player_Repository_Interface::class )
        );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $presenter = new Competition_Shortcode_Presenter();

        // 1. Box event teams links
        $teams_handler = new Get_Event_Teams_Handler(
            $service,
            $this->createStub( Player_Stats_Service::class ),
            new Tournament_Presenter(),
            null,
            new Team_Details_Presenter( $this->createStub( Option_Gateway_Interface::class ), $this->createStub( Settings_Service::class ) ),
            $presenter,
            $query_service
        );
        $teams_vm = $teams_handler->handle( new Get_Event_Teams_Query( event_id: 77, template: 'x' ) );

        $this->assertFalse( $teams_vm->is_error() );
        $this->assertSame(
            [
                11 => [
                    'team_link'   => '/league/box-one/2026/team/westcliff-1/',
                    'club_link'   => '/leagues/mens-doubles/2026/club/westcliff-tc/',
                    'league_link' => '/league/box-one/round-2026/',
                ],
            ],
            $teams_vm->vars['team_links']
        );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'teams', $teams_vm->vars, $teams_vm->template_type ),
            $warnings
        );
        $this->assertSame( [], $warnings, 'Rendering box event teams must produce zero warnings' );
        $this->assertStringContainsString( 'href="/league/box-one/round-2026/"', $output );
        $this->assertStringContainsString( 'href="/league/box-one/2026/team/westcliff-1/"', $output );

        // 2. Box event standings links
        $standings_handler = new Get_Event_Standings_Handler( $service, $presenter, $query_service );
        $standings_vm      = $standings_handler->handle( new Get_Event_Standings_Query( event_id: 77 ) );

        $this->assertFalse( $standings_vm->is_error() );
        $this->assertSame(
            [ 301 => '/league/box-one/round-2026/' ],
            $standings_vm->vars['league_links']
        );

        $standings_warnings = [];
        $standings_output   = $this->render_view_model( $standings_vm, $standings_warnings );
        $this->assertSame( [], $standings_warnings, 'Rendering box event standings must produce zero warnings' );
        $this->assertStringContainsString( 'href="/league/box-one/round-2026/"', $standings_output );
    }

    public function test_competition_players_list_and_detail_handle_integer_season_without_type_error(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition                 = $this->make_competition_stub();
        $competition->current_season = [ 'name' => 2024 ];

        $player = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );

        $league_event_competition       = new stdClass();
        $league_event_competition->type = Competition_Type::LEAGUE;
        $league_event                   = new stdClass();
        $league_event->name             = "Men's Doubles";
        $league_event->competition      = $league_event_competition;
        $league                         = new stdClass();
        $league->event                  = $league_event;
        $league->title                  = 'Division One';
        $league->season                 = '2024';

        $match_group   = [ 'league' => $league, 'matches' => [] ];

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_players_for_competition' )->willReturn(
            [
                new Players_List_DTO( (object) [ 'player_id' => 51, 'display_name' => 'Roger Federer', 'firstName' => 'Roger', 'surname' => 'Federer' ] ),
            ]
        );
        $player_service->method( 'get_player_by_name' )->willReturn( $player );

        $query_service = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [ '2024-05-01' => $match_group ] );

        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );

        $handler = new Get_Competition_Players_Handler(
            $comp_service,
            $player_service,
            $query_service,
            $stats_service,
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(), $this->competition_season_service() );

        // 1. List with integer season
        $list_vm = $handler->handle( new Get_Competition_Players_Query( competition_id: 5 ) );
        $this->assertFalse( $list_vm->is_error() );
        $this->assertSame( 'players', $list_vm->template );
        $this->assertSame(
            [ 51 => '/summer-league/2024/player/roger-federer/' ],
            $list_vm->vars['player_links']
        );
        $warnings = [];
        $output   = $this->render_view_model( $list_vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering competition players list with integer season must produce zero warnings' );
        $this->assertStringContainsString( 'href="/summer-league/2024/player/roger-federer/"', $output );

        // 2. Single player detail with integer season
        $detail_vm = $handler->handle( new Get_Competition_Players_Query( competition_id: 5, player_identifier: 'roger-federer' ) );
        $this->assertFalse( $detail_vm->is_error() );
        $this->assertSame( 'players', $detail_vm->template );
        $this->assertNotNull( $detail_vm->vars['player_vm'] );
        $this->assertSame(
            [
                '2024-05-01' => [
                    'event_link'  => '/leagues/mens-doubles/2024/',
                    'league_link' => '/league/division-one/2024/',
                ],
            ],
            $detail_vm->vars['match_group_links']
        );
        $detail_warnings = [];
        $detail_output   = $this->render_view_model( $detail_vm, $detail_warnings );
        $this->assertSame( [], $detail_warnings, 'Rendering competition player detail with integer season must produce zero warnings' );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2024/"', $detail_output );
        $this->assertStringContainsString( 'href="/league/division-one/2024/"', $detail_output );
    }

    public function test_event_single_player_handles_integer_season_without_type_error(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition           = $this->make_competition_stub();
        $event                 = $this->make_event_stub( $competition );
        $event->current_season = [ 'name' => 2024 ];

        $player = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );

        $league_event_competition       = new stdClass();
        $league_event_competition->type = Competition_Type::LEAGUE;
        $league_event                   = new stdClass();
        $league_event->name             = "Men's Doubles";
        $league_event->competition      = $league_event_competition;
        $league                         = new stdClass();
        $league->event                  = $league_event;
        $league->title                  = 'Division One';
        $league->season                 = '2024';

        $match_group   = [ 'league' => $league, 'matches' => [] ];

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $query_service = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [ '2024-05-01' => $match_group ] );

        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );

        $handler = new Get_Event_Players_Handler(
            $comp_service,
            $player_service,
            $query_service,
            $stats_service,
            new Player_Presenter(),
            new Competition_Shortcode_Presenter(),
            $this->event_query_service()
        );

        $vm = $handler->handle( new Get_Event_Players_Query( event_id: 77, player_id: 51 ) );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players', $vm->template );
        $this->assertSame(
            [
                '2024-05-01' => [
                    'event_link'  => '/leagues/mens-doubles/2024/',
                    'league_link' => '/league/division-one/2024/',
                ],
            ],
            $vm->vars['match_group_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering event player detail with integer season must produce zero warnings' );
        $this->assertStringContainsString( 'href="/leagues/mens-doubles/2024/"', $output );
        $this->assertStringContainsString( 'href="/league/division-one/2024/"', $output );
    }

    public function test_get_event_handler_header_handles_integer_season_without_type_error(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition           = $this->make_competition_stub();
        $event                 = $this->make_event_stub( $competition );
        $event->current_season = [ 'name' => 2024 ];
        $event->method( 'get_seasons' )->willReturn( [ [ 'name' => 2024 ] ] );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Handler( $service, new Competition_Shortcode_Presenter(), $this->event_query_service() );
        $vm      = $handler->handle( new Get_Event_Query( event_id: 77 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'event', $vm->template );
        $this->assertSame( '2024', $vm->vars['curr_season'] );
        $this->assertNotNull( $vm->vars['event_header'] );
    }

    public function test_get_competition_clubs_detail_with_players_handles_integer_season_without_type_error(): void {
        unset( $GLOBALS['racketmanager'] );

        $competition                 = $this->make_competition_stub();
        $competition->current_season = [ 'name' => 2024 ];

        $club   = Club_Test_Factory::create( 3, 'Westcliff Tennis Club', 'Westcliff TC' );
        $player = Player_Test_Factory::create( 51, 'Roger Federer', null, 'Roger', 'Federer' );

        $service = $this->createStub( Competition_Service::class );
        $service->method( 'get_by_id' )->willReturn( $competition );
        $service->method( 'get_teams_by_query' )->willReturn( [] );
        $service->method( 'get_matches_for_competition' )->willReturn( [] );
        $service->method( 'get_players_for_competition_query' )->willReturn( [ $player ] );
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturnArgument( 0 );

        $handler = new Get_Competition_Clubs_Handler( $service, $club_service, $stats, new Tournament_Presenter(), new Competition_Shortcode_Presenter(), $this->competition_season_service() );
        $vm      = $handler->handle( new Get_Competition_Clubs_Query( competition_id: 5, club_identifier: 'westcliff-tc' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( '/clubs/westcliff-tc/', $vm->vars['club_url'] );
        $this->assertSame(
            [ 51 => '/summer-league/2024/player/roger-federer/' ],
            $vm->vars['player_links']
        );

        $warnings = [];
        $output   = $this->render_view_model( $vm, $warnings );
        $this->assertSame( [], $warnings, 'Rendering competition club detail with integer season must produce zero warnings' );
        $this->assertStringContainsString( 'href="/summer-league/2024/player/roger-federer/"', $output );
    }
}
