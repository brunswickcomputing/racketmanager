<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Query;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Query;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Query;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Query;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Query;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

class General_Shortcode_Handlers_Test extends TestCase {
    private RacketManager $racketmanager;
    private View_Renderer_Interface $renderer;
    private Shortcode_View_Presenter $view_presenter;

    protected function setUp(): void {
        parent::setUp();

        $this->racketmanager             = $this->createStub( RacketManager::class );
        $this->racketmanager->site_name  = 'Test Club';
        $this->renderer                  = new class implements View_Renderer_Interface {
            public string $last_template = '';
            public array $last_vars      = [];

            public function render( string $template, object|array $model = [], array $additional_vars = [] ): void {
                $this->last_template = $template;
                $this->last_vars     = $additional_vars;
            }

            public function render_to_string( string $template, object|array $model = [], array $additional_vars = [] ): string {
                $this->last_template = $template;
                $this->last_vars     = $additional_vars;
                return "rendered:{$template}";
            }

            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $template_renderer     = new Shortcode_Template_Renderer( $this->renderer );
        $this->view_presenter = new Shortcode_View_Presenter( $template_renderer );
    }

    public function test_get_players_handler_success(): void {
        $handler = new Get_Players_Handler();
        $query   = new Get_Players_Query(
            template: 'custom',
            search_string: 'Smith',
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'players-custom', $vm->template );
        $this->assertSame( 'Smith', $vm->vars['search_string'] );
        $this->assertSame( [], $vm->vars['favourites'] );
    }

    public function test_get_players_query_from_atts(): void {
        $query = Get_Players_Query::from_atts(
            [ 'template' => 'grid' ],
            [ 'q' => 'Jones' ],
            []
        );

        $this->assertSame( 'grid', $query->template );
        $this->assertSame( 'Jones', $query->search_string );
    }

    public function test_get_player_handler_not_found(): void {
        $handler = new Get_Player_Handler();
        $query   = new Get_Player_Query(
            template: null,
            player_name: 'non-existent',
            btm: null
        );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Player not found', $vm->error_message );
    }

    public function test_get_player_handler_success(): void {
        $player = new class {
            public int $id = 42;
            public ?object $stats = null;
            public function get_id(): int { return 42; }
            public function get_career_stats(): object { return (object) [ 'played' => 10, 'won' => 8 ]; }
            public function get_competitions( array $args = [] ): array { return [ 'comp1' ]; }
        };

        $GLOBALS['wp_stubs_players']['btm']['BTM123'] = $player;

        $registration_service = $this->createStub( Registration_Service::class );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [ 'Club A' ] );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_titles_for_player' )->willReturn( [ 'Title 1' ] );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournaments_for_player' )->willReturn( [ 'Tourn 1' ] );

        $handler = new Get_Player_Handler( $registration_service, $player_service, $tournament_service );
        $query   = new Get_Player_Query( template: 'profile', player_name: null, btm: 'BTM123' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player-profile', $vm->template );
        $this->assertSame( $player, $vm->vars['player'] );
        $this->assertSame( [ 'Club A' ], $vm->vars['clubs'] );
        $this->assertSame( [ 'Title 1' ], $vm->vars['titles'] );
        $this->assertSame( [ 'Tourn 1' ], $vm->vars['player_competitions']['tournament'] );
        $this->assertSame( [ 'comp1' ], $vm->vars['player_competitions']['cup'] );
        $this->assertSame( [ 'comp1' ], $vm->vars['player_competitions']['league'] );
        $this->assertSame( 10, $player->stats->played );
    }

    public function test_get_player_query_parsing(): void {
        $query = Get_Player_Query::from_atts(
            [ 'template' => 'card' ],
            [ 'player_id' => 'john-doe', 'btm' => 'BTM123' ]
        );

        $this->assertSame( 'card', $query->template );
        $this->assertSame( 'john doe', $query->player_name );
        $this->assertSame( 'BTM123', $query->btm );
    }

    public function test_get_favourites_handler_requires_login(): void {
        $handler = new Get_Favourites_Handler();
        $query   = new Get_Favourites_Query(
            template: null,
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'You must be logged in to view favourites', $vm->error_message );
    }

    public function test_get_invoice_handler_not_found(): void {
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice missing' ) );

        $handler = new Get_Invoice_Handler( $finance_service, $this->racketmanager );
        $query   = new Get_Invoice_Query( id: 999 );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Invoice missing', $vm->error_message );
    }

    public function test_get_invoice_handler_success(): void {
        $invoice = $this->createStub( Invoice::class );
        $invoice->method( 'get_invoice_number' )->willReturn( 2026001 );

        $invoice_dto          = $this->createStub( Invoice_Full_Details_DTO::class );
        $invoice_dto->invoice = $invoice;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willReturn( $invoice_dto );

        $handler = new Get_Invoice_Handler( $finance_service, $this->racketmanager );
        $query   = new Get_Invoice_Query( id: 10 );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'invoice', $vm->template );
        $this->assertSame( 2026001, $vm->vars['invoice_number'] );
        $this->assertSame( 'Test Club', $vm->vars['organisation_name'] );
    }

    public function test_get_invoice_query_parsing_with_non_numeric_id(): void {
        $query = Get_Invoice_Query::from_atts( [ 'id' => 'abc-invalid', 'template' => 'custom' ] );

        $this->assertSame( 0, $query->id );
        $this->assertSame( 'custom', $query->template );
    }

    public function test_get_purchase_order_handler_modal_error(): void {
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'PO not found' ) );

        $handler = new Get_Purchase_Order_Handler( $finance_service );
        $query   = new Get_Purchase_Order_Query( id: 5, modal: 'true' );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'modal', $vm->error_variant );
    }

    public function test_get_purchase_order_handler_success(): void {
        $invoice     = $this->createStub( Invoice::class );
        $invoice->id = 5;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )
            ->willReturn( $invoice );

        $handler = new Get_Purchase_Order_Handler( $finance_service );
        $query   = new Get_Purchase_Order_Query( id: 5, modal: 'true', template: 'dialog' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'purchase-order-modal-dialog', $vm->template );
        $this->assertSame( 'club', $vm->template_type );
        $this->assertSame( $invoice, $vm->vars['invoice'] );
        $this->assertSame( 'true', $vm->vars['modal'] );
    }

    public function test_get_purchase_order_query_parsing_with_non_numeric_id(): void {
        $query = Get_Purchase_Order_Query::from_atts( [ 'id' => 'xyz-invalid', 'modal' => 'true' ] );

        $this->assertSame( 0, $query->id );
        $this->assertSame( 'true', $query->modal );
    }

    public function test_get_memberships_handler_requires_login(): void {
        $handler = new Get_Memberships_Handler();
        $query   = new Get_Memberships_Query( is_logged_in: false, user_id: null );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'You must be logged in to view memberships', $vm->error_message );
    }

    public function test_get_memberships_handler_success(): void {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( 42 );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $reg_service = $this->createStub( Registration_Service::class );
        $reg_service->method( 'get_clubs_for_player' )->willReturnCallback( function ( $id, $type = null ) {
            return 'removed' === $type ? [ 'Old Club' ] : [ 'Current Club' ];
        } );

        $handler = new Get_Memberships_Handler( $player_service, $reg_service );
        $query   = new Get_Memberships_Query( is_logged_in: true, user_id: 42, template: 'compact' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player-clubs-compact', $vm->template );
        $this->assertSame( 'account', $vm->template_type );
        $this->assertSame( [ 'Current Club' ], $vm->vars['clubs'] );
        $this->assertSame( [ 'Old Club' ], $vm->vars['clubs_archive'] );
    }

    public function test_get_player_search_handler_success(): void {
        $this->racketmanager->method( 'get_all_players' )->willReturn( [ 'player1', 'player2' ] );

        $handler = new Get_Player_Search_Handler( $this->racketmanager );
        $query   = new Get_Player_Search_Query( search: 'Roger' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players-list', $vm->template );
        $this->assertSame( [ 'player1', 'player2' ], $vm->vars['players'] );
    }

    public function test_get_team_order_handler_no_clubs(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [] );

        $handler = new Get_Team_Order_Handler( $club_service, $this->racketmanager );
        $query   = new Get_Team_Order_Query();

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'No clubs found', $vm->error_message );
    }

    public function test_get_team_order_handler_success(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [ new stdClass() ] );

        $this->racketmanager->method( 'get_events' )->willReturn( [ new stdClass() ] );

        $handler = new Get_Team_Order_Handler( $club_service, $this->racketmanager );
        $query   = new Get_Team_Order_Query( template: 'entry' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'team-order-entry', $vm->template );
        $this->assertNotEmpty( $vm->vars['clubs'] );
        $this->assertNotEmpty( $vm->vars['events'] );
        $this->assertIsArray( $vm->vars['event_types'] );
        $this->assertIsArray( $vm->vars['age_groups'] );
    }

    public function test_get_alert_handler_success(): void {
        $handler = new Get_Alert_Handler();
        $query   = new Get_Alert_Query( msg: 'Attention needed', type: 'warning', template: 'banner' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'alert-banner', $vm->template );
        $this->assertSame( 'Attention needed', $vm->vars['msg'] );
        $this->assertSame( 'warning', $vm->vars['class'] );
    }

    public function test_real_template_alert_rendering(): void {
        $real_renderer     = new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $real_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $handler = new Get_Alert_Handler();
        $query   = new Get_Alert_Query( msg: 'Notice message', type: 'info' );
        $vm      = $handler->handle( $query );

        $output = $presenter->render( $vm );

        $this->assertStringContainsString( 'Notice message', $output );
        $this->assertStringContainsString( 'alert--info', $output );
    }

    public function test_real_template_players_list_rendering(): void {
        $real_renderer     = new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $real_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $handler = new Get_Player_Search_Handler( $this->racketmanager );
        $this->racketmanager->method( 'get_all_players' )->willReturn( [] );

        $query = new Get_Player_Search_Query();
        $vm    = $handler->handle( $query );

        $output = $presenter->render( $vm );

        $this->assertStringContainsString( 'No players found', $output );
    }

    public function test_adapter_and_facade_delegation(): void {
        $players_handler        = new Get_Players_Handler();
        $player_handler         = new Get_Player_Handler();
        $favourites_handler     = new Get_Favourites_Handler();
        $invoice_handler        = new Get_Invoice_Handler( null, $this->racketmanager );
        $purchase_order_handler = new Get_Purchase_Order_Handler();
        $memberships_handler    = new Get_Memberships_Handler();
        $player_search_handler  = new Get_Player_Search_Handler( $this->racketmanager );
        $team_order_handler     = new Get_Team_Order_Handler( null, $this->racketmanager );
        $alert_handler          = new Get_Alert_Handler();

        $adapter = new General_Shortcode_Adapter(
            $this->racketmanager,
            $this->view_presenter,
            $players_handler,
            $player_handler,
            $favourites_handler,
            $invoice_handler,
            $purchase_order_handler,
            $memberships_handler,
            $player_search_handler,
            $team_order_handler,
            $alert_handler
        );

        $container = new Simple_Container();
        $container->set( 'general_shortcode_adapter', fn() => $adapter );
        $this->racketmanager->container = $container;

        $facade = new Shortcodes( $this->racketmanager );

        $this->assertNotEmpty( $facade->show_players( [] ) );
        $this->assertNotEmpty( $facade->show_player_search( [] ) );
        $this->assertNotEmpty( $facade->show_alert( [ 'msg' => 'Hi', 'type' => 'info' ] ) );
    }
}
