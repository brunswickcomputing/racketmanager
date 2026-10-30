<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Application\General\DTOs\Alert_Result;
use Racketmanager\Application\General\DTOs\Favourites_Result;
use Racketmanager\Application\General\DTOs\Invoice_Result;
use Racketmanager\Application\General\DTOs\Memberships_Result;
use Racketmanager\Application\General\DTOs\Player_Result;
use Racketmanager\Application\General\DTOs\Player_Search_Result;
use Racketmanager\Application\General\DTOs\Players_Result;
use Racketmanager\Application\General\DTOs\Purchase_Order_Result;
use Racketmanager\Application\General\DTOs\Team_Order_Result;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

#[AllowMockObjectsWithoutExpectations]
class General_Shortcode_Adapter_Test extends TestCase {
    use Creates_Test_Container;

    private General_Shortcode_Adapter $adapter;
    private Get_Players_Handler $players_handler;
    private Get_Player_Handler $player_handler;
    private Get_Favourites_Handler $favourites_handler;
    private Get_Invoice_Handler $invoice_handler;
    private Get_Purchase_Order_Handler $purchase_order_handler;
    private Get_Memberships_Handler $memberships_handler;
    private Get_Player_Search_Handler $player_search_handler;
    private Get_Team_Order_Handler $team_order_handler;
    private Get_Alert_Handler $alert_handler;
    private Shortcode_View_Presenter $view_presenter;
    private Shortcode_Template_Renderer $template_renderer;
    private Shortcodes $legacy_handler;

    protected function setUp(): void {
        parent::setUp();

        $racketmanager                  = $this->createStub( RacketManager::class );
        $this->players_handler        = $this->createStub( Get_Players_Handler::class );
        $this->player_handler         = $this->createStub( Get_Player_Handler::class );
        $this->favourites_handler     = $this->createStub( Get_Favourites_Handler::class );
        $this->invoice_handler        = $this->createStub( Get_Invoice_Handler::class );
        $this->purchase_order_handler = $this->createStub( Get_Purchase_Order_Handler::class );
        $this->memberships_handler    = $this->createStub( Get_Memberships_Handler::class );
        $this->player_search_handler  = $this->createStub( Get_Player_Search_Handler::class );
        $this->team_order_handler     = $this->createStub( Get_Team_Order_Handler::class );
        $this->alert_handler          = $this->createStub( Get_Alert_Handler::class );
        $this->view_presenter         = $this->createMock( Shortcode_View_Presenter::class );
        $this->template_renderer      = $this->createMock( Shortcode_Template_Renderer::class );
        $this->legacy_handler         = $this->createStub( Shortcodes::class );

        $this->adapter = new General_Shortcode_Adapter(
            racketmanager: $racketmanager,
            view_presenter: $this->view_presenter,
            players_handler: $this->players_handler,
            player_handler: $this->player_handler,
            favourites_handler: $this->favourites_handler,
            invoice_handler: $this->invoice_handler,
            purchase_order_handler: $this->purchase_order_handler,
            memberships_handler: $this->memberships_handler,
            player_search_handler: $this->player_search_handler,
            team_order_handler: $this->team_order_handler,
            alert_handler: $this->alert_handler,
            template_renderer: $this->template_renderer
        );
    }

    public function test_show_players_renders_via_handler(): void {
        $result = new Players_Result( players: [ 'p1' ], search_string: 'test', template: 'custom' );
        $this->players_handler->method( 'handle' )->willReturn( $result );

        $this->template_renderer
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( 'players-list', [ 'players' => [ 'p1' ] ] )
            ->willReturn( 'RENDERED_LIST' );

        $this->view_presenter
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( function( Shortcode_View_Model $vm ) {
                return $vm->template === 'players-custom' &&
                       isset( $vm->vars['search_results'] ) &&
                       $vm->vars['search_results'] === 'RENDERED_LIST';
            } ) )
            ->willReturn( '<div class="players-list"></div>' );

        $output = $this->adapter->show_players( [ 'template' => 'custom' ] );
        $this->assertSame( '<div class="players-list"></div>', $output );
    }

    public function test_show_players_renders_empty_search_results(): void {
        $this->players_handler->method( 'handle' )->willReturn( new Players_Result( search_string: 'No Match' ) );
        $this->template_renderer
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( 'players-list', [ 'players' => [] ] )
            ->willReturn( '<p>No players found</p>' );

        $this->view_presenter
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( function( Shortcode_View_Model $vm ) {
                return $vm->template === 'players'
                    && ( $vm->vars['search_results'] ?? null ) === '<p>No players found</p>';
            } ) )
            ->willReturn( '<div class="players">No players found</div>' );

        $this->assertSame( '<div class="players">No players found</div>', $this->adapter->show_players() );
    }

    public function test_show_player_renders_via_handler(): void {
        $this->player_handler
            ->method( 'handle' )
            ->willReturn( new Player_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="player-profile">John Doe</div>' );

        $output = $this->adapter->show_player( [ 'player_id' => 'john-doe' ] );
        $this->assertSame( '<div class="player-profile">John Doe</div>', $output );
    }

    public function test_show_favourites_renders_via_handler(): void {
        $this->favourites_handler
            ->method( 'handle' )
            ->willReturn( new Favourites_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="favourites">Favs</div>' );

        $output = $this->adapter->show_favourites( [] );
        $this->assertSame( '<div class="favourites">Favs</div>', $output );
    }

    public function test_show_invoice_renders_via_handler(): void {
        $this->invoice_handler
            ->method( 'handle' )
            ->willReturn( new Invoice_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="invoice">INV-100</div>' );

        $output = $this->adapter->show_invoice( [ 'id' => 100 ] );
        $this->assertSame( '<div class="invoice">INV-100</div>', $output );
    }

    public function test_show_purchase_order_renders_via_handler(): void {
        $this->purchase_order_handler
            ->method( 'handle' )
            ->willReturn( new Purchase_Order_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="po-modal">PO</div>' );

        $output = $this->adapter->show_purchase_order( [ 'id' => 100, 'modal' => 'true' ] );
        $this->assertSame( '<div class="po-modal">PO</div>', $output );
    }

    public function test_show_purchase_order_renders_modal_error(): void {
        $this->purchase_order_handler->method( 'handle' )->willReturn( Purchase_Order_Result::error( 'Invoice not found' ) );
        $this->view_presenter
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( function( Shortcode_View_Model $vm ) {
                return $vm->is_error()
                    && $vm->error_message === 'Invoice not found'
                    && $vm->error_variant === 'modal';
            } ) )
            ->willReturn( '<div class="alert-modal">Invoice not found</div>' );

        $this->assertSame(
            '<div class="alert-modal">Invoice not found</div>',
            $this->adapter->show_purchase_order( [ 'id' => 100, 'modal' => 'true' ] )
        );
    }

    public function test_show_memberships_renders_via_handler(): void {
        $this->memberships_handler
            ->method( 'handle' )
            ->willReturn( new Memberships_Result( template: 'custom' ) );
        $this->view_presenter
            ->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( function( Shortcode_View_Model $vm ) {
                return $vm->template === 'player-clubs-custom'
                    && $vm->vars === [ 'clubs' => [], 'clubs_archive' => [] ]
                    && $vm->template_type === 'account';
            } ) )
            ->willReturn( '<div class="memberships">Clubs</div>' );

        $output = $this->adapter->show_memberships( [] );
        $this->assertSame( '<div class="memberships">Clubs</div>', $output );
    }

    public function test_show_player_search_renders_via_handler(): void {
        $this->player_search_handler
            ->method( 'handle' )
            ->willReturn( new Player_Search_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="player-search">Results</div>' );

        $output = $this->adapter->show_player_search( [ 'search' => 'Smith' ] );
        $this->assertSame( '<div class="player-search">Results</div>', $output );
    }

    public function test_show_team_order_renders_via_handler(): void {
        $this->team_order_handler
            ->method( 'handle' )
            ->willReturn( new Team_Order_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="team-order">Order</div>' );

        $output = $this->adapter->show_team_order( [] );
        $this->assertSame( '<div class="team-order">Order</div>', $output );
    }

    public function test_show_alert_renders_via_handler(): void {
        $this->alert_handler
            ->method( 'handle' )
            ->willReturn( new Alert_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="alert alert-info">Test message</div>' );

        $output = $this->adapter->show_alert( [ 'type' => 'info', 'msg' => 'Test message' ] );
        $this->assertSame( '<div class="alert alert-info">Test message</div>', $output );
    }

    public function test_load_template_renders_via_template_renderer(): void {
        $this->template_renderer
            ->method( 'render' )
            ->willReturn( '<div>hello</div>' );

        $output = $this->adapter->load_template( 'alert', [ 'msg' => 'hello' ], false );
        $this->assertSame( '<div>hello</div>', $output );
    }

    public function test_facade_delegates_to_adapter(): void {
        $racketmanager = $this->create_app_with_services( [ 'general_shortcode_adapter' => $this->adapter ] );

        $facade = new Shortcodes( $racketmanager );

        $this->players_handler
            ->method( 'handle' )
            ->willReturn( new Players_Result() );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="players">Players List</div>' );

        $this->assertSame( '<div class="players">Players List</div>', $facade->show_players( [] ) );
    }
}
