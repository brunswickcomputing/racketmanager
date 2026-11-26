<?php
/**
 * Regression tests for Admin_Competition_Scheduler
 *
 * Covers:
 * 1. Repeated team IDs across leagues (multi-event junior competitions).
 * 2. Proper court alternation without court conflicts.
 * 3. Prevention of self matches (home_id !== away_id).
 * 4. Distinct-ID teams sharing courts within the same league.
 * 5. Guard against duplicate team IDs within the same league.
 * 6. 0-indexed league IDs.
 * 7. Cross-league direct pairing seed allocation.
 * 8. Underpopulated leagues with byes and league-scoped equity tracking.
 * 9. Conflict reporting format, league attribution, and alert formatting.
 */

require_once __DIR__ . '/../include/admin/class-admin-competition-scheduler.php';

use Racketmanager\admin\Admin_Competition_Scheduler;

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

function assert_true( bool $condition, string $message ): void {
	if ( ! $condition ) {
		echo "[-] FAILED: $message\n";
		exit( 1 );
	}
	echo "[+] PASSED: $message\n";
}

echo "=== Running Admin_Competition_Scheduler Regression Tests ===\n\n";

// -------------------------------------------------------------
// Test 1: Multi-league competition with repeated team IDs across leagues
// -------------------------------------------------------------
echo "Test 1: Repeated team IDs across leagues & court alternation...\n";

$match_dates = [
	'2026-05-07',
	'2026-05-14',
	'2026-05-21',
	'2026-05-28',
	'2026-06-04',
];

$leagues = [
	3 => [
		'league_id'   => 3,
		'league_name' => 'U12 Girls',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 3, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 60, 'name' => 'Thorpe Bay Girls 1', 'club_id' => 200, 'event_id' => 3, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 31, 'name' => 'Westcliff Girls 1', 'club_id' => 300, 'event_id' => 3, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 32, 'name' => 'Wickford Girls 1', 'club_id' => 400, 'event_id' => 3, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 33, 'name' => 'Hockley Girls 1', 'club_id' => 500, 'event_id' => 3, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 34, 'name' => 'Deanes Girls 1', 'club_id' => 600, 'event_id' => 3, 'preferred_slot' => 'Sunday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
		],
	],
	4 => [
		'league_id'   => 4,
		'league_name' => 'U16 Girls',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 4, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 60, 'name' => 'Thorpe Bay Girls 1', 'club_id' => 200, 'event_id' => 4, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 41, 'name' => 'Westcliff Girls 16', 'club_id' => 300, 'event_id' => 4, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 42, 'name' => 'Wickford Girls 16', 'club_id' => 400, 'event_id' => 4, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 43, 'name' => 'Hockley Girls 16', 'club_id' => 500, 'event_id' => 4, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 44, 'name' => 'Deanes Girls 16', 'club_id' => 600, 'event_id' => 4, 'preferred_slot' => 'Sunday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
		],
	],
];

$scheduler = new Admin_Competition_Scheduler( 6, false, $match_dates );
$result    = $scheduler->build_competition_schedule( $leagues );

assert_true( $result['is_valid'] === true, "Schedule generation should be valid" );
assert_true( empty( $result['conflicts'] ), "Schedule should have 0 court conflicts" );
assert_true( $result['retry'] <= 5, "Schedule should solve in minimal retries (actual: {$result['retry']})" );

// Verify no self matches and verify court alternation
$home_weeks_by_team_and_league = [];

foreach ( $result['schedule'] as $league_id => $league_data ) {
	foreach ( $league_data['weeks'] as $week_num => $matches ) {
		foreach ( $matches as $m ) {
			if ( empty( $m['is_bye'] ) ) {
				assert_true(
					$m['home_id'] !== $m['away_id'],
					"Match in League $league_id Week $week_num must not be a self-match ({$m['home']} vs {$m['away']})"
				);
				$home_weeks_by_team_and_league[ $league_id ][ $m['home_id'] ][] = $week_num;
			}
		}
	}
}

// Rayleigh Girls 1 (ID 50) sharing court C0 between League 3 and League 4
$rayleigh_u12_home = $home_weeks_by_team_and_league[3][50] ?? [];
$rayleigh_u16_home = $home_weeks_by_team_and_league[4][50] ?? [];
$rayleigh_overlap  = array_intersect( $rayleigh_u12_home, $rayleigh_u16_home );
assert_true(
	empty( $rayleigh_overlap ),
	"Rayleigh Girls 1 must not play at home in both leagues in the same week (overlaps: " . implode( ',', $rayleigh_overlap ) . ")"
);

// Thorpe Bay Girls 1 (ID 60) sharing court C0 between League 3 and League 4
$thorpe_u12_home = $home_weeks_by_team_and_league[3][60] ?? [];
$thorpe_u16_home = $home_weeks_by_team_and_league[4][60] ?? [];
$thorpe_overlap  = array_intersect( $thorpe_u12_home, $thorpe_u16_home );
assert_true(
	empty( $thorpe_overlap ),
	"Thorpe Bay Girls 1 must not play at home in both leagues in the same week (overlaps: " . implode( ',', $thorpe_overlap ) . ")"
);

// -------------------------------------------------------------
// Test 2: Distinct-ID teams sharing courts within the same league
// -------------------------------------------------------------
echo "\nTest 2: Distinct-ID teams sharing courts within the same league...\n";

$single_league = [
	1 => [
		'league_id'   => 1,
		'league_name' => 'Premier League',
		'teams'       => [
			[ 'id' => 10, 'name' => 'Rayleigh Boys 1', 'club_id' => 100, 'event_id' => 1, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 11, 'name' => 'Rayleigh Boys 2', 'club_id' => 100, 'event_id' => 1, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 12, 'name' => 'Thorpe Bay Boys 1', 'club_id' => 200, 'event_id' => 1, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 13, 'name' => 'Thorpe Bay Boys 2', 'club_id' => 200, 'event_id' => 1, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 14, 'name' => 'Westcliff Boys 1', 'club_id' => 300, 'event_id' => 1, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 15, 'name' => 'Wickford Boys 1', 'club_id' => 400, 'event_id' => 1, 'preferred_slot' => 'Sunday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
		],
	],
];

$scheduler2 = new Admin_Competition_Scheduler( 6, false, $match_dates );
$result2    = $scheduler2->build_competition_schedule( $single_league );

assert_true( $result2['is_valid'] === true, "Single league schedule should be valid" );
assert_true( empty( $result2['conflicts'] ), "Single league schedule should have 0 court conflicts" );

$rayleigh_1_home = [];
$rayleigh_2_home = [];

foreach ( $result2['schedule'][1]['weeks'] as $week_num => $matches ) {
	foreach ( $matches as $m ) {
		if ( empty( $m['is_bye'] ) ) {
			assert_true(
				$m['home_id'] !== $m['away_id'],
				"Match in Week $week_num must not be a self-match ({$m['home']} vs {$m['away']})"
			);
			if ( $m['home_id'] === 10 ) {
				$rayleigh_1_home[] = $week_num;
			}
			if ( $m['home_id'] === 11 ) {
				$rayleigh_2_home[] = $week_num;
			}
		}
	}
}

// They share a court, so they should not both be at home unless playing each other
$same_week_home = array_intersect( $rayleigh_1_home, $rayleigh_2_home );
assert_true(
	empty( $same_week_home ),
	"Rayleigh Boys 1 and Boys 2 must alternate home weeks on shared court"
);

// -------------------------------------------------------------
// Test 3: Guard against identical team IDs within same league
// -------------------------------------------------------------
echo "\nTest 3: Guard against identical team IDs in same league...\n";

$edge_league = [
	1 => [
		'league_id'   => 1,
		'league_name' => 'Edge League',
		'teams'       => [
			[ 'id' => 99, 'name' => 'Team A', 'club_id' => 100, 'event_id' => 1, 'preferred_slot' => 'Monday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 99, 'name' => 'Team A Duplicate', 'club_id' => 100, 'event_id' => 1, 'preferred_slot' => 'Monday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 101, 'name' => 'Team B', 'club_id' => 200, 'event_id' => 1, 'preferred_slot' => 'Tuesday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Tuesday' ],
			[ 'id' => 102, 'name' => 'Team C', 'club_id' => 300, 'event_id' => 1, 'preferred_slot' => 'Wednesday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Wednesday' ],
		],
	],
];

// Cap timeout to 1 second so it doesn't spin if invalid
$scheduler3 = new Admin_Competition_Scheduler( 4, false, array_slice( $match_dates, 0, 3 ) );
// Use reflection to set low timeout / retries for edge case
$ref = new ReflectionClass( $scheduler3 );
$prop = $ref->getProperty( 'timeout' );
$prop->setAccessible( true );
$prop->setValue( $scheduler3, 1.0 );
$prop2 = $ref->getProperty( 'max_retries' );
$prop2->setAccessible( true );
$prop2->setValue( $scheduler3, 50 );

$result3 = $scheduler3->build_competition_schedule( $edge_league );
// Verify scheduler failed fast with structured invalid result
assert_true( is_array( $result3 ), "Scheduler should return array result for duplicate IDs in same league" );
assert_true( $result3['is_valid'] === false, "Scheduler should mark duplicate team IDs as invalid" );
assert_true( $result3['retry'] === 0, "Scheduler should fail fast without spinning retries (actual: {$result3['retry']})" );
assert_true( ! empty( $result3['alerts'] ), "Scheduler should provide alerts on duplicate team IDs" );
assert_true( is_array( $result3['alerts'][0] ), "Alert item must be a structured array" );
assert_true( $result3['alerts'][0]['type'] === 'duplicate_team', "Alert type should be 'duplicate_team'" );
assert_true( $result3['alerts'][0]['team_id'] === 99, "Alert team_id should be 99" );
assert_true( $result3['alerts'][0]['league'] === 'Edge League', "Alert league should match league name" );
assert_true( ! array_key_exists( 'division', $result3['alerts'][0] ), "Alert payload must not contain residual 'division' key" );
assert_true( str_contains( $result3['alerts'][0]['message'], "contains duplicate team ID '99'" ), "Alert message should mention duplicate team ID '99'" );
assert_true( str_contains( $result3['alerts'][0]['message'], "League 'Edge League'" ), "Alert message should use League nomenclature" );
assert_true( isset( $result3['total_weeks'] ), "Result should contain total_weeks" );
assert_true( isset( $result3['resource_pairings'] ), "Result should contain resource_pairings" );

$has_self_match = false;
foreach ( ( $result3['schedule'] ?? [] ) as $league_data ) {
	foreach ( ( $league_data['weeks'] ?? [] ) as $matches ) {
		foreach ( $matches as $m ) {
			if ( empty( $m['is_bye'] ) && $m['home_id'] === $m['away_id'] ) {
				$has_self_match = true;
			}
		}
	}
}
assert_true( ! $has_self_match, "There should be no self-matches (home_id === away_id) generated" );

// -------------------------------------------------------------
// Test 4: 0-indexed league IDs (keys 0 and 1)
// -------------------------------------------------------------
echo "\nTest 4: 0-indexed league IDs (keys 0 and 1)...\n";

$zero_indexed_leagues = [
	0 => [
		'league_id'   => 0,
		'league_name' => 'U12 Girls (League 0)',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 3, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 60, 'name' => 'Thorpe Bay Girls 1', 'club_id' => 200, 'event_id' => 3, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 31, 'name' => 'Westcliff Girls 1', 'club_id' => 300, 'event_id' => 3, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 32, 'name' => 'Wickford Girls 1', 'club_id' => 400, 'event_id' => 3, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 33, 'name' => 'Hockley Girls 1', 'club_id' => 500, 'event_id' => 3, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 34, 'name' => 'Deanes Girls 1', 'club_id' => 600, 'event_id' => 3, 'preferred_slot' => 'Sunday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
		],
	],
	1 => [
		'league_id'   => 1,
		'league_name' => 'U16 Girls (League 1)',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 4, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 60, 'name' => 'Thorpe Bay Girls 1', 'club_id' => 200, 'event_id' => 4, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 41, 'name' => 'Westcliff Girls 16', 'club_id' => 300, 'event_id' => 4, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 42, 'name' => 'Wickford Girls 16', 'club_id' => 400, 'event_id' => 4, 'preferred_slot' => 'Wednesday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 43, 'name' => 'Hockley Girls 16', 'club_id' => 500, 'event_id' => 4, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 44, 'name' => 'Deanes Girls 16', 'club_id' => 600, 'event_id' => 4, 'preferred_slot' => 'Sunday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
		],
	],
];

$scheduler4 = new Admin_Competition_Scheduler( 6, false, $match_dates );
$result4    = $scheduler4->build_competition_schedule( $zero_indexed_leagues );

assert_true( $result4['is_valid'] === true, "0-indexed schedule generation should be valid" );
assert_true( empty( $result4['conflicts'] ), "0-indexed schedule should have 0 court conflicts" );
assert_true( $result4['retry'] <= 5, "0-indexed schedule should solve in minimal retries (actual: {$result4['retry']})" );

$home_weeks_zero_leagues = [];
foreach ( $result4['schedule'] as $league_id => $league_data ) {
	foreach ( $league_data['weeks'] as $week_num => $matches ) {
		foreach ( $matches as $m ) {
			if ( empty( $m['is_bye'] ) ) {
				assert_true(
					$m['home_id'] !== $m['away_id'],
					"Match in League $league_id Week $week_num must not be a self-match"
				);
				$home_weeks_zero_leagues[ $league_id ][ $m['home_id'] ][] = $week_num;
			}
		}
	}
}

$rayleigh_0_home = $home_weeks_zero_leagues[0][50] ?? [];
$rayleigh_1_home = $home_weeks_zero_leagues[1][50] ?? [];
$rayleigh_zero_overlap = array_intersect( $rayleigh_0_home, $rayleigh_1_home );
assert_true(
	empty( $rayleigh_zero_overlap ),
	"Rayleigh Girls 1 must not play at home in both league 0 and league 1 in same week"
);

// -------------------------------------------------------------
// Test 5: Deterministic regression test for cross-league direct pairing seed assignment
// -------------------------------------------------------------
echo "\nTest 5: Cross-league direct pairing seed assignment regression...\n";

// Only Rayleigh Girls 1 (ID 50) is shared across League 3 and League 4 on 'Thursday 17:00:00'.
// All other teams have exclusive clubs and slots so no other court collisions are possible.
$cross_league_pair_fixture = [
	3 => [
		'league_id'   => 3,
		'league_name' => 'U12 Girls',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 3, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 31, 'name' => 'Team 31', 'club_id' => 301, 'event_id' => 3, 'preferred_slot' => 'Monday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 32, 'name' => 'Team 32', 'club_id' => 302, 'event_id' => 3, 'preferred_slot' => 'Tuesday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Tuesday' ],
			[ 'id' => 33, 'name' => 'Team 33', 'club_id' => 303, 'event_id' => 3, 'preferred_slot' => 'Wednesday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 34, 'name' => 'Team 34', 'club_id' => 304, 'event_id' => 3, 'preferred_slot' => 'Friday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Friday' ],
			[ 'id' => 35, 'name' => 'Team 35', 'club_id' => 305, 'event_id' => 3, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
		],
	],
	4 => [
		'league_id'   => 4,
		'league_name' => 'U16 Girls',
		'teams'       => [
			[ 'id' => 50, 'name' => 'Rayleigh Girls 1', 'club_id' => 100, 'event_id' => 4, 'preferred_slot' => 'Thursday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Thursday' ],
			[ 'id' => 41, 'name' => 'Team 41', 'club_id' => 401, 'event_id' => 4, 'preferred_slot' => 'Monday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 42, 'name' => 'Team 42', 'club_id' => 402, 'event_id' => 4, 'preferred_slot' => 'Tuesday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Tuesday' ],
			[ 'id' => 43, 'name' => 'Team 43', 'club_id' => 403, 'event_id' => 4, 'preferred_slot' => 'Wednesday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Wednesday' ],
			[ 'id' => 44, 'name' => 'Team 44', 'club_id' => 404, 'event_id' => 4, 'preferred_slot' => 'Friday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Friday' ],
			[ 'id' => 45, 'name' => 'Team 45', 'club_id' => 405, 'event_id' => 4, 'preferred_slot' => 'Saturday 14:00:00', 'match_time' => '14:00:00', 'match_day' => 'Saturday' ],
		],
	],
];

// 1. Verify resource_pairings explicitly preserves league_id from each league
$scheduler5 = new Admin_Competition_Scheduler( 6, false, $match_dates );
$ref5 = new ReflectionClass( $scheduler5 );
$max_retries_prop = $ref5->getProperty( 'max_retries' );
$max_retries_prop->setAccessible( true );
$max_retries_prop->setValue( $scheduler5, 1 );

$result5 = $scheduler5->build_competition_schedule( $cross_league_pair_fixture );

assert_true( isset( $result5['resource_pairings']['100_Thursday 17:00:00_C0'] ), "Shared court resource should exist in resource_pairings" );
$pairings = $result5['resource_pairings']['100_Thursday 17:00:00_C0'];
assert_true( count( $pairings ) === 2, "Shared court should have exactly 2 teams paired" );
assert_true( $pairings[0]['id'] === 50 && $pairings[1]['id'] === 50, "Both paired teams must be team ID 50" );
assert_true( isset( $pairings[0]['league_id'] ) && isset( $pairings[1]['league_id'] ), "Pairing records must have league_id" );
assert_true( ! array_key_exists( 'division_id', $pairings[0] ) && ! array_key_exists( 'division_id', $pairings[1] ), "Pairing records must not contain residual 'division_id' key" );
$paired_league_ids = [ $pairings[0]['league_id'], $pairings[1]['league_id'] ];
sort( $paired_league_ids );
assert_true( $paired_league_ids === [ 3, 4 ], "Pairing records must track league_id 3 and 4 directly" );
assert_true( isset( $pairings[0]['league'] ) && isset( $pairings[1]['league'] ), "Pairing records must have league name" );
assert_true( ! array_key_exists( 'division', $pairings[0] ) && ! array_key_exists( 'division', $pairings[1] ), "Pairing records must not contain residual 'division' key" );

// 2. Strict controlled test across multiple single-attempt runs:
// With direct league_id seeding, complementary seeds are ALWAYS assigned on retry 1, guaranteeing 0 conflicts.
for ( $run = 1; $run <= 15; $run++ ) {
	$s_run = new Admin_Competition_Scheduler( 6, false, $match_dates );
	$p_run = $ref5->getProperty( 'max_retries' );
	$p_run->setAccessible( true );
	$p_run->setValue( $s_run, 1 );

	$res_run = $s_run->build_competition_schedule( $cross_league_pair_fixture );
	assert_true( $res_run['is_valid'] === true, "Run $run: Single retry must succeed when cross-league seeds are complementary" );
	assert_true( empty( $res_run['conflicts'] ), "Run $run: Single retry must produce 0 conflicts (actual: " . count( $res_run['conflicts'] ) . ")" );
	assert_true( $res_run['retry'] === 1, "Run $run: Should succeed on attempt 1" );

	// Verify Rayleigh home weeks are strictly disjoint
	$home_3 = [];
	$home_4 = [];
	foreach ( $res_run['schedule'][3]['weeks'] as $w => $matches ) {
		foreach ( $matches as $m ) {
			if ( empty( $m['is_bye'] ) && $m['home_id'] === 50 ) {
				$home_3[] = $w;
			}
		}
	}
	foreach ( $res_run['schedule'][4]['weeks'] as $w => $matches ) {
		foreach ( $matches as $m ) {
			if ( empty( $m['is_bye'] ) && $m['home_id'] === 50 ) {
				$home_4[] = $w;
			}
		}
	}
	$overlap = array_intersect( $home_3, $home_4 );
	assert_true( empty( $overlap ), "Run $run: Rayleigh Girls 1 must have 0 overlapping home weeks between League 3 and League 4" );
}

// -------------------------------------------------------------
// Test 6: Underpopulated leagues with byes and league-scoped equity tracking
// -------------------------------------------------------------
echo "\nTest 6: Underpopulated leagues bye matching & league-scoped equity...\n";

// Two 4-team leagues in a 6-team competition (underpopulated, 2 byes per week)
// Team 101 is entered in both League 10 and League 20.
$underpop_leagues = [
	10 => [
		'league_id'   => 10,
		'league_name' => 'U12 Mixed Underpop',
		'teams'       => [
			[ 'id' => 101, 'name' => 'Club Alpha 1', 'club_id' => 110, 'event_id' => 10, 'preferred_slot' => 'Friday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Friday' ],
			[ 'id' => 102, 'name' => 'Club Beta 1',  'club_id' => 120, 'event_id' => 10, 'preferred_slot' => 'Monday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 103, 'name' => 'Club Gamma 1', 'club_id' => 130, 'event_id' => 10, 'preferred_slot' => 'Tuesday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Tuesday' ],
			[ 'id' => 104, 'name' => 'Club Delta 1', 'club_id' => 140, 'event_id' => 10, 'preferred_slot' => 'Wednesday 18:00:00', 'match_time' => '18:00:00', 'match_day' => 'Wednesday' ],
		],
	],
	20 => [
		'league_id'   => 20,
		'league_name' => 'U16 Mixed Underpop',
		'teams'       => [
			[ 'id' => 101, 'name' => 'Club Alpha 1', 'club_id' => 110, 'event_id' => 20, 'preferred_slot' => 'Friday 17:00:00', 'match_time' => '17:00:00', 'match_day' => 'Friday' ],
			[ 'id' => 202, 'name' => 'Club Epsilon 1', 'club_id' => 220, 'event_id' => 20, 'preferred_slot' => 'Saturday 10:00:00', 'match_time' => '10:00:00', 'match_day' => 'Saturday' ],
			[ 'id' => 203, 'name' => 'Club Zeta 1',    'club_id' => 230, 'event_id' => 20, 'preferred_slot' => 'Sunday 10:00:00',   'match_time' => '10:00:00', 'match_day' => 'Sunday' ],
			[ 'id' => 204, 'name' => 'Club Eta 1',     'club_id' => 240, 'event_id' => 20, 'preferred_slot' => 'Thursday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Thursday' ],
		],
	],
];

$scheduler6 = new Admin_Competition_Scheduler( 6, false, $match_dates );
$result6    = $scheduler6->build_competition_schedule( $underpop_leagues );

assert_true( $result6['is_valid'] === true, "Underpopulated leagues schedule must be valid" );
assert_true( empty( $result6['conflicts'] ), "Underpopulated leagues should have 0 court conflicts" );

// Check that equity compromises are empty (no false equity compromise alerts due to cross-league team 101)
$equity_alerts = array_filter( $result6['alerts'], fn( $a ) => ( $a['type'] ?? '' ) === 'equity_compromise' );
assert_true( empty( $equity_alerts ), "Cross-league team 101 must not trigger false equity compromise alerts" );

// Verify bye replacement matches:
// 1. All matches across both leagues are real matches (no remaining byes).
// 2. No duplicate match records in the same week.
// 3. No team plays at home in both leagues in the same week.
$alpha_home_weeks_league10 = [];
$alpha_home_weeks_league20 = [];

foreach ( $result6['schedule'] as $league_id => $league_data ) {
	$pair_meetings = [];
	foreach ( $league_data['weeks'] as $week_num => $matches ) {
		$seen_week_match_keys = [];
		foreach ( $matches as $m ) {
			assert_true( empty( $m['is_bye'] ), "League $league_id Week $week_num: byes should have been replaced with real matches" );
			assert_true( $m['home_id'] !== $m['away_id'], "League $league_id Week $week_num: match must not be self-match" );
			assert_true( ! array_key_exists( 'is_cross_division', $m ), "Bye replacement match must not contain residual 'is_cross_division' key" );
			assert_true( ! array_key_exists( 'division_id', $m ), "Match must not contain residual 'division_id' key" );
			assert_true( ! array_key_exists( 'division', $m ), "Match must not contain residual 'division' key" );

			// Check no duplicate match in this week
			$match_key = min( $m['home_id'], $m['away_id'] ) . '_' . max( $m['home_id'], $m['away_id'] );
			assert_true( ! isset( $seen_week_match_keys[ $match_key ] ), "League $league_id Week $week_num: duplicate match $match_key found" );
			$seen_week_match_keys[ $match_key ] = true;

			// Track meetings
			$pair_meetings[ $match_key ][ $m['home_id'] ] = ( $pair_meetings[ $match_key ][ $m['home_id'] ] ?? 0 ) + 1;

			if ( $m['home_id'] === 101 ) {
				if ( $league_id === 10 ) {
					$alpha_home_weeks_league10[] = $week_num;
				} else {
					$alpha_home_weeks_league20[] = $week_num;
				}
			}
		}
	}

	// Verify meeting counts between any two teams do not exceed 3, and home counts do not exceed 2
	foreach ( $pair_meetings as $pkey => $hcounts ) {
		$total_m = array_sum( $hcounts );
		assert_true( $total_m <= 3, "League $league_id: pair $pkey must not meet more than 3 times (actual: $total_m)" );
		foreach ( $hcounts as $tid => $hcount ) {
			assert_true( $hcount <= 2, "League $league_id: team $tid in pair $pkey must not play at home more than 2 times (actual: $hcount)" );
		}
	}
}

// Verify Club Alpha 1 never plays at home in both leagues in the same week
$alpha_simultaneous_home = array_intersect( $alpha_home_weeks_league10, $alpha_home_weeks_league20 );
assert_true(
	empty( $alpha_simultaneous_home ),
	"Club Alpha 1 must not play at home simultaneously in both underpopulated leagues (overlaps: " . implode( ',', $alpha_simultaneous_home ) . ")"
);

// Verify equity compromise alert payload structure and absence of residual division keys
$equity_fixture = [
	99 => [
		'league_id'   => 99,
		'league_name' => 'Equity Test League',
		'teams'       => [
			[ 'id' => 901, 'name' => 'Equity Team 1', 'club_id' => 910, 'preferred_slot' => 'Monday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Monday' ],
			[ 'id' => 902, 'name' => 'Equity Team 2', 'club_id' => 920, 'preferred_slot' => 'Tuesday 19:00:00', 'match_time' => '19:00:00', 'match_day' => 'Tuesday' ],
		],
	],
];
$scheduler_eq = new Admin_Competition_Scheduler( 6, false, $match_dates );
$result_eq    = $scheduler_eq->build_competition_schedule( $equity_fixture );
$eq_alerts    = array_filter( $result_eq['alerts'], fn( $a ) => ( $a['type'] ?? '' ) === 'equity_compromise' );
assert_true( ! empty( $eq_alerts ), "Equity test fixture should produce equity compromise alert" );
$first_eq = reset( $eq_alerts );
assert_true( $first_eq['league'] === 'Equity Test League', "Equity alert must contain canonical 'league'" );
assert_true( ! array_key_exists( 'division', $first_eq ), "Equity alert must not contain residual 'division' key" );

// -------------------------------------------------------------
// Test 7: Conflict reporting format, league attribution, and alert formatting
// -------------------------------------------------------------
echo "\nTest 7: Conflict reporting format & league attribution...\n";

// Multi-league fixture with interdependent shared preferred slots across 4 leagues.
// When max_retries = 1, complementary seeds cannot be assigned to all overlapping courts,
// surfacing authentic court conflicts with league attribution.
$conflict_fixture = [
	1 => [
		'league_id'   => 1,
		'league_name' => 'League Red 1',
		'teams'       => [
			[ 'id' => 1, 'name' => 'L1 T1', 'club_id' => 10, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 2, 'name' => 'L1 T2', 'club_id' => 20, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 3, 'name' => 'L1 T3', 'club_id' => 30, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 4, 'name' => 'L1 T4', 'club_id' => 40, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 5, 'name' => 'L1 T5', 'club_id' => 50, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 6, 'name' => 'L1 T6', 'club_id' => 60, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
		],
	],
	2 => [
		'league_id'   => 2,
		'league_name' => 'League Blue 2',
		'teams'       => [
			[ 'id' => 11, 'name' => 'L2 T1', 'club_id' => 10, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 12, 'name' => 'L2 T2', 'club_id' => 20, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 13, 'name' => 'L2 T3', 'club_id' => 30, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 14, 'name' => 'L2 T4', 'club_id' => 70, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 15, 'name' => 'L2 T5', 'club_id' => 80, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 16, 'name' => 'L2 T6', 'club_id' => 90, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
		],
	],
	3 => [
		'league_id'   => 3,
		'league_name' => 'League Green 3',
		'teams'       => [
			[ 'id' => 21, 'name' => 'L3 T1', 'club_id' => 10, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 22, 'name' => 'L3 T2', 'club_id' => 40, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 23, 'name' => 'L3 T3', 'club_id' => 50, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 24, 'name' => 'L3 T4', 'club_id' => 70, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 25, 'name' => 'L3 T5', 'club_id' => 80, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 26, 'name' => 'L3 T6', 'club_id' => 90, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
		],
	],
	4 => [
		'league_id'   => 4,
		'league_name' => 'League Yellow 4',
		'teams'       => [
			[ 'id' => 31, 'name' => 'L4 T1', 'club_id' => 20, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 32, 'name' => 'L4 T2', 'club_id' => 30, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 33, 'name' => 'L4 T3', 'club_id' => 60, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 34, 'name' => 'L4 T4', 'club_id' => 70, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 35, 'name' => 'L4 T5', 'club_id' => 80, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
			[ 'id' => 36, 'name' => 'L4 T6', 'club_id' => 90, 'preferred_slot' => 'Thu 18:00', 'match_time' => '18:00', 'match_day' => 'Thursday' ],
		],
	],
];

$res7 = null;
$ref7 = new ReflectionClass( Admin_Competition_Scheduler::class );
for ( $attempt = 0; $attempt < 10; $attempt++ ) {
	$s7 = new Admin_Competition_Scheduler( 6, false, $match_dates );
	$p7 = $ref7->getProperty( 'max_retries' );
	$p7->setAccessible( true );
	$p7->setValue( $s7, 1 );
	$try_res = $s7->build_competition_schedule( $conflict_fixture );
	if ( ! empty( $try_res['conflicts'] ) ) {
		$res7 = $try_res;
		break;
	}
}

assert_true( $res7 !== null, "Should find a run with court conflicts under single-attempt scheduler" );
assert_true( $res7['is_valid'] === false, "Fixture with conflicts must be marked invalid" );
assert_true( ! empty( $res7['conflicts'] ), "Conflicts array must not be empty" );

$first_conflict = $res7['conflicts'][0];
assert_true( isset( $first_conflict['week'] ), "Conflict must have 'week'" );
assert_true( isset( $first_conflict['league'] ), "Conflict must have 'league'" );
assert_true( ! array_key_exists( 'division', $first_conflict ), "Conflict must not contain residual 'division' key" );
assert_true( isset( $first_conflict['court'] ), "Conflict must have 'court'" );
assert_true( isset( $first_conflict['home'] ), "Conflict must have 'home'" );
assert_true( isset( $first_conflict['with'] ), "Conflict must have 'with'" );
assert_true( isset( $first_conflict['with_league'] ), "Conflict must have 'with_league'" );
assert_true( ! array_key_exists( 'with_division', $first_conflict ), "Conflict must not contain residual 'with_division' key" );
assert_true( ! empty( $first_conflict['with_league'] ), "Conflict 'with_league' must not be empty" );

// Verify the conflict message formatting using production helper Admin_Competition_Scheduler::format_conflict_item
$rendered_conflict = Admin_Competition_Scheduler::format_conflict_item( $first_conflict );

assert_true(
	str_starts_with( $rendered_conflict, '<li>' ) && str_ends_with( $rendered_conflict, '</li>' ),
	"Rendered conflict must be wrapped in <li>...</li>"
);
assert_true(
	str_contains( $rendered_conflict, 'clashing on Shared Court:' ),
	"Rendered conflict message must contain 'clashing on Shared Court:'"
);
assert_true(
	! str_contains( $rendered_conflict, ' vs ' ),
	"Rendered conflict message must NOT contain misleading ' vs '"
);
assert_true(
	str_contains( $rendered_conflict, "({$first_conflict['league']})" ),
	"Rendered conflict must contain the home team league in parentheses"
);
assert_true(
	str_contains( $rendered_conflict, "({$first_conflict['with_league']})" ),
	"Rendered conflict must contain the conflicting team league in parentheses"
);

// Canonical single-league conflict rendering test (when conflicting team has no with_league)
$single_league_conflict = [
	'week'   => 2,
	'home'   => 'Home Club 1',
	'league' => 'Championship League',
	'with'   => 'Home Club 2',
	'court'  => 'Court 1',
];
$single_rendered = Admin_Competition_Scheduler::format_conflict_item( $single_league_conflict );
assert_true(
	str_contains( $single_rendered, 'Week 2: Home Club 1 (Championship League) and Home Club 2 clashing on Shared Court: Court 1' ),
	"format_conflict_item must format canonical conflict without with_league correctly"
);
assert_true(
	! str_contains( $single_rendered, '()' ),
	"format_conflict_item without with_league must not contain empty parentheses ()"
);

// Adversarial HTML test for format_conflict_item (escaping verification)
$adversarial_conflict = [
	'week'        => 3,
	'home'        => 'Team <script>alert("home")</script>',
	'league'      => 'League <>&"\' 1',
	'with'        => 'Team <b>with</b> & co',
	'with_league' => 'League <script>alert("with")</script>',
	'court'       => 'Court "C0" & <test>',
];
$adv_conflict_rendered = Admin_Competition_Scheduler::format_conflict_item( $adversarial_conflict );
assert_true( ! str_contains( $adv_conflict_rendered, '<script>' ), "Conflict HTML must NOT contain unescaped <script>" );
assert_true( ! str_contains( $adv_conflict_rendered, '<b>' ), "Conflict HTML must NOT contain unescaped <b>" );
assert_true( ! str_contains( $adv_conflict_rendered, '<test>' ), "Conflict HTML must NOT contain unescaped <test>" );
assert_true( str_contains( $adv_conflict_rendered, '&lt;script&gt;alert(&quot;home&quot;)&lt;/script&gt;' ), "Conflict HTML must escape home team script tag and quotes" );
assert_true( str_contains( $adv_conflict_rendered, '&lt;script&gt;alert(&quot;with&quot;)&lt;/script&gt;' ), "Conflict HTML must escape with-team league script tag and quotes" );
assert_true( str_contains( $adv_conflict_rendered, 'League &lt;&gt;&amp;&quot;&#039; 1' ), "Conflict HTML must escape league special chars" );
assert_true( str_contains( $adv_conflict_rendered, 'Court &quot;C0&quot; &amp; &lt;test&gt;' ), "Conflict HTML must escape court special chars" );

// Verify duplicate alert rendering using production helper Admin_Competition_Scheduler::format_duplicate_alert_item
$rendered_dup_alert = Admin_Competition_Scheduler::format_duplicate_alert_item( $result3['alerts'][0] );
assert_true(
	str_starts_with( $rendered_dup_alert, '<li>' ) && str_ends_with( $rendered_dup_alert, '</li>' ),
	"Duplicate alert HTML must be wrapped in <li>...</li>"
);
assert_true(
	str_contains( $rendered_dup_alert, "League &#039;Edge League&#039; contains duplicate team ID &#039;99&#039;" ),
	"Duplicate alert HTML must strictly escape single quotes and match the exact escaped message"
);
assert_true(
	! str_contains( $rendered_dup_alert, "'" ),
	"Duplicate alert HTML must NOT contain raw unescaped single quotes"
);

// Adversarial HTML test for format_duplicate_alert_item
$adversarial_duplicate = [
	'type'    => 'duplicate_team',
	'team_id' => '<script>99</script>',
	'league'  => 'Evil <img src=x onerror=alert(1)> League',
	'message' => 'League "Evil <script>" contains duplicate team ID \'<script>99</script>\'',
];
$adv_dup_rendered = Admin_Competition_Scheduler::format_duplicate_alert_item( $adversarial_duplicate );
assert_true( ! str_contains( $adv_dup_rendered, '<script>' ), "Duplicate alert HTML must NOT contain unescaped <script>" );
assert_true( ! str_contains( $adv_dup_rendered, '<img' ), "Duplicate alert HTML must NOT contain unescaped <img>" );
assert_true( str_contains( $adv_dup_rendered, 'League &quot;Evil &lt;script&gt;&quot; contains duplicate team ID &#039;&lt;script&gt;99&lt;/script&gt;&#039;' ), "Duplicate alert HTML must strictly escape HTML tags and quotes" );

// Fallback message test (when message key is omitted) with league key
$fallback_duplicate = [
	'type'    => 'duplicate_team',
	'team_id' => '<bad_id>',
	'league'  => 'Edge League <league_alert>',
];
$fallback_dup_rendered = Admin_Competition_Scheduler::format_duplicate_alert_item( $fallback_duplicate );
assert_true( ! str_contains( $fallback_dup_rendered, '<league_alert>' ), "Fallback duplicate alert must not contain unescaped tags" );
assert_true( ! str_contains( $fallback_dup_rendered, '<bad_id>' ), "Fallback duplicate alert must not contain unescaped team_id" );
assert_true( str_contains( $fallback_dup_rendered, "League &#039;Edge League &lt;league_alert&gt;&#039; contains duplicate team ID &#039;&lt;bad_id&gt;&#039;" ), "Fallback duplicate alert must strictly escape league and team_id" );

// Fallback message test (when message key is omitted) with canonical league key
$fallback_canonical_duplicate = [
	'type'    => 'duplicate_team',
	'team_id' => 42,
	'league'  => 'Championship League',
];
$fallback_canonical_rendered = Admin_Competition_Scheduler::format_duplicate_alert_item( $fallback_canonical_duplicate );
assert_true(
	str_contains( $fallback_canonical_rendered, "League &#039;Championship League&#039; contains duplicate team ID &#039;42&#039;" ),
	"Fallback duplicate alert with canonical league key must format with League prefix"
);

echo "\n=== All Admin_Competition_Scheduler Tests Passed Successfully ===\n";
