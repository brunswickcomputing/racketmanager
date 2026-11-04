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

    public function test_render_header_outputs_model_content(): void {
        $header_model = new Fixture_Header_Read_Model(
            id: 88,
            event_name: 'Summer League',
            event_url: 'https://example.com/event',
            league_title: 'Premier Division',
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
            league_title: 'Scoped League',
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
            league_title: 'Premier',
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
}
