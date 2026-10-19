<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Event_View_Model;
use Racketmanager\Admin\View_Models\Admin_League_Schedule_Page_View_Model;
use Racketmanager\Admin\View_Models\Cup_Plan_Page_View_Model;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Team;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Admin_Plan_Templates_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    /** @var array<int, Player> */
    private array $players = [];

    /** @var array<int, string|null> Club shortcode by team id, served by the team resolver stub. */
    private array $team_clubs = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Cup planner
    // ------------------------------------------------------------------

    public function test_cup_plan_renders_with_numeric_teams_and_clash_detection(): void {
        $this->stub_player( 101, 'Alice', 'Smith' );
        $this->stub_player( 102, 'Bob', 'Jones' );

        $output = $this->render_cup_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '10:00', 'matches' => [ 1 ] ],
                [ 'court' => 'Court 2', 'start_time' => '10:00', 'matches' => [ 2 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => match ( (int) $id ) {
                1, 2    => [ 101, 102 ],
                default => [],
            },
        );

        $this->assertStringContainsString( 'Potential clashes', $output );
        $this->assertStringContainsString( 'Alice Smith', $output );
        $this->assertStringContainsString( 'Bob Jones', $output );
    }

    public function test_cup_plan_renders_with_placeholder_teams_and_clash_detection(): void {
        $this->stub_player( 201, 'Charlie', 'Brown' );

        $output = $this->render_cup_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '10:00', 'matches' => [ 10 ] ],
                [ 'court' => 'Court 2', 'start_time' => '10:00', 'matches' => [ 10 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => 10 === (int) $id ? [ 201 ] : [],
            finals: [ $this->make_fixture( 10, '1_semi_1', '5' ) ],
        );

        $this->assertStringContainsString( 'Potential clashes', $output );
        $this->assertStringContainsString( 'Charlie Brown', $output );
        $this->assertSame( 1, substr_count( $output, 'Charlie Brown' ) );
    }

    public function test_cup_plan_renders_cleanly_when_no_clashes(): void {
        $this->stub_player( 101, 'Alice', 'Smith' );
        $this->stub_player( 102, 'Bob', 'Jones' );

        $output = $this->render_cup_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '10:00', 'matches' => [ 1 ] ],
                [ 'court' => 'Court 2', 'start_time' => '10:00', 'matches' => [ 2 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => match ( (int) $id ) {
                1       => [ 101 ],
                2       => [ 102 ],
                default => [],
            },
        );

        $this->assertStringNotContainsString( 'Potential clashes', $output );
    }

    public function test_cup_plan_preserves_dispatcher_and_drag_drop_contract_with_fixture_finals(): void {
        $finals = [
            $this->make_fixture( 10, '5', '6', home_club: 'WCC', away_club: 'LTC' ),
            $this->make_fixture( 11, '1_semi_1', '2_semi_1' ),
        ];

        $output = $this->render_cup_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '10:00', 'matches' => [ 10 ] ],
                [ 'court' => 'Court 2', 'start_time' => '10:30', 'matches' => [ 11 ] ],
            ],
            courts: 2,
            finals: $finals,
        );

        // Config form (Cup_Plan_Action_Dispatcher::handle_save_cup_config).
        $this->assertStringContainsString( 'id="cupDetails"', $output );
        $this->assertStringContainsString( 'name="saveCup"', $output );
        $this->assertStringContainsString( 'name="racketmanager_cup-nonce"', $output );
        $this->assertStringContainsString( 'data-action="racketmanager_cup"', $output );
        $this->assertStringContainsString( 'name="numCourtsAvailable"', $output );
        $this->assertStringContainsString( 'name="timeIncrement"', $output );

        // Planner form (handle_save_plan / handle_reset_plan).
        $this->assertStringContainsString( 'id="cup-planner"', $output );
        $this->assertStringContainsString( 'name="racketmanager_cup-planner-nonce"', $output );
        $this->assertStringContainsString( 'data-action="racketmanager_cup-planner"', $output );
        $this->assertStringContainsString( 'name="numFinals" value="2"', $output );
        $this->assertStringContainsString( 'name="court[0]"', $output );
        $this->assertStringContainsString( 'name="court[1]"', $output );
        $this->assertStringContainsString( 'name="startTime[0]" id="startTime-0" value="10:00"', $output );
        $this->assertStringContainsString( 'name="startTime[1]" id="startTime-1" value="10:30"', $output );
        $this->assertStringContainsString( 'name="savePlan" id="savePlan"', $output );
        $this->assertStringContainsString( 'name="resetPlan" id="resetPlan"', $output );
        $this->assertStringNotContainsString( 'courtStartTime', $output );

        // Drag and drop (src/js/admin/draggable.js).
        $this->assertStringContainsString( 'finals-match" name="match-10" id="match-10" draggable="true"', $output );
        $this->assertStringContainsString( 'finals-match" name="match-11" id="match-11" draggable="true"', $output );
        $this->assertStringContainsString( 'class="col-6 tournament-match" name="schedule[0][0]" id="schedule-0-0"', $output );
        $this->assertStringContainsString( 'class="matchId" name="match[0][0]" id="match-0-0" value="10"', $output );
        $this->assertStringContainsString( 'class="matchId" name="match[1][0]" id="match-1-0" value="11"', $output );
        $this->assertStringContainsString( 'name="matchtime[0][0]" id="matchtime-0-0" value="0"', $output );

        // Final cards: known teams are success, placeholders are warning and pending (italic).
        $this->assertStringContainsString( 'btn btn-success finals-match', $output );
        $this->assertStringContainsString( 'btn btn-warning finals-match', $output );
        $this->assertStringContainsString( 'Team 5', $output );
        $this->assertStringContainsString( '(WCC)', $output );
        $this->assertStringContainsString( '(LTC)', $output );
        $this->assertStringContainsString( 'Winner 1_semi_1', $output );
        $this->assertStringContainsString( 'Winner 2_semi_1', $output );
        $this->assertStringContainsString( '<div class="fst-italic">', $output );
    }

    public function test_cup_plan_loads_team_names_and_clubs_for_fixtures_without_loaded_teams(): void {
        $fixture = $this->make_fixture( 10, '5', '6', home_club: 'WCC', away_club: 'LTC' );
        $this->assertSame( [], $fixture->get_loaded_teams() );

        $vm = $this->cup_vm( order_of_play: [], courts: 1, finals: [ $fixture ] );

        $card = $vm->final_cards[0];
        $this->assertSame( 'Team 5', $card->home_title );
        $this->assertSame( 'Team 6', $card->away_title );
        $this->assertSame( 'WCC', $card->home_club );
        $this->assertSame( 'LTC', $card->away_club );
        $this->assertSame( 'success', $card->btn_type );
    }

    public function test_cup_plan_sizing_uses_event_count_for_rows_and_columns(): void {
        $finals = array_map( fn( int $id ) => $this->make_fixture( $id, '5', '6' ), range( 1, 5 ) );

        $vm = $this->cup_vm(
            order_of_play: [],
            courts: 3,
            finals: $finals,
            num_events: 2,
        );

        // ceil(5 matches / 2 events) + 1, columns floor(12 / 2 events), as before the presenter refactor.
        $this->assertSame( 4, $vm->max_schedules );
        $this->assertSame( 6, $vm->column_width );
        $this->assertCount( 4, $vm->schedule_slots );
        $this->assertCount( 3, $vm->order_of_play );
        $this->assertSame( 'Court 3', $vm->order_of_play[2]['court'] );
        $this->assertSame( '10:00:00', $vm->order_of_play[2]['start_time'] );

        $output = $this->render_template( '/templates/admin/cup/plan.php', [ 'vm' => $vm ] );

        $this->assertSame( 12, substr_count( $output, 'tournament-match" name="schedule[' ) );
        $this->assertSame( 12, substr_count( $output, 'class="col-6 tournament-match"' ) );
    }

    public function test_cup_plan_hourly_increment_doubles_schedule_rows(): void {
        $vm = $this->cup_vm(
            order_of_play: [],
            courts: 2,
            finals: [ $this->make_fixture( 1, '5', '6' ), $this->make_fixture( 2, '5', '6' ) ],
            num_events: 2,
            time_increment: '01:00:00',
        );

        $this->assertSame( 4, $vm->max_schedules );
    }

    public function test_cup_plan_renders_config_errors(): void {
        $output = $this->render_template(
            '/templates/admin/cup/plan.php',
            [
                'vm' => $this->cup_vm(
                    order_of_play: [],
                    courts: 2,
                    finals: [ $this->make_fixture( 10, '5', '6' ) ],
                    num_events: 2,
                    errors: new Error_Bag( [ 'start_time' => 'Start time is invalid.' ] ),
                    tab: 'config',
                ),
            ]
        );

        $this->assertStringContainsString( "activaTab('config')", $output );
        $this->assertStringContainsString( 'is-invalid', $output );
        $this->assertStringContainsString( 'Start time is invalid.', $output );
    }

    public function test_cup_plan_without_events_renders_no_schedule_form(): void {
        $vm = $this->cup_vm(
            order_of_play: [],
            courts: 0,
            finals: [],
            num_events: 0,
            time_increment: null,
            start_time: null,
        );

        $this->assertSame( 0, $vm->max_schedules );
        $this->assertSame( [], $vm->schedule_slots );
        $this->assertSame( [], $vm->final_cards );

        $output = $this->render_template( '/templates/admin/cup/plan.php', [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'id="cupDetails"', $output );
        $this->assertStringContainsString( 'name="saveCup"', $output );
        $this->assertStringNotContainsString( 'id="cup-planner"', $output );
        $this->assertStringNotContainsString( 'finals-match', $output );
        $this->assertStringNotContainsString( 'Potential clashes', $output );
    }

    // ------------------------------------------------------------------
    // Tournament planner
    // ------------------------------------------------------------------

    public function test_tournament_plan_renders_with_clash_detection(): void {
        $this->stub_player( 301, 'David', 'Miller' );

        $output = $this->render_tournament_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '09:00', 'fixtures' => [ 1 ] ],
                [ 'court' => 'Court 2', 'start_time' => '09:00', 'fixtures' => [ 2 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => match ( (int) $id ) {
                1, 2    => [ 301 ],
                default => [],
            },
        );

        $this->assertStringContainsString( 'Potential clashes', $output );
        $this->assertStringContainsString( 'David Miller', $output );
    }

    public function test_tournament_plan_renders_with_placeholder_fixtures_and_clash_detection(): void {
        $this->stub_player( 401, 'Eva', 'Green' );

        $output = $this->render_tournament_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '09:00', 'fixtures' => [ 20 ] ],
                [ 'court' => 'Court 2', 'start_time' => '09:00', 'fixtures' => [ 20 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => 20 === (int) $id ? [ 401 ] : [],
            finals: [ $this->make_fixture_details( 20, '1_semi_1', '2_semi_1', 'Winner Semi Final 1', 'Loser Semi Final 1' ) ],
        );

        $this->assertStringContainsString( 'Potential clashes', $output );
        $this->assertStringContainsString( 'Eva Green', $output );
    }

    public function test_tournament_plan_renders_cleanly_when_no_clashes(): void {
        $this->stub_player( 501, 'Frank', 'Sinatra' );
        $this->stub_player( 502, 'Grace', 'Kelly' );

        $output = $this->render_tournament_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '09:00', 'fixtures' => [ 1 ] ],
                [ 'court' => 'Court 2', 'start_time' => '09:00', 'fixtures' => [ 2 ] ],
            ],
            courts: 2,
            player_ids: fn( $id ) => match ( (int) $id ) {
                1       => [ 501 ],
                2       => [ 502 ],
                default => [],
            },
        );

        $this->assertStringNotContainsString( 'Potential clashes', $output );
    }

    public function test_tournament_plan_preserves_controller_and_drag_drop_contract_with_details_dtos(): void {
        $finals = [
            $this->make_fixture_details( 5, '7', '8', home_name: 'Team Seven', away_name: 'Team Eight', home_club: 'WCC', away_club: 'LTC' ),
            $this->make_fixture_details( 6, '1_semi_1', '2_semi_1', 'Winner Semi Final 1', 'Loser Semi Final 1' ),
        ];

        $output = $this->render_tournament_plan(
            order_of_play: [
                [ 'court' => 'Court 1', 'start_time' => '09:00', 'fixtures' => [ 5 ] ],
                [ 'court' => 'Court 2', 'start_time' => '09:30', 'fixtures' => [ 6 ] ],
            ],
            courts: 2,
            finals: $finals,
        );

        // Config form (Tournament_Plan_Admin_Controller::handle_save_tournament_finals_config).
        $this->assertStringContainsString( 'id="tournamentDetails"', $output );
        $this->assertStringContainsString( 'data-action="racketmanager_tournament-finals-config"', $output );
        $this->assertStringContainsString( 'name="saveTournamentFinalsConfig"', $output );
        $this->assertStringContainsString( 'name="tournamentId" value="5"', $output );

        // Planner form (handle_save_tournament_plan / handle_reset_tournament_plan).
        $this->assertStringContainsString( 'id="tournament-planner"', $output );
        $this->assertStringContainsString( 'data-action="racketmanager_tournament-planner"', $output );
        $this->assertStringContainsString( 'name="racketmanager_nonce"', $output );
        $this->assertStringContainsString( 'name="numFinals" value="2"', $output );
        $this->assertStringContainsString( 'name="courtStartTime[0]" id="courtStartTime-0" value="09:00"', $output );
        $this->assertStringContainsString( 'name="courtStartTime[1]" id="courtStartTime-1" value="09:30"', $output );
        $this->assertStringContainsString( 'name="saveTournamentPlan" id="saveTournamentPlan"', $output );
        $this->assertStringContainsString( 'name="resetTournamentPlan" id="resetTournamentPlan"', $output );

        // Drag and drop (src/js/admin/draggable.js).
        $this->assertStringContainsString( 'finals-match" name="match-5" id="match-5" draggable="true"', $output );
        $this->assertStringContainsString( 'finals-match" name="match-6" id="match-6" draggable="true"', $output );
        $this->assertStringContainsString( 'class="col-6 tournament-match" name="schedule[1][0]" id="schedule-1-0"', $output );
        $this->assertStringContainsString( 'class="matchId" name="match[0][0]" id="match-0-0" value="5"', $output );
        $this->assertStringContainsString( 'class="matchId" name="match[1][0]" id="match-1-0" value="6"', $output );

        // Real Fixture_Details_DTO finals: names, clubs and placeholder titles.
        $this->assertStringContainsString( 'btn btn-success finals-match', $output );
        $this->assertStringContainsString( 'btn btn-warning finals-match', $output );
        $this->assertStringContainsString( 'Team Seven', $output );
        $this->assertStringContainsString( 'Team Eight', $output );
        $this->assertStringContainsString( '(WCC)', $output );
        $this->assertStringContainsString( '(LTC)', $output );
        $this->assertStringContainsString( 'Winner Semi Final 1', $output );
        $this->assertStringContainsString( 'Loser Semi Final 1', $output );
        $this->assertStringContainsString( 'Mens Open', $output );
    }

    public function test_tournament_plan_falls_back_to_placeholder_reference_without_title(): void {
        $vm = $this->tournament_vm(
            order_of_play: [],
            courts: 1,
            finals: [ $this->make_fixture_details( 9, '1_final_1', '4' ) ],
        );

        $this->assertCount( 1, $vm->final_cards );
        $this->assertSame( '1_final_1', $vm->final_cards[0]->home_title );
        $this->assertTrue( $vm->final_cards[0]->is_home_pending );
        $this->assertFalse( $vm->final_cards[0]->is_away_pending );
        $this->assertSame( 'warning', $vm->final_cards[0]->btn_type );
    }

    public function test_tournament_plan_redisplays_submitted_config_and_errors(): void {
        $vm = $this->tournament_vm(
            order_of_play: [],
            courts: 2,
            finals: [],
            errors: new Error_Bag( [ 'start_time' => 'Tournament start time is required.' ] ),
            submitted: [ 'startTime' => '', 'timeIncrement' => '00:45:00', 'numCourtsAvailable' => '6' ],
            tab: 'config',
        );

        $output = $this->render_template( '/templates/admin/tournament/plan.php', [ 'vm' => $vm ] );

        $this->assertStringContainsString( "activaTab('config')", $output );
        $this->assertStringContainsString( 'is-invalid', $output );
        $this->assertStringContainsString( 'Tournament start time is required.', $output );
        $this->assertStringContainsString( 'name="timeIncrement" id="timeIncrement" value="00:45:00"', $output );
        $this->assertStringContainsString( 'name="numCourtsAvailable" id="numCourtsAvailable" value="6"', $output );
    }

    public function test_tournament_plan_without_courts_renders_no_schedule_form(): void {
        $vm = $this->tournament_vm( order_of_play: [], courts: 0, finals: [] );

        $this->assertSame( 0, $vm->max_schedules );
        $this->assertSame( [], $vm->schedule_slots );

        $output = $this->render_template( '/templates/admin/tournament/plan.php', [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'id="tournamentDetails"', $output );
        $this->assertStringNotContainsString( 'id="tournament-planner"', $output );
        $this->assertStringNotContainsString( 'finals-match', $output );
    }

    // ------------------------------------------------------------------
    // League schedule and order of play (unchanged by the planner work)
    // ------------------------------------------------------------------

    public function test_league_schedule_renders_via_view_model(): void {
        $competition = $this->make_competition();

        $events = [
            new Admin_League_Schedule_Event_View_Model(
                id: 1,
                name: 'Men Division 1',
                season: '2026',
                event_link: 'admin.php?page=racketmanager-leagues&view=event&competition_id=10&event_id=1&season=2026',
                can_send_fixtures: true,
            ),
            new Admin_League_Schedule_Event_View_Model(
                id: 2,
                name: 'Men Division 2',
                season: '2026',
                event_link: 'admin.php?page=racketmanager-leagues&view=event&competition_id=10&event_id=2&season=2026',
                can_send_fixtures: false,
            ),
        ];

        $vm = new Admin_League_Schedule_Page_View_Model(
            competition: $competition,
            season: '2026',
            competition_id: 10,
            events: $events,
        );

        $output = $this->render_template(
            '/templates/admin/league/show-schedule.php',
            [ 'vm' => $vm ]
        );

        $this->assertStringContainsString( "id='schedule-filter'", $output );
        $this->assertStringContainsString( 'name="actionSchedule"', $output );
        $this->assertStringContainsString( 'Men Division 1', $output );
        $this->assertStringContainsString( 'Men Division 2', $output );
        $this->assertStringContainsString( 'data-event-id="1"', $output );
        $this->assertStringNotContainsString( 'data-event-id="2"', $output );
        $this->assertStringContainsString( 'name="event[1]"', $output );
        $this->assertStringContainsString( 'name="event[2]"', $output );
    }

    public function test_empty_league_schedule_and_order_of_play_rendering(): void {
        // 1. League schedule with no events
        $league_vm = new Admin_League_Schedule_Page_View_Model(
            competition: $this->make_competition(),
            season: '2026',
            competition_id: 10,
            events: [],
        );
        $league_output = $this->render_template(
            '/templates/admin/league/show-schedule.php',
            [ 'vm' => $league_vm ]
        );
        $this->assertStringContainsString( "id='schedule-filter'", $league_output );
        $this->assertStringNotContainsString( '<button class="btn btn-secondary sendFixtures"', $league_output );

        // 2. Order of play court with empty time slots
        $court_times = [
            [],
        ];
        $is_expanded = false;

        $oop_output = $this->render_template(
            '/templates/includes/order-of-play-court.php',
            [ 'court_times' => $court_times, 'is_expanded' => $is_expanded ]
        );
        $this->assertStringContainsString( 'match-group__item-wrapper', $oop_output );
        $this->assertStringNotContainsString( 'match__header-title', $oop_output );
    }

    public function test_order_of_play_court_renders_handler_match_view_model_with_placeholder(): void {
        $match_vm = new Order_Of_Play_Match_View_Model(
            id: 99,
            league_title: 'Championship Semi Final',
            home_match_title: '1_semi_1',
            away_match_title: 'Westcliff 1',
            is_home_pending: true,
            is_away_pending: false,
        );

        $court_times = [
            [ $match_vm ],
        ];
        $is_expanded = false;

        $output = $this->render_template(
            '/templates/includes/order-of-play-court.php',
            [ 'court_times' => $court_times, 'is_expanded' => $is_expanded ]
        );

        $this->assertStringContainsString( 'Championship Semi Final', $output );
        $this->assertStringContainsString( '1_semi_1', $output );
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'is_pending', $output );
    }

    public function test_admin_league_schedule_delivery_with_service_and_match_counts(): void {
        $event1 = (object) [
            'id'     => 101,
            'name'   => 'Premier League',
            'season' => '2026',
        ];
        $event2 = (object) [
            'id'     => 102,
            'name'   => 'Division 1',
            'season' => '2026',
        ];

        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 55 );
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_events' )->willReturn( [ $event1, $event2 ] );
        $competition->id   = 55;
        $competition->name = 'Premier Leagues';

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition' )
            ->with( 55 )
            ->willReturn( $competition );
        $comp_service->expects( $this->atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 55 )
            ->willReturn( $competition );

        $event_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $event_repo->method( 'count_by_event_season' )->willReturnCallback(
            static function ( int $event_id, string $season, bool $completed_only ): int {
                if ( 101 === $event_id && '2026' === $season ) {
                    return $completed_only ? 0 : 10;
                }
                if ( 102 === $event_id && '2026' === $season ) {
                    return $completed_only ? 5 : 5;
                }

                return 0;
            }
        );

        $container = $this->create_admin_delivery_container(
            services: [
                'competition_service' => $comp_service,
                'event_repository'    => $event_repo,
            ]
        );

        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $admin_league = new Admin_League( $racketmanager );

        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET['competition_id']               = '55';
        unset( $_GET['season'], $_POST['scheduleAction'] );

        ob_start();
        try {
            $admin_league->display_schedule_page();
            $output = (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }

        $this->assertStringContainsString( 'Premier League', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'data-event-id="101"', $output );
        $this->assertStringNotContainsString( 'data-event-id="102"', $output );
        $this->assertStringContainsString( 'event_id=101', $output );
        $this->assertStringContainsString( 'event_id=102', $output );
    }

    public function test_admin_league_schedule_delivery_with_no_events(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 55 );
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_events' )->willReturn( [] );
        $competition->id   = 55;
        $competition->name = 'Empty Competition';

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition' )
            ->with( 55 )
            ->willReturn( $competition );
        $comp_service->expects( $this->atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 55 )
            ->willReturn( $competition );

        $event_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );

        $container = $this->create_admin_delivery_container(
            services: [
                'competition_service' => $comp_service,
                'event_repository'    => $event_repo,
            ]
        );

        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $admin_league = new Admin_League( $racketmanager );

        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET['competition_id']               = '55';
        unset( $_GET['season'], $_POST['scheduleAction'] );

        ob_start();
        try {
            $admin_league->display_schedule_page();
            $output = (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }

        $this->assertStringContainsString( 'Empty Competition', $output );
        $this->assertStringNotContainsString( 'data-event-id=', $output );
    }

    private function stub_player( int $id, string $first_name, string $last_name ): void {
        $player               = new Player();
        $player->id           = $id;
        $player->display_name = $first_name . ' ' . $last_name;

        $this->players[ $id ] = $player;
    }

    /**
     * @param callable|null $player_ids Maps a fixture id to the ids of the players taking part (including ancestors).
     */
    private function presenter( ?callable $player_ids = null ): Admin_Plan_Presenter {
        $detail_service = $this->createStub( Fixture_Detail_Service::class );
        $detail_service->method( 'collect_fixture_player_ids' )
            ->willReturnCallback( $player_ids ?? static fn( $id ): array => [] );
        $detail_service->method( 'resolve_placeholder_title' )
            ->willReturnCallback( static fn( string $team_ref ): string => 'Winner ' . $team_ref );

        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find_by_id' )
            ->willReturnCallback( fn( $id ) => $this->players[ (int) $id ] ?? null );

        $resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
        $resolver->method( 'get_team_details' )->willReturnCallback(
            fn( $id ): Team_Details_DTO => $this->make_team_details( (int) $id, 'Team ' . $id, $this->team_clubs[ (int) $id ] ?? null )
        );
        $resolver->method( 'derive_team_details' )->willReturn( null );

        return new Admin_Plan_Presenter( $detail_service, $player_repository, new Fixture_Relation_Loader( null, $resolver ) );
    }

    private function make_fixture( int $id, string $home_team, string $away_team, ?string $home_club = null, ?string $away_club = null ): Fixture {
        $fixture            = new Fixture();
        $fixture->set_id( $id );
        $fixture->home_team = $home_team;
        $fixture->away_team = $away_team;
        $fixture->season    = '2026';
        // Production shape: Competition::get_matches() leaves teams unloaded; the presenter loads them.
        if ( is_numeric( $home_team ) ) {
            $this->team_clubs[ (int) $home_team ] = $home_club;
        }
        if ( is_numeric( $away_team ) ) {
            $this->team_clubs[ (int) $away_team ] = $away_club;
        }
        $fixture->league    = (object) [ 'title' => 'Mens Open' ];

        return $fixture;
    }

    private function make_fixture_details(
        int $id,
        string $home_team,
        string $away_team,
        ?string $prev_home_title = null,
        ?string $prev_away_title = null,
        ?string $home_name = null,
        ?string $away_name = null,
        ?string $home_club = null,
        ?string $away_club = null,
    ): Fixture_Details_DTO {
        $fixture = new Fixture();
        $fixture->set_id( $id );
        $fixture->home_team = $home_team;
        $fixture->away_team = $away_team;

        $league        = $this->createStub( League::class );
        $league->title = 'Mens Open';

        return new Fixture_Details_DTO(
            $fixture,
            $league,
            $this->createStub( Event::class ),
            $this->createStub( Competition::class ),
            null !== $home_name ? $this->make_team_details( (int) $home_team, $home_name, $home_club ) : null,
            null !== $away_name ? $this->make_team_details( (int) $away_team, $away_name, $away_club ) : null,
            $prev_home_title,
            $prev_away_title,
        );
    }

    private function make_team_details( int $id, string $name, ?string $club_shortcode ): Team_Details_DTO {
        $team = new Team();
        $team->set_id( $id );
        $team->set_name( $name );
        $club = null !== $club_shortcode ? new Club( (object) [ 'name' => 'Club ' . $club_shortcode, 'shortcode' => $club_shortcode ] ) : null;

        return new Team_Details_DTO( $team, $club );
    }

    private function make_competition(): Competition {
        return new Competition( name: 'Main Cup', type: 'cup', age_group: 'open', id: 10 );
    }

    private function make_tournament( array $order_of_play, int $courts, string $time_increment = '00:30:00', string $start_time = '09:00:00' ): object {
        return new class( $order_of_play, $courts, $time_increment, $start_time ) {
            public function __construct( private array $order_of_play, private int $courts, private string $time_increment, private string $start_time ) {}
            public function get_id(): int { return 5; }
            public function get_season(): string { return '2026'; }
            public function get_name(): string { return 'Annual Open'; }
            public function get_meta( string $key ): string { return 'Tennis Club'; }
            public function get_end_date(): string { return '2026-07-01'; }
            public function get_num_courts(): int { return $this->courts; }
            public function get_time_increment(): string { return $this->time_increment; }
            public function get_start_time(): string { return $this->start_time; }
            public function get_order_of_play(): array { return $this->order_of_play; }
        };
    }

    /**
     * @param array<int, array<string, mixed>> $order_of_play
     * @param array<int, Fixture|Fixture_Details_DTO>|null $finals
     */
    private function cup_vm(
        array $order_of_play,
        int $courts,
        ?array $finals = null,
        ?int $num_events = null,
        ?string $time_increment = '00:30:00',
        ?string $start_time = '10:00:00',
        ?Error_Bag $errors = null,
        string $tab = 'matches',
        ?callable $player_ids = null,
    ): Cup_Plan_Page_View_Model {
        $cup_season = (object) [
            'starttime'      => $start_time,
            'time_increment' => $time_increment,
            'num_courts'     => $courts,
            'venue_name'     => 'Main Center',
            'date_end'       => '2026-06-01',
            'orderofplay'    => $order_of_play,
        ];

        return $this->presenter( $player_ids )->present_cup_plan(
            competition: $this->make_competition(),
            cup_season: $cup_season,
            season: '2026',
            final_matches: $finals ?? array_map( fn( int $id ) => $this->make_fixture( $id, '5', '6' ), range( 1, max( 1, $courts ) ) ),
            tab: $tab,
            num_events: $num_events ?? $courts,
            errors: $errors ?? new Error_Bag(),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $order_of_play
     * @param array<int, Fixture|Fixture_Details_DTO>|null $finals
     */
    private function render_cup_plan( array $order_of_play, int $courts, ?callable $player_ids = null, ?array $finals = null ): string {
        return $this->render_template(
            '/templates/admin/cup/plan.php',
            [ 'vm' => $this->cup_vm( order_of_play: $order_of_play, courts: $courts, finals: $finals, player_ids: $player_ids ) ]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $order_of_play
     * @param array<int, Fixture|Fixture_Details_DTO>|null $finals
     */
    private function tournament_vm(
        array $order_of_play,
        int $courts,
        ?array $finals = null,
        ?Error_Bag $errors = null,
        array $submitted = [],
        string $tab = 'finals',
        ?callable $player_ids = null,
    ): Tournament_Plan_Page_View_Model {
        return $this->presenter( $player_ids )->present_tournament_plan(
            tournament: $this->make_tournament( $order_of_play, $courts ),
            final_matches: $finals ?? array_map( fn( int $id ) => $this->make_fixture_details( $id, '7', '8', home_name: 'Team Seven', away_name: 'Team Eight' ), range( 1, max( 1, $courts ) ) ),
            tab: $tab,
            errors: $errors ?? new Error_Bag(),
            submitted: $submitted,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $order_of_play
     * @param array<int, Fixture|Fixture_Details_DTO>|null $finals
     */
    private function render_tournament_plan( array $order_of_play, int $courts, ?callable $player_ids = null, ?array $finals = null ): string {
        return $this->render_template(
            '/templates/admin/tournament/plan.php',
            [ 'vm' => $this->tournament_vm( order_of_play: $order_of_play, courts: $courts, finals: $finals, player_ids: $player_ids ) ]
        );
    }


    /**
     * @param array<string, mixed> $vars
     */
    private function render_template( string $relative, array $vars ): string {
        extract( $vars, EXTR_SKIP );
        ob_start();
        try {
            include dirname( __DIR__, 3 ) . $relative;

            return (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
