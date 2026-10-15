<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Services\Validator\Validator_Entry_Form;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Validator_Entry_Form_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Validator_Entry_Form $validator;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->validator = new Validator_Entry_Form();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_logged_in_entry_fails_when_user_logged_out(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;

        $result = $this->validator->logged_in_entry();

        $this->assertTrue( $result->error );
        $this->assertContains( 'clubId', $result->err_flds );
        $this->assertContains( 'You must be logged in to submit an entry', $result->err_msgs );
    }

    public function test_logged_in_entry_passes_when_user_logged_in(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;

        $result = $this->validator->logged_in_entry();

        $this->assertFalse( $result->error );
        $this->assertEmpty( $result->err_flds );
    }

    public function test_club_membership_validation(): void {
        $this->validator->club_membership( null );
        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'clubId', $this->validator->err_flds );

        $fresh_validator = new Validator_Entry_Form();
        $fresh_validator->club_membership( '10' );
        $this->assertFalse( $fresh_validator->error );
    }

    public function test_events_entry_empty_fails(): void {
        $result = $this->validator->events_entry( [] );

        $this->assertTrue( $result->error );
        $this->assertContains( 'event', $result->err_flds );
        $this->assertContains( 'You must select a event to enter', $result->err_msgs );
    }

    public function test_events_entry_passes_with_valid_events(): void {
        $result = $this->validator->events_entry( [ 1, 2, 3 ] );

        $this->assertFalse( $result->error );
        $this->assertEmpty( $result->err_flds );
    }

    public function test_events_entry_exceeds_max_entries(): void {
        $result = $this->validator->events_entry( [ 1, 2, 3 ], 2 );

        $this->assertTrue( $result->error );
        $this->assertContains( 'event', $result->err_flds );
        $this->assertContains( 'You have entered too many events', $result->err_msgs );
    }

    public function test_withdrawn_team(): void {
        $result = $this->validator->withdrawn_team( 'event1-team2' );

        $this->assertTrue( $result->error );
        $this->assertContains( 'teamEvent-event1-team2', $result->err_flds );
        $this->assertContains( 'A higher ranked team cannot be withdrawn if there are still active lower ranked teams', $result->err_msgs );

        $fresh = new Validator_Entry_Form();
        $result2 = $fresh->team_withdrawn( 'event1-team3' );
        $this->assertTrue( $result2->error );
        $this->assertContains( 'teamEvent-event1-team3', $result2->err_flds );
    }

    public function test_team_exists_validation(): void {
        $result = $this->validator->team_exists( null, 'event1-team99' );
        $this->assertTrue( $result->error );
        $this->assertContains( 'teamEvent-event1-team99', $result->err_flds );
        $this->assertContains( 'Team not found', $result->err_msgs );

        $fresh = new Validator_Entry_Form();
        $team = new stdClass();
        $team->id = 10;
        $result_pass = $fresh->team_exists( $team, 'event1-team10' );
        $this->assertFalse( $result_pass->error );
    }

    public function test_team_duplicate_validation(): void {
        $result = $this->validator->team_duplicate( 'event1-team2' );
        $this->assertTrue( $result->error );
        $this->assertContains( 'teamEvent-event1-team2', $result->err_flds );
        $this->assertContains( 'Team is already registered in conflicting divisions', $result->err_msgs );
    }

    public function test_events_entry_with_allowed_event_ids(): void {
        $allowed = [ 1, 2, 3 ];
        $e1      = (object) [ 'id' => 1 ];
        $e2      = (object) [ 'id' => 2 ];
        $result  = $this->validator->events_entry( [ 1 => $e1, 2 => $e2 ], null, $allowed );
        $this->assertFalse( $result->error );
        $this->assertSame( [ 1 => $e1, 2 => $e2 ], $this->validator->validated_events );

        $fresh          = new Validator_Entry_Form();
        $result_invalid = $fresh->events_entry( [ 1 => $e1, 99 => (object) [] ], null, $allowed );
        $this->assertTrue( $result_invalid->error );
        $this->assertContains( 'event', $result_invalid->err_flds );
        $this->assertContains( 'One or more selected events are not available for this season', $result_invalid->err_msgs );
        $this->assertSame( [ 1 => $e1 ], $fresh->validated_events );
    }

    public function test_events_entry_with_allowlist_ignores_forged_entry_id(): void {
        $forged = (object) [ 'id' => 1 ];
        $fresh  = new Validator_Entry_Form();
        $result = $fresh->events_entry( [ 99 => $forged ], null, [ 1, 2, 3 ] );
        $this->assertTrue( $result->error );
        $this->assertSame( [], $fresh->validated_events );
    }

    public function test_check_player_event_eligibility_and_partner_eligibility_rules(): void {
        $senior_event = (object) [
            'type'       => 'SD',
            'age_limit'  => 45,
            'age_offset' => 5,
        ];

        // 1. Male partner born 1985 (age 41 in 2026) in 45+ event -> too young
        $partner_m_young = (object) [
            'gender'        => 'M',
            'year_of_birth' => 1985,
        ];
        $res1 = Validator_Entry_Form::check_player_event_eligibility( $partner_m_young, $senior_event, 2026 );
        $this->assertFalse( $res1['eligible'] );
        $this->assertSame( 'too_young', $res1['reason'] );

        $v1 = new Validator_Entry_Form();
        $v1->partner_eligibility( $partner_m_young, $senior_event, 2026, 'partner-1' );
        $this->assertTrue( $v1->error );
        $this->assertContains( 'Partner is too young', $v1->err_msgs );

        // 2. Female partner born 1985 (age 41 in 2026) in 45+ event with 5y offset (min age 40) -> passes
        $partner_f_offset = (object) [
            'gender'        => 'F',
            'year_of_birth' => 1985,
        ];
        $res2 = Validator_Entry_Form::check_player_event_eligibility( $partner_f_offset, $senior_event, 2026 );
        $this->assertTrue( $res2['eligible'] );

        $v2 = new Validator_Entry_Form();
        $v2->partner_eligibility( $partner_f_offset, $senior_event, 2026, 'partner-1' );
        $this->assertFalse( $v2->error );

        // 3. Male partner entering Women event -> gender ineligible
        $women_event = (object) [
            'type'       => 'WD',
            'age_limit'  => null,
            'age_offset' => 0,
        ];
        $res3 = Validator_Entry_Form::check_player_event_eligibility( $partner_m_young, $women_event, 2026 );
        $this->assertFalse( $res3['eligible'] );
        $this->assertSame( 'gender_invalid', $res3['reason'] );

        $v3 = new Validator_Entry_Form();
        $v3->partner_eligibility( $partner_m_young, $women_event, 2026, 'partner-1' );
        $this->assertTrue( $v3->error );
        $this->assertContains( 'Partner is not eligible for this event', $v3->err_msgs );

        // 4. Unknown/empty gender -> gender ineligible even in Open/Senior event
        $partner_unknown_gender = (object) [
            'gender'        => '',
            'year_of_birth' => 1985,
        ];
        $res_unknown = Validator_Entry_Form::check_player_event_eligibility( $partner_unknown_gender, $senior_event, 2026 );
        $this->assertFalse( $res_unknown['eligible'] );
        $this->assertSame( 'gender_invalid', $res_unknown['reason'] );

        // 5. Partner with missing birth year in age-restricted event -> no age specified
        $partner_no_age = (object) [
            'gender'        => 'M',
            'year_of_birth' => 0,
        ];
        $res4 = Validator_Entry_Form::check_player_event_eligibility( $partner_no_age, $senior_event, 2026 );
        $this->assertFalse( $res4['eligible'] );
        $this->assertSame( 'no_age_specified', $res4['reason'] );

        $v4 = new Validator_Entry_Form();
        $v4->partner_eligibility( $partner_no_age, $senior_event, 2026, 'partner-1' );
        $this->assertTrue( $v4->error );
        $this->assertContains( 'Partner has no age specified', $v4->err_msgs );

        // 6. Junior event (age_limit 18) with older partner (born 2000, age 26 in 2026) -> too old
        $junior_event = (object) [
            'type'       => 'BD',
            'age_limit'  => 18,
            'age_offset' => 0,
        ];
        $partner_old = (object) [
            'gender'        => 'M',
            'year_of_birth' => 2000,
        ];
        $res5 = Validator_Entry_Form::check_player_event_eligibility( $partner_old, $junior_event, 2026 );
        $this->assertFalse( $res5['eligible'] );
        $this->assertSame( 'too_old', $res5['reason'] );

        $v5 = new Validator_Entry_Form();
        $v5->partner_eligibility( $partner_old, $junior_event, 2026, 'partner-1' );
        $this->assertTrue( $v5->error );
        $this->assertContains( 'Partner is too old', $v5->err_msgs );

        // 7. Age-restricted event with missing/invalid tournament end year -> fail closed
        $res_no_year = Validator_Entry_Form::check_player_event_eligibility( $partner_f_offset, $senior_event, 0 );
        $this->assertFalse( $res_no_year['eligible'] );
        $this->assertSame( 'missing_tournament_year', $res_no_year['reason'] );
    }

    public function test_events_missing_teams_and_has_teams(): void {
        $this->validator->events_missing_teams( [ 5, 8 ] );

        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'event-5', $this->validator->err_flds );
        $this->assertContains( 'event-8', $this->validator->err_flds );

        $fresh = new Validator_Entry_Form();
        $fresh->events_has_teams( null, '9' );
        $this->assertTrue( $fresh->error );
        $this->assertContains( 'event-9', $fresh->err_flds );
    }

    public function test_court_needs_fails_when_needed_exceeds_available(): void {
        // court_data: 4 courts, 2 teams => court_needs = 2; court_needs_by_day = 2 * ceil(2/2) = 2 courts needed
        $court_data = [ 'courts' => 4, 'teams' => 2 ];
        $this->validator->court_needs( 1, $court_data, '0', '19:00:00' );

        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'numCourtsAvailable', $this->validator->err_flds );
    }

    public function test_entry_acceptance(): void {
        $this->validator->entry_acceptance( false );

        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'acceptance', $this->validator->err_flds );
        $this->assertContains( 'You must agree to the rules', $this->validator->err_msgs );

        $fresh = new Validator_Entry_Form();
        $fresh->entry_acceptance( true );
        $this->assertFalse( $fresh->error );
    }

    public function test_match_overlap_within_two_hours_fails(): void {
        $this->validator->match_overlap( '19:00:00', '20:00:00', '1-10' );

        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'matchtime-1-10', $this->validator->err_flds );
        $this->assertContains( 'Match overlap', $this->validator->err_msgs );

        $fresh = new Validator_Entry_Form();
        $fresh->match_overlap( '14:00:00', '19:00:00', '1-10' );
        $this->assertFalse( $fresh->error );
    }

    public function test_weekend_match_rule(): void {
        $result = $this->validator->weekend_match( 'event1-team1' );

        $this->assertTrue( $result->error );
        $this->assertContains( 'matchday-event1-team1', $result->err_flds );
        $this->assertContains( 'Only the highest numbered team(s) can play at the weekend', $result->err_msgs );
    }

    public function test_free_slots_validation(): void {
        $this->validator->free_slots( '0' );

        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'event', $this->validator->err_flds );
        $this->assertContains( 'Weekend games not allowed when free weekday slots', $this->validator->err_msgs );

        $fresh = new Validator_Entry_Form();
        $fresh->free_slots( '1.5' );
        $this->assertFalse( $fresh->error );
    }

    public function test_competition_open_and_tournament_open(): void {
        $comp = new stdClass();
        $comp->is_open = false;
        $this->validator->competition_open( $comp );
        $this->assertTrue( $this->validator->error );
        $this->assertContains( 'Competition not open for entries', $this->validator->err_msgs );

        $tourn = new stdClass();
        $tourn->date_closing = '';
        $fresh = new Validator_Entry_Form();
        $fresh->tournament_open( $tourn );
        $this->assertTrue( $fresh->error );
        $this->assertContains( 'Tournament close date not set', $fresh->err_msgs );
    }
}
