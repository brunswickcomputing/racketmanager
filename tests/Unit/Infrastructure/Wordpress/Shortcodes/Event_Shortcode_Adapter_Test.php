<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\DTOs\Event_Clubs_Result;
use Racketmanager\Application\Event\DTOs\Event_Draw_Result;
use Racketmanager\Application\Event\DTOs\Event_Dropdown_Result;
use Racketmanager\Application\Event\DTOs\Event_Matches_Result;
use Racketmanager\Application\Event\DTOs\Event_Page_Result;
use Racketmanager\Application\Event\DTOs\Event_Partner_Result;
use Racketmanager\Application\Event\DTOs\Event_Players_Result;
use Racketmanager\Application\Event\DTOs\Event_Standings_Result;
use Racketmanager\Application\Event\DTOs\Event_Team_Matches_Result;
use Racketmanager\Application\Event\DTOs\Event_Teams_Result;
use Racketmanager\Application\Event\DTOs\Team_Order_Players_Result;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Event_Shortcode_Adapter_Test extends TestCase {
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

    private function create_adapter( array $overrides = [] ): Event_Shortcode_Adapter {
        return new Event_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->createStub( RacketManager::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['event_handler'] ?? $this->createStub( Get_Event_Handler::class ),
            $overrides['standings_handler'] ?? $this->createStub( Get_Event_Standings_Handler::class ),
            $overrides['draw_handler'] ?? $this->createStub( Get_Event_Draw_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Event_Matches_Handler::class ),
            $overrides['clubs_handler'] ?? $this->createStub( Get_Event_Clubs_Handler::class ),
            $overrides['teams_handler'] ?? $this->createStub( Get_Event_Teams_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Event_Players_Handler::class ),
            $overrides['partner_handler'] ?? $this->createStub( Get_Event_Partner_Handler::class ),
            $overrides['team_matches_handler'] ?? $this->createStub( Get_Event_Team_Matches_Handler::class ),
            $overrides['team_order_players_handler'] ?? $this->createStub( Get_Team_Order_Players_Handler::class ),
            $overrides['dropdown_handler'] ?? $this->createStub( Get_Event_Dropdown_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    public function test_all_shortcode_methods_dispatch_to_handlers(): void {
        $methods = [
            'show_event'              => [ 'event_handler', Get_Event_Handler::class, new Event_Page_Result() ],
            'show_event_standings'    => [ 'standings_handler', Get_Event_Standings_Handler::class, new Event_Standings_Result() ],
            'show_event_draw'         => [ 'draw_handler', Get_Event_Draw_Handler::class, new Event_Draw_Result() ],
            'show_event_matches'      => [ 'matches_handler', Get_Event_Matches_Handler::class, new Event_Matches_Result() ],
            'show_event_clubs'        => [ 'clubs_handler', Get_Event_Clubs_Handler::class, new Event_Clubs_Result() ],
            'show_event_teams'        => [ 'teams_handler', Get_Event_Teams_Handler::class, new Event_Teams_Result() ],
            'show_event_players'      => [ 'players_handler', Get_Event_Players_Handler::class, new Event_Players_Result() ],
            'show_event_partner'      => [ 'partner_handler', Get_Event_Partner_Handler::class, new Event_Partner_Result() ],
            'show_event_team_matches' => [ 'team_matches_handler', Get_Event_Team_Matches_Handler::class, new Event_Team_Matches_Result() ],
            'show_team_order_players' => [ 'team_order_players_handler', Get_Team_Order_Players_Handler::class, new Team_Order_Players_Result() ],
            'show_dropdown'           => [ 'dropdown_handler', Get_Event_Dropdown_Handler::class, new Event_Dropdown_Result() ],
        ];

        foreach ( $methods as $method => [ $key, $class, $result_dto ] ) {
            $handler = $this->createMock( $class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result_dto );
            $presenter = $this->createMock( Shortcode_View_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( "output_for_{$method}" );

            $adapter = $this->create_adapter( [
                $key             => $handler,
                'view_presenter' => $presenter,
            ] );

            $result = $adapter->$method( [ 'id' => 10 ] );
            $this->assertSame( "output_for_{$method}", $result, "Failed for {$method}" );
        }
    }

    public function test_partner_error_is_rendered_as_modal_and_other_errors_as_generic(): void {
        foreach ( [
            [ 'show_event_partner', 'partner_handler', Get_Event_Partner_Handler::class, Event_Partner_Result::error( 'Partner unavailable' ), 'modal', 'Partner unavailable' ],
            [ 'show_event', 'event_handler', Get_Event_Handler::class, Event_Page_Result::error( 'Event unavailable' ), null, 'Event unavailable' ],
        ] as [ $method, $key, $class, $result, $variant, $message ] ) {
            $handler = $this->createMock( $class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );
            $presenter = $this->createMock( Shortcode_View_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )
                ->with( $this->callback( function ( Shortcode_View_Model $vm ) use ( $variant, $message ): bool {
                    $this->assertTrue( $vm->is_error() );
                    $this->assertSame( $message, $vm->error_message );
                    $this->assertSame( $variant, $vm->error_variant );
                    return true;
                } ) )->willReturn( 'error rendered' );

            $this->assertSame( 'error rendered', $this->create_adapter( [
                $key             => $handler,
                'view_presenter' => $presenter,
            ] )->$method( [ 'id' => 10 ] ) );
        }
    }

    public function test_teams_list_uses_default_team_details_presenter_when_not_injected(): void {
        $event = (object) [ 'name' => 'Summer Open', 'competition' => (object) [ 'type' => 'league' ] ];
        $vm = $this->create_adapter()->present_teams( new Event_Teams_Result(
            event: $event,
            teams: [],
            curr_season: '2026',
            template: 'teams-list'
        ) );

        $this->assertSame( 'teams-list', $vm->template );
        $this->assertSame( [], $vm->vars['team_rows'] );
        $this->assertSame( 'event', $vm->template_type );
    }

    public function test_normalize_atts_resolves_event_id_and_name_from_query_vars_and_globals(): void {
        global $wp;

        $handler = $this->createMock( Get_Event_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( function ( $query ) {
                return 42 === $query->event_id;
            } ) )
            ->willReturn( new Event_Page_Result() );

        $presenter = $this->createStub( Shortcode_View_Presenter::class );
        $presenter->method( 'render' )->willReturn( 'rendered_event' );

        $wp = (object) [ 'query_vars' => [ 'event_id' => '42' ] ];
        $adapter = $this->create_adapter( [
            'event_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->show_event( [] );
        $this->assertSame( 'rendered_event', $output );

        // Test fallback to global $event and name resolution with hyphen replacement
        $wp = (object) [ 'query_vars' => [ 'event' => 'mens-singles' ] ];
        $name_handler = $this->createMock( Get_Event_Handler::class );
        $name_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( function ( $query ) {
                return 'mens singles' === $query->event_name;
            } ) )
            ->willReturn( new Event_Page_Result() );

        $adapter2 = $this->create_adapter( [
            'event_handler'  => $name_handler,
            'view_presenter' => $presenter,
        ] );

        $output2 = $adapter2->show_event( [] );
        $this->assertSame( 'rendered_event', $output2 );
    }
}
