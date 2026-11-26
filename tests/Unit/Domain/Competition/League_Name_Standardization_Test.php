<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;
use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;
use Racketmanager\Admin\View_Models\Plan_Final_Card_View_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO as Application_Fixture_Details_DTO;
use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO as Domain_Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Rest\Rest_Match_Response;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\Tests\Helpers\League_Test_Factory;
use stdClass;

final class League_Name_Standardization_Test extends TestCase {

	public function test_league_title_and_name_bidirectional_property_hook(): void {
		$league = new League();

		$league->set_name( 'Premier Division' );
		$this->assertSame( 'Premier Division', $league->name );
		$this->assertSame( 'Premier Division', $league->title );
		$this->assertSame( 'Premier Division', $league->get_name() );
		$this->assertSame( 'Premier Division', $league->get_title() );

		$league->set_title( 'Championship Division' );
		$this->assertSame( 'Championship Division', $league->name );
		$this->assertSame( 'Championship Division', $league->title );
		$this->assertSame( 'Championship Division', $league->get_name() );
		$this->assertSame( 'Championship Division', $league->get_title() );

		$league->update( 'Division 1', '3' );
		$this->assertSame( 'Division 1', $league->name );
		$this->assertSame( 'Division 1', $league->title );
		$this->assertSame( '3', $league->sequence );
	}

	public function test_league_hydration_precedence_preserves_column_name_over_settings(): void {
		$event_raw                 = new stdClass();
		$event_raw->id             = 10;
		$event_raw->name           = 'Event';
		$event_raw->type           = 'singles';
		$event_raw->competition_id = 1;
		$event                     = Event::from_object( $event_raw );

		$dto = new League_Hydration_DTO(
			id: 5,
			name: 'Canonical Division',
			sequence: '1',
			settings: array(
				'title' => 'Settings Title Override',
				'name'  => 'Settings Name Override',
			)
		);

		$league = new League( $dto, $event );

		$this->assertSame( 'Canonical Division', $league->name );
		$this->assertSame( 'Canonical Division', $league->title );
		$this->assertSame( 'Settings Title Override', $league->settings['title'] );
		$this->assertSame( 'Settings Name Override', $league->settings['name'] );
	}

	public function test_league_hydration_dto_constructor_and_from_object_fallback(): void {
		$dto_named = new League_Hydration_DTO( id: 1, name: 'Division A' );
		$this->assertSame( 'Division A', $dto_named->name );
		$this->assertSame( 'Division A', $dto_named->title );

		$dto_legacy = new League_Hydration_DTO( id: 2, title: 'Division B' );
		$this->assertSame( 'Division B', $dto_legacy->name );
		$this->assertSame( 'Division B', $dto_legacy->title );

		$from_title = League_Hydration_DTO::from_object( (object) array( 'id' => 3, 'title' => 'From Title' ) );
		$this->assertSame( 'From Title', $from_title->name );
		$this->assertSame( 'From Title', $from_title->title );

		$from_both = League_Hydration_DTO::from_object( (object) array( 'id' => 4, 'name' => 'From Name', 'title' => 'From Title' ) );
		$this->assertSame( 'From Name', $from_both->name );
		$this->assertSame( 'From Name', $from_both->title );
	}

	public function test_order_of_play_match_view_model_hook(): void {
		$vm = new Order_Of_Play_Match_View_Model(
			id: 10,
			league_name: 'Premier Division',
			home_match_title: 'Home Team',
			away_match_title: 'Away Team'
		);

		$this->assertSame( 'Premier Division', $vm->league_name );
		$this->assertSame( 'Premier Division', $vm->league_title );
	}

	public function test_admin_results_match_view_model_hook(): void {
		$vm = new Admin_Results_Match_View_Model(
			league_id: 1,
			league_name: 'Championship',
			formatted_date: '2026-05-01',
			match_title: 'Team A vs Team B',
			match_link: 'https://example.test/match/1',
			result_link: 'https://example.test/match/1/result',
			confirmed_display: 'Confirmed',
			score: '6-2 6-4',
			tooltip: ''
		);

		$this->assertSame( 'Championship', $vm->league_name );
		$this->assertSame( 'Championship', $vm->league_title );
	}

	public function test_fixture_header_read_model_hook(): void {
		$model = new Fixture_Header_Read_Model(
			id: 20,
			event_name: 'Summer League',
			event_url: 'https://example.test/event',
			league_name: 'Division 2',
			league_url: 'https://example.test/league',
			season: '2026',
			round_name: null,
			match_day: 1,
			leg: null,
			fixture_date: '2026-06-01',
			formatted_date: '01/06/2026',
			original_date_formatted: null,
			home_team_name: 'Home',
			home_team_url: 'https://example.test/home',
			home_team_withdrawn: false,
			away_team_name: 'Away',
			away_team_url: 'https://example.test/away',
			away_team_withdrawn: false,
			is_pending: false,
			score_class: '',
			home_points: '2',
			away_points: '1',
			match_time: '14:00',
			status_message: null,
			status_description: null,
			show_menu: false,
			allow_amend_score: false,
			allow_schedule_match: false,
			allow_switch_match: false,
			allow_reset_match_result: false,
			amend_score_label: 'Amend',
			match_link: 'https://example.test/match/20',
			rubbers_score: '2 - 1',
			sets_score: '4 - 2',
			games_score: '24 - 18',
			edit_mode: false,
			user_can_update: false,
			match_approval_mode: false
		);

		$this->assertSame( 'Division 2', $model->league_name );
		$this->assertSame( 'Division 2', $model->league_title );
	}

	public function test_domain_fixture_details_dto_hook(): void {
		$fixture     = $this->createStub( Fixture::class );
		$league      = new League();
		$league->set_name( 'Division 3' );
		$event       = $this->createStub( Event::class );
		$competition = $this->createStub( Competition::class );

		$dto = new Domain_Fixture_Details_DTO( $fixture, $league, $event, $competition );

		$this->assertSame( 'Division 3', $dto->league_name );
		$this->assertSame( 'Division 3', $dto->league_title );
	}

	public function test_application_fixture_details_dto_hook(): void {
		$fixture = $this->createStub( Fixture::class );
		$dto     = new Application_Fixture_Details_DTO( fixture: $fixture );

		$this->assertSame( '', $dto->league_name );
		$this->assertSame( '', $dto->league_title );

		$league       = new League();
		$league->set_name( 'Veterans Division' );
		$dto->league  = $league;

		$this->assertSame( 'Veterans Division', $dto->league_name );
		$this->assertSame( 'Veterans Division', $dto->league_title );
	}

	public function test_final_property_hooks_work_on_phpunit_stubs(): void {
		$stub = $this->createStub( League::class );

		League_Test_Factory::set_property( $stub, 'title', 'Stub League' );
		$this->assertSame( 'Stub League', $stub->title );
		$this->assertSame( 'Stub League', $stub->name );

		League_Test_Factory::set_property( $stub, 'name', 'Stub League Renamed' );
		$this->assertSame( 'Stub League Renamed', $stub->title );
		$this->assertSame( 'Stub League Renamed', $stub->name );
	}

	public function test_plan_final_card_view_model_hook(): void {
		$card = new Plan_Final_Card_View_Model(
			id: 42,
			league_name: 'Cup Final Division',
			btn_type: 'final',
			home_title: 'Club A',
			away_title: 'Club B',
			is_home_pending: false,
			is_away_pending: false
		);

		$this->assertSame( 'Cup Final Division', $card->league_name );
		$this->assertSame( 'Cup Final Division', $card->league_title );

		// Empty string edge case
		$empty_card = new Plan_Final_Card_View_Model(
			id: 43,
			league_name: '',
			btn_type: 'final',
			home_title: 'Club A',
			away_title: 'Club B',
			is_home_pending: false,
			is_away_pending: false
		);
		$this->assertSame( '', $empty_card->league_name );
		$this->assertSame( '', $empty_card->league_title );
	}

	public function test_league_event_page_view_model_hook_and_constructor_fallback(): void {
		$event = (object) array( 'id' => 1 );

		// Constructed with canonical league_name
		$vm1 = new League_Event_Page_View_Model(
			event: $event,
			season: '2026',
			league_name: 'Division 1'
		);
		$this->assertSame( 'Division 1', $vm1->league_name );
		$this->assertSame( 'Division 1', $vm1->league_title );

		// Constructed with legacy league_title
		$vm2 = new League_Event_Page_View_Model(
			event: $event,
			season: '2026',
			league_title: 'Legacy Division'
		);
		$this->assertSame( 'Legacy Division', $vm2->league_name );
		$this->assertSame( 'Legacy Division', $vm2->league_title );

		// Defaults to empty string
		$vm3 = new League_Event_Page_View_Model(
			event: $event,
			season: '2026'
		);
		$this->assertSame( '', $vm3->league_name );
		$this->assertSame( '', $vm3->league_title );
	}

	public function test_rest_match_response_hooks(): void {
		$resp = new Rest_Match_Response(
			league: 'Summer League',
			home_team: 'Team A',
			away_team: 'Team B',
			match_date: '2026-06-01',
			match_time: '18:00'
		);

		$this->assertSame( 'Summer League', $resp->league );
		$this->assertSame( 'Summer League', $resp->league_name );
		$this->assertSame( 'Summer League', $resp->league_title );

		$empty_resp = new Rest_Match_Response(
			league: '',
			home_team: 'Team A',
			away_team: 'Team B',
			match_date: '2026-06-01',
			match_time: '18:00'
		);
		$this->assertSame( '', $empty_resp->league_name );
		$this->assertSame( '', $empty_resp->league_title );
	}

	public function test_null_and_empty_edge_cases(): void {
		// League defaults
		$league = new League();
		$this->assertSame( '', $league->name );
		$this->assertSame( '', $league->title );
		$this->assertSame( '', $league->get_name() );
		$this->assertSame( '', $league->get_title() );

		// Setting empty string
		$league->set_name( '' );
		$this->assertSame( '', $league->name );
		$this->assertSame( '', $league->title );

		$league->set_title( '' );
		$this->assertSame( '', $league->name );
		$this->assertSame( '', $league->title );

		// League_Hydration_DTO from nulls/empty object
		$dto_empty = League_Hydration_DTO::from_object( (object) array() );
		$this->assertSame( '', $dto_empty->name );
		$this->assertSame( '', $dto_empty->title );

		$dto_nulls = League_Hydration_DTO::from_object( (object) array( 'name' => null, 'title' => null ) );
		$this->assertSame( '', $dto_nulls->name );
		$this->assertSame( '', $dto_nulls->title );

		// Application_Fixture_Details_DTO with null league
		$fixture = $this->createStub( Fixture::class );
		$app_dto_null_league = new Application_Fixture_Details_DTO( fixture: $fixture );
		$this->assertSame( '', $app_dto_null_league->league_name );
		$this->assertSame( '', $app_dto_null_league->league_title );

		// Fixture_Details_DTO with empty league name
		$event   = $this->createStub( Event::class );
		$comp    = $this->createStub( Competition::class );
		$league_empty = new League();
		$dto_empty_league = new Domain_Fixture_Details_DTO( $fixture, $league_empty, $event, $comp );
		$this->assertSame( '', $dto_empty_league->league_name );
		$this->assertSame( '', $dto_empty_league->league_title );

		// Order_Of_Play_Match_View_Model empty
		$oop_empty = new Order_Of_Play_Match_View_Model(
			id: 1,
			league_name: '',
			home_match_title: 'H',
			away_match_title: 'A'
		);
		$this->assertSame( '', $oop_empty->league_name );
		$this->assertSame( '', $oop_empty->league_title );

		// Admin_Results_Match_View_Model empty
		$admin_empty = new Admin_Results_Match_View_Model(
			league_id: 0,
			league_name: '',
			formatted_date: '',
			match_title: '',
			match_link: '',
			result_link: '',
			confirmed_display: '',
			score: '',
			tooltip: ''
		);
		$this->assertSame( '', $admin_empty->league_name );
		$this->assertSame( '', $admin_empty->league_title );
	}
}
