<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

require_once __DIR__ . '/../../wp-stubs.php';

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Services\View\Php_View_Renderer;
use stdClass;

final class Modal_And_Invoice_Email_Render_Test extends TestCase {
    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    private function render_strictly( string $template, object|array $vars ): string {
        set_error_handler( static function ( int $errno, string $errstr, string $errfile, int $errline ): bool {
            throw new \ErrorException( $errstr, 0, $errno, $errfile, $errline );
        } );

        try {
            return $this->renderer->render_to_string( $template, $vars );
        } finally {
            restore_error_handler();
        }
    }

    public function test_match_status_modal_renders_with_actual_presenter_shape_without_warnings(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 301 );
        $fixture->method( 'get_home_team' )->willReturn( 'Leigh LTC' );
        $fixture->method( 'get_away_team' )->willReturn( 'Westcliff LTC' );

        $league              = $this->createStub( League::class );
        $league->num_rubbers = 0;

        $competition                 = $this->createStub( Competition::class );
        $competition->is_team_entry   = true;
        $competition->is_player_entry = false;

        $event = $this->createStub( Event::class );

        $dto = new Fixture_Details_DTO( $fixture, $league, $event, $competition );

        $opt1         = new stdClass();
        $opt1->value  = 'walkover_player1';
        $opt1->select = 'walkover_player1';
        $opt1->desc   = 'Match not played - Westcliff LTC did not show';

        $vars = [
            'dto'    => $dto,
            'match'  => $fixture,
            'status' => 'walkover_player1',
            'modal'  => 'statusModalTest',
            'select' => [ $opt1 ],
        ];

        $html = $this->render_strictly( 'match/match-status-modal', $vars );

        $this->assertStringContainsString( 'name="match_id" value="301"', $html );
        $this->assertStringContainsString( 'name="home_team" value="Leigh LTC"', $html );
        $this->assertStringContainsString( 'name="away_team" value="Westcliff LTC"', $html );
        $this->assertStringContainsString( 'name="modal" value="statusModalTest"', $html );
        $this->assertStringContainsString( 'Match not played - Westcliff LTC did not show', $html );
        $this->assertStringContainsString( 'selected', $html );
    }

    public function test_rubber_status_modal_renders_with_actual_presenter_shape_without_warnings(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 402 );
        $fixture->method( 'get_home_team' )->willReturn( 'Thorpe Bay' );
        $fixture->method( 'get_away_team' )->willReturn( 'Rayleigh' );

        $league      = $this->createStub( League::class );
        $event       = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $dto         = new Fixture_Details_DTO( $fixture, $league, $event, $competition );

        $rubber                = new stdClass();
        $rubber->id            = 905;
        $rubber->rubber_number = 3;
        $rubber->title         = 'Mixed Doubles 2';

        $opt1         = new stdClass();
        $opt1->value  = 'retired_player1';
        $opt1->select = 'retired_player1';
        $opt1->desc   = 'Retired - Thorpe Bay';

        $vars = [
            'dto'        => $dto,
            'match'      => $fixture,
            'rubber'     => $rubber,
            'status'     => 'retired_player1',
            'modal'      => 'rubberModalTest',
            'select'     => [ $opt1 ],
            'not_played' => 'Not played',
        ];

        $html = $this->render_strictly( 'match/rubber-status-modal', $vars );

        $this->assertStringContainsString( 'name="match_id" value="402"', $html );
        $this->assertStringContainsString( 'name="rubber_id" value="905"', $html );
        $this->assertStringContainsString( 'name="rubber_number" value="3"', $html );
        $this->assertStringContainsString( 'name="home_team" value="Thorpe Bay"', $html );
        $this->assertStringContainsString( 'name="away_team" value="Rayleigh"', $html );
        $this->assertStringContainsString( 'name="modal" value="rubberModalTest"', $html );
        $this->assertStringContainsString( 'Mixed Doubles 2', $html );
        $this->assertStringContainsString( 'Retired - Thorpe Bay', $html );
        $this->assertStringContainsString( 'selected', $html );
    }

    public function test_alert_modal_renders_message_and_class_without_warnings(): void {
        $vars = [
            'msg'   => 'Cannot update rubber score: invalid player selected.',
            'class' => 'danger',
        ];

        $html = $this->render_strictly( 'alert-modal', $vars );

        $this->assertStringContainsString( 'alert--danger', $html );
        $this->assertStringContainsString( 'Cannot update rubber score: invalid player selected.', $html );
    }

    public function test_tournament_withdrawal_modal_renders_with_actual_adapter_shape_without_warnings(): void {
        $vars = [
            'tournament_id'  => 55,
            'player_id'      => 808,
            'modal'          => 'withdrawModalDlg',
            'msg'            => 'Are you sure you want to withdraw from the tournament?',
            'events_entered' => 2,
        ];

        $html = $this->render_strictly( 'tournament/withdrawal-modal', $vars );

        $this->assertStringContainsString( 'name="tournamentId" value="55"', $html );
        $this->assertStringContainsString( 'name="playerId" value="808"', $html );
        $this->assertStringContainsString( 'name="modal" value="withdrawModalDlg"', $html );
        $this->assertStringContainsString( 'Are you sure you want to withdraw from the tournament?', $html );
        $this->assertStringContainsString( 'You will be withdrawn from all events if you proceed.', $html );
        $this->assertStringContainsString( 'data-action="confirm-tournament-withdrawal"', $html );
    }

    public function test_alert_modal_renders_with_object_vm_without_warnings(): void {
        $vm        = new stdClass();
        $vm->msg   = 'Object VM message test.';
        $vm->class = 'warning';

        $html = $this->render_strictly( 'alert-modal', $vm );

        $this->assertStringContainsString( 'alert--warning', $html );
        $this->assertStringContainsString( 'Object VM message test.', $html );
    }

    public function test_tournament_withdrawal_modal_renders_with_object_vm_without_warnings(): void {
        $vm                 = new stdClass();
        $vm->tournament_id  = 99;
        $vm->player_id      = 123;
        $vm->modal          = 'vmModal';
        $vm->msg            = 'Withdrawal text from VM';
        $vm->events_entered = 1;

        $html = $this->render_strictly( 'tournament/withdrawal-modal', $vm );

        $this->assertStringContainsString( 'name="tournamentId" value="99"', $html );
        $this->assertStringContainsString( 'name="playerId" value="123"', $html );
        $this->assertStringContainsString( 'name="modal" value="vmModal"', $html );
        $this->assertStringContainsString( 'Withdrawal text from VM', $html );
    }

    public function test_email_send_invoice_renders_with_actual_finance_service_shape_without_warnings(): void {
        $invoice_mock              = new stdClass();
        $invoice_mock->charge_name = 'Annual Club Affiliation';

        $vars = [
            'email_subject' => 'Affiliation Fee Invoice',
            'action_url'    => 'https://example.com/invoice/999/',
            'organisation'  => 'Leigh Tennis League',
            'invoice'       => $invoice_mock,
            'resend'        => true,
            'from_email'    => 'treasurer@example.com',
            'addressee'     => 'Sir Reginald Smith',
        ];

        $html = $this->render_strictly( 'email/send-invoice', $vars );

        $this->assertStringContainsString( 'Sir Reginald Smith', $html );
        $this->assertStringContainsString( 'another copy of', $html );
        $this->assertStringContainsString( 'Annual Club Affiliation', $html );
        $this->assertStringContainsString( 'https://example.com/invoice/999/', $html );
        $this->assertStringContainsString( 'View invoice', $html );
        $this->assertStringContainsString( 'treasurer@example.com', $html );
    }
}
