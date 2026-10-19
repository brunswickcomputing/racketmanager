<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Enums;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Enums\Club_Role;
use Racketmanager\Domain\Enums\Fixture_Status;
use Racketmanager\Domain\Enums\Invoice_Status;
use Racketmanager\Domain\Enums\Team_Profile;

/**
 * @covers \Racketmanager\Domain\Enums\Invoice_Status
 * @covers \Racketmanager\Domain\Enums\Charge_Status
 * @covers \Racketmanager\Domain\Enums\Club_Role
 * @covers \Racketmanager\Domain\Enums\Fixture_Status
 * @covers \Racketmanager\Domain\Enums\Team_Profile
 */
final class Domain_Enums_Test extends TestCase {

    public function test_invoice_status_enum(): void {
        self::assertSame( 'draft', Invoice_Status::DRAFT->value );
        self::assertSame( 'new', Invoice_Status::NEW->value );
        self::assertSame( 'final', Invoice_Status::FINAL->value );
        self::assertSame( 'sent', Invoice_Status::SENT->value );
        self::assertSame( 'paid', Invoice_Status::PAID->value );
        self::assertSame( 'cancelled', Invoice_Status::CANCELLED->value );

        self::assertTrue( Invoice_Status::PAID->is_paid() );
        self::assertTrue( Invoice_Status::DRAFT->is_draft() );
        self::assertTrue( Invoice_Status::NEW->is_new() );
        self::assertTrue( Invoice_Status::FINAL->is_final() );
        self::assertTrue( Invoice_Status::SENT->is_sent() );
        self::assertTrue( Invoice_Status::CANCELLED->is_cancelled() );

        self::assertTrue( Invoice_Status::DRAFT->can_be_sent() );
        self::assertTrue( Invoice_Status::NEW->can_be_sent() );
        self::assertTrue( Invoice_Status::FINAL->can_be_sent() );
        self::assertTrue( Invoice_Status::SENT->can_be_sent() );
        self::assertFalse( Invoice_Status::PAID->can_be_sent() );
        self::assertFalse( Invoice_Status::CANCELLED->can_be_sent() );

        self::assertSame( Invoice_Status::SENT, Invoice_Status::from_value( 'resend' ) );
        self::assertSame( Invoice_Status::PAID, Invoice_Status::from_value( 'paid' ) );
        self::assertSame( Invoice_Status::PAID, Invoice_Status::from_value( Invoice_Status::PAID ) );
        self::assertSame( Invoice_Status::SENT, Invoice_Status::try_from_value( 'resend' ) );
        self::assertNull( Invoice_Status::try_from_value( 'unknown' ) );
        self::assertNull( Invoice_Status::try_from_value( null ) );
    }

    public function test_charge_status_enum(): void {
        self::assertSame( 'draft', Charge_Status::DRAFT->value );
        self::assertSame( 'final', Charge_Status::FINAL->value );

        self::assertTrue( Charge_Status::DRAFT->is_draft() );
        self::assertTrue( Charge_Status::FINAL->is_final() );
        self::assertFalse( Charge_Status::DRAFT->is_final() );

        self::assertSame( Charge_Status::DRAFT, Charge_Status::from_value( 'draft' ) );
        self::assertSame( Charge_Status::FINAL, Charge_Status::from_value( 'final' ) );
        self::assertSame( Charge_Status::FINAL, Charge_Status::from_value( Charge_Status::FINAL ) );
        self::assertSame( Charge_Status::FINAL, Charge_Status::try_from_value( 'final' ) );
        self::assertNull( Charge_Status::try_from_value( 'paid' ) );
        self::assertNull( Charge_Status::try_from_value( null ) );
    }

    public function test_club_role_enum(): void {
        self::assertSame( 1, Club_Role::MATCH_SECRETARY->value );
        self::assertSame( 2, Club_Role::COACH->value );
        self::assertSame( 3, Club_Role::TREASURER->value );

        self::assertTrue( Club_Role::MATCH_SECRETARY->is_match_secretary() );
        self::assertTrue( Club_Role::COACH->is_coach() );
        self::assertTrue( Club_Role::TREASURER->is_treasurer() );

        self::assertSame( 1, Club_Role::MATCH_SECRETARY->limit() );
        self::assertNull( Club_Role::COACH->limit() );
        self::assertSame( 1, Club_Role::TREASURER->limit() );

        self::assertSame( Club_Role::MATCH_SECRETARY, Club_Role::from_value( 1 ) );
        self::assertSame( Club_Role::COACH, Club_Role::from_value( '2' ) );
        self::assertSame( Club_Role::TREASURER, Club_Role::from_value( Club_Role::TREASURER ) );
        self::assertSame( Club_Role::TREASURER, Club_Role::try_from_value( 3 ) );
        self::assertNull( Club_Role::try_from_value( 99 ) );
        self::assertNull( Club_Role::try_from_value( null ) );
    }

    public function test_fixture_status_enum(): void {
        self::assertSame( 0, Fixture_Status::COMPLETE->value );
        self::assertSame( 1, Fixture_Status::WALKOVER->value );
        self::assertSame( 2, Fixture_Status::RETIRED->value );
        self::assertSame( 3, Fixture_Status::SHARED->value );
        self::assertSame( 5, Fixture_Status::RESCHEDULED->value );
        self::assertSame( 6, Fixture_Status::ABANDONED->value );
        self::assertSame( 7, Fixture_Status::WITHDRAWN->value );
        self::assertSame( 8, Fixture_Status::CANCELLED->value );
        self::assertSame( 9, Fixture_Status::INVALID->value );

        self::assertTrue( Fixture_Status::COMPLETE->is_complete() );
        self::assertTrue( Fixture_Status::WALKOVER->is_walkover() );
        self::assertTrue( Fixture_Status::RETIRED->is_retired() );
        self::assertTrue( Fixture_Status::SHARED->is_shared() );
        self::assertTrue( Fixture_Status::RESCHEDULED->is_rescheduled() );
        self::assertTrue( Fixture_Status::ABANDONED->is_abandoned() );
        self::assertTrue( Fixture_Status::WITHDRAWN->is_withdrawn() );
        self::assertTrue( Fixture_Status::CANCELLED->is_cancelled() );
        self::assertTrue( Fixture_Status::INVALID->is_invalid() );

        self::assertSame( Fixture_Status::WALKOVER, Fixture_Status::from_value( 1 ) );
        self::assertSame( Fixture_Status::SHARED, Fixture_Status::from_value( '3' ) );
        self::assertSame( Fixture_Status::CANCELLED, Fixture_Status::from_value( Fixture_Status::CANCELLED ) );

        // Code 4 does not exist in domain / lookup
        self::assertNull( Fixture_Status::try_from_value( 4 ) );
        self::assertNull( Fixture_Status::try_from_value( 99 ) );
        self::assertNull( Fixture_Status::try_from_value( null ) );
        self::assertNull( Fixture_Status::try_from_value( '' ) );
        self::assertNull( Fixture_Status::try_from_value( 'abc' ) );
        foreach ( [ '', 'abc', 4, 99, '4' ] as $invalid ) {
            try {
                Fixture_Status::from_value( $invalid );
                self::fail( 'Expected ValueError for ' . var_export( $invalid, true ) );
            } catch ( \ValueError ) {
                self::assertTrue( true );
            }
        }
        self::assertSame( Fixture_Status::WALKOVER, Fixture_Status::from_value( '1' ) );
        self::assertSame( Fixture_Status::COMPLETE, Fixture_Status::try_from_value( 0 ) );
        self::assertSame( Fixture_Status::COMPLETE, Fixture_Status::try_from_value( '0' ) );
    }

    public function test_team_profile_enum(): void {
        self::assertSame( 0, Team_Profile::PENDING->value );
        self::assertSame( 1, Team_Profile::ACTIVE->value );
        self::assertSame( 2, Team_Profile::NEW->value );
        self::assertSame( 3, Team_Profile::WITHDRAWN->value );

        self::assertTrue( Team_Profile::PENDING->is_pending() );
        self::assertTrue( Team_Profile::ACTIVE->is_active() );
        self::assertTrue( Team_Profile::NEW->is_new() );
        self::assertTrue( Team_Profile::WITHDRAWN->is_withdrawn() );

        self::assertSame( Team_Profile::ACTIVE, Team_Profile::from_value( 1 ) );
        self::assertSame( Team_Profile::WITHDRAWN, Team_Profile::from_value( '3' ) );
        self::assertSame( Team_Profile::ACTIVE, Team_Profile::from_value( Team_Profile::ACTIVE ) );
        self::assertNull( Team_Profile::try_from_value( 99 ) );
        self::assertNull( Team_Profile::try_from_value( null ) );
    }
}
