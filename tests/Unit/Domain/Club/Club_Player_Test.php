<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Tests\Support\Fixed_Clock;
use ReflectionClass;

final class Club_Player_Test extends TestCase {

    private function registration( ?string $status = null ): Club_Player {
        return Club_Player::from_object(
            (object) array(
                'id'             => 100,
                'club_id'        => 5,
                'player_id'      => 20,
                'status'         => $status,
                'requested_date' => '2026-01-01',
                'requested_user' => 1,
            )
        );
    }

    public function test_empty_instantiation_defaults_all_properties_to_null(): void {
        $registration = new Club_Player();

        $this->assertNull( $registration->id );
        $this->assertNull( $registration->get_id() );
        $this->assertNull( $registration->player_id );
        $this->assertNull( $registration->get_player_id() );
        $this->assertNull( $registration->club_id );
        $this->assertNull( $registration->get_club_id() );
        $this->assertNull( $registration->player );
        $this->assertNull( $registration->get_player() );
        $this->assertNull( $registration->status );
        $this->assertNull( $registration->get_status() );
        $this->assertNull( $registration->system_record );
        $this->assertNull( $registration->get_system_record() );
        $this->assertNull( $registration->created_date );
        $this->assertNull( $registration->get_created_date() );
        $this->assertNull( $registration->created_user );
        $this->assertNull( $registration->get_created_user() );
        $this->assertNull( $registration->requested_date );
        $this->assertNull( $registration->get_requested_date() );
        $this->assertNull( $registration->requested_user );
        $this->assertNull( $registration->get_requested_user() );
        $this->assertNull( $registration->removed_date );
        $this->assertNull( $registration->get_removed_date() );
        $this->assertNull( $registration->removed_user );
        $this->assertNull( $registration->get_removed_user() );

        $this->assertFalse( $registration->is_active() );
        $this->assertFalse( $registration->is_pending() );
        $this->assertFalse( $registration->is_removed() );
        $this->assertFalse( $registration->is_system_record() );
    }

    public function test_set_id_mutates_id_property(): void {
        $registration = new Club_Player();
        $this->assertNull( $registration->get_id() );
        $this->assertNull( $registration->id );

        $registration->set_id( 42 );
        $this->assertSame( 42, $registration->get_id() );
        $this->assertSame( 42, $registration->id );

        $registration->set_id( null );
        $this->assertNull( $registration->get_id() );
        $this->assertNull( $registration->id );
    }

    public function test_getters_and_hydration_roundtrip_all_properties(): void {
        $player_obj = (object) array( 'name' => 'John Doe' );
        $data       = array(
            'id'             => 42,
            'player_id'      => 123,
            'club_id'        => 456,
            'player'         => $player_obj,
            'status'         => 'active',
            'system_record'  => true,
            'created_date'   => '2026-02-01 10:00:00',
            'created_user'   => 10,
            'requested_date' => '2026-01-15 09:30:00',
            'requested_user' => 5,
            'removed_date'   => '2026-03-01 18:00:00',
            'removed_user'   => 20,
        );

        $registration = Club_Player::from_array( $data );

        $this->assertSame( 42, $registration->get_id() );
        $this->assertSame( 42, $registration->id );
        $this->assertSame( 123, $registration->get_player_id() );
        $this->assertSame( 123, $registration->player_id );
        $this->assertSame( 456, $registration->get_club_id() );
        $this->assertSame( 456, $registration->club_id );
        $this->assertSame( $player_obj, $registration->get_player() );
        $this->assertSame( $player_obj, $registration->player );
        $this->assertSame( 'active', $registration->get_status() );
        $this->assertSame( 'active', $registration->status );
        $this->assertTrue( $registration->system_record );
        $this->assertTrue( $registration->get_system_record() );
        $this->assertSame( '2026-02-01 10:00:00', $registration->get_created_date() );
        $this->assertSame( '2026-02-01 10:00:00', $registration->created_date );
        $this->assertSame( 10, $registration->get_created_user() );
        $this->assertSame( 10, $registration->created_user );
        $this->assertSame( '2026-01-15 09:30:00', $registration->get_requested_date() );
        $this->assertSame( '2026-01-15 09:30:00', $registration->requested_date );
        $this->assertSame( 5, $registration->get_requested_user() );
        $this->assertSame( 5, $registration->requested_user );
        $this->assertSame( '2026-03-01 18:00:00', $registration->get_removed_date() );
        $this->assertSame( '2026-03-01 18:00:00', $registration->removed_date );
        $this->assertSame( 20, $registration->get_removed_user() );
        $this->assertSame( 20, $registration->removed_user );
    }

    public function test_from_object_and_from_array_hydration_populated_and_null_values(): void {
        $player_obj = (object) array( 'id' => 15 );
        $populated  = array(
            'id'             => 77,
            'player_id'      => 15,
            'club_id'        => 3,
            'status'         => 'active',
            'system_record'  => true,
            'created_date'   => '2026-01-01 12:00:00',
            'created_user'   => 2,
            'requested_date' => '2025-12-15',
            'requested_user' => 1,
            'removed_date'   => null,
            'removed_user'   => null,
            'player'         => $player_obj,
        );

        $from_array = Club_Player::from_array( $populated );
        $this->assertSame( 77, $from_array->get_id() );
        $this->assertSame( 15, $from_array->get_player_id() );
        $this->assertSame( 3, $from_array->get_club_id() );
        $this->assertSame( 'active', $from_array->get_status() );
        $this->assertTrue( $from_array->is_system_record() );
        $this->assertSame( '2026-01-01 12:00:00', $from_array->get_created_date() );
        $this->assertSame( 2, $from_array->get_created_user() );
        $this->assertSame( '2025-12-15', $from_array->get_requested_date() );
        $this->assertSame( 1, $from_array->get_requested_user() );
        $this->assertNull( $from_array->get_removed_date() );
        $this->assertNull( $from_array->get_removed_user() );
        $this->assertSame( $player_obj, $from_array->get_player() );

        $from_object = Club_Player::from_object( (object) $populated );
        $this->assertSame( 77, $from_object->get_id() );
        $this->assertSame( 15, $from_object->get_player_id() );
        $this->assertSame( 3, $from_object->get_club_id() );
        $this->assertSame( $player_obj, $from_object->get_player() );

        // Null / minimal values
        $minimal = Club_Player::from_array( array() );
        $this->assertNull( $minimal->get_id() );
        $this->assertNull( $minimal->get_player_id() );
        $this->assertNull( $minimal->get_club_id() );
        $this->assertNull( $minimal->get_player() );
        $this->assertNull( $minimal->get_status() );
    }

    public function test_status_helper_methods(): void {
        $registration = new Club_Player();

        // Null status
        $this->assertFalse( $registration->is_active() );
        $this->assertFalse( $registration->is_pending() );
        $this->assertFalse( $registration->is_removed() );
        $this->assertFalse( $registration->is_system_record() );

        // Active status
        $active = Club_Player::from_array( array( 'status' => 'active' ) );
        $this->assertTrue( $active->is_active() );
        $this->assertFalse( $active->is_pending() );
        $this->assertFalse( $active->is_removed() );

        // Approved status (also counts as active)
        $approved = Club_Player::from_array( array( 'status' => 'approved' ) );
        $this->assertTrue( $approved->is_active() );
        $this->assertFalse( $approved->is_pending() );
        $this->assertFalse( $approved->is_removed() );

        // Pending status
        $pending = Club_Player::from_array( array( 'status' => 'pending' ) );
        $this->assertFalse( $pending->is_active() );
        $this->assertTrue( $pending->is_pending() );
        $this->assertFalse( $pending->is_removed() );

        // Removed status
        $removed = Club_Player::from_array( array( 'status' => 'removed' ) );
        $this->assertFalse( $removed->is_active() );
        $this->assertFalse( $removed->is_pending() );
        $this->assertTrue( $removed->is_removed() );

        // System record helper
        $system_true = Club_Player::from_array( array( 'system_record' => true ) );
        $this->assertTrue( $system_true->is_system_record() );

        $system_false = Club_Player::from_array( array( 'system_record' => false ) );
        $this->assertFalse( $system_false->is_system_record() );

        $system_null = Club_Player::from_array( array( 'system_record' => null ) );
        $this->assertFalse( $system_null->is_system_record() );
    }

    public function test_external_mutation_of_id_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->id = 999;
    }

    public function test_external_mutation_of_player_id_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->player_id = 999;
    }

    public function test_external_mutation_of_club_id_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->club_id = 999;
    }

    public function test_external_mutation_of_player_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->player = (object) array();
    }

    public function test_external_mutation_of_status_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->status = 'active';
    }

    public function test_external_mutation_of_system_record_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->system_record = true;
    }

    public function test_external_mutation_of_created_date_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->created_date = '2026-01-01';
    }

    public function test_external_mutation_of_created_user_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->created_user = 1;
    }

    public function test_external_mutation_of_requested_date_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->requested_date = '2026-01-01';
    }

    public function test_external_mutation_of_requested_user_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->requested_user = 1;
    }

    public function test_external_mutation_of_removed_date_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->removed_date = '2026-01-01';
    }

    public function test_external_mutation_of_removed_user_throws_error(): void {
        $registration = $this->registration( 'pending' );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $registration->removed_user = 1;
    }

    public function test_approve_records_user_and_site_local_mysql_timestamp(): void {
        $registration = $this->registration( 'pending' );
        $clock        = new Fixed_Clock( '2026-03-01 12:34:56', 'Europe/London' );

        $registration->approve( 7, $clock->now() );

        $this->assertSame( 'approved', $registration->get_status() );
        $this->assertSame( 7, $registration->created_user );
        $this->assertSame( 7, $registration->get_created_user() );
        $this->assertSame( '2026-03-01 12:34:56', $registration->created_date );
        $this->assertSame( '2026-03-01 12:34:56', $registration->get_created_date() );
        $this->assertNull( $registration->removed_date );
        $this->assertTrue( $registration->is_active() );
        $this->assertFalse( $registration->is_pending() );
    }

    #[DataProvider( 'non_pending_statuses' )]
    public function test_approve_rejects_registrations_that_are_not_pending( ?string $status ): void {
        $registration = $this->registration( $status );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessageIsOrContains( 'Registration is not pending approval' );

        $registration->approve( 7, new Fixed_Clock()->now() );
    }

    /**
     * Provide statuses that cannot be approved.
     *
     * @return array<string, array{?string}> Test statuses.
     */
    public static function non_pending_statuses(): array {
        return array(
            'approved' => array( 'approved' ),
            'active'   => array( 'active' ),
            'removed'  => array( 'removed' ),
            'null'     => array( null ),
        );
    }

    public function test_remove_records_user_and_site_local_mysql_timestamp_from_approved(): void {
        $registration = $this->registration( 'approved' );
        $clock        = new Fixed_Clock( '2026-07-15 23:59:59', 'Europe/London' );

        $registration->remove( 9, $clock->now() );

        $this->assertSame( 'removed', $registration->get_status() );
        $this->assertSame( 9, $registration->removed_user );
        $this->assertSame( 9, $registration->get_removed_user() );
        $this->assertSame( '2026-07-15 23:59:59', $registration->removed_date );
        $this->assertSame( '2026-07-15 23:59:59', $registration->get_removed_date() );
        $this->assertTrue( $registration->is_removed() );
        $this->assertFalse( $registration->is_active() );
    }

    public function test_remove_records_user_and_timestamp_from_pending(): void {
        $registration = $this->registration( 'pending' );
        $clock        = new Fixed_Clock( '2026-07-15 11:22:33', 'Europe/London' );

        $registration->remove( 4, $clock->now() );

        $this->assertSame( 'removed', $registration->get_status() );
        $this->assertSame( 4, $registration->get_removed_user() );
        $this->assertSame( '2026-07-15 11:22:33', $registration->get_removed_date() );
        $this->assertTrue( $registration->is_removed() );
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

        $this->assertSame( '2026-08-01 09:00:00', $registration->created_date );
    }

    public function test_status_constants_have_expected_values(): void {
        $this->assertSame( 'active', Club_Player::STATUS_ACTIVE );
        $this->assertSame( 'approved', Club_Player::STATUS_APPROVED );
        $this->assertSame( 'pending', Club_Player::STATUS_PENDING );
        $this->assertSame( 'removed', Club_Player::STATUS_REMOVED );
    }

    public function test_method_count_does_not_exceed_limit(): void {
        $reflection = new ReflectionClass( Club_Player::class );
        $methods    = $reflection->getMethods();

        $this->assertCount( 23, $methods );
        $this->assertLessThanOrEqual( 30, count( $methods ) );
    }
}
