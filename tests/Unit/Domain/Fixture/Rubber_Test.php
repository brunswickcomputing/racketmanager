<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
        return new Rubber( $data );
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
        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2 ] );
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

        $rubber->status = 1;
        $this->assertTrue( $rubber->is_walkover() );
        $this->assertFalse( $rubber->is_shared() );

        $rubber->status = 3;
        $this->assertTrue( $rubber->is_shared() );
        $this->assertFalse( $rubber->is_walkover() );

        foreach ( [ 0, 2, 4, null ] as $status ) {
            $rubber->status = $status;
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

        $rubber = new Rubber( (object) [
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

        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => serialize( $custom ) ] );

        $this->assertInstanceOf( stdClass::class, $rubber->sets[1] );
        $this->assertSame( '6', $rubber->sets[1]->player1 );
    }

    public function test_custom_column_that_is_not_serialized_is_tolerated(): void {
        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => '' ] );
        $this->assertSame( [], $rubber->sets );
        $this->assertSame( [], $rubber->get_custom() );

        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'custom' => [ 'sets' => [ 1 => [ 'player1' => '6' ] ] ] ] );
        $this->assertSame( [ 1 => [ 'player1' => '6' ] ], $rubber->sets );
    }

    public function test_date_and_time_slices_match_mysql2date(): void {
        $date   = '2026-06-01 19:05:00';
        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'date' => $date ] );

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
        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'date' => '2026-06-01 00:00:00' ] );
        $this->assertSame( '2026-06-01', $rubber->rubber_date );
        $this->assertSame( '', $rubber->start_time );

        $rubber = new Rubber( (object) [ 'id' => 1, 'match_id' => 2, 'date' => '0000-00-00 00:00:00' ] );
        $this->assertSame( 'N/A', $rubber->rubber_date );
        $this->assertSame( '', $rubber->start_time );
        $this->assertSame( 0, $rubber->year );
    }
}
