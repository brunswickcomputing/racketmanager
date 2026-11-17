<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\DTO\Fixture\Rubber_Hydration_DTO;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Rubber_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_dummy_rubber(): Rubber {
        $data = (object) [
            'id'            => 10,
            'match_id'      => 100,
            'date'          => '2026-06-01 10:00:00',
            'rubber_number' => 1,
            'status'        => 0,
            'type'          => 'XD',
            'score'         => '',
            'custom'        => [],
        ];
        return Rubber::from_object( $data );
    }

    private function get_default_point_rule( array $custom = [] ): array {
        return array_merge(
            [
                'forwin'             => 1,
                'forwin_split'       => 1,
                'forshare'           => 1,
                'forwalkover_rubber' => 1,
            ],
            $custom
        );
    }

    public function test_instantiation_with_empty_or_null_date(): void {
        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2 ] );
        $this->assertSame( 1, $rubber->get_id() );
        $this->assertSame( 2, $rubber->get_match_id() );
        $this->assertSame( 0, $rubber->year );
        $this->assertSame( 0, $rubber->month );
        $this->assertSame( 0, $rubber->day );
        $this->assertSame( '', $rubber->rubber_date );
        $this->assertSame( '-:-', $rubber->score );
        $this->assertSame( 0, $rubber->get_winner_id() );
        $this->assertSame( 0, $rubber->get_loser_id() );
        $this->assertSame( '', $rubber->get_group() );
        $this->assertSame( 0, $rubber->get_post_id() );
        $this->assertNull( $rubber->get_status() );
    }

    public function test_calculate_result_straight_sets_home_win(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule( [ 'forwin' => 1 ] );

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 2 ],
            'away'   => [ 'team' => '102', 'sets' => 0 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertEquals( 3.0, (float) $res->home ); // 2 sets + 1 forwin bonus
        $this->assertEquals( 0.0, (float) $res->away );
        $this->assertSame( '101', (string) $res->winner );
        $this->assertSame( '102', (string) $res->loser );
    }

    public function test_calculate_result_away_win(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule( [ 'forwin' => 1 ] );

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 0 ],
            'away'   => [ 'team' => '102', 'sets' => 2 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertEquals( 0.0, (float) $res->home );
        $this->assertEquals( 3.0, (float) $res->away );
        $this->assertSame( '102', (string) $res->winner );
        $this->assertSame( '101', (string) $res->loser );
    }

    public function test_calculate_result_home_walkover_gives_win_to_away(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 0, 'walkover' => 1 ],
            'away'   => [ 'team' => '102', 'sets' => 0 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertSame( '102', (string) $res->winner );
        $this->assertSame( '101', (string) $res->loser );
    }

    public function test_calculate_result_away_walkover_gives_win_to_home(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 0 ],
            'away'   => [ 'team' => '102', 'sets' => 0, 'walkover' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertSame( '101', (string) $res->winner );
        $this->assertSame( '102', (string) $res->loser );
    }

    public function test_calculate_result_both_walkover(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 0, 'walkover' => 1 ],
            'away'   => [ 'team' => '102', 'sets' => 0, 'walkover' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertEquals( -1, $res->winner );
        $this->assertEquals( -1, $res->loser );
    }

    public function test_calculate_result_home_retired_gives_win_to_away(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 0, 'retired' => 1 ],
            'away'   => [ 'team' => '102', 'sets' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertSame( '102', (string) $res->winner );
        $this->assertSame( '101', (string) $res->loser );
    }

    public function test_calculate_result_float_points_comparison(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        $points = [
            'home'   => [ 'team' => '101', 'sets' => 1 ],
            'away'   => [ 'team' => '102', 'sets' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        // 1 set home + 1 split = 2; 1 set away = 1
        $this->assertEquals( 2.0, (float) $res->home );
        $this->assertEquals( 1.0, (float) $res->away );
        $this->assertSame( '101', (string) $res->winner );
        $this->assertSame( '102', (string) $res->loser );
    }

    public function test_calculate_result_with_mixed_type_team_ids(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        // Home is int, Away is string
        $points = [
            'home'   => [ 'team' => 101, 'sets' => 2 ],
            'away'   => [ 'team' => '102', 'sets' => 0 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res = $rubber->calculate_result( $points, $rule, 2 );

        $this->assertSame( 101, $res->winner );
        $this->assertSame( '102', $res->loser );
        $this->assertSame( 3.0, (float) $res->home );
        $this->assertSame( 0.0, (float) $res->away );

        // Home is string, Away is int, with away winning
        $points_away = [
            'home'   => [ 'team' => '101', 'sets' => 0 ],
            'away'   => [ 'team' => 102, 'sets' => 2 ],
            'shared' => [ 'sets' => 0 ],
        ];

        $res_away = $rubber->calculate_result( $points_away, $rule, 2 );

        $this->assertSame( 102, $res_away->winner );
        $this->assertSame( '101', $res_away->loser );
    }

    public function test_calculate_result_invalid_player_scenarios(): void {
        $rubber = $this->create_dummy_rubber();
        $rule   = $this->get_default_point_rule();

        // Home invalid -> away wins
        $points_home_invalid = [
            'home'   => [ 'team' => 101, 'sets' => 2, 'invalid' => 1 ],
            'away'   => [ 'team' => 102, 'sets' => 0 ],
            'shared' => [ 'sets' => 0 ],
        ];
        $res1 = $rubber->calculate_result( $points_home_invalid, $rule, 2 );
        $this->assertSame( 102, $res1->winner );
        $this->assertSame( 101, $res1->loser );

        // Away invalid -> home wins
        $points_away_invalid = [
            'home'   => [ 'team' => 101, 'sets' => 0 ],
            'away'   => [ 'team' => 102, 'sets' => 2, 'invalid' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];
        $res2 = $rubber->calculate_result( $points_away_invalid, $rule, 2 );
        $this->assertSame( 101, $res2->winner );
        $this->assertSame( 102, $res2->loser );

        // Both invalid -> -1 draw
        $points_both_invalid = [
            'home'   => [ 'team' => 101, 'sets' => 1 ],
            'away'   => [ 'team' => 102, 'sets' => 1 ],
            'both'   => [ 'invalid' => 1 ],
            'shared' => [ 'sets' => 0 ],
        ];
        $res3 = $rubber->calculate_result( $points_both_invalid, $rule, 2 );
        $this->assertSame( -1, $res3->winner );
        $this->assertSame( -1, $res3->loser );
    }

    public function test_is_walkover_and_is_shared_match_stored_status_codes(): void {
        $rubber = $this->create_dummy_rubber();

        $rubber->set_status( 1 );
        $this->assertTrue( $rubber->is_walkover() );
        $this->assertFalse( $rubber->is_shared() );

        $rubber->set_status( 3 );
        $this->assertTrue( $rubber->is_shared() );
        $this->assertFalse( $rubber->is_walkover() );

        foreach ( [ 0, 2, 4, null ] as $status ) {
            $rubber->set_status( $status );
            $this->assertFalse( $rubber->is_walkover() );
            $this->assertFalse( $rubber->is_shared() );
        }
    }

    public function test_hydrates_serialized_custom_column_and_strips_slashes(): void {
        $custom = [
            'sets'     => [ 1 => [ 'player1' => '6', 'player2' => '4' ], 2 => [ 'player1' => '7', 'player2' => '5' ] ],
            'walkover' => 'home',
            'invalid'  => "Rain delay at O\\'Neill Park",
        ];

        $rubber = Rubber::from_object( (object) [
            'id'       => 10,
            'match_id' => 100,
            'date'     => '2026-06-01 10:30:00',
            'type'     => 'XD',
            'custom'   => serialize( $custom ),
        ] );

        $this->assertSame( $custom['sets'], $rubber->sets );
        $this->assertSame( $custom['sets'], $rubber->custom['sets'] );
        $this->assertSame( "Rain delay at O'Neill Park", $rubber->custom['invalid'] );
        $this->assertSame( "Rain delay at O'Neill Park", $rubber->invalid );
        $this->assertTrue( $rubber->is_invalid );
        $this->assertTrue( $rubber->is_walkover );
        $this->assertSame( 'home', $rubber->walkover );
    }

    public function test_hydrates_serialized_custom_containing_stdclass_rows(): void {
        $custom = [ 'sets' => [ 1 => (object) [ 'player1' => '6', 'player2' => '3' ] ] ];

        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => serialize( $custom ) ] );

        $this->assertInstanceOf( stdClass::class, $rubber->sets[1] );
        $this->assertSame( '6', $rubber->sets[1]->player1 );
    }

    public function test_custom_column_that_is_not_serialized_is_tolerated(): void {
        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => '' ] );
        $this->assertSame( [], $rubber->sets );
        $this->assertSame( [], $rubber->get_custom() );

        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => [ 'sets' => [ 1 => [ 'player1' => '6' ] ] ] ] );
        $this->assertSame( [ 1 => [ 'player1' => '6' ] ], $rubber->sets );
    }

    public function test_date_and_time_slices_match_mysql2date(): void {
        $date   = '2026-06-01 19:05:00';
        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'date' => $date ] );

        $this->assertSame( mysql2date( 'Y-m-d', $date ), $rubber->rubber_date );
        $this->assertSame( mysql2date( 'H:i', $date ), $rubber->start_time );
        $this->assertSame( '2026-06-01', $rubber->rubber_date );
        $this->assertSame( '19:05', $rubber->start_time );
        $this->assertSame( 2026, $rubber->year );
        $this->assertSame( 6, $rubber->month );
        $this->assertSame( 1, $rubber->day );
        $this->assertSame( '19', $rubber->hour );
        $this->assertSame( '05', $rubber->minutes );
    }

    public function test_midnight_time_means_no_start_time_and_zero_date_means_not_applicable(): void {
        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'date' => '2026-06-01 00:00:00' ] );
        $this->assertSame( '2026-06-01', $rubber->rubber_date );
        $this->assertSame( '', $rubber->start_time );

        $rubber = Rubber::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'date' => '0000-00-00 00:00:00' ] );
        $this->assertSame( 'N/A', $rubber->rubber_date );
        $this->assertSame( '', $rubber->start_time );
        $this->assertSame( 0, $rubber->year );
    }

    public function test_asymmetric_visibility_rejects_external_writes(): void {
        $rubber = $this->create_dummy_rubber();

        $this->expectException( \Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $rubber->status = 1;
    }

    public function test_asymmetric_visibility_rejects_indirect_array_modification(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->set_players( array( 'home' => 'Player 1' ) );

        $this->expectException( \Error::class );
        $this->expectExceptionMessageMatches( '/Cannot indirectly modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $rubber->players['home'] = 'Player 2';
    }

    public function test_set_class_and_set_title_accessors(): void {
        $rubber = $this->create_dummy_rubber();

        $rubber->set_class( 'rubber-active' );
        $rubber->set_title( 'Rubber 1' );

        $this->assertSame( 'rubber-active', $rubber->class );
        $this->assertSame( 'Rubber 1', $rubber->title );
    }

    public function test_properties_are_readable_natively_after_hydration(): void {
        $dto = new Rubber_Hydration_DTO(
            id: 15,
            match_id: 100,
            status: 1,
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 101,
            loser_id: 102,
        );
        $rubber = new Rubber( $dto );

        $this->assertSame( 15, $rubber->id );
        $this->assertSame( 100, $rubber->match_id );
        $this->assertSame( 1, $rubber->status );
        $this->assertSame( '2 - 0', $rubber->score );
        $this->assertSame( 2.0, $rubber->home_points );
        $this->assertSame( 0.0, $rubber->away_points );
        $this->assertSame( 101, $rubber->winner_id );
        $this->assertSame( 102, $rubber->loser_id );
    }

    public function test_record_result_synchronizes_state_atomically(): void {
        $rubber = $this->create_dummy_rubber();

        $custom = [
            'sets'     => [ 1 => [ 'p1' => 6, 'p2' => 4 ], 2 => [ 'p1' => 6, 'p2' => 2 ] ],
            'stats'    => [ 'duration' => 65 ],
            'walkover' => 'home',
        ];

        $rubber->record_result(
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 101,
            loser_id: 102,
            status: 1, // Walkover
            custom: $custom
        );

        $this->assertSame( 2.0, $rubber->home_points );
        $this->assertSame( 0.0, $rubber->away_points );
        $this->assertSame( 101, $rubber->winner_id );
        $this->assertSame( 102, $rubber->loser_id );
        $this->assertSame( 1, $rubber->status );
        $this->assertSame( '2 - 0', $rubber->score );
        $this->assertSame( $custom['sets'], $rubber->sets );
        $this->assertSame( [ 'duration' => 65 ], $rubber->stats );
        $this->assertTrue( $rubber->is_walkover );
        $this->assertTrue( $rubber->is_walkover() );
        $this->assertFalse( $rubber->is_shared );
    }

    public function test_record_result_with_null_points_sets_empty_score(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->record_result( null, null );

        $this->assertNull( $rubber->home_points );
        $this->assertNull( $rubber->away_points );
        $this->assertSame( '-:-', $rubber->score );
    }

    public function test_record_result_with_empty_sets_and_stats(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->record_result(
            home_points: 1.0,
            away_points: 0.0,
            winner_id: 10,
            loser_id: 20,
            status: 0,
            custom: []
        );

        $this->assertSame( [], $rubber->sets );
        $this->assertSame( [], $rubber->stats );
        $this->assertSame( '1 - 0', $rubber->score );
        $this->assertFalse( $rubber->is_walkover );
        $this->assertFalse( $rubber->is_retired );
        $this->assertFalse( $rubber->is_shared );
    }

    public function test_record_result_repeated_overwrites_stale_stats_and_flags(): void {
        $rubber = $this->create_dummy_rubber();

        // First: Walkover with custom stats and sets
        $rubber->record_result(
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 10,
            loser_id: 20,
            status: 1, // Walkover
            custom: [
                'sets'     => [ 1 => [ 'p1' => 6, 'p2' => 0 ] ],
                'stats'    => [ 'duration' => 45 ],
                'walkover' => 'home',
            ]
        );

        $this->assertTrue( $rubber->is_walkover );
        $this->assertSame( [ 'duration' => 45 ], $rubber->stats );
        $this->assertSame( [ 1 => [ 'p1' => 6, 'p2' => 0 ] ], $rubber->sets );

        // Second: Normal completed result without stats
        $rubber->record_result(
            home_points: 1.0,
            away_points: 2.0,
            winner_id: 20,
            loser_id: 10,
            status: 0, // Complete
            custom: [
                'sets' => [ 1 => [ 'p1' => 4, 'p2' => 6 ], 2 => [ 'p1' => 3, 'p2' => 6 ] ],
            ]
        );

        $this->assertFalse( $rubber->is_walkover );
        $this->assertSame( [], $rubber->stats );
        $this->assertSame( [ 1 => [ 'p1' => 4, 'p2' => 6 ], 2 => [ 'p1' => 3, 'p2' => 6 ] ], $rubber->sets );
        $this->assertSame( '1 - 2', $rubber->score );
    }

    public function test_reset_result_clears_all_derived_state_atomically(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->record_result(
            home_points: 2.0,
            away_points: 0.0,
            winner_id: 10,
            loser_id: 20,
            status: 1,
            custom: [
                'sets'     => [ 1 => [ 'p1' => 6, 'p2' => 0 ] ],
                'stats'    => [ 'duration' => 45 ],
                'walkover' => 'home',
            ]
        );

        $rubber->reset_result();

        $this->assertNull( $rubber->home_points );
        $this->assertNull( $rubber->away_points );
        $this->assertSame( 0, $rubber->winner_id );
        $this->assertSame( 0, $rubber->loser_id );
        $this->assertNull( $rubber->status );
        $this->assertNull( $rubber->custom );
        $this->assertSame( [], $rubber->sets );
        $this->assertSame( [], $rubber->stats );
        $this->assertSame( '-:-', $rubber->score );
        $this->assertFalse( $rubber->is_walkover );
        $this->assertFalse( $rubber->is_retired );
        $this->assertFalse( $rubber->is_shared );
    }

    public function test_reschedule_with_empty_and_zero_date(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->reschedule( '2026-07-20 15:45:00' );
        $this->assertSame( '15:45', $rubber->start_time );

        // Reschedule to empty string
        $rubber->reschedule( '' );
        $this->assertSame( '', $rubber->date );
        $this->assertSame( '', $rubber->rubber_date );
        $this->assertSame( 0, $rubber->year );
        $this->assertSame( 0, $rubber->month );
        $this->assertSame( 0, $rubber->day );
        $this->assertSame( '', $rubber->hour );
        $this->assertSame( '', $rubber->minutes );
        $this->assertSame( '', $rubber->start_time );

        // Reschedule to zero date
        $rubber->reschedule( '0000-00-00 00:00:00' );
        $this->assertSame( '0000-00-00 00:00:00', $rubber->date );
        $this->assertSame( 'N/A', $rubber->rubber_date );
        $this->assertSame( 0, $rubber->year );
        $this->assertSame( 0, $rubber->month );
        $this->assertSame( 0, $rubber->day );
        $this->assertSame( '', $rubber->hour );
        $this->assertSame( '', $rubber->minutes );
        $this->assertSame( '', $rubber->start_time );
    }

    public function test_assign_players(): void {
        $rubber  = $this->create_dummy_rubber();
        $players = [
            'home' => [ 1 => 501, 2 => 502 ],
            'away' => [ 1 => 601, 2 => 602 ],
        ];

        $rubber->assign_players( $players );

        $this->assertSame( $players, $rubber->players );
        $this->assertSame( $players, $rubber->get_players() );
    }

    public function test_reschedule_updates_date_and_time_components(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->reschedule( '2026-07-20 15:45:00' );

        $this->assertSame( '2026-07-20 15:45:00', $rubber->date );
        $this->assertSame( '2026-07-20', $rubber->rubber_date );
        $this->assertSame( 2026, $rubber->year );
        $this->assertSame( 7, $rubber->month );
        $this->assertSame( 20, $rubber->day );
        $this->assertSame( '15', $rubber->hour );
        $this->assertSame( '45', $rubber->minutes );
        $this->assertSame( '15:45', $rubber->start_time );
    }

    public function test_set_date_delegates_to_reschedule(): void {
        $rubber = $this->create_dummy_rubber();
        $rubber->set_date( '2026-08-10 11:15:00' );

        $this->assertSame( '2026-08-10 11:15:00', $rubber->date );
        $this->assertSame( '2026-08-10', $rubber->rubber_date );
        $this->assertSame( '11:15', $rubber->start_time );
    }

    public function test_hydration_with_status_only_derives_flags_correctly(): void {
        $rubber_shared = Rubber::from_object(
            (object) [
                'id'     => 101,
                'status' => 3, // SHARED
                'custom' => [],
            ]
        );
        $this->assertTrue( $rubber_shared->is_shared );
        $this->assertTrue( $rubber_shared->is_shared() );
        $this->assertFalse( $rubber_shared->is_walkover );

        $rubber_walkover = Rubber::from_object(
            (object) [
                'id'     => 102,
                'status' => 1, // WALKOVER
                'custom' => [],
            ]
        );
        $this->assertTrue( $rubber_walkover->is_walkover );
        $this->assertTrue( $rubber_walkover->is_walkover() );
        $this->assertFalse( $rubber_walkover->is_shared );

        $rubber_retired = Rubber::from_object(
            (object) [
                'id'     => 103,
                'status' => 2, // RETIRED
                'custom' => [],
            ]
        );
        $this->assertTrue( $rubber_retired->is_retired );

        $rubber_abandoned = Rubber::from_object(
            (object) [
                'id'     => 104,
                'status' => 6, // ABANDONED
                'custom' => [],
            ]
        );
        $this->assertTrue( $rubber_abandoned->is_abandoned );

        $rubber_invalid = Rubber::from_object(
            (object) [
                'id'     => 105,
                'status' => 9, // INVALID
                'custom' => [],
            ]
        );
        $this->assertTrue( $rubber_invalid->is_invalid );
    }

    public function test_set_status_resyncs_derived_flags(): void {
        $rubber = $this->create_dummy_rubber();
        $this->assertFalse( $rubber->is_shared );
        $this->assertFalse( $rubber->is_walkover );

        $rubber->set_status( 3 );
        $this->assertTrue( $rubber->is_shared );
        $this->assertFalse( $rubber->is_walkover );

        $rubber->set_status( 1 );
        $this->assertFalse( $rubber->is_shared );
        $this->assertTrue( $rubber->is_walkover );

        $rubber->set_status( 0 );
        $this->assertFalse( $rubber->is_shared );
        $this->assertFalse( $rubber->is_walkover );
    }

    public function test_set_custom_and_set_points_resync_derived_state(): void {
        $rubber = $this->create_dummy_rubber();
        $this->assertFalse( $rubber->is_walkover );
        $this->assertSame( '-:-', $rubber->score );

        $rubber->set_custom( [ 'walkover' => 'home' ] );
        $this->assertTrue( $rubber->is_walkover );

        $rubber->set_home_points( 3.0 );
        $rubber->set_away_points( 1.0 );
        $this->assertSame( '3 - 1', $rubber->score );
    }
}
