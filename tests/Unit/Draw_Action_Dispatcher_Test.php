<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

final class Draw_Action_Dispatcher_Test extends TestCase {

    public function test_invokes_rank_teams_handler_with_mode_from_context(): void {
        $calls = array();

        $handler = new class( $calls ) implements Draw_Action_Handler_Interface {
            /** @var array<int,array{method:string,args:array}> */
            public array $calls;
            public function __construct( array &$calls ) { $this->calls = &$calls; }

            public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function manage_matches_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }

            public function rank_teams( Draw_Action_Request_DTO $dto, string $mode ): Action_Result_DTO {
                $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array( 'mode' => $mode ) );
                return new Action_Result_DTO( 'ok', Admin_Message_Type::SUCCESS );
            }

            public function start_finals( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function set_championship_matches( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
        };

        $guard = new class implements Action_Guard_Interface {
            public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void {
                // no-op for unit test
            }
        };

        $dispatcher = new Draw_Action_Dispatcher( $handler, $guard );

        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: 2,
            season: null,
            post: array(
                'randomRanking' => '1',
            )
        );

        $response = $dispatcher->handle( $dto );

        self::assertSame( 'ok', $response->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $response->message_type );
        self::assertSame( 'preliminary', $response->tab_override );

        self::assertCount( 1, $calls );
        self::assertSame( 'rank_teams', $calls[0]['method'] );
        self::assertSame( 'random', $calls[0]['args']['mode'] ?? null );
    }

    public function test_unknown_action_does_not_invoke_handler_or_guard(): void {
        $calls = array();
        $guard_calls = 0;

        $handler = new class( $calls ) implements Draw_Action_Handler_Interface {
            /** @var array<int,array{method:string,args:array}> */
            public array $calls;
            public function __construct( array &$calls ) { $this->calls = &$calls; }

            public function handle_league_teams_action( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function add_teams_to_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function manage_matches_in_league( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function rank_teams( Draw_Action_Request_DTO $dto, string $mode ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array( 'mode' => $mode ) ); return new Action_Result_DTO(); }
            public function start_finals( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function update_final_results( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
            public function set_championship_matches( Draw_Action_Request_DTO $dto ): Action_Result_DTO { $this->calls[] = array( 'method' => __FUNCTION__, 'args' => array() ); return new Action_Result_DTO(); }
        };

        $guard = new class( $guard_calls ) implements Action_Guard_Interface {
            public int $guard_calls;
            public function __construct( int &$guard_calls ) { $this->guard_calls = &$guard_calls; }
            public function assert_allowed( string $nonce_field, string $nonce_action, string $capability ): void {
                ++$this->guard_calls;
            }
        };

        $dispatcher = new Draw_Action_Dispatcher( $handler, $guard );

        // Make has_any_action() true, but ensure no policy matches.
        $dto = new Draw_Action_Request_DTO(
            tournament_id: 1,
            league_id: 2,
            season: null,
            post: array(
                'action' => 'someUnknownAction',
            )
        );

        $response = $dispatcher->handle( $dto );

        self::assertNull( $response->message );
        self::assertNull( $response->message_type );
        self::assertNull( $response->tab_override );
        self::assertCount( 0, $calls );
        self::assertSame( 0, $guard_calls );
    }
}
