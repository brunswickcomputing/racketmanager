<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
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
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_Entry_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class Tournament_Shortcode_Adapter_Test extends TestCase {
    private function create_adapter( array $overrides = [] ): Tournament_Shortcode_Adapter {
        return new Tournament_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->createStub( RacketManager::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['tournament_handler'] ?? $this->createStub( Get_Tournament_Handler::class ),
            $overrides['overview_handler'] ?? $this->createStub( Get_Tournament_Overview_Handler::class ),
            $overrides['events_handler'] ?? $this->createStub( Get_Tournament_Events_Handler::class ),
            $overrides['draws_handler'] ?? $this->createStub( Get_Tournament_Draws_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Tournament_Players_Handler::class ),
            $overrides['winners_handler'] ?? $this->createStub( Get_Tournament_Winners_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Tournament_Matches_Handler::class ),
            $overrides['match_handler'] ?? $this->createStub( Get_Tournament_Match_Handler::class ),
            $overrides['order_of_play_handler'] ?? $this->createStub( Get_Order_Of_Play_Handler::class ),
            $overrides['latest_tournament_handler'] ?? $this->createStub( Get_Latest_Tournament_Handler::class ),
            $overrides['withdrawal_handler'] ?? $this->createStub( Get_Tournament_Withdrawal_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    public function test_show_tournament_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'tournament', [] );
        $handler = $this->createMock( Get_Tournament_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Tournament Output</div>' );

        $adapter = $this->create_adapter( [
            'tournament_handler' => $handler,
            'view_presenter'     => $presenter,
        ] );

        $output = $adapter->show_tournament( [ 'tournament' => 'Championship' ] );
        $this->assertSame( '<div>Tournament Output</div>', $output );
    }

    public function test_show_tournament_overview_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'overview', [] );
        $handler = $this->createMock( Get_Tournament_Overview_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Overview_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Overview Output</div>' );

        $adapter = $this->create_adapter( [
            'overview_handler' => $handler,
            'view_presenter'   => $presenter,
        ] );

        $output = $adapter->show_tournament_overview( [ 'id' => 123 ] );
        $this->assertSame( '<div>Overview Output</div>', $output );
    }

    public function test_show_tournament_events_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'events', [] );
        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Events_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Events Output</div>' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->show_tournament_events( [ 'id' => 123 ] );
        $this->assertSame( '<div>Events Output</div>', $output );
    }

    public function test_show_draws_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'draws', [] );
        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Draws_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Draws Output</div>' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->show_draws( [ 'id' => 123 ] );
        $this->assertSame( '<div>Draws Output</div>', $output );
    }

    public function test_show_tournament_players_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'players', [] );
        $handler = $this->createMock( Get_Tournament_Players_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Players_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Players Output</div>' );

        $adapter = $this->create_adapter( [
            'players_handler' => $handler,
            'view_presenter'  => $presenter,
        ] );

        $output = $adapter->show_tournament_players( [ 'id' => 123 ] );
        $this->assertSame( '<div>Players Output</div>', $output );
    }

    public function test_show_tournament_winners_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'winners', [] );
        $handler = $this->createMock( Get_Tournament_Winners_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Winners_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Winners Output</div>' );

        $adapter = $this->create_adapter( [
            'winners_handler' => $handler,
            'view_presenter'  => $presenter,
        ] );

        $output = $adapter->show_tournament_winners( [ 'id' => 123 ] );
        $this->assertSame( '<div>Winners Output</div>', $output );
    }

    public function test_show_tournament_matches_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'matches', [] );
        $handler = $this->createMock( Get_Tournament_Matches_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Matches_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Matches Output</div>' );

        $adapter = $this->create_adapter( [
            'matches_handler' => $handler,
            'view_presenter'  => $presenter,
        ] );

        $output = $adapter->show_tournament_matches( [ 'id' => 123 ] );
        $this->assertSame( '<div>Matches Output</div>', $output );
    }

    public function test_show_tournament_match_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'match-tournament', [] );
        $handler = $this->createMock( Get_Tournament_Match_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Match_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Match Output</div>' );

        $adapter = $this->create_adapter( [
            'match_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->show_tournament_match( [ 'match_id' => 456 ] );
        $this->assertSame( '<div>Match Output</div>', $output );
    }

    public function test_show_order_of_play_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'orderofplay', [] );
        $handler = $this->createMock( Get_Order_Of_Play_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Order_Of_Play_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div>Order of Play Output</div>' );

        $adapter = $this->create_adapter( [
            'order_of_play_handler' => $handler,
            'view_presenter'        => $presenter,
        ] );

        $output = $adapter->show_order_of_play( [ 'id' => 123 ] );
        $this->assertSame( '<div>Order of Play Output</div>', $output );
    }

    public function test_show_latest_tournament_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_html( '<script>location.href = "/tournament/active/"</script>' );
        $handler = $this->createMock( Get_Latest_Tournament_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Latest_Tournament_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<script>location.href = "/tournament/active/"</script>' );

        $adapter = $this->create_adapter( [
            'latest_tournament_handler' => $handler,
            'view_presenter'            => $presenter,
        ] );

        $output = $adapter->show_latest_tournament( [ 'age_group' => 'seniors' ] );
        $this->assertSame( '<script>location.href = "/tournament/active/"</script>', $output );
    }

    public function test_show_tournament_withdrawal_modal_delegates_to_handler_and_presenter(): void {
        $vm = Shortcode_View_Model::for_template( 'withdrawal-modal', [] );
        $handler = $this->createMock( Get_Tournament_Withdrawal_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Withdrawal_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div class="tournament-withdrawal-modal"></div>' );

        $adapter = $this->create_adapter( [
            'withdrawal_handler' => $handler,
            'view_presenter'     => $presenter,
        ] );

        $output = $adapter->show_tournament_withdrawal_modal( [ 'id' => 44 ] );
        $this->assertSame( '<div class="tournament-withdrawal-modal"></div>', $output );
    }

    public function test_show_tournament_withdrawal_alias_delegates_to_modal(): void {
        $vm = Shortcode_View_Model::for_template( 'withdrawal-modal', [] );
        $handler = $this->createMock( Get_Tournament_Withdrawal_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( Get_Tournament_Withdrawal_Query::class ) )
            ->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $vm )
            ->willReturn( '<div class="tournament-withdrawal-modal"></div>' );

        $adapter = $this->create_adapter( [
            'withdrawal_handler' => $handler,
            'view_presenter'     => $presenter,
        ] );

        $output = $adapter->show_tournament_withdrawal( [ 'id' => 44 ] );
        $this->assertSame( '<div class="tournament-withdrawal-modal"></div>', $output );
    }

    public function test_show_tournament_events_renders_allowed_single_event(): void {
        $event       = new Tournament_Event_DTO( 42, 'Men Singles', [ new Tournament_Event_Entry_DTO( 101, 'Alice Example', null, null, [] ) ], 4 );
        $resolved    = $this->createStub( Event::class );
        $competition = $this->createMock( Competition::class );
        $competition->expects( $this->once() )
            ->method( 'resolve_event' )
            ->with( $event, '2026' )
            ->willReturn( $resolved );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturnCallback( static fn( $identifier ): bool => $event === $identifier );
        $vm         = Shortcode_View_Model::for_template( 'event', [
            'tournament' => $tournament,
            'event'      => $event,
        ] );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->identicalTo( $vm ) )
            ->willReturn( '<div>Allowed Event</div>' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Allowed Event</div>', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_tournament_events_rejects_disallowed_single_event(): void {
        $event      = 'forged-event-slug';
        $tournament = $this->create_tournament_stub( false );
        $vm         = Shortcode_View_Model::for_template( 'event', [
            'tournament' => $tournament,
            'event'      => $event,
        ] );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ): bool {
                return $rendered->is_error()
                    && 'Event not found for tournament' === $rendered->error_message;
            } ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( 'error view', $adapter->show_tournament_events( [ 'id' => 8, 'event' => 99 ] ) );
    }

    public function test_show_tournament_events_filters_list_to_allowed_events(): void {
        $allowed    = (object) [ 'id' => 42, 'name' => 'Men Singles' ];
        $denied     = (object) [ 'id' => 99, 'name' => 'Closed Event' ];
        $tournament = $this->create_tournament_stub( null, [ 42 ] );
        $vm         = Shortcode_View_Model::for_template( 'events', [
            'tournament' => $tournament,
            'events'     => [ $allowed, $denied ],
        ] );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ) use ( $allowed ): bool {
                return ! $rendered->is_error()
                    && 'events' === $rendered->template
                    && [ $allowed ] === array_values( $rendered->vars['events'] );
            } ) )
            ->willReturn( '<div>Filtered Events</div>' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Filtered Events</div>', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_filters_list_to_allowed_draws(): void {
        $allowed    = (object) [ 'id' => 42, 'name' => 'Men Singles' ];
        $denied     = (object) [ 'id' => 99, 'name' => 'Closed Draw' ];
        $tournament = $this->create_tournament_stub( null, [ 42 ] );
        $vm         = Shortcode_View_Model::for_template( 'draws', [
            'tournament' => $tournament,
            'draws'      => [ $allowed, $denied ],
        ] );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ) use ( $allowed ): bool {
                return ! $rendered->is_error()
                    && 'draws' === $rendered->template
                    && [ $allowed ] === array_values( $rendered->vars['draws'] );
            } ) )
            ->willReturn( '<div>Filtered Draws</div>' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Filtered Draws</div>', $adapter->show_draws( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_renders_allowed_single_draw_without_replacing_view_data(): void {
        $draw        = new Tournament_Event_DTO( 42, 'Men Singles', [], 4 );
        $resolved    = $this->createStub( Event::class );
        $competition = $this->createMock( Competition::class );
        $competition->expects( $this->once() )
            ->method( 'resolve_event' )
            ->with( $draw, '2026' )
            ->willReturn( $resolved );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturnCallback( static fn( $identifier ): bool => $draw === $identifier );
        $vm = Shortcode_View_Model::for_template( 'draw', [
            'tournament' => $tournament,
            'draw'       => $draw,
        ] );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->identicalTo( $vm ) )
            ->willReturn( '<div>Allowed Draw</div>' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Allowed Draw</div>', $adapter->show_draws( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_rejects_disallowed_single_draw(): void {
        $draw       = (object) [ 'id' => 99, 'name' => 'Forged Draw' ];
        $tournament = $this->create_tournament_stub( false );
        $vm         = Shortcode_View_Model::for_template( 'draw', [
            'tournament' => $tournament,
            'draw'       => $draw,
        ] );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $vm );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ): bool {
                return $rendered->is_error()
                    && 'Event not found for tournament' === $rendered->error_message;
            } ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( 'error view', $adapter->show_draws( [ 'id' => 8, 'draw' => 99 ] ) );
    }

    /**
     * @param bool|null $allow_all When true/false, always allow/deny. When null, allow only IDs in $allowed_ids.
     * @param int[]     $allowed_ids
     */
    private function create_tournament_stub( ?bool $allow_all = true, array $allowed_ids = [] ): Tournament {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_competition_object' )->willReturn( null );
        if ( null === $allow_all ) {
            $tournament->method( 'is_event_allowed' )->willReturnCallback(
                static function ( $item ) use ( $allowed_ids ): bool {
                    $id = is_object( $item ) ? (int) ( $item->id ?? 0 ) : (int) $item;
                    return in_array( $id, $allowed_ids, true );
                }
            );
        } else {
            $tournament->method( 'is_event_allowed' )->willReturn( $allow_all );
        }

        return $tournament;
    }
}
