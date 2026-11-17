<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Result\Result;

final class Fixture_Test extends TestCase {
    public function test_set_result_updates_fixture_properties(): void {
        $fixture = Fixture_Factory::from_object((object)[
            'id' => 1,
            'status' => 0
        ]);

        $result = new Result(
            home_points: 3.0,
            away_points: 1.0,
            winner_id: 10,
            loser_id: 20,
            status: 1, // Walkover
            sets: [['p1' => 6, 'p2' => 4]],
            custom: ['some' => 'data']
        );

        $fixture->set_result($result);

        $this->assertSame('3', $fixture->get_home_points());
        $this->assertSame('1', $fixture->get_away_points());
        $this->assertSame(10, $fixture->get_winner_id());
        $this->assertSame(20, $fixture->get_loser_id());
        $this->assertSame(1, $fixture->get_status());
        $this->assertTrue($fixture->is_walkover());
        
        $custom = $fixture->get_custom();
        $this->assertSame('data', $custom['some']);
        $this->assertSame([['p1' => 6, 'p2' => 4]], $custom['sets']);
    }

    public function test_set_result_updates_status_flags(): void {
        $fixture = Fixture_Factory::from_object((object)['id' => 1]);
        
        // Test Shared status
        $result = new Result(home_points: 2, away_points: 2, status: 3);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_shared());
        $this->assertFalse($fixture->is_walkover());

        // Test Cancelled status
        $result = new Result(home_points: 0, away_points: 0, status: 8);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_cancelled());
        $this->assertFalse($fixture->is_shared());
    }

    public function test_is_pending_reflects_winner_id(): void {
        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => null ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => 0 ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => 123 ]);
        $this->assertFalse($fixture->is_pending());

        $result = new Result(home_points: 3, away_points: 0, winner_id: 456);
        $fixture->set_result($result);
        $this->assertFalse($fixture->is_pending());

        $fixture->reset_result();
        $this->assertTrue($fixture->is_pending());
    }

    public function test_start_time_is_set_from_constructor(): void {
        $fixture = Fixture_Factory::from_object((object)[ 'start_time' => '10:00' ]);
        $this->assertEquals('10:00', $fixture->get_start_time());

        $fixture->set_start_time('11:30');
        $this->assertEquals('11:30', $fixture->get_start_time());
    }

    public function test_constructor_handles_string_comments_gracefully(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => 'This is a plain string comment'
        ];

        // This should NOT throw a TypeError
        $fixture = Fixture_Factory::from_object($fixture_data);

        $this->assertIsArray($fixture->get_comments(), 'Comments should be converted to an array');
        $this->assertSame('This is a plain string comment', $fixture->get_comments()['legacy']);
    }

    public function test_constructor_handles_null_comments_gracefully(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => null
        ];

        $fixture = Fixture_Factory::from_object($fixture_data);
        $this->assertNull($fixture->get_comments());
    }

    public function test_create_from_dto_directly(): void {
        $dto = new Fixture_Hydration_DTO(
            id: 99,
            fixture_title: 'Title',
            link: 'http://test',
            date: '2026-05-01',
            start_time: '14:00',
            season: '2026',
            league_id: 10,
            home_team: 'Home Team',
            away_team: 'Away Team',
            home_points: '4',
            away_points: '2',
            winner_id: 1,
            loser_id: 2,
            status: 0,
            confirmed: 'Y',
            custom: array( 'note' => 'test' ),
            comments: array( 'admin' => 'ok' ),
            rubbers: array()
        );

        $fixture = new Fixture( $dto );
        $this->assertSame( 99, $fixture->get_id() );
        $this->assertSame( 'Title', $fixture->fixture_title );
        $this->assertSame( '2026', $fixture->get_season() );
        $this->assertSame( 10, $fixture->get_league_id() );
        $this->assertSame( 'Home Team', $fixture->get_home_team() );
        $this->assertSame( 'Away Team', $fixture->get_away_team() );
        $this->assertSame( '4', $fixture->get_home_points() );
        $this->assertSame( '2', $fixture->get_away_points() );
        $this->assertSame( 1, $fixture->get_winner_id() );
        $this->assertSame( 2, $fixture->get_loser_id() );
        $this->assertSame( 'Y', $fixture->get_confirmed() );
        $this->assertSame( array( 'note' => 'test' ), $fixture->get_custom() );
        $this->assertSame( array( 'admin' => 'ok' ), $fixture->get_comments() );
    }

    public function test_fixture_final_property_and_finals_data(): void {
        $fixture = Fixture_Factory::from_object( (object) [
            'id'    => 10,
            'final' => 'final',
            'date'  => '2026-06-01 10:00:00',
        ] );

        $this->assertSame( 'final', $fixture->get_final() );

        $fixture->set_final( 'semi' );
        $this->assertSame( 'semi', $fixture->get_final() );

        // Test set_finals_data
        $changed = $fixture->set_finals_data( '2026-06-15 14:00:00', 'Court 1' );
        $this->assertTrue( $changed );
        $this->assertSame( '2026-06-15 14:00:00', $fixture->get_date() );
        $this->assertSame( 'Court 1', $fixture->get_location() );

        // Test reset_finals_data
        $reset_changed = $fixture->reset_finals_data( '2026-06-20 10:00:00' );
        $this->assertTrue( $reset_changed );
        $this->assertSame( '2026-06-20 10:00:00', $fixture->get_date() );
    }

    public function test_get_and_set_rubbers_pure_in_memory(): void {
        $fixture = Fixture_Factory::from_array( array( 'id' => 10 ) );
        $this->assertSame( array(), $fixture->get_rubbers() );

        $rubber1 = $this->createStub( \Racketmanager\Domain\Fixture\Rubber::class );
        $rubber2 = $this->createStub( \Racketmanager\Domain\Fixture\Rubber::class );

        $fixture->set_rubbers( array( $rubber1, $rubber2 ) );
        $this->assertSame( array( $rubber1, $rubber2 ), $fixture->get_rubbers() );
        $fixture->set_rubbers( array() );
        $this->assertSame( array(), $fixture->get_rubbers() );
    }

    public function test_set_date_synchronizes_start_time_with_valid_time(): void {
        $fixture = new Fixture();

        $fixture->set_date( '2026-05-15 19:30:00' );
        $this->assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
        $this->assertSame( '19:30', $fixture->get_start_time() );

        $fixture->set_date( '2026-05-15 14:15' );
        $this->assertSame( '2026-05-15 14:15', $fixture->get_date() );
        $this->assertSame( '14:15', $fixture->get_start_time() );

        $fixture->set_date( '2026-05-15T16:45:00' );
        $this->assertSame( '2026-05-15T16:45:00', $fixture->get_date() );
        $this->assertSame( '16:45', $fixture->get_start_time() );
    }

    public function test_set_date_clears_start_time_for_date_only_and_midnight(): void {
        $fixture = new Fixture();

        $fixture->set_date( '2026-05-15 19:30:00' );
        $this->assertSame( '19:30', $fixture->get_start_time() );

        $fixture->set_date( '2026-06-01' );
        $this->assertSame( '2026-06-01', $fixture->get_date() );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_date( '2026-06-01 19:30:00' );
        $this->assertSame( '19:30', $fixture->get_start_time() );

        $fixture->set_date( '2026-06-02 00:00:00' );
        $this->assertSame( '2026-06-02 00:00:00', $fixture->get_date() );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_date( '2026-06-01 19:30:00' );
        $fixture->set_date( '0000-00-00 00:00:00' );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_date( '2026-06-01 19:30:00' );
        $fixture->set_date( null );
        $this->assertNull( $fixture->get_date() );
        $this->assertNull( $fixture->get_start_time() );
    }

    public function test_set_start_time_merges_date_without_mutating_date_original(): void {
        $fixture = Fixture_Factory::from_object( (object) [
            'id'            => 10,
            'date'          => '2026-06-15 10:00:00',
            'date_original' => '2026-06-01 09:00:00',
        ] );

        $this->assertSame( '10:00', $fixture->get_start_time() );
        $this->assertSame( '2026-06-15 10:00:00', $fixture->get_date() );
        $this->assertSame( '2026-06-01 09:00:00', $fixture->get_date_original() );

        $fixture->set_start_time( '18:30' );
        $this->assertSame( '18:30', $fixture->get_start_time() );
        $this->assertSame( '2026-06-15 18:30:00', $fixture->get_date() );
        $this->assertSame( '2026-06-01 09:00:00', $fixture->get_date_original() );

        $fixture->set_start_time( '  20:00:00  ' );
        $this->assertSame( '20:00', $fixture->get_start_time() );
        $this->assertSame( '2026-06-15 20:00:00', $fixture->get_date() );
        $this->assertSame( '2026-06-01 09:00:00', $fixture->get_date_original() );

        $fixture->set_start_time( null );
        $this->assertNull( $fixture->get_start_time() );
        $this->assertSame( '2026-06-15 00:00:00', $fixture->get_date() );
        $this->assertSame( '2026-06-01 09:00:00', $fixture->get_date_original() );

        $fixture->set_start_time( '00:00' );
        $this->assertNull( $fixture->get_start_time() );
        $this->assertSame( '2026-06-15 00:00:00', $fixture->get_date() );
        $this->assertSame( '2026-06-01 09:00:00', $fixture->get_date_original() );
    }

    public function test_set_start_time_when_date_is_null(): void {
        $fixture = new Fixture();
        $fixture->set_start_time( '14:30' );

        $this->assertSame( '14:30', $fixture->get_start_time() );
        $this->assertNull( $fixture->get_date() );
    }

    public function test_staged_construction_merges_subsequent_date_only_set_date(): void {
        $fixture = new Fixture();
        $fixture->set_start_time( '18:30' );
        $this->assertNull( $fixture->get_date() );
        $this->assertSame( '18:30', $fixture->get_start_time() );

        $fixture->set_date( '2026-06-15' );
        $this->assertSame( '2026-06-15 18:30:00', $fixture->get_date() );
        $this->assertSame( '18:30', $fixture->get_start_time() );
    }

    public function test_set_start_time_on_date_only_date_merges_datetime(): void {
        $fixture = new Fixture();
        $fixture->set_date( '2026-06-15' );
        $this->assertSame( '2026-06-15', $fixture->get_date() );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_start_time( '18:30' );
        $this->assertSame( '2026-06-15 18:30:00', $fixture->get_date() );
        $this->assertSame( '18:30', $fixture->get_start_time() );
    }

    public function test_set_start_time_throws_exception_on_invalid_string_and_preserves_schedule(): void {
        $fixture = new Fixture();
        $fixture->set_date( '2026-06-15 19:30:00' );
        $this->assertSame( '2026-06-15 19:30:00', $fixture->get_date() );
        $this->assertSame( '19:30', $fixture->get_start_time() );

        $invalid_values = [ '7pm', '25:00', '19.30', 'invalid', '12:60', '-01:00' ];

        foreach ( $invalid_values as $invalid_value ) {
            $exception_thrown = false;
            try {
                $fixture->set_start_time( $invalid_value );
            } catch ( \InvalidArgumentException ) {
                $exception_thrown = true;
            }

            $this->assertTrue( $exception_thrown, sprintf( 'Expected InvalidArgumentException for "%s"', $invalid_value ) );
            $this->assertSame( '2026-06-15 19:30:00', $fixture->get_date(), 'Date must remain untouched after invalid time' );
            $this->assertSame( '19:30', $fixture->get_start_time(), 'Start time must remain untouched after invalid time' );
        }
    }

    public function test_set_date_iso_format_and_empty_handling(): void {
        $fixture = new Fixture();

        $fixture->set_date( '2026-05-15T19:30:00' );
        $this->assertSame( '19:30', $fixture->get_start_time() );

        $fixture->set_date( '2026-05-15T00:00:00' );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_start_time( '18:30' );
        $fixture->set_date( '' );
        $this->assertNull( $fixture->get_start_time() );

        $fixture->set_start_time( '18:30' );
        $fixture->set_date( '0000-00-00 00:00:00' );
        $this->assertNull( $fixture->get_start_time() );
    }

    public function test_get_start_time_normalizes_empty_and_midnight_values(): void {
        $fixture = Fixture_Factory::from_array( [ 'start_time' => '00:00:00' ] );
        $this->assertNull( $fixture->get_start_time() );

        $fixture = Fixture_Factory::from_array( [ 'start_time' => '00:00' ] );
        $this->assertNull( $fixture->get_start_time() );

        $fixture = Fixture_Factory::from_array( [ 'start_time' => '' ] );
        $this->assertNull( $fixture->get_start_time() );

        $fixture = Fixture_Factory::from_array( [ 'start_time' => '19:45:00' ] );
        $this->assertSame( '19:45', $fixture->get_start_time() );
    }

    public function test_finals_data_synchronizes_start_time(): void {
        $fixture = Fixture_Factory::from_object( (object) [
            'id'   => 10,
            'date' => '2026-06-01 10:00:00',
        ] );

        $this->assertSame( '10:00', $fixture->get_start_time() );

        $fixture->set_finals_data( '2026-06-15 15:30:00', 'Court 1' );
        $this->assertSame( '2026-06-15 15:30:00', $fixture->get_date() );
        $this->assertSame( '15:30', $fixture->get_start_time() );

        $fixture->reset_finals_data( '2026-06-20' );
        $this->assertSame( '2026-06-20', $fixture->get_date() );
        $this->assertNull( $fixture->get_start_time() );
    }

    public function test_hydration_preserves_date_string_format_and_precision_exactly(): void {
        $fixture_seconds = Fixture_Factory::from_object( (object) [
            'id'   => 101,
            'date' => '2026-05-15 19:30:45',
        ] );
        $this->assertSame( '2026-05-15 19:30:45', $fixture_seconds->get_date() );
        $this->assertSame( '19:30', $fixture_seconds->get_start_time() );

        $fixture_16_char = Fixture_Factory::from_object( (object) [
            'id'   => 102,
            'date' => '2026-10-12 19:30',
        ] );
        $this->assertSame( '2026-10-12 19:30', $fixture_16_char->get_date() );
        $this->assertSame( '19:30', $fixture_16_char->get_start_time() );

        $fixture_iso_offset = Fixture_Factory::from_object( (object) [
            'id'   => 103,
            'date' => '2026-05-15T19:30:00+01:00',
        ] );
        $this->assertSame( '2026-05-15T19:30:00+01:00', $fixture_iso_offset->get_date() );
        $this->assertSame( '19:30', $fixture_iso_offset->get_start_time() );
    }

    public function test_direct_dto_derives_start_time_when_not_provided(): void {
        $dto_timed = new Fixture_Hydration_DTO( date: '2026-05-15 19:30:00' );
        $fixture_timed = new Fixture( $dto_timed );
        $this->assertSame( '2026-05-15 19:30:00', $fixture_timed->get_date() );
        $this->assertSame( '19:30', $fixture_timed->get_start_time() );

        $dto_date_only = new Fixture_Hydration_DTO( date: '2026-05-15' );
        $fixture_date_only = new Fixture( $dto_date_only );
        $this->assertSame( '2026-05-15', $fixture_date_only->get_date() );
        $this->assertNull( $fixture_date_only->get_start_time() );
    }

    public function test_from_object_handles_invalid_explicit_start_time_leniently(): void {
        $fixture_with_time = Fixture_Factory::from_object( (object) [
            'id'         => 104,
            'date'       => '2026-05-15 19:30:00',
            'start_time' => '7pm',
        ] );
        $this->assertSame( '2026-05-15 19:30:00', $fixture_with_time->get_date() );
        $this->assertSame( '19:30', $fixture_with_time->get_start_time() );

        $fixture_without_time = Fixture_Factory::from_object( (object) [
            'id'         => 105,
            'date'       => '2026-05-15',
            'start_time' => '25:00',
        ] );
        $this->assertSame( '2026-05-15', $fixture_without_time->get_date() );
        $this->assertNull( $fixture_without_time->get_start_time() );

        $this->expectException( \InvalidArgumentException::class );
        $fixture_with_time->set_start_time( '7pm' );
    }

    public function test_asymmetric_visibility_rejects_external_writes(): void {
        $fixture = new Fixture();

        $this->expectException( \Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $fixture->date = '2026-12-01 10:00:00';
    }

    public function test_asymmetric_visibility_rejects_indirect_array_modification(): void {
        $fixture = new Fixture();
        $fixture->set_teams( array( 'home' => 'Team A' ) );

        $this->expectException( \Error::class );
        $this->expectExceptionMessageMatches( '/Cannot indirectly modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $fixture->teams['home'] = 'Team B';
    }

    public function test_set_class_and_set_match_title_accessors(): void {
        $fixture = new Fixture();
        $this->assertNull( $fixture->class );
        $this->assertNull( $fixture->match_title );

        $fixture->set_class( 'alternate' );
        $fixture->set_match_title( 'Finals Match' );

        $this->assertSame( 'alternate', $fixture->class );
        $this->assertSame( 'Finals Match', $fixture->match_title );
    }

    public function test_set_final_round_updates_fixture_property(): void {
        $fixture = new Fixture();

        $fixture->set_final_round( 'Semi-final' );
        $this->assertSame( 'Semi-final', $fixture->final_round );

        $fixture->set_final_round( null );
        $this->assertNull( $fixture->final_round );
    }

    public function test_properties_are_readable_natively_after_hydration(): void {
        $dto = new Fixture_Hydration_DTO(
            id: 42,
            date: '2026-06-15 19:30:00',
            home_team: 'Home Club',
            away_team: 'Away Club',
            status: 1,
            winner_id: 10,
            home_points: '4',
            away_points: '2',
        );
        $fixture = new Fixture( $dto );

        $this->assertSame( 42, $fixture->id );
        $this->assertSame( '2026-06-15 19:30:00', $fixture->date );
        $this->assertSame( 'Home Club', $fixture->home_team );
        $this->assertSame( 'Away Club', $fixture->away_team );
        $this->assertSame( 1, $fixture->status );
        $this->assertSame( 10, $fixture->winner_id );
        $this->assertSame( '4', $fixture->home_points );
        $this->assertSame( '2', $fixture->away_points );
    }

    public function test_record_result_synchronizes_state_atomically(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 1 ] );

        $result = new Result(
            home_points: 4.5,
            away_points: 2.5,
            winner_id: 11,
            loser_id: 22,
            status: 1, // Walkover
            sets: [ [ 'p1' => 6, 'p2' => 0 ] ],
            custom: [ 'notes' => 'Match completed under walkover rule' ]
        );

        $fixture->record_result( $result );

        $this->assertSame( '4.5', $fixture->home_points );
        $this->assertSame( '2.5', $fixture->away_points );
        $this->assertSame( 11, $fixture->winner_id );
        $this->assertSame( 22, $fixture->loser_id );
        $this->assertSame( 1, $fixture->status );
        $this->assertTrue( $fixture->is_walkover );
        $this->assertTrue( $fixture->is_walkover() );
        $this->assertFalse( $fixture->is_pending );
        $this->assertSame( [ [ 'p1' => 6, 'p2' => 0 ] ], $fixture->custom['sets'] );
        $this->assertSame( 'Match completed under walkover rule', $fixture->custom['notes'] );
    }

    public function test_confirm_result_with_default_arguments(): void {
        $fixture = new Fixture();
        $this->assertNull( $fixture->confirmed );
        $this->assertNull( $fixture->date_result_entered );
        $this->assertNull( $fixture->date_result_confirmed );

        $fixture->confirm_result();

        $this->assertSame( 'Y', $fixture->confirmed );
        $this->assertNull( $fixture->date_result_confirmed, 'domain must not read the clock' );
        $this->assertNull( $fixture->date_result_entered, 'date_result_entered must not be touched' );
    }

    public function test_confirm_result_sets_supplied_timestamp(): void {
        $fixture = new Fixture();
        $fixture->confirm_result( 'Y', '2026-11-17 12:00:00' );

        $this->assertSame( 'Y', $fixture->confirmed );
        $this->assertSame( '2026-11-17 12:00:00', $fixture->date_result_confirmed );
        $this->assertNull( $fixture->date_result_entered );
    }

    public function test_confirm_result_without_date_keeps_existing_timestamp(): void {
        $fixture = new Fixture();
        $fixture->confirm_result( 'Y', '2026-11-17 12:00:00' );
        $fixture->confirm_result( 'Y' );

        $this->assertSame( '2026-11-17 12:00:00', $fixture->date_result_confirmed );

        $fixture->confirm_result( 'Y', '2026-11-18 09:15:00' );
        $this->assertSame( '2026-11-18 09:15:00', $fixture->date_result_confirmed );
    }

    public function test_confirm_result_non_confirmed_status_clears_timestamp(): void {
        $fixture = new Fixture();
        $fixture->confirm_result( 'Y', '2026-11-17 12:00:00' );

        $fixture->confirm_result( 'P' );
        $this->assertSame( 'P', $fixture->confirmed );
        $this->assertNull( $fixture->date_result_confirmed );

        $fixture->confirm_result( 'Y', '2026-11-17 13:00:00' );
        $fixture->confirm_result( 'N', '2026-11-17 14:00:00' );
        $this->assertSame( 'N', $fixture->confirmed );
        $this->assertNull( $fixture->date_result_confirmed, 'explicit date is ignored when not confirming' );

        $fixture->confirm_result( 'Y', '2026-11-17 15:00:00' );
        $fixture->confirm_result( null );
        $this->assertNull( $fixture->confirmed );
        $this->assertNull( $fixture->date_result_confirmed );
    }

    public function test_reset_result_clears_date_result_confirmed(): void {
        $fixture = new Fixture();
        $fixture->confirm_result( 'Y', '2026-11-17 12:00:00' );
        $this->assertSame( '2026-11-17 12:00:00', $fixture->date_result_confirmed );

        $fixture->reset_result();
        $this->assertNull( $fixture->date_result_confirmed );
        $this->assertNull( $fixture->confirmed );
    }

    public function test_hydration_populates_date_result_confirmed(): void {
        $fixture = Fixture_Factory::from_object(
            (object) [
                'id'                    => 1,
                'date_result_confirmed' => '2026-11-17 14:30:00',
            ]
        );

        $this->assertSame( '2026-11-17 14:30:00', $fixture->date_result_confirmed );
    }

    public function test_record_result_with_empty_sets(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 1 ] );

        $result = new Result(
            home_points: 3.0,
            away_points: 1.0,
            winner_id: 5,
            loser_id: 6,
            status: 0,
            sets: [],
            custom: [ 'notes' => 'No sets recorded' ]
        );

        $fixture->record_result( $result );

        $this->assertSame( '3', $fixture->home_points );
        $this->assertSame( '1', $fixture->away_points );
        $this->assertSame( 5, $fixture->winner_id );
        $this->assertSame( 6, $fixture->loser_id );
        $this->assertArrayNotHasKey( 'sets', $fixture->custom );
        $this->assertSame( 'No sets recorded', $fixture->custom['notes'] );
    }

    public function test_record_result_repeated_overwrites_previous_result_and_flags(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 1 ] );

        // First: Walkover result
        $result_walkover = new Result(
            home_points: 4.0,
            away_points: 0.0,
            winner_id: 11,
            loser_id: 22,
            status: 1, // Walkover
            sets: [ [ 'p1' => 6, 'p2' => 0 ] ],
            custom: [ 'walkover' => true ]
        );
        $fixture->record_result( $result_walkover );

        $this->assertTrue( $fixture->is_walkover );
        $this->assertSame( '4', $fixture->home_points );
        $this->assertSame( 11, $fixture->winner_id );

        // Second: Overwrite with completed result
        $result_complete = new Result(
            home_points: 2.0,
            away_points: 3.0,
            winner_id: 22,
            loser_id: 11,
            status: 0, // Complete
            sets: [ [ 'p1' => 4, 'p2' => 6 ], [ 'p1' => 2, 'p2' => 6 ] ],
            custom: []
        );
        $fixture->record_result( $result_complete );

        $this->assertFalse( $fixture->is_walkover );
        $this->assertSame( '2', $fixture->home_points );
        $this->assertSame( '3', $fixture->away_points );
        $this->assertSame( 22, $fixture->winner_id );
        $this->assertSame( 11, $fixture->loser_id );
        $this->assertSame( 0, $fixture->status );
        $this->assertSame( [ [ 'p1' => 4, 'p2' => 6 ], [ 'p1' => 2, 'p2' => 6 ] ], $fixture->custom['sets'] );
    }

    public function test_reschedule_with_empty_and_zero_date(): void {
        $fixture = new Fixture();
        $fixture->reschedule( '2026-11-25 18:30:00' );
        $this->assertSame( '18:30', $fixture->get_start_time() );

        // Reschedule to empty string
        $fixture->reschedule( '' );
        $this->assertSame( '', $fixture->date );
        $this->assertNull( $fixture->get_start_time() );

        // Reschedule to zero date
        $fixture->reschedule( '0000-00-00 00:00:00' );
        $this->assertSame( '0000-00-00 00:00:00', $fixture->date );
        $this->assertNull( $fixture->get_start_time() );
    }

    public function test_reschedule_updates_date_time_and_location(): void {
        $fixture = new Fixture();
        $fixture->reschedule( '2026-11-25', '18:30', 'Court 3' );

        $this->assertSame( '2026-11-25 18:30:00', $fixture->date );
        $this->assertSame( '18:30', $fixture->get_start_time() );
        $this->assertSame( 'Court 3', $fixture->location );
    }

    public function test_reschedule_preserves_location_when_omitted(): void {
        $fixture = new Fixture();
        $fixture->set_location( 'Original Venue' );

        $fixture->reschedule( '2026-12-05 14:00:00' );

        $this->assertSame( '2026-12-05 14:00:00', $fixture->date );
        $this->assertSame( '14:00', $fixture->get_start_time() );
        $this->assertSame( 'Original Venue', $fixture->location );
    }

    public function test_assign_teams_updates_both_teams(): void {
        $fixture = new Fixture();
        $fixture->assign_teams( 101, '202' );

        $this->assertSame( '101', $fixture->home_team );
        $this->assertSame( '202', $fixture->away_team );
    }
}
