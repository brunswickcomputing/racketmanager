<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Exceptions\Fixture_Validation_Exception;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Fixture_Date_Update_Request_Test extends TestCase {

    public function test_from_post_and_validate_success(): void {
        $post = [
            'match_id'      => '123',
            'schedule-date' => '2026-05-15 19:30:00',
            'modal'         => 'reschedule',
        ];

        $request = Fixture_Date_Update_Request::from_post( $post );
        $this->assertSame( 123, $request->match_id );
        $this->assertSame( '2026-05-15 19:30:00', $request->schedule_date );
        $this->assertSame( 'reschedule', $request->modal );

        // Should not throw
        $request->validate();
    }

    public function test_validate_date_only_success(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: '2026-05-15'
        );

        $request->validate();
        $this->assertSame( 123, $request->match_id );
    }

    public function test_validate_throws_on_missing_match_id(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 0,
            schedule_date: '2026-05-15'
        );

        $this->expectException( Fixture_Validation_Exception::class );
        try {
            $request->validate();
        } catch ( Fixture_Validation_Exception $e ) {
            $this->assertContains( 'match_id', $e->get_error_flds() );
            throw $e;
        }
    }

    public function test_validate_throws_on_missing_schedule_date(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: ''
        );

        $this->expectException( Fixture_Validation_Exception::class );
        try {
            $request->validate();
        } catch ( Fixture_Validation_Exception $e ) {
            $this->assertContains( 'schedule-date', $e->get_error_flds() );
            throw $e;
        }
    }

    public function test_validate_throws_on_invalid_schedule_date_format(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: 'garbage-date'
        );

        $this->expectException( Fixture_Validation_Exception::class );
        try {
            $request->validate();
        } catch ( Fixture_Validation_Exception $e ) {
            $this->assertContains( 'schedule-date', $e->get_error_flds() );
            throw $e;
        }
    }

    public function test_validate_throws_on_out_of_range_calendar_date(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: '2026-13-45 25:99'
        );

        $this->expectException( Fixture_Validation_Exception::class );
        try {
            $request->validate();
        } catch ( Fixture_Validation_Exception $e ) {
            $this->assertContains( 'schedule-date', $e->get_error_flds() );
            throw $e;
        }
    }

    public function test_validate_throws_on_zero_date(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: '0000-00-00 00:00:00'
        );

        $this->expectException( Fixture_Validation_Exception::class );
        try {
            $request->validate();
        } catch ( Fixture_Validation_Exception $e ) {
            $this->assertContains( 'schedule-date', $e->get_error_flds() );
            throw $e;
        }
    }
}
