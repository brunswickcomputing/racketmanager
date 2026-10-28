<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Finance\Charge_Details_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Details_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use stdClass;

/**
 * @covers \Racketmanager\Domain\Finance\Invoice
 * @covers \Racketmanager\Domain\Finance\Charge
 * @covers \Racketmanager\Domain\Team\Team
 * @covers \Racketmanager\Domain\DTO\Finance\Invoice_Details_DTO
 * @covers \Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO
 * @covers \Racketmanager\Domain\DTO\Finance\Charge_Details_DTO
 * @covers \Racketmanager\Services\Finance_Service
 */
final class Finance_Entities_Test extends TestCase {

    public function test_invoice_money_and_status_enum(): void {
        $invoice = new Invoice();
        $invoice->set_amount( '150.00' );
        $invoice->set_status( 'paid' );

        self::assertSame( '150.00', $invoice->get_amount() );
        $money = $invoice->get_money();
        self::assertInstanceOf( Money::class, $money );
        self::assertSame( 15000, $money->get_cents() );
        self::assertSame( '£150.00', $money->format() );

        self::assertSame( Invoice_Status::PAID, $invoice->get_status_enum() );

        $invoice->set_money( Money::from_cents( 20000 ) );
        self::assertSame( '200.00', $invoice->get_amount() );

        $invoice->set_status_enum( Invoice_Status::DRAFT );
        self::assertSame( 'draft', $invoice->get_status() );
    }

    public function test_invoice_hydration_from_string_db_rows(): void {
        $row = (object) [
            'id'             => '5',
            'invoice_number' => '1001',
            'charge_id'      => '2',
            'billable_id'    => '42',
            'billable_type'  => 'club',
            'amount'         => '125.50',
            'status'         => 'final',
            'details'        => '{"item":"Entry fee"}',
        ];

        $invoice = Invoice::from_object( $row );
        self::assertSame( 5, $invoice->id );
        self::assertSame( 1001, $invoice->invoice_number );
        self::assertSame( 2, $invoice->charge_id );
        self::assertSame( 42, $invoice->billable_id );
        self::assertSame( '125.50', $invoice->amount );
        self::assertSame( Invoice_Status::FINAL, $invoice->get_status_enum() );
        self::assertSame( 12550, $invoice->get_money( Currency::EUR )->get_cents() );
        self::assertSame( '€125.50', $invoice->get_money( Currency::EUR )->format() );
    }

    public function test_charge_money_and_status_enum(): void {
        $charge = new Charge();
        $charge->set_fee_competition( 50.0 );
        $charge->set_fee_event( 25.50 );
        $charge->set_status( 'final' );

        $comp_money = $charge->get_competition_fee_money();
        self::assertInstanceOf( Money::class, $comp_money );
        self::assertSame( 5000, $comp_money->get_cents() );

        $event_money = $charge->get_event_fee_money();
        self::assertInstanceOf( Money::class, $event_money );
        self::assertSame( 2550, $event_money->get_cents() );

        $total_money = $charge->get_total_money();
        self::assertInstanceOf( Money::class, $total_money );
        self::assertSame( 7550, $total_money->get_cents() );
        self::assertSame( '£75.50', $total_money->format() );

        self::assertSame( Charge_Status::FINAL, $charge->get_status_enum() );
        $charge->set_status_enum( Charge_Status::DRAFT );
        self::assertSame( 'draft', $charge->get_status() );
    }

    public function test_charge_uninitialized_and_partial_fees(): void {
        $empty_charge = new Charge();
        self::assertNull( $empty_charge->get_competition_fee_money() );
        self::assertNull( $empty_charge->get_event_fee_money() );
        self::assertNull( $empty_charge->get_total_money() );

        $partial_charge = new Charge();
        $partial_charge->set_fee_event( 15.0 );
        self::assertNull( $partial_charge->get_competition_fee_money() );
        self::assertSame( 1500, $partial_charge->get_event_fee_money()->get_cents() );
        self::assertSame( 1500, $partial_charge->get_total_money()->get_cents() );
    }

    public function test_charge_hydration_from_string_db_rows(): void {
        $row = (object) [
            'id'              => '10',
            'competition_id'  => '2',
            'season'          => '2026',
            'status'          => 'draft',
            'fee_competition' => '40.00',
            'fee_event'       => '20.00',
            'total'           => '60.00',
        ];

        $charge = Charge::from_object( $row );
        self::assertSame( 10, $charge->id );
        self::assertSame( 2, $charge->competition_id );
        self::assertSame( '2026', $charge->season );
        self::assertSame( 40.0, $charge->fee_competition );
        self::assertSame( 20.0, $charge->fee_event );
        self::assertSame( Charge_Status::DRAFT, $charge->get_status_enum() );

        $total = $charge->get_total_money( Currency::EUR );
        self::assertSame( 6000, $total->get_cents() );
        self::assertSame( '€60.00', $total->format() );
    }

    public function test_team_profile_enum_integration(): void {
        $team = new Team();
        $team->profile = 1;
        self::assertSame( Team_Profile::ACTIVE, $team->get_profile_enum() );

        $team->set_profile_enum( Team_Profile::WITHDRAWN );
        self::assertSame( 3, $team->profile );
        self::assertSame( Team_Profile::WITHDRAWN, $team->get_profile_enum() );
    }

    public function test_invoice_details_dto(): void {
        $raw                    = new stdClass();
        $raw->id                = 1;
        $raw->charge_id         = 10;
        $raw->billable_id       = 5;
        $raw->billable_type     = 'club';
        $raw->billable_name     = 'Leigh TC';
        $raw->invoice_number    = 1001;
        $raw->status            = 'paid';
        $raw->amount            = '150.50';
        $raw->date              = '2026-01-01';
        $raw->date_due          = '2026-01-31';
        $raw->payment_reference = 'REF123';
        $raw->purchase_order    = 'PO456';
        $raw->details           = '{}';
        $raw->season            = '2026';
        $raw->competition_name  = 'Summer League';

        $dto = new Invoice_Details_DTO( $raw );
        self::assertSame( Invoice_Status::PAID, $dto->get_status_enum() );
        self::assertSame( '150.50', $dto->amount );
        self::assertSame( 15050, $dto->get_money()->get_cents() );
        self::assertSame( '£150.50', $dto->get_money()->format() );
        self::assertSame( '€150.50', $dto->get_money( Currency::EUR )->format() );

        $raw->amount     = null;
        $null_amount_dto = new Invoice_Details_DTO( $raw );
        self::assertNull( $null_amount_dto->amount );
        self::assertNull( $null_amount_dto->get_money() );
    }

    public function test_invoice_full_details_dto(): void {
        $raw_invoice                 = new Invoice();
        $raw_invoice->id             = 1;
        $raw_invoice->invoice_number = 1001;
        $raw_invoice->charge_id      = 10;
        $raw_invoice->billable_id    = 5;
        $raw_invoice->billable_type  = 'club';
        $raw_invoice->amount         = '150.50';
        $raw_invoice->status         = 'sent';
        $raw_invoice->date           = '2026-01-01';
        $raw_invoice->date_due       = '2026-01-31';
        $raw_invoice->details        = (object) [];

        $charge_raw                   = new stdClass();
        $charge_raw->id               = 10;
        $charge_raw->competition_id   = 2;
        $charge_raw->season           = '2026';
        $charge_raw->status           = 'final';
        $charge_raw->date             = '2026-01-01';
        $charge_raw->fee_competition  = 50;
        $charge_raw->fee_event        = 25;
        $charge_raw->charge_name      = '2026 League';
        $charge_raw->competition_name = 'League';
        $charge_raw->competition_type = 'league';
        $charge_dto                   = new Charge_Details_DTO( $charge_raw );

        $dto = new Invoice_Full_Details_DTO( $raw_invoice, 'Leigh TC', '1 Club Rd', null, $charge_dto );
        self::assertSame( Invoice_Status::SENT, $dto->get_status_enum() );
        self::assertSame( '150.50', $dto->amount );
        self::assertSame( 15050, $dto->get_money()->get_cents() );
        self::assertSame( '£150.50', $dto->get_money()->format() );
    }

    public function test_charge_details_dto_decimal_and_null_fees(): void {
        $raw                   = new stdClass();
        $raw->id               = '10';
        $raw->competition_id   = '2';
        $raw->season           = '2026';
        $raw->status           = 'final';
        $raw->date             = '2026-01-01';
        $raw->fee_competition  = '12.50';
        $raw->fee_event        = '3.75';
        $raw->competition_name = 'Summer League';
        $raw->competition_type = 'league';

        $dto = new Charge_Details_DTO( $raw );
        self::assertSame( '12.50', $dto->fee_competition );
        self::assertSame( 1250, $dto->get_competition_fee_money()->get_cents() );
        self::assertSame( 375, $dto->get_event_fee_money()->get_cents() );
        self::assertSame( 1625, $dto->get_total_money()->get_cents() );
        self::assertSame( '£16.25', $dto->get_total_money()->format() );

        $raw->fee_competition = null;
        $raw->fee_event       = null;
        $null_dto             = new Charge_Details_DTO( $raw );
        self::assertNull( $null_dto->fee_competition );
        self::assertSame( 0, $null_dto->get_total_money()->get_cents() );

        $raw->fee_competition = '12.50';
        $raw->fee_event       = null;
        $partial_dto          = new Charge_Details_DTO( $raw );
        self::assertSame( 1250, $partial_dto->get_total_money()->get_cents() );
    }

    public function test_charge_details_dto(): void {
        $raw                   = new stdClass();
        $raw->id               = 10;
        $raw->competition_id   = 2;
        $raw->season           = '2026';
        $raw->status           = 'draft';
        $raw->date             = '2026-01-01';
        $raw->fee_competition  = 40;
        $raw->fee_event        = 20;
        $raw->competition_name = 'Summer League';
        $raw->competition_type = 'league';

        $dto = new Charge_Details_DTO( $raw );
        self::assertSame( Charge_Status::DRAFT, $dto->get_status_enum() );
        self::assertSame( '£40.00', $dto->get_competition_fee_money()->format() );
        self::assertSame( '£20.00', $dto->get_event_fee_money()->format() );
        self::assertSame( '£60.00', $dto->get_total_money()->format() );

        self::assertSame( '€60.00', $dto->get_total_money( Currency::EUR )->format() );

        $empty_status                   = clone $raw;
        $empty_status->status           = '';
        $empty_dto                      = new Charge_Details_DTO( $empty_status );
        self::assertNull( $empty_dto->get_status_enum() );
    }

    public function test_charge_empty_db_status_is_safe(): void {
        $charge = Charge::from_object( (object) [ 'status' => '' ] );
        self::assertSame( '', $charge->get_status() );
        self::assertNull( $charge->get_status_enum() );
    }

    public function test_finance_service_workflow_with_money_and_status(): void {
        $plugin = $this->createStub( RacketManager::class );
        $plugin->method( 'get_options' )->willReturnCallback(
            static fn( $key = null ) => 'billing' === $key ? [ 'billingCurrency' => 'EUR' ] : []
        );

        $charge_repo = $this->createStub( Charge_Repository_Interface::class );
        $inv_repo    = $this->createStub( Invoice_Repository_Interface::class );
        $club_repo   = $this->createStub( Club_Repository_Interface::class );
        $tourn_repo  = $this->createStub( Tournament_Repository_Interface::class );
        $comp_svc    = $this->createStub( Competition_Service::class );
        $player_svc  = $this->createStub( Player_Service::class );

        $finance_svc = new Finance_Service(
            $plugin,
            $charge_repo,
            $inv_repo,
            $club_repo,
            $tourn_repo,
            $comp_svc,
            $player_svc
        );

        self::assertSame( Currency::EUR, $finance_svc->get_currency() );

        // Test action_invoice status update via Invoice_Status
        $invoice = new Invoice();
        $invoice->id = 100;
        $invoice->set_status( 'draft' );

        $inv_repo->method( 'find_by_id' )->willReturn( $invoice );
        $inv_repo->method( 'save' )->willReturn( true );

        $res = $finance_svc->action_invoice( 100, 'paid' );
        self::assertSame( Invoice_Status::PAID, $invoice->get_status_enum() );
        self::assertStringContainsString( 'status updated', $res );

        // Test get_club_entry fee calculation via Money
        $charge = new Charge();
        $charge->competition_id = 1;
        $charge->season = '2026';
        $charge->set_fee_competition( 30.0 );
        $charge->set_fee_event( 15.0 );

        $club = new Club();
        $club->id = 10;
        $club->name = 'Westcliff Lawn';

        $event = new class {
            public string $type = 'MD';
            public function get_id(): int {
                return 5;
            }
        };

        $comp_svc->method( 'get_events_for_competition' )->willReturn( [ $event ] );
        $comp_svc->method( 'get_teams_for_event' )->willReturn( [ (object) [ 'id' => 1 ], (object) [ 'id' => 2 ] ] );

        $entry = $finance_svc->get_club_entry( $charge, $club );
        self::assertNotFalse( $entry );
        self::assertSame( 2, $entry->num_teams );
        self::assertSame( 30.0, $entry->fee_competition );
        self::assertSame( 30.0, $entry->fee_events ); // 15.0 * 2 teams
        self::assertSame( 60.0, $entry->fee ); // 30.0 + 30.0
    }

    public function test_get_club_entry_fractional_event_fees(): void {
        $plugin = $this->createStub( RacketManager::class );
        $plugin->method( 'get_options' )->willReturnCallback(
            static fn( $key = null ) => 'billing' === $key ? [ 'billingCurrency' => 'EUR' ] : []
        );

        $comp_svc = $this->createStub( Competition_Service::class );
        $finance_svc = new Finance_Service(
            $plugin,
            $this->createStub( Charge_Repository_Interface::class ),
            $this->createStub( Invoice_Repository_Interface::class ),
            $this->createStub( Club_Repository_Interface::class ),
            $this->createStub( Tournament_Repository_Interface::class ),
            $comp_svc,
            $this->createStub( Player_Service::class )
        );

        $charge = new Charge();
        $charge->competition_id = 1;
        $charge->season = '2026';
        $charge->set_fee_competition( 0.0 );
        $charge->set_fee_event( 0.10 );

        $club       = new Club();
        $club->id   = 10;
        $club->name = 'Westcliff Lawn';

        $event = new class {
            public string $type = 'MD';
            public function get_id(): int {
                return 5;
            }
        };

        $comp_svc->method( 'get_events_for_competition' )->willReturn( [ $event ] );
        $comp_svc->method( 'get_teams_for_event' )->willReturn( [
            (object) [ 'id' => 1 ],
            (object) [ 'id' => 2 ],
            (object) [ 'id' => 3 ],
        ] );

        $entry = $finance_svc->get_club_entry( $charge, $club );
        self::assertNotFalse( $entry );
        self::assertSame( 3, $entry->num_teams );
        self::assertSame( 0.30, $entry->fee_events );
        self::assertSame( 0.30, $entry->fee );
    }

    public function test_invoice_and_charge_status_transitions_and_money_write_paths(): void {
        $invoice = new Invoice();
        $invoice->set_amount( Money::from_cents( 7550, Currency::GBP ) );
        self::assertSame( '75.50', $invoice->get_amount() );
        self::assertSame( 7550, $invoice->get_money()->get_cents() );

        $invoice->set_status( Invoice_Status::SENT );
        self::assertTrue( $invoice->is_sent() );
        self::assertFalse( $invoice->is_paid() );
        self::assertTrue( $invoice->can_be_sent() );

        $invoice->set_status( 'resend' );
        self::assertTrue( $invoice->is_sent() );

        $invoice->set_status( Invoice_Status::PAID );
        self::assertTrue( $invoice->is_paid() );

        $invoice->set_status( 'final' );
        self::assertTrue( $invoice->is_final() );

        $invoice->set_status( 'cancelled' );
        self::assertTrue( $invoice->is_cancelled() );

        $charge = new Charge();
        $charge->set_fee_competition( Money::from_float( 40.0 ) );
        $charge->set_fee_event( Money::from_float( 15.0 ) );
        self::assertSame( 40.0, $charge->get_fee_competition() );
        self::assertSame( 15.0, $charge->get_fee_event() );

        $charge->set_status( Charge_Status::DRAFT );
        self::assertTrue( $charge->is_draft() );
        self::assertFalse( $charge->is_final() );

        $charge->set_status( 'final' );
        self::assertTrue( $charge->is_final() );
        self::assertFalse( $charge->is_draft() );
    }

    public function test_unknown_and_empty_statuses_through_invoice_entity_dtos_and_presenter(): void {
        $invoice = new Invoice();
        $invoice->id = 55;
        $invoice->invoice_number = 2026055;
        $invoice->set_amount( '50.00' );
        $invoice->set_status( 'pending' );

        self::assertSame( 'pending', $invoice->get_status() );
        self::assertNull( $invoice->get_status_enum() );
        self::assertFalse( $invoice->is_paid() );
        self::assertFalse( $invoice->is_draft() );
        self::assertFalse( $invoice->is_final() );
        self::assertFalse( $invoice->is_sent() );
        self::assertFalse( $invoice->is_cancelled() );
        self::assertFalse( $invoice->is_new() );
        self::assertFalse( $invoice->can_be_sent() );

        $invoice->set_status( 'failed' );
        self::assertSame( 'failed', $invoice->get_status() );
        self::assertNull( $invoice->get_status_enum() );
        self::assertFalse( $invoice->is_paid() );

        $invoice->set_status( '' );
        self::assertSame( '', $invoice->get_status() );
        self::assertNull( $invoice->get_status_enum() );
        self::assertFalse( $invoice->is_paid() );

        // Test DTOs with unknown statuses
        $details_dto = new Invoice_Details_DTO( (object) [
            'id'                => 10,
            'charge_id'         => 1,
            'billable_id'       => 2,
            'billable_type'     => 'club',
            'billable_name'     => 'Club',
            'invoice_number'    => 2026010,
            'status'            => 'pending',
            'amount'            => '30.00',
            'date'              => '2026-05-01',
            'date_due'          => '2026-05-15',
            'payment_reference' => null,
            'purchase_order'    => null,
            'details'           => '{}',
            'season'            => '2026',
            'competition_name'  => 'Affiliation',
        ] );
        self::assertNull( $details_dto->get_status_enum() );

        // Test Invoice_Presenter with unknown / pending / failed / empty status
        $presenter = new Invoice_Presenter();

        $invoice_pending = (object) [
            'id'             => 55,
            'invoice_number' => 2026055,
            'status'         => 'pending',
            'amount'         => '50.00',
        ];
        $vm_pending = $presenter->present_invoice( $invoice_pending );
        self::assertFalse( $vm_pending->is_paid );
        self::assertSame( 'pending', $vm_pending->status );
        self::assertNull( $vm_pending->status_display );
        self::assertSame( 'Invoice 2026055 (pending)', $vm_pending->title );

        $invoice_paid = (object) [
            'id'             => 56,
            'invoice_number' => 2026056,
            'status'         => 'paid',
            'amount'         => '50.00',
        ];
        $vm_paid = $presenter->present_invoice( $invoice_paid );
        self::assertTrue( $vm_paid->is_paid );
        self::assertSame( 'paid', $vm_paid->status );
        self::assertSame( 'PAID', $vm_paid->status_display );

        $invoice_empty = (object) [
            'id'             => 57,
            'invoice_number' => 2026057,
            'status'         => '',
            'amount'         => '50.00',
        ];
        $vm_empty = $presenter->present_invoice( $invoice_empty );
        self::assertFalse( $vm_empty->is_paid );
        self::assertSame( '', $vm_empty->status );
        self::assertNull( $vm_empty->status_display );
        self::assertSame( 'Invoice 2026057', $vm_empty->title );
    }
}
