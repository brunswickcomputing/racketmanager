<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_League;
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
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
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

    private const string TEMPLATE_CUP_PLAN = '/templates/admin/cup/plan.php';
    private const string TEMPLATE_TOURNAMENT_PLAN = '/templates/admin/tournament/plan.php';
    private const string TEMPLATE_SHOW_SCHEDULE = '/templates/admin/league/show-schedule.php';
    private const string TEMPLATE_ORDER_OF_PLAY_COURT = '/templates/includes/order-of-play-court.php';

    private const string COURT_1 = 'Court 1';
    private const string COURT_2 = 'Court 2';
    private const string TIME_0900 = '09:00';
    private const string TIME_1000 = '10:00';

    private const string TEXT_POTENTIAL_CLASHES = 'Potential clashes';
    private const string CLASS_FINALS_MATCH = 'finals-match';
    private const string CLASS_BTN_SUCCESS_FINALS_MATCH = 'btn btn-success finals-match';
    private const string CLASS_BTN_WARNING_FINALS_MATCH = 'btn btn-warning finals-match';
    private const string TAB_CONFIG_SCRIPT = "activaTab('config')";
    private const string CLASS_IS_INVALID = 'is-invalid';
    private const string SELECTOR_SCHEDULE_FILTER = "id='schedule-filter'";
    private const string FORM_CUP_DETAILS = 'id="cupDetails"';
    private const string FORM_CUP_PLANNER = 'id="cup-planner"';
    private const string FORM_TOURNAMENT_DETAILS = 'id="tournamentDetails"';
    private const string FORM_TOURNAMENT_PLANNER = 'id="tournament-planner"';
    private const string ACTION_SAVE_CUP = 'name="saveCup"';
    private const string WINNER_SEMI_FINAL_1 = 'Winner Semi Final 1';
    private const string LOSER_SEMI_FINAL_1 = 'Loser Semi Final 1';
    private const string SEMI_FINAL_1_HOME = '1_semi_1';
    private const string SEMI_FINAL_1_AWAY = '2_semi_1';
    private const string WINNER_PREFIX = 'Winner ';

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

    #[DataProvider( 'provide_cup_plan_clash_cases' )]
    public function test_cup_plan_renders_clash_detection_scenarios(
        array $players,
        array $order_of_play,
        array $match_players,
        ?array $finals_spec,
        bool $expect_clashes,
        array $expected_contained_names,
        array $expected_exact_name_counts = [],
    ): void {
        foreach ( $players as $id => $name_parts ) {
            $this->stub_player( $id, $name_parts[0], $name_parts[1] );
        }

        $finals = null;
        if ( null !== $finals_spec ) {
            $finals = [
                $this->make_fixture( $finals_spec['id'], $finals_spec['home'], $finals_spec['away'] ),
            ];
        }

        $output = $this->render_cup_plan(
            order_of_play: $order_of_play,
            courts: 2,
            player_ids: fn( $id ) => $match_players[ (int) $id ] ?? [],
            finals: $finals,
        );

        $this->assert_clash_report( $output, $expect_clashes, $expected_contained_names, $expected_exact_name_counts );
    }

    private function assert_clash_report( string $output, bool $expect_clashes, array $names = [], array $exact_counts = [] ): void {
        if ( $expect_clashes ) {
            $this->assertStringContainsString( self::TEXT_POTENTIAL_CLASHES, $output );
            foreach ( $names as $name ) {
                $this->assertStringContainsString( $name, $output );
            }
            foreach ( $exact_counts as $name => $count ) {
                $this->assertSame( $count, substr_count( $output, $name ) );
            }
        } else {
            $this->assertStringNotContainsString( self::TEXT_POTENTIAL_CLASHES, $output );
        }
    }

    /**
     * @return array<string, array{
     *     players: array<int, array{string, string}>,
     *     order_of_play: array<int, array<string, mixed>>,
     *     match_players: array<int, list<int>>,
     *     finals_spec: array{id: int, home: string, away: string}|null,
     *     expect_clashes: bool,
     *     expected_contained_names: list<string>,
     *     expected_exact_name_counts?: array<string, int>
     * }>
     */
    public static function provide_cup_plan_clash_cases(): array {
        return [
            'numeric teams with clash detection' => [
                'players'                    => [
                    101 => [ 'Alice', 'Smith' ],
                    102 => [ 'Bob', 'Jones' ],
                ],
                'order_of_play'              => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_1000, 'matches' => [ 1 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_1000, 'matches' => [ 2 ] ],
                ],
                'match_players'              => [
                    1 => [ 101, 102 ],
                    2 => [ 101, 102 ],
                ],
                'finals_spec'                => null,
                'expect_clashes'             => true,
                'expected_contained_names'   => [ 'Alice Smith', 'Bob Jones' ],
                'expected_exact_name_counts' => [],
            ],
            'placeholder teams with clash detection' => [
                'players'                    => [
                    201 => [ 'Charlie', 'Brown' ],
                ],
                'order_of_play'              => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_1000, 'matches' => [ 10 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_1000, 'matches' => [ 10 ] ],
                ],
                'match_players'              => [
                    10 => [ 201 ],
                ],
                'finals_spec'                => [
                    'id'   => 10,
                    'home' => self::SEMI_FINAL_1_HOME,
                    'away' => '5',
                ],
                'expect_clashes'             => true,
                'expected_contained_names'   => [ 'Charlie Brown' ],
                'expected_exact_name_counts' => [ 'Charlie Brown' => 1 ],
            ],
            'clean rendering when no clashes' => [
                'players'                    => [
                    101 => [ 'Alice', 'Smith' ],
                    102 => [ 'Bob', 'Jones' ],
                ],
                'order_of_play'              => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_1000, 'matches' => [ 1 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_1000, 'matches' => [ 2 ] ],
                ],
                'match_players'              => [
                    1 => [ 101 ],
                    2 => [ 102 ],
                ],
                'finals_spec'                => null,
                'expect_clashes'             => false,
                'expected_contained_names'   => [],
                'expected_exact_name_counts' => [],
            ],
        ];
    }

    public function test_cup_plan_preserves_dispatcher_and_drag_drop_contract_with_fixture_finals(): void {
        $finals = [
            $this->make_fixture( 10, '5', '6', home_club: 'WCC', away_club: 'LTC' ),
            $this->make_fixture( 11, self::SEMI_FINAL_1_HOME, self::SEMI_FINAL_1_AWAY ),
        ];

        $output = $this->render_cup_plan(
            order_of_play: [
                [ 'court' => self::COURT_1, 'start_time' => '10:00', 'matches' => [ 10 ] ],
                [ 'court' => self::COURT_2, 'start_time' => '10:30', 'matches' => [ 11 ] ],
            ],
            courts: 2,
            finals: $finals,
        );

        // Config form (Cup_Plan_Action_Dispatcher::handle_save_cup_config).
        $this->assertStringContainsString( self::FORM_CUP_DETAILS, $output );
        $this->assertStringContainsString( self::ACTION_SAVE_CUP, $output );
        $this->assertStringContainsString( 'name="racketmanager_cup-nonce"', $output );
        $this->assertStringContainsString( 'data-action="racketmanager_cup"', $output );
        $this->assertStringContainsString( 'name="numCourtsAvailable"', $output );
        $this->assertStringContainsString( 'name="timeIncrement"', $output );

        // Planner form (handle_save_plan / handle_reset_plan).
        $this->assertStringContainsString( self::FORM_CUP_PLANNER, $output );
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
        $this->assertStringContainsString( self::CLASS_BTN_SUCCESS_FINALS_MATCH, $output );
        $this->assertStringContainsString( self::CLASS_BTN_WARNING_FINALS_MATCH, $output );
        $this->assertStringContainsString( 'Team 5', $output );
        $this->assertStringContainsString( '(WCC)', $output );
        $this->assertStringContainsString( '(LTC)', $output );
        $this->assertStringContainsString( self::WINNER_PREFIX . self::SEMI_FINAL_1_HOME, $output );
        $this->assertStringContainsString( self::WINNER_PREFIX . self::SEMI_FINAL_1_AWAY, $output );
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

        $output = $this->render_template( self::TEMPLATE_CUP_PLAN, [ 'vm' => $vm ] );

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
            self::TEMPLATE_CUP_PLAN,
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

        $this->assertStringContainsString( self::TAB_CONFIG_SCRIPT, $output );
        $this->assertStringContainsString( self::CLASS_IS_INVALID, $output );
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

        $output = $this->render_template( self::TEMPLATE_CUP_PLAN, [ 'vm' => $vm ] );

        $this->assertStringContainsString( self::FORM_CUP_DETAILS, $output );
        $this->assertStringContainsString( self::ACTION_SAVE_CUP, $output );
        $this->assertStringNotContainsString( self::FORM_CUP_PLANNER, $output );
        $this->assertStringNotContainsString( self::CLASS_FINALS_MATCH, $output );
        $this->assertStringNotContainsString( self::TEXT_POTENTIAL_CLASHES, $output );
    }

    // ------------------------------------------------------------------
    // Tournament planner
    // ------------------------------------------------------------------

    #[DataProvider( 'provide_tournament_plan_clash_cases' )]
    public function test_tournament_plan_renders_clash_detection_scenarios(
        array $players,
        array $order_of_play,
        array $match_players,
        ?array $finals_spec,
        bool $expect_clashes,
        array $expected_contained_names,
    ): void {
        foreach ( $players as $id => $name_parts ) {
            $this->stub_player( $id, $name_parts[0], $name_parts[1] );
        }

        $finals = null;
        if ( null !== $finals_spec ) {
            $finals = [
                $this->make_fixture_details(
                    $finals_spec['id'],
                    $finals_spec['home'],
                    $finals_spec['away'],
                    $finals_spec['prev_home'] ?? null,
                    $finals_spec['prev_away'] ?? null,
                ),
            ];
        }

        $output = $this->render_tournament_plan(
            order_of_play: $order_of_play,
            courts: 2,
            player_ids: fn( $id ) => $match_players[ (int) $id ] ?? [],
            finals: $finals,
        );

        $this->assert_clash_report( $output, $expect_clashes, $expected_contained_names );
    }

    /**
     * @return array<string, array{
     *     players: array<int, array{string, string}>,
     *     order_of_play: array<int, array<string, mixed>>,
     *     match_players: array<int, list<int>>,
     *     finals_spec: array{id: int, home: string, away: string, prev_home?: string, prev_away?: string}|null,
     *     expect_clashes: bool,
     *     expected_contained_names: list<string>
     * }>
     */
    public static function provide_tournament_plan_clash_cases(): array {
        return [
            'numeric fixtures with clash detection' => [
                'players'                  => [
                    301 => [ 'David', 'Miller' ],
                ],
                'order_of_play'            => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_0900, 'fixtures' => [ 1 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_0900, 'fixtures' => [ 2 ] ],
                ],
                'match_players'            => [
                    1 => [ 301 ],
                    2 => [ 301 ],
                ],
                'finals_spec'              => null,
                'expect_clashes'           => true,
                'expected_contained_names' => [ 'David Miller' ],
            ],
            'placeholder fixtures with clash detection' => [
                'players'                  => [
                    401 => [ 'Eva', 'Green' ],
                ],
                'order_of_play'            => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_0900, 'fixtures' => [ 20 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_0900, 'fixtures' => [ 20 ] ],
                ],
                'match_players'            => [
                    20 => [ 401 ],
                ],
                'finals_spec'              => [
                    'id'        => 20,
                    'home'      => self::SEMI_FINAL_1_HOME,
                    'away'      => self::SEMI_FINAL_1_AWAY,
                    'prev_home' => self::WINNER_SEMI_FINAL_1,
                    'prev_away' => self::LOSER_SEMI_FINAL_1,
                ],
                'expect_clashes'           => true,
                'expected_contained_names' => [ 'Eva Green' ],
            ],
            'clean rendering when no clashes' => [
                'players'                  => [
                    501 => [ 'Frank', 'Sinatra' ],
                    502 => [ 'Grace', 'Kelly' ],
                ],
                'order_of_play'            => [
                    [ 'court' => self::COURT_1, 'start_time' => self::TIME_0900, 'fixtures' => [ 1 ] ],
                    [ 'court' => self::COURT_2, 'start_time' => self::TIME_0900, 'fixtures' => [ 2 ] ],
                ],
                'match_players'            => [
                    1 => [ 501 ],
                    2 => [ 502 ],
                ],
                'finals_spec'              => null,
                'expect_clashes'           => false,
                'expected_contained_names' => [],
            ],
        ];
    }

    public function test_tournament_plan_preserves_controller_and_drag_drop_contract_with_details_dtos(): void {
        $finals = [
            $this->make_fixture_details( 5, '7', '8', home_name: 'Team Seven', away_name: 'Team Eight', home_club: 'WCC', away_club: 'LTC' ),
            $this->make_fixture_details( 6, self::SEMI_FINAL_1_HOME, self::SEMI_FINAL_1_AWAY, self::WINNER_SEMI_FINAL_1, self::LOSER_SEMI_FINAL_1 ),
        ];

        $output = $this->render_tournament_plan(
            order_of_play: [
                [ 'court' => self::COURT_1, 'start_time' => '09:00', 'fixtures' => [ 5 ] ],
                [ 'court' => self::COURT_2, 'start_time' => '09:30', 'fixtures' => [ 6 ] ],
            ],
            courts: 2,
            finals: $finals,
        );

        // Config form (Tournament_Plan_Admin_Controller::handle_save_tournament_finals_config).
        $this->assertStringContainsString( self::FORM_TOURNAMENT_DETAILS, $output );
        $this->assertStringContainsString( 'data-action="racketmanager_tournament-finals-config"', $output );
        $this->assertStringContainsString( 'name="saveTournamentFinalsConfig"', $output );
        $this->assertStringContainsString( 'name="tournamentId" value="5"', $output );

        // Planner form (handle_save_tournament_plan / handle_reset_tournament_plan).
        $this->assertStringContainsString( self::FORM_TOURNAMENT_PLANNER, $output );
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
        $this->assertStringContainsString( self::CLASS_BTN_SUCCESS_FINALS_MATCH, $output );
        $this->assertStringContainsString( self::CLASS_BTN_WARNING_FINALS_MATCH, $output );
        $this->assertStringContainsString( 'Team Seven', $output );
        $this->assertStringContainsString( 'Team Eight', $output );
        $this->assertStringContainsString( '(WCC)', $output );
        $this->assertStringContainsString( '(LTC)', $output );
        $this->assertStringContainsString( self::WINNER_SEMI_FINAL_1, $output );
        $this->assertStringContainsString( self::LOSER_SEMI_FINAL_1, $output );
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

        $output = $this->render_template( self::TEMPLATE_TOURNAMENT_PLAN, [ 'vm' => $vm ] );

        $this->assertStringContainsString( self::TAB_CONFIG_SCRIPT, $output );
        $this->assertStringContainsString( self::CLASS_IS_INVALID, $output );
        $this->assertStringContainsString( 'Tournament start time is required.', $output );
        $this->assertStringContainsString( 'name="timeIncrement" id="timeIncrement" value="00:45:00"', $output );
        $this->assertStringContainsString( 'name="numCourtsAvailable" id="numCourtsAvailable" value="6"', $output );
    }

    public function test_tournament_plan_without_courts_renders_no_schedule_form(): void {
        $vm = $this->tournament_vm( order_of_play: [], courts: 0, finals: [] );

        $this->assertSame( 0, $vm->max_schedules );
        $this->assertSame( [], $vm->schedule_slots );

        $output = $this->render_template( self::TEMPLATE_TOURNAMENT_PLAN, [ 'vm' => $vm ] );

        $this->assertStringContainsString( self::FORM_TOURNAMENT_DETAILS, $output );
        $this->assertStringNotContainsString( self::FORM_TOURNAMENT_PLANNER, $output );
        $this->assertStringNotContainsString( self::CLASS_FINALS_MATCH, $output );
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
            self::TEMPLATE_SHOW_SCHEDULE,
            [ 'vm' => $vm ]
        );

        $this->assertStringContainsString( self::SELECTOR_SCHEDULE_FILTER, $output );
        $this->assertStringContainsString( 'name="actionSchedule"', $output );
        $this->assertStringContainsString( 'Men Division 1', $output );
        $this->assertStringContainsString( 'Men Division 2', $output );
        $this->assertStringContainsString( 'data-event-id="1"', $output );
        $this->assertStringNotContainsString( 'data-event-id="2"', $output );
        $this->assertStringContainsString( 'name="event[1]"', $output );
        $this->assertStringContainsString( 'name="event[2]"', $output );
        $this->assertStringContainsString( '<input type="hidden" name="season" value="2026" />', $output );
        $this->assertStringNotContainsString( 'id="schedule-report"', $output );
    }

    public function test_league_schedule_renders_scheduling_report_with_escaped_output(): void {
        $vm = new Admin_League_Schedule_Page_View_Model(
            competition: $this->make_competition(),
            season: '2026',
            competition_id: 10,
            events: [],
            schedule_report: [
                'retry'             => 42,
                'scheduled'         => 0,
                'conflicts'         => [
                    [ 'week' => 3, 'division' => 'Division <1>', 'court' => '5_Mon 18:00_C0', 'home' => 'Club A & Co', 'with' => 'Club A Ladies' ],
                ],
                'alerts'            => [
                    [ 'type' => 'equity_compromise', 'team_name' => 'Club B', 'division' => 'Division 2', 'count' => 6, 'ideal' => 5 ],
                ],
                'resource_pairings' => [
                    '5_Mon 18:00_C0' => [
                        [ 'id' => 1, 'name' => 'Club A & Co', 'division' => 'Division <1>' ],
                        [ 'id' => 2, 'name' => 'Club A Ladies', 'division' => 'Ladies 1' ],
                    ],
                ],
            ],
        );

        $GLOBALS['wp_stubs_esc_html_callback'] = static fn( $text ): string => htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        try {
            $output = $this->render_template( self::TEMPLATE_SHOW_SCHEDULE, [ 'vm' => $vm ] );
        } finally {
            unset( $GLOBALS['wp_stubs_esc_html_callback'] );
        }

        $this->assertStringContainsString( 'id="schedule-report"', $output );
        $this->assertStringContainsString( '<details open>', $output );
        $this->assertStringContainsString( 'Scheduling report - 42 attempts, 0 fixtures created', $output );
        $this->assertStringContainsString( 'Court conflicts', $output );
        $this->assertStringContainsString( '<td>3</td>', $output );
        $this->assertStringContainsString( 'Club A &amp; Co', $output );
        $this->assertStringContainsString( 'Division &lt;1&gt;', $output );
        $this->assertStringNotContainsString( 'Division <1>', $output );
        $this->assertStringContainsString( 'Club A Ladies', $output );
        $this->assertStringContainsString( '5_Mon 18:00_C0', $output );
        $this->assertStringContainsString( 'Home and away equity', $output );
        $this->assertStringContainsString( 'Club B (Division 2) has 6 home matches (ideal 5)', $output );
        $this->assertStringContainsString( 'Court sharing', $output );
        $this->assertStringContainsString( '<span class="text-muted">(Ladies 1)</span>', $output );
    }

    public function test_league_schedule_report_without_conflicts_is_collapsed(): void {
        $vm = new Admin_League_Schedule_Page_View_Model(
            competition: $this->make_competition(),
            season: '2026',
            competition_id: 10,
            events: [],
            schedule_report: [
                'retry'             => 1,
                'scheduled'         => 30,
                'conflicts'         => [],
                'alerts'            => [],
                'resource_pairings' => [
                    '5_Mon 18:00_C0' => [
                        [ 'id' => 1, 'name' => 'Club A', 'division' => 'Division 1' ],
                    ],
                ],
            ],
        );

        $output = $this->render_template( self::TEMPLATE_SHOW_SCHEDULE, [ 'vm' => $vm ] );

        $this->assertStringContainsString( 'id="schedule-report"', $output );
        $this->assertStringContainsString( '<details >', $output );
        $this->assertStringContainsString( 'Scheduling report - 1 attempts, 30 fixtures created', $output );
        $this->assertStringNotContainsString( 'Court conflicts', $output );
        $this->assertStringNotContainsString( 'Home and away equity', $output );
        $this->assertStringContainsString( 'Court sharing', $output );
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
            self::TEMPLATE_SHOW_SCHEDULE,
            [ 'vm' => $league_vm ]
        );
        $this->assertStringContainsString( self::SELECTOR_SCHEDULE_FILTER, $league_output );
        $this->assertStringNotContainsString( '<button class="btn btn-secondary sendFixtures"', $league_output );

        // 2. Order of play court with empty time slots
        $court_times = [
            [],
        ];
        $is_expanded = false;

        $oop_output = $this->render_template(
            self::TEMPLATE_ORDER_OF_PLAY_COURT,
            [ 'court_times' => $court_times, 'is_expanded' => $is_expanded ]
        );
        $this->assertStringContainsString( 'match-group__item-wrapper', $oop_output );
        $this->assertStringNotContainsString( 'match__header-title', $oop_output );
    }

    public function test_order_of_play_court_renders_handler_match_view_model_with_placeholder(): void {
        $match_vm = new Order_Of_Play_Match_View_Model(
            id: 99,
            league_title: 'Championship Semi Final',
            home_match_title: self::SEMI_FINAL_1_HOME,
            away_match_title: 'Westcliff 1',
            is_home_pending: true,
            is_away_pending: false,
        );

        $court_times = [
            [ $match_vm ],
        ];
        $is_expanded = false;

        $output = $this->render_template(
            self::TEMPLATE_ORDER_OF_PLAY_COURT,
            [ 'court_times' => $court_times, 'is_expanded' => $is_expanded ]
        );

        $this->assertStringContainsString( 'Championship Semi Final', $output );
        $this->assertStringContainsString( self::SEMI_FINAL_1_HOME, $output );
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
        $competition->id   = 55;
        $competition->name = 'Premier Leagues';

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition' )
            ->with( 55 )
            ->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_events_for_competition' )
            ->with( 55 )
            ->willReturn( [ $event1, $event2 ] );
        $comp_service->expects( $this->atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 55 )
            ->willReturn( $competition );

        $event_repo = $this->createStub( Event_Repository_Interface::class );
        $event_repo->method( 'count_by_event_season' )->willReturnCallback(
            static function ( int $event_id, string $season, bool $completed_only ): int {
                if ( 101 === $event_id && '2026' === $season ) {
                    return $completed_only ? 0 : 10;
                }
                if ( 102 === $event_id && '2026' === $season ) {
                    return 5;
                }

                return 0;
            }
        );

        $output = $this->execute_admin_league_schedule_page( $comp_service, $event_repo );

        $this->assertStringContainsString( 'Premier League', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'data-event-id="101"', $output );
        $this->assertStringNotContainsString( 'data-event-id="102"', $output );
        $this->assertStringContainsString( 'event_id=101', $output );
        $this->assertStringContainsString( 'event_id=102', $output );
        $this->assertStringNotContainsString( 'id="schedule-report"', $output );
    }

    public function test_admin_league_schedule_counts_follow_viewed_season_and_renders_flash_report(): void {
        $event1 = (object) [
            'id'     => 101,
            'name'   => 'Premier League',
            'season' => '2026',
        ];

        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 55 );
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->id   = 55;
        $competition->name = 'Premier Leagues';

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_competition' )->willReturn( $competition );
        $comp_service->method( 'get_events_for_competition' )->willReturn( [ $event1 ] );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $seasons_counted = [];
        $event_repo      = $this->createStub( Event_Repository_Interface::class );
        $event_repo->method( 'count_by_event_season' )->willReturnCallback(
            static function ( int $event_id, string $season, bool $completed_only ) use ( &$seasons_counted ): int {
                $seasons_counted[] = $season;
                if ( '2025' === $season ) {
                    return $completed_only ? 0 : 8;
                }

                return 0;
            }
        );

        $flash_store = $this->createStub( \Racketmanager\Admin\Flash\Admin_Flash_Message_Store::class );
        $flash_store->method( 'pop' )->willReturn(
            [
                'message'      => 'Scheduling failed after 9 attempts',
                'message_type' => true,
                'flash_data'   => [
                    'retry'             => 9,
                    'scheduled'         => 0,
                    'conflicts'         => [
                        [ 'week' => 1, 'division' => 'Premier League', 'court' => '7_Tue 19:00_C0', 'home' => 'Club X', 'with' => 'Club X 2' ],
                    ],
                    'alerts'            => [],
                    'resource_pairings' => [],
                ],
            ]
        );

        $output = $this->execute_admin_league_schedule_page( $comp_service, $event_repo, season: '2025', flash_store: $flash_store );

        $this->assertSame( [ '2025', '2025' ], $seasons_counted, 'Match counts must use the viewed season, not the event season.' );
        $this->assertStringContainsString( 'data-event-id="101"', $output );
        $this->assertStringContainsString( 'season=2025', $output );
        $this->assertStringContainsString( '<input type="hidden" name="season" value="2025" />', $output );
        $this->assertStringContainsString( 'id="schedule-report"', $output );
        $this->assertStringContainsString( 'Scheduling report - 9 attempts, 0 fixtures created', $output );
        $this->assertStringContainsString( 'Club X 2', $output );
        $this->assertStringContainsString( '7_Tue 19:00_C0', $output );
    }

    public function test_admin_league_schedule_delivery_with_no_events(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 55 );
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->id   = 55;
        $competition->name = 'Empty Competition';

        $comp_service = $this->createMock( Competition_Service::class );
        $comp_service->expects( $this->once() )
            ->method( 'get_competition' )
            ->with( 55 )
            ->willReturn( $competition );
        $comp_service->expects( $this->once() )
            ->method( 'get_events_for_competition' )
            ->with( 55 )
            ->willReturn( [] );
        $comp_service->expects( $this->atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 55 )
            ->willReturn( $competition );

        $event_repo = $this->createStub( Event_Repository_Interface::class );

        $output = $this->execute_admin_league_schedule_page( $comp_service, $event_repo );

        $this->assertStringContainsString( 'Empty Competition', $output );
        $this->assertStringNotContainsString( 'data-event-id=', $output );
    }

    private function execute_admin_league_schedule_page(
        Competition_Service $comp_service,
        Event_Repository_Interface $event_repo,
        string $competition_id = '55',
        ?string $season = null,
        ?\Racketmanager\Admin\Flash\Admin_Flash_Message_Store $flash_store = null,
    ): string {
        $container = $this->create_admin_delivery_container(
            services: [
                'competition_service' => $comp_service,
                'event_repository'    => $event_repo,
            ],
            flash_store: $flash_store,
        );

        $racketmanager = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $admin_league = new Admin_League( $racketmanager );

        $GLOBALS['wp_stubs_current_user_can'] = true;
        $_GET['competition_id']               = $competition_id;
        unset( $_GET['season'], $_POST['scheduleAction'] );
        if ( null !== $season ) {
            $_GET['season'] = $season;
        }

        ob_start();
        try {
            $admin_league->display_schedule_page();
            return (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }
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
            ->willReturnCallback( static fn( string $team_ref ): string => self::WINNER_PREFIX . $team_ref );

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
        $club = null !== $club_shortcode ? Club::from_object( (object) [ 'name' => 'Club ' . $club_shortcode, 'shortcode' => $club_shortcode ] ) : null;

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
            self::TEMPLATE_CUP_PLAN,
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
            self::TEMPLATE_TOURNAMENT_PLAN,
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
