<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;

final class Fixture_Presenter_Render_Vm_Scope_Test extends TestCase {
    use Creates_Team_Match_Row_Presenter;

    private Fixture_Presenter $presenter;

    private ?string $probe_name = null;

    private ?string $probe_path = null;

    /**
     * Tracked temporary directories for cleanup.
     *
     * @var list<string>
     */
    private array $temp_dirs = [];

    protected function setUp(): void {
        parent::setUp();
        $this->temp_dirs = [];
        $link_service    = $this->createStub( Fixture_Link_Service::class );
        $option_gateway  = $this->createStub( Option_Gateway_Interface::class );
        $this->presenter = new Fixture_Presenter(
            $link_service,
            $option_gateway,
            new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ),
            team_match_row_presenter: $this->create_team_match_row_presenter()
        );
        $this->probe_name = 'match/fragments/zz-fixture-scope-probe-' . uniqid();
        $this->probe_path = RACKETMANAGER_PATH . 'templates/' . $this->probe_name . '.php';
        file_put_contents(
            $this->probe_path,
            '<?php $GLOBALS["rm_fixture_scope_probe"] = array( "vm" => $vm ?? null, "arbitrary_arg_set" => isset( $arbitrary_arg ) );'
        );
    }

    protected function tearDown(): void {
        if ( null !== $this->probe_path && file_exists( $this->probe_path ) ) {
            unlink( $this->probe_path );
        }
        foreach ( $this->temp_dirs as $dir ) {
            $this->delete_directory( $dir );
        }
        $this->temp_dirs = [];
        unset( $GLOBALS['rm_fixture_scope_probe'], $GLOBALS['wp_stubs_stylesheet_directory'], $GLOBALS['wp_stubs_template_directory'] );
        parent::tearDown();
    }

    public function test_render_detail_outputs_model_content_from_real_fragment(): void {
        $model = new Fixture_Detail_Read_Model(
            rubbers: [],
            approvals: [],
            general_comments: null,
            is_standalone: true,
            show_print_button: true,
            show_edit_button: true,
            location: [
                'club_name'            => 'Westcliff Tennis Club',
                'address'              => '1 Court Road',
                'match_secretary_name' => 'Pat Secretary',
                'match_secretary_contactno' => '0123456789',
                'match_secretary_email'     => 'pat@example.com',
                'website'              => 'https://westcliff.example.com',
            ],
            match_id: 321,
            edit_url: 'https://example.com/match/321/edit',
        );

        $html = $this->presenter->render_detail( $model );

        $this->assertStringContainsString( 'data-match-id="321"', $html );
        $this->assertStringContainsString( 'https://example.com/match/321/edit', $html );
        $this->assertStringContainsString( 'Westcliff Tennis Club', $html );
        $this->assertStringContainsString( '1 Court Road', $html );
        $this->assertStringContainsString( 'Pat Secretary', $html );
    }

    public function test_render_detail_uses_result_form_fragment_when_editing(): void {
        $model = new Fixture_Detail_Read_Model(
            rubbers: [],
            approvals: [],
            general_comments: 'Edited comment',
            match_id: 654,
            edit_url: 'https://example.com/match/654',
            is_editing: true,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        $this->assertStringContainsString( 'id="form-match-654"', $html );
        $this->assertStringContainsString( 'name="current_match_id" id="current_match_id" value="654"', $html );
        $this->assertStringContainsString( 'Edited comment', $html );
        $this->assertStringContainsString( 'https://example.com/match/654', $html );
    }

    public function test_render_result_form_for_mixed_match_provides_male_and_female_dropdowns(): void {
        $club_players = $this->build_test_club_players();
        $rubber       = $this->build_test_rubber(
            number: 1,
            type: 'XD',
            title: 'XD1',
            home_players: [],
            away_players: []
        );

        $model = new Fixture_Detail_Read_Model(
            rubbers: [ $rubber ],
            approvals: [],
            general_comments: null,
            match_id: 100,
            edit_url: 'https://example.com/match/100/edit',
            is_editing: true,
            club_players: $club_players,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        // Home Team Slot 1: Male players only
        $home_s1_options = $this->extract_select_options( $html, 'players_1_home_1' );
        $this->assertNotEmpty( $home_s1_options );
        $home_s1_values = array_column( $home_s1_options, 'value' );
        $this->assertSame( [ '0', '101', '102' ], $home_s1_values );
        $this->assertContains( 'John Home (M) - BTM101', array_column( $home_s1_options, 'text' ) );
        $this->assertContains( 'Dave Home (M)', array_column( $home_s1_options, 'text' ) );
        $this->assertNotContains( '103', $home_s1_values );
        $this->assertNotContains( '104', $home_s1_values );

        // Home Team Slot 2: Female players only
        $home_s2_options = $this->extract_select_options( $html, 'players_1_home_2' );
        $this->assertNotEmpty( $home_s2_options );
        $home_s2_values = array_column( $home_s2_options, 'value' );
        $this->assertSame( [ '0', '103', '104' ], $home_s2_values );
        $this->assertContains( 'Mary Home (F) - BTM103', array_column( $home_s2_options, 'text' ) );
        $this->assertContains( 'Sarah Home (F)', array_column( $home_s2_options, 'text' ) );
        $this->assertNotContains( '101', $home_s2_values );
        $this->assertNotContains( '102', $home_s2_values );

        // Away Team Slot 1: Male players only
        $away_s1_options = $this->extract_select_options( $html, 'players_1_away_1' );
        $this->assertNotEmpty( $away_s1_options );
        $away_s1_values = array_column( $away_s1_options, 'value' );
        $this->assertSame( [ '0', '201', '202' ], $away_s1_values );
        $this->assertContains( 'Bob Away (M) - BTM201', array_column( $away_s1_options, 'text' ) );
        $this->assertContains( 'Tom Away (M)', array_column( $away_s1_options, 'text' ) );
        $this->assertNotContains( '203', $away_s1_values );
        $this->assertNotContains( '204', $away_s1_values );

        // Away Team Slot 2: Female players only
        $away_s2_options = $this->extract_select_options( $html, 'players_1_away_2' );
        $this->assertNotEmpty( $away_s2_options );
        $away_s2_values = array_column( $away_s2_options, 'value' );
        $this->assertSame( [ '0', '203', '204' ], $away_s2_values );
        $this->assertContains( 'Alice Away (F) - BTM203', array_column( $away_s2_options, 'text' ) );
        $this->assertContains( 'Emma Away (F)', array_column( $away_s2_options, 'text' ) );
        $this->assertNotContains( '201', $away_s2_values );
        $this->assertNotContains( '202', $away_s2_values );
    }

    public function test_render_result_form_preserves_selected_players_in_mixed_match(): void {
        $club_players = $this->build_test_club_players();
        $rubber       = $this->build_test_rubber(
            number: 1,
            type: 'XD',
            title: 'XD1',
            home_players: [
                [ 'id' => 101, 'slot' => 1, 'gender' => 'm' ],
                [ 'id' => 104, 'slot' => 2, 'gender' => 'f' ],
            ],
            away_players: [
                [ 'id' => 202, 'slot' => 1, 'gender' => 'm' ],
                [ 'id' => 203, 'slot' => 2, 'gender' => 'f' ],
            ]
        );

        $model = new Fixture_Detail_Read_Model(
            rubbers: [ $rubber ],
            approvals: [],
            general_comments: null,
            match_id: 101,
            edit_url: 'https://example.com/match/101/edit',
            is_editing: true,
            club_players: $club_players,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        // Home Slot 1: Player 101 selected
        $home_s1 = $this->extract_select_options( $html, 'players_1_home_1' );
        $this->assertTrue( $this->is_option_selected( $home_s1, '101' ) );
        $this->assertFalse( $this->is_option_selected( $home_s1, '0' ) );
        $this->assertFalse( $this->is_option_selected( $home_s1, '102' ) );

        // Home Slot 2: Player 104 selected
        $home_s2 = $this->extract_select_options( $html, 'players_1_home_2' );
        $this->assertTrue( $this->is_option_selected( $home_s2, '104' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '0' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '103' ) );

        // Away Slot 1: Player 202 selected
        $away_s1 = $this->extract_select_options( $html, 'players_1_away_1' );
        $this->assertTrue( $this->is_option_selected( $away_s1, '202' ) );
        $this->assertFalse( $this->is_option_selected( $away_s1, '0' ) );
        $this->assertFalse( $this->is_option_selected( $away_s1, '201' ) );

        // Away Slot 2: Player 203 selected
        $away_s2 = $this->extract_select_options( $html, 'players_1_away_2' );
        $this->assertTrue( $this->is_option_selected( $away_s2, '203' ) );
        $this->assertFalse( $this->is_option_selected( $away_s2, '0' ) );
        $this->assertFalse( $this->is_option_selected( $away_s2, '204' ) );
    }

    public function test_render_result_form_pads_sparse_doubles_players_and_preserves_slots(): void {
        $club_players = $this->build_test_club_players();

        // Home has only slot 1 saved (1-based key); Away has only slot 2 saved (sparse key 2 with slot 2)
        $rubber = $this->build_test_rubber(
            number: 1,
            type: 'XD',
            title: 'XD1',
            home_players: [
                1 => [ 'id' => 102, 'slot' => 1, 'gender' => 'm' ],
            ],
            away_players: [
                2 => [ 'id' => 204, 'slot' => 2, 'gender' => 'f' ],
            ]
        );

        $model = new Fixture_Detail_Read_Model(
            rubbers: [ $rubber ],
            approvals: [],
            general_comments: null,
            match_id: 102,
            edit_url: 'https://example.com/match/102/edit',
            is_editing: true,
            club_players: $club_players,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        // Home Slot 1: Male options, player 102 selected
        $home_s1 = $this->extract_select_options( $html, 'players_1_home_1' );
        $this->assertSame( [ '0', '101', '102' ], array_column( $home_s1, 'value' ) );
        $this->assertTrue( $this->is_option_selected( $home_s1, '102' ) );

        // Home Slot 2: Padded with ID 0, female options, no player selected
        $home_s2 = $this->extract_select_options( $html, 'players_1_home_2' );
        $this->assertSame( [ '0', '103', '104' ], array_column( $home_s2, 'value' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '103' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '104' ) );

        // Away Slot 1: Padded with ID 0, male options, no player selected
        $away_s1 = $this->extract_select_options( $html, 'players_1_away_1' );
        $this->assertSame( [ '0', '201', '202' ], array_column( $away_s1, 'value' ) );
        $this->assertFalse( $this->is_option_selected( $away_s1, '201' ) );
        $this->assertFalse( $this->is_option_selected( $away_s1, '202' ) );

        // Away Slot 2: Female options, player 204 selected
        $away_s2 = $this->extract_select_options( $html, 'players_1_away_2' );
        $this->assertSame( [ '0', '203', '204' ], array_column( $away_s2, 'value' ) );
        $this->assertTrue( $this->is_option_selected( $away_s2, '204' ) );
        $this->assertFalse( $this->is_option_selected( $away_s2, '203' ) );
    }

    public function test_render_result_form_pads_legacy_zero_indexed_single_player(): void {
        $club_players = $this->build_test_club_players();

        // Legacy 0-indexed array with single player (e.g. only player 1 entered)
        $rubber = $this->build_test_rubber(
            number: 1,
            type: 'XD',
            title: 'XD1',
            home_players: [
                0 => [ 'id' => 101, 'gender' => 'm' ],
            ],
            away_players: []
        );

        $model = new Fixture_Detail_Read_Model(
            rubbers: [ $rubber ],
            approvals: [],
            general_comments: null,
            match_id: 103,
            edit_url: 'https://example.com/match/103/edit',
            is_editing: true,
            club_players: $club_players,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        // Slot 1 has player 101 selected
        $home_s1 = $this->extract_select_options( $html, 'players_1_home_1' );
        $this->assertTrue( $this->is_option_selected( $home_s1, '101' ) );
        $this->assertFalse( $this->is_option_selected( $home_s1, '102' ) );

        // Slot 2 is padded with female options and no player selected
        $home_s2 = $this->extract_select_options( $html, 'players_1_home_2' );
        $this->assertSame( [ '0', '103', '104' ], array_column( $home_s2, 'value' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '103' ) );
        $this->assertFalse( $this->is_option_selected( $home_s2, '104' ) );
    }

    public function test_render_result_form_non_mixed_and_singles_rubbers_gender_dropdowns(): void {
        $club_players = $this->build_test_club_players();

        $rubbers = [
            $this->build_test_rubber( 1, 'WD', 'WD1' ),
            $this->build_test_rubber( 2, 'MD', 'MD1' ),
            $this->build_test_rubber( 3, 'WS', 'WS1' ),
            $this->build_test_rubber( 4, 'MS', 'MS1' ),
        ];

        $model = new Fixture_Detail_Read_Model(
            rubbers: $rubbers,
            approvals: [],
            general_comments: null,
            match_id: 104,
            edit_url: 'https://example.com/match/104/edit',
            is_editing: true,
            club_players: $club_players,
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        // Rubber 1 (WD): Both slots must have female options
        $wd_s1 = $this->extract_select_options( $html, 'players_1_home_1' );
        $wd_s2 = $this->extract_select_options( $html, 'players_1_home_2' );
        $this->assertSame( [ '0', '103', '104' ], array_column( $wd_s1, 'value' ) );
        $this->assertSame( [ '0', '103', '104' ], array_column( $wd_s2, 'value' ) );

        // Rubber 2 (MD): Both slots must have male options
        $md_s1 = $this->extract_select_options( $html, 'players_2_home_1' );
        $md_s2 = $this->extract_select_options( $html, 'players_2_home_2' );
        $this->assertSame( [ '0', '101', '102' ], array_column( $md_s1, 'value' ) );
        $this->assertSame( [ '0', '101', '102' ], array_column( $md_s2, 'value' ) );

        // Rubber 3 (WS): Slot 1 female options, Slot 2 must not exist
        $ws_s1 = $this->extract_select_options( $html, 'players_3_home_1' );
        $ws_s2 = $this->extract_select_options( $html, 'players_3_home_2' );
        $this->assertSame( [ '0', '103', '104' ], array_column( $ws_s1, 'value' ) );
        $this->assertEmpty( $ws_s2 );

        // Rubber 4 (MS): Slot 1 male options, Slot 2 must not exist
        $ms_s1 = $this->extract_select_options( $html, 'players_4_home_1' );
        $ms_s2 = $this->extract_select_options( $html, 'players_4_home_2' );
        $this->assertSame( [ '0', '101', '102' ], array_column( $ms_s1, 'value' ) );
        $this->assertEmpty( $ms_s2 );
    }

    public function test_render_result_form_uses_gender_options_per_rubber_in_league_fixture(): void {
        $rubbers = [
            $this->build_test_rubber( 1, 'WD', 'WD1' ),
            $this->build_test_rubber( 2, 'MD', 'MD1' ),
            $this->build_test_rubber( 3, 'XD', 'XD1' ),
        ];

        $model = new Fixture_Detail_Read_Model(
            rubbers: $rubbers,
            approvals: [],
            general_comments: null,
            match_id: 105,
            edit_url: 'https://example.com/match/105/edit',
            is_editing: true,
            club_players: $this->build_test_club_players(),
            match_status: 'pending',
        );

        $html = $this->presenter->render_detail( $model );

        $expected_options = [
            'home' => [
                'm' => [ '0', '101', '102' ],
                'f' => [ '0', '103', '104' ],
            ],
            'away' => [
                'm' => [ '0', '201', '202' ],
                'f' => [ '0', '203', '204' ],
            ],
        ];
        $rubber_slot_genders = [
            1 => [ 1 => 'f', 2 => 'f' ],
            2 => [ 1 => 'm', 2 => 'm' ],
            3 => [ 1 => 'm', 2 => 'f' ],
        ];

        foreach ( $rubber_slot_genders as $rubber_number => $slots ) {
            foreach ( [ 'home', 'away' ] as $opponent ) {
                foreach ( $slots as $slot => $gender ) {
                    $select_id = "players_{$rubber_number}_{$opponent}_{$slot}";
                    $options   = $this->extract_select_options( $html, $select_id );

                    $this->assertSame(
                        $expected_options[ $opponent ][ $gender ],
                        array_column( $options, 'value' ),
                        "Unexpected options for rubber {$rubber_number}, {$opponent}, slot {$slot}."
                    );
                }
            }
        }
    }

    public function test_render_header_outputs_model_content(): void {
        $header_model = new Fixture_Header_Read_Model(
            id: 88,
            event_name: 'Summer League',
            event_url: 'https://example.com/event',
            league_name: 'Premier Division',
            league_url: 'https://example.com/league',
            season: '2026',
            round_name: null,
            match_day: 1,
            leg: null,
            fixture_date: '2026-06-01',
            formatted_date: '01/06/2026',
            original_date_formatted: null,
            home_team_name: 'LTC Hawks',
            home_team_url: 'https://example.com/hawks',
            home_team_withdrawn: false,
            away_team_name: 'WTC Eagles',
            away_team_url: 'https://example.com/eagles',
            away_team_withdrawn: false,
            is_pending: false,
            score_class: '',
            home_points: '0',
            away_points: '0',
            match_time: '',
            status_message: null,
            status_description: null,
            show_menu: false,
            allow_amend_score: false,
            allow_schedule_match: false,
            allow_switch_match: false,
            allow_reset_match_result: false,
            amend_score_label: 'Amend',
            match_link: 'https://example.com/match/88',
            rubbers_score: '0 - 0',
            sets_score: '0 - 0',
            games_score: '0 - 0',
            edit_mode: false,
            user_can_update: false,
            match_approval_mode: false,
            is_shortcode: false,
            show_home_away_labels: true,
            start_time_display: '14:00',
            pencil_icon_url: '',
            plus_icon_url: ''
        );

        $html = $this->presenter->render_header( $header_model );

        $this->assertStringContainsString( 'LTC Hawks', $html );
        $this->assertStringContainsString( 'WTC Eagles', $html );
        $this->assertStringContainsString( 'Premier Division', $html );
    }

    public function test_render_header_fragment_html_with_extra_arg_renders_model(): void {
        $reflection = new \ReflectionMethod( $this->presenter, 'render' );
        $header_model = new Fixture_Header_Read_Model(
            id: 99,
            event_name: 'Scoped Event',
            event_url: 'https://example.com/event',
            league_name: 'Scoped League',
            league_url: 'https://example.com/league',
            season: '2026',
            round_name: null,
            match_day: 1,
            leg: null,
            fixture_date: '2026-06-01',
            formatted_date: '01/06/2026',
            original_date_formatted: null,
            home_team_name: 'Team A',
            home_team_url: 'https://example.com/a',
            home_team_withdrawn: false,
            away_team_name: 'Team B',
            away_team_url: 'https://example.com/b',
            away_team_withdrawn: false,
            is_pending: false,
            score_class: '',
            home_points: '0',
            away_points: '0',
            match_time: '',
            status_message: null,
            status_description: null,
            show_menu: false,
            allow_amend_score: false,
            allow_schedule_match: false,
            allow_switch_match: false,
            allow_reset_match_result: false,
            amend_score_label: 'Amend',
            match_link: 'https://example.com/match/99',
            rubbers_score: '0 - 0',
            sets_score: '0 - 0',
            games_score: '0 - 0',
            edit_mode: false,
            user_can_update: false,
            match_approval_mode: false,
            is_shortcode: false,
            show_home_away_labels: true,
            start_time_display: '14:00',
            pencil_icon_url: '',
            plus_icon_url: ''
        );

        $html = $reflection->invoke(
            $this->presenter,
            'match/fragments/header',
            [ 'model' => $header_model, 'arbitrary_arg' => 'should_not_extract' ]
        );

        $this->assertStringContainsString( 'Team A', $html );
        $this->assertStringContainsString( 'Team B', $html );
        $this->assertStringContainsString( 'Scoped Event', $html );
    }

    public function test_render_binds_model_as_vm_and_does_not_extract_extra_args(): void {
        $model = new \stdClass();

        $probe = $this->render_probe( $model, [ 'arbitrary_arg' => 'should_not_extract' ] );

        $this->assertSame( $model, $probe['vm'] );
        $this->assertFalse( $probe['arbitrary_arg_set'] );
    }

    public function test_render_binds_explicit_vm_object_and_does_not_extract_extra_args(): void {
        $model = new \stdClass();
        $vm    = new \stdClass();

        $probe = $this->render_probe( $model, [ 'vm' => $vm, 'arbitrary_arg' => 'should_not_extract' ] );

        $this->assertSame( $vm, $probe['vm'] );
        $this->assertFalse( $probe['arbitrary_arg_set'] );
    }

    public function test_render_returns_empty_string_when_template_does_not_exist(): void {
        $reflection = new \ReflectionMethod( $this->presenter, 'render' );
        $html       = $reflection->invoke( $this->presenter, 'match/fragments/non_existent_template', [] );

        $this->assertSame( '', $html );
    }

    public function test_render_honors_child_theme_override(): void {
        $child_dir = sys_get_temp_dir() . '/rm_test_child_' . uniqid();
        $this->temp_dirs[] = $child_dir;
        mkdir( $child_dir . '/racketmanager/match/fragments', 0777, true );
        file_put_contents(
            $child_dir . '/racketmanager/match/fragments/header.php',
            '<div class="child-theme-override">Child: <?php echo esc_html( $vm->event_name ); ?></div>'
        );

        $GLOBALS['wp_stubs_stylesheet_directory'] = $child_dir;
        try {
            $header_model = $this->create_test_header_model( 'Overridden Championship' );
            $html         = $this->presenter->render_header( $header_model );

            $this->assertStringContainsString( '<div class="child-theme-override">Child: Overridden Championship</div>', $html );
        } finally {
            $this->delete_directory( $child_dir );
            unset( $GLOBALS['wp_stubs_stylesheet_directory'] );
            $this->assertDirectoryDoesNotExist( $child_dir );
        }
    }

    public function test_render_honors_parent_theme_override_and_child_precedence(): void {
        $parent_dir = sys_get_temp_dir() . '/rm_test_parent_' . uniqid();
        $this->temp_dirs[] = $parent_dir;
        mkdir( $parent_dir . '/racketmanager/match/fragments', 0777, true );
        file_put_contents(
            $parent_dir . '/racketmanager/match/fragments/header.php',
            '<div class="parent-theme-override">Parent: <?php echo esc_html( $vm->event_name ); ?></div>'
        );

        $child_dir = sys_get_temp_dir() . '/rm_test_child_' . uniqid();
        $this->temp_dirs[] = $child_dir;
        mkdir( $child_dir . '/racketmanager/match/fragments', 0777, true );
        file_put_contents(
            $child_dir . '/racketmanager/match/fragments/header.php',
            '<div class="child-theme-override">Child: <?php echo esc_html( $vm->event_name ); ?></div>'
        );

        $header_model = $this->create_test_header_model( 'Precedence Event' );

        try {
            // When only parent theme has override:
            $GLOBALS['wp_stubs_template_directory']   = $parent_dir;
            $GLOBALS['wp_stubs_stylesheet_directory'] = '';

            $html = $this->presenter->render_header( $header_model );
            $this->assertStringContainsString( '<div class="parent-theme-override">Parent: Precedence Event</div>', $html );

            // When child theme also has override: child takes precedence over parent
            $GLOBALS['wp_stubs_stylesheet_directory'] = $child_dir;

            $html_child = $this->presenter->render_header( $header_model );
            $this->assertStringContainsString( '<div class="child-theme-override">Child: Precedence Event</div>', $html_child );
            $this->assertStringNotContainsString( 'Parent: Precedence Event', $html_child );
        } finally {
            $this->delete_directory( $parent_dir );
            $this->delete_directory( $child_dir );
            unset( $GLOBALS['wp_stubs_template_directory'], $GLOBALS['wp_stubs_stylesheet_directory'] );
            $this->assertDirectoryDoesNotExist( $parent_dir );
            $this->assertDirectoryDoesNotExist( $child_dir );
        }
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param string $dir Directory path.
     */
    private function delete_directory( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }

        $items = scandir( $dir );
        if ( false === $items ) {
            return;
        }

        foreach ( $items as $item ) {
            if ( '.' === $item || '..' === $item ) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if ( is_dir( $path ) ) {
                $this->delete_directory( $path );
            } else {
                unlink( $path );
            }
        }

        rmdir( $dir );
    }

    private function create_test_header_model( string $event_name ): Fixture_Header_Read_Model {
        return new Fixture_Header_Read_Model(
            id: 1,
            event_name: $event_name,
            event_url: 'https://example.com/event',
            league_name: 'Premier',
            league_url: 'https://example.com/league',
            season: '2026',
            round_name: null,
            match_day: 1,
            leg: null,
            fixture_date: '2026-06-01',
            formatted_date: '01/06/2026',
            original_date_formatted: null,
            home_team_name: 'Home',
            home_team_url: 'https://example.com/home',
            home_team_withdrawn: false,
            away_team_name: 'Away',
            away_team_url: 'https://example.com/away',
            away_team_withdrawn: false,
            is_pending: false,
            score_class: '',
            home_points: '0',
            away_points: '0',
            match_time: '',
            status_message: null,
            status_description: null,
            show_menu: false,
            allow_amend_score: false,
            allow_schedule_match: false,
            allow_switch_match: false,
            allow_reset_match_result: false,
            amend_score_label: 'Amend',
            match_link: 'https://example.com/match/1',
            rubbers_score: '0 - 0',
            sets_score: '0 - 0',
            games_score: '0 - 0',
            edit_mode: false,
            user_can_update: false,
            match_approval_mode: false,
            is_shortcode: false,
            show_home_away_labels: true,
            start_time_display: '14:00',
            pencil_icon_url: '',
            plus_icon_url: ''
        );
    }

    private function render_probe( object $model, array $extra ): array {
        $reflection = new \ReflectionMethod( $this->presenter, 'render' );
        $reflection->invoke( $this->presenter, (string) $this->probe_name, array_merge( [ 'model' => $model ], $extra ) );
        $this->assertArrayHasKey( 'rm_fixture_scope_probe', $GLOBALS );

        return $GLOBALS['rm_fixture_scope_probe'];
    }

    private function create_test_player( int $id, string $fullname, ?string $btm = null ): object {
        return new class( $id, $fullname, $btm ) {
            public function __construct(
                private int $id,
                private string $fullname,
                private ?string $btm
            ) {}

            public function get_id(): int {
                return $this->id;
            }

            public function get_fullname(): string {
                return $this->fullname;
            }

            public function get_btm(): ?string {
                return $this->btm;
            }
        };
    }

    private function build_test_club_players(): array {
        return [
            'home' => [
                'm' => [
                    $this->create_test_player( 101, 'John Home (M)', 'BTM101' ),
                    $this->create_test_player( 102, 'Dave Home (M)', null ),
                ],
                'f' => [
                    $this->create_test_player( 103, 'Mary Home (F)', 'BTM103' ),
                    $this->create_test_player( 104, 'Sarah Home (F)', null ),
                ],
            ],
            'away' => [
                'm' => [
                    $this->create_test_player( 201, 'Bob Away (M)', 'BTM201' ),
                    $this->create_test_player( 202, 'Tom Away (M)', null ),
                ],
                'f' => [
                    $this->create_test_player( 203, 'Alice Away (F)', 'BTM203' ),
                    $this->create_test_player( 204, 'Emma Away (F)', null ),
                ],
            ],
        ];
    }

    private function build_test_rubber(
        int $number = 1,
        string $type = 'XD',
        string $title = 'XD1',
        array $home_players = [],
        array $away_players = []
    ): array {
        return [
            'id'         => 100 + $number,
            'number'     => $number,
            'type'       => $type,
            'title'      => $title,
            'status_key' => '',
            'opponents'  => [
                'home' => [
                    'team_name'    => 'Home Team',
                    'team_id'      => 1,
                    'is_winner'    => false,
                    'gender'       => 'm',
                    'message'      => null,
                    'status_class' => '',
                    'status_text'  => '',
                    'players'      => $home_players,
                ],
                'away' => [
                    'team_name'    => 'Away Team',
                    'team_id'      => 2,
                    'is_winner'    => false,
                    'gender'       => 'm',
                    'message'      => null,
                    'status_class' => '',
                    'status_text'  => '',
                    'players'      => $away_players,
                ],
            ],
            'sets'       => [
                1 => [ 'home' => '', 'away' => '' ],
                2 => [ 'home' => '', 'away' => '' ],
                3 => [ 'home' => '', 'away' => '' ],
            ],
        ];
    }

    /**
     * @return list<array{value: string, text: string, selected: bool}>
     */
    private function extract_select_options( string $html, string $select_id ): array {
        $dom     = new \DOMDocument();
        $prev    = libxml_use_internal_errors( true );
        $dom->loadHTML( $html );
        libxml_clear_errors();
        libxml_use_internal_errors( $prev );

        $xpath   = new \DOMXPath( $dom );
        $nodes   = $xpath->query( "//select[@id='{$select_id}']/option" );
        $options = [];
        if ( false !== $nodes ) {
            foreach ( $nodes as $node ) {
                /** @var \DOMElement $node */
                // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                $text = trim( $node->textContent );

                $options[] = [
                    'value'    => $node->getAttribute( 'value' ),
                    'text'     => $text,
                    'selected' => $node->hasAttribute( 'selected' ),
                ];
            }
        }
        return $options;
    }

    /**
     * @param list<array{value: string, text: string, selected: bool}> $options
     */
    private function is_option_selected( array $options, string $value ): bool {
        foreach ( $options as $option ) {
            if ( $option['value'] === $value ) {
                return $option['selected'];
            }
        }
        return false;
    }
}
