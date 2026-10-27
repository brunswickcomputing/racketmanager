<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Tests\Support\Fixed_Clock;

final class Club_Player_Test extends TestCase {

    private function registration( string $status ): Club_Player {
        return Club_Player::from_object( (object) [
            'id'             => 100,
            'club_id'        => 5,
            'player_id'      => 20,
            'status'         => $status,
            'requested_date' => '2026-01-01',
            'requested_user' => 1,
        ] );
    }

    public function test_approve_records_user_and_site_local_mysql_timestamp(): void {
        $registration = $this->registration( 'pending' );
        $clock        = new Fixed_Clock( '2026-03-01 12:34:56', 'Europe/London' );

        $registration->approve( 7, $clock->now() );

        $this->assertSame( 'approved', $registration->get_status() );
        $this->assertSame( 7, $registration->get_created_user() );
        $this->assertSame( '2026-03-01 12:34:56', $registration->get_created_date() );
        $this->assertNull( $registration->get_removed_date() );
    }

    public function test_approve_rejects_registrations_that_are_not_pending(): void {
        $registration = $this->registration( 'approved' );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessageIsOrContains( 'Registration is not pending approval' );

        $registration->approve( 7, new Fixed_Clock()->now() );
    }

    public function test_remove_records_user_and_site_local_mysql_timestamp(): void {
        $registration = $this->registration( 'approved' );
        $clock        = new Fixed_Clock( '2026-07-15 23:59:59', 'Europe/London' );

        $registration->remove( 9, $clock->now() );

        $this->assertSame( 'removed', $registration->get_status() );
        $this->assertSame( 9, $registration->get_removed_user() );
        $this->assertSame( '2026-07-15 23:59:59', $registration->get_removed_date() );
    }

    public function test_remove_rejects_registrations_already_removed(): void {
        $registration = $this->registration( 'removed' );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessageIsOrContains( 'Registration is already removed' );

        $registration->remove( 9, new Fixed_Clock()->now() );
    }

    public function test_timestamp_is_formatted_in_the_clock_timezone_not_converted(): void {
        $registration = $this->registration( 'pending' );
        $clock        = new Fixed_Clock( '2026-08-01 09:00:00', 'Pacific/Auckland' );

        $registration->approve( 1, $clock->now() );

        $this->assertSame( '2026-08-01 09:00:00', $registration->get_created_date() );
    }
}
