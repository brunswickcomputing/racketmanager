<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Tournament\Commands;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Tournament\Commands\Create_Tournament_Payment_Command;
use Racketmanager\Application\Tournament\Commands\Create_Tournament_Payment_Handler;
use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Command;
use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Entry_Command;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Entry_Handler;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Partner_Command;
use Racketmanager\Application\Tournament\Commands\Submit_Tournament_Partner_Handler;
use Racketmanager\Application\Tournament\Commands\Withdraw_Tournament_Entry_Command;
use Racketmanager\Application\Tournament\Commands\Withdraw_Tournament_Entry_Handler;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Entry_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Response_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Partner_Request_DTO;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Stripe_API_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Finance_Service;
use WP_Error;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

final class Tournament_Commands_Test extends TestCase {

    public function test_submit_tournament_entry_command_from_post_and_to_request_dto(): void {
        $post = [
            'tournament_id'    => '8',
            'playerId'         => '15',
            'clubId'           => '22',
            'season'           => '2026',
            'contactemail'     => 'player@example.com',
            'contactno'        => '07123456789',
            'btm'              => 'LTA123456',
            'competitionFee'   => '10.50',
            'pricePaidTotal'   => '25.00',
            'priceCostTotal'   => '25.00',
            'commentDetails'   => 'Seeding request',
            'acceptance'       => '1',
            'tournamentEvents' => '100,200,300',
            'event'            => [ '100', '200' ],
            'eventFee'         => [ '100' => '15.00', '200' => '10.00' ],
            'partner'          => [ '200' => 'Jane Partner' ],
            'partnerId'        => [ '200' => '99' ],
        ];

        $command = Submit_Tournament_Entry_Command::from_post( $post );

        $this->assertSame( 8, $command->tournament_id );
        $this->assertSame( 15, $command->player_id );
        $this->assertSame( 22, $command->club_id );
        $this->assertSame( '2026', $command->season );
        $this->assertSame( 'player@example.com', $command->email );
        $this->assertSame( '07123456789', $command->phone );
        $this->assertSame( 'LTA123456', $command->lta_number );
        $this->assertSame( 10.50, $command->competition_fee );
        $this->assertSame( 25.00, $command->paid_amt );
        $this->assertSame( 25.00, $command->total_cost );
        $this->assertSame( 'Seeding request', $command->comments );
        $this->assertTrue( $command->acceptance );
        $this->assertArrayHasKey( 100, $command->entries );
        $this->assertArrayHasKey( 200, $command->entries );
        $this->assertSame( [ 300 ], $command->missed_event_ids );

        $dto = $command->to_request_dto();
        $this->assertInstanceOf( Tournament_Entry_Request_DTO::class, $dto );
        $this->assertSame( 8, $dto->tournament_id );
        $this->assertSame( 15, $dto->player_id );
        $this->assertSame( 22, $dto->club_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 'player@example.com', $dto->email );
        $this->assertSame( '07123456789', $dto->phone );
        $this->assertSame( 'LTA123456', $dto->lta_number );
        $this->assertSame( 10.50, $dto->competition_fee );
        $this->assertSame( 25.00, $dto->paid_amt );
        $this->assertSame( 25.00, $dto->total_cost );
        $this->assertSame( 'Seeding request', $dto->comments );
        $this->assertTrue( $dto->acceptance );
        $this->assertSame( $command->entries, $dto->entries );
        $this->assertSame( $command->missed_event_ids, $dto->missed_event_ids );
    }

    public function test_submit_tournament_entry_handler_delegates_to_service(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $response_dto = new Tournament_Entry_Response_DTO(
            status: 200,
            message: 'Entry submitted'
        );

        $service->expects( $this->once() )
            ->method( 'request_tournament_entry' )
            ->with( $this->isInstanceOf( Tournament_Entry_Request_DTO::class ) )
            ->willReturn( $response_dto );

        $handler = new Submit_Tournament_Entry_Handler( $service );
        $command = new Submit_Tournament_Entry_Command(
            tournament_id: 8,
            player_id: 15,
            club_id: 22,
            season: '2026'
        );

        $result = $handler->handle( $command );
        $this->assertSame( $response_dto, $result );
    }

    public function test_submit_tournament_entry_handler_returns_wp_error(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $error   = new WP_Error( 'entry_closed', 'Tournament is closed' );

        $service->expects( $this->once() )
            ->method( 'request_tournament_entry' )
            ->willReturn( $error );

        $handler = new Submit_Tournament_Entry_Handler( $service );
        $command = new Submit_Tournament_Entry_Command(
            tournament_id: 8,
            player_id: 15
        );

        $result = $handler->handle( $command );
        $this->assertSame( $error, $result );
    }

    public function test_withdraw_tournament_entry_command_from_post(): void {
        $post = [
            'tournamentId' => '12',
            'playerId'     => '34',
        ];

        $command = Withdraw_Tournament_Entry_Command::from_post( $post );
        $this->assertSame( 12, $command->tournament_id );
        $this->assertSame( 34, $command->player_id );

        // Snake_case fallback
        $post_snake = [
            'tournament_id' => '56',
            'player_id'     => '78',
        ];
        $command_snake = Withdraw_Tournament_Entry_Command::from_post( $post_snake );
        $this->assertSame( 56, $command_snake->tournament_id );
        $this->assertSame( 78, $command_snake->player_id );

        // Empty yields null
        $command_empty = Withdraw_Tournament_Entry_Command::from_post( [] );
        $this->assertNull( $command_empty->tournament_id );
        $this->assertNull( $command_empty->player_id );
    }

    public function test_withdraw_tournament_entry_handler_delegates_to_service(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'confirm_tournament_withdrawal' )
            ->with( 12, 34 )
            ->willReturn( 15 );

        $handler = new Withdraw_Tournament_Entry_Handler( $service );
        $command = new Withdraw_Tournament_Entry_Command( tournament_id: 12, player_id: 34 );

        $refund = $handler->handle( $command );
        $this->assertSame( 15, $refund );
    }

    public function test_withdraw_tournament_entry_handler_propagates_exceptions(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'confirm_tournament_withdrawal' )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $this->expectException( Tournament_Not_Found_Exception::class );

        $handler = new Withdraw_Tournament_Entry_Handler( $service );
        $command = new Withdraw_Tournament_Entry_Command( tournament_id: 999, player_id: 34 );

        $handler->handle( $command );
    }

    public function test_submit_tournament_partner_command_from_post_and_to_request_dto(): void {
        $post = [
            'playerId'     => '10',
            'partnerId'    => '20',
            'tournamentId' => '30',
            'eventId'      => '40',
            'modal'        => 'partner_modal',
            'partner'      => 'Alice Smith',
        ];

        $command = Submit_Tournament_Partner_Command::from_post( $post );

        $this->assertSame( 10, $command->player_id );
        $this->assertSame( 20, $command->partner_id );
        $this->assertSame( 30, $command->tournament_id );
        $this->assertSame( 40, $command->event_id );
        $this->assertSame( 'partner_modal', $command->modal );
        $this->assertSame( 'Alice Smith', $command->partner_name );

        $dto = $command->to_request_dto();
        $this->assertInstanceOf( Tournament_Partner_Request_DTO::class, $dto );
        $this->assertSame( 10, $dto->player_id );
        $this->assertSame( 20, $dto->partner_id );
        $this->assertSame( 30, $dto->tournament_id );
        $this->assertSame( 40, $dto->event_id );
        $this->assertSame( 'partner_modal', $dto->modal );
        $this->assertSame( 'Alice Smith', $dto->partner_name );
    }

    public function test_submit_tournament_partner_handler_delegates_to_service(): void {
        $service = $this->createMock( Competition_Entry_Service::class );
        $service->expects( $this->once() )
            ->method( 'validate_tournament_partner' )
            ->with( $this->isInstanceOf( Tournament_Partner_Request_DTO::class ) )
            ->willReturn( true );

        $handler = new Submit_Tournament_Partner_Handler( $service );
        $command = new Submit_Tournament_Partner_Command(
            player_id: 10,
            partner_id: 20,
            tournament_id: 30,
            event_id: 40
        );

        $result = $handler->handle( $command );
        $this->assertTrue( $result );
    }

    public function test_create_tournament_payment_command_from_post(): void {
        $post = [
            'tournamentId' => '5',
            'playerId'     => '15',
            'invoiceId'    => '25',
        ];

        $command = Create_Tournament_Payment_Command::from_post( $post );
        $this->assertSame( 5, $command->tournament_id );
        $this->assertSame( 15, $command->player_id );
        $this->assertSame( 25, $command->invoice_id );

        // Snake_case fallback
        $post_snake = [
            'tournament_id' => '55',
            'player_id'     => '65',
            'invoice_id'    => '75',
        ];
        $command_snake = Create_Tournament_Payment_Command::from_post( $post_snake );
        $this->assertSame( 55, $command_snake->tournament_id );
        $this->assertSame( 65, $command_snake->player_id );
        $this->assertSame( 75, $command_snake->invoice_id );

        // Blank alias does not shadow fallback
        $post_blank = [
            'tournamentId'  => '',
            'tournament_id' => '100',
            'playerId'      => '',
            'player_id'     => '200',
            'invoiceId'     => '',
            'invoice_id'    => '300',
        ];
        $command_blank = Create_Tournament_Payment_Command::from_post( $post_blank );
        $this->assertSame( 100, $command_blank->tournament_id );
        $this->assertSame( 200, $command_blank->player_id );
        $this->assertSame( 300, $command_blank->invoice_id );
    }

    public function test_create_tournament_payment_handler_delegates_to_service(): void {
        $finance_service = $this->createMock( Finance_Service::class );
        $finance_service->expects( $this->once() )
            ->method( 'create_tournament_payment_request' )
            ->with( 5, 15, 25 )
            ->willReturn( 'pi_secret_test_123' );

        $handler = new Create_Tournament_Payment_Handler( $finance_service );
        $command = new Create_Tournament_Payment_Command(
            invoice_id: 25,
            tournament_id: 5,
            player_id: 15
        );

        $client_secret = $handler->handle( $command );
        $this->assertSame( 'pi_secret_test_123', $client_secret );
    }

    public function test_create_tournament_payment_handler_propagates_exceptions(): void {
        $finance_service = $this->createMock( Finance_Service::class );
        $finance_service->expects( $this->once() )
            ->method( 'create_tournament_payment_request' )
            ->willThrowException( new Stripe_API_Exception( 'Stripe error' ) );

        $this->expectException( Stripe_API_Exception::class );

        $handler = new Create_Tournament_Payment_Handler( $finance_service );
        $command = new Create_Tournament_Payment_Command(
            invoice_id: 25,
            tournament_id: 5,
            player_id: 15
        );

        $handler->handle( $command );
    }

    public function test_generate_tournament_bracket_command_from_post_and_to_draw_action_dto(): void {
        $post = [
            'action' => 'replace',
            'rounds' => [
                [ 'round' => 1, 'key' => 'Final', 'match_date' => '2026-07-20', 'num_matches' => 1 ],
            ],
            'season' => '2026',
        ];

        $command = Generate_Tournament_Bracket_Command::from_post(
            post: $post,
            league_id: 10,
            tournament_id: 5
        );

        $this->assertSame( 10, $command->league_id );
        $this->assertSame( 5, $command->tournament_id );
        $this->assertSame( '2026', $command->season );
        $this->assertSame( 'replace', $command->action );
        $this->assertSame( $post['rounds'], $command->rounds );

        $dto = $command->to_draw_action_dto();
        $this->assertInstanceOf( Draw_Action_Request_DTO::class, $dto );
        $this->assertSame( 10, $dto->league_id );
        $this->assertSame( 5, $dto->tournament_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 'replace', $dto->post['action'] );
        $this->assertSame( $command->rounds, $dto->post['rounds'] );
        $this->assertSame( '2026', $dto->post['season'] );
    }

    public function test_generate_bracket_without_tournament_id_for_cup_league(): void {
        $command = Generate_Tournament_Bracket_Command::from_post( [
            'league' => '3',
            'season' => '2026',
            'rounds' => [ [ 'round' => 1, 'key' => 'Final', 'match_date' => '2026-07-20', 'num_matches' => 1 ] ],
        ] );
        $this->assertSame( 0, $command->to_draw_action_dto()->tournament_id );
        $this->assertSame( 3, $command->to_draw_action_dto()->league_id );
        $this->assertSame( 0, ( new Generate_Tournament_Bracket_Command( league_id: 10, season: '2026' ) )->to_draw_action_dto()->tournament_id );
    }

    public function test_generate_tournament_bracket_handler_delegates_to_service(): void {
        $championship_service = $this->createMock( Draw_Action_Handler_Interface::class );
        $action_result = new Action_Result_DTO( 'Fixtures added', Admin_Message_Type::SUCCESS );

        $championship_service->expects( $this->once() )
            ->method( 'set_championship_fixtures' )
            ->with( $this->isInstanceOf( Draw_Action_Request_DTO::class ) )
            ->willReturn( $action_result );

        $handler = new Generate_Tournament_Bracket_Handler( $championship_service );
        $command = new Generate_Tournament_Bracket_Command(
            league_id: 10,
            season: 2026,
            action: 'add',
            rounds: [ [ 'round' => 2, 'key' => 'Semi-final', 'match_date' => '2026-07-10', 'num_matches' => 2 ] ],
            tournament_id: 5
        );

        $result = $handler->handle( $command );
        $this->assertSame( $action_result, $result );
        $this->assertSame( 'Fixtures added', $result->message );
        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
