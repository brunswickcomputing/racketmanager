<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Club_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Players_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Roles_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Teams_Page_View_Model;
use Racketmanager\Admin\View_Models\Clubs_Page_View_Model;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Club_Templates_Render_Test extends TestCase {
    use Creates_View_Renderer;
    use Manages_Wordpress_Globals;

    private View_Renderer_Interface $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->renderer = $this->create_view_renderer();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_render_clubs_template_with_rows_and_capture_warnings(): void {
        $club1 = Club_Test_Factory::create( id: 1, name: 'Leigh LTC', shortcode: 'LLTC' );
        $club2 = Club_Test_Factory::create( id: 2, name: 'Kent Elms', shortcode: 'KE' );

        $vm = new Clubs_Page_View_Model( array( $club1, $club2 ) );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/show-clubs', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Clubs', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'Kent Elms', $html );
        self::assertStringContainsString( 'page=racketmanager-clubs&amp;view=club&amp;club_id=1', $html );
        self::assertStringContainsString( 'page=racketmanager-clubs&amp;view=club&amp;club_id=2', $html );
        self::assertStringContainsString( 'doClubDel', $html );
    }

    public function test_render_club_add_template_with_view_model_and_capture_warnings(): void {
        $vm = new Club_Page_View_Model(
            club: null,
            edit: false,
            form_title: 'Add Club',
            form_action: 'Add Club',
            club_id: null
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/club', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Add Club', $html );
        self::assertStringContainsString( '<input type="hidden" name="addClub" value="club" />', $html );
        self::assertStringContainsString( 'name="club"', $html );
        self::assertStringContainsString( 'name="type"', $html );
        self::assertStringContainsString( 'name="shortcode"', $html );
        self::assertStringContainsString( 'name="contactno"', $html );
        self::assertStringContainsString( 'name="website"', $html );
        self::assertStringContainsString( 'name="address"', $html );
        self::assertStringContainsString( 'name="founded"', $html );
        self::assertStringContainsString( 'name="facilities"', $html );
    }

    public function test_render_club_edit_template_with_view_model_and_capture_warnings(): void {
        $club = Club_Test_Factory::create(
            id: 10,
            name: 'Leigh LTC',
            shortcode: 'LLTC',
            website: 'https://example.com',
            contactno: '01234567890',
            founded: 1950,
            facilities: '6 courts',
            address: '123 Tennis Lane'
        );
        $club->match_secretary = (object) array(
            'id'           => 42,
            'display_name' => 'Jane Secretary',
            'contactno'    => '07700900123',
            'email'        => 'sec@example.com',
        );

        $vm = new Club_Page_View_Model(
            club: $club,
            edit: true,
            form_title: 'Edit Club',
            form_action: 'Update Club',
            club_id: 10
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/club', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Edit Club', $html );
        self::assertStringContainsString( '<input type="hidden" name="club_id" id="club_id" value="10" />', $html );
        self::assertStringContainsString( '<input type="hidden" name="editClub" value="club" />', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'LLTC', $html );
        self::assertStringContainsString( 'Jane Secretary', $html );
        self::assertStringContainsString( 'sec@example.com', $html );
        self::assertStringContainsString( '07700900123', $html );
        self::assertStringContainsString( '123 Tennis Lane', $html );
        self::assertStringContainsString( 'https://example.com', $html );
        self::assertStringContainsString( '1950', $html );
        self::assertStringContainsString( '6 courts', $html );
        self::assertStringContainsString( 'Update Club', $html );
    }

    public function test_render_club_players_template_with_rows_and_capture_warnings(): void {
        $club = Club_Test_Factory::create( id: 10, name: 'Leigh LTC', shortcode: 'LLTC' );

        $player = (object) array(
            'registration_id' => 101,
            'user_id'         => 501,
            'display_name'    => 'Roger Federer',
            'btm'             => '12345678',
            'wtn'             => array(
                'singles' => '1.0',
                'doubles' => '1.5',
            ),
        );

        $vm = new Club_Players_Page_View_Model(
            club: $club,
            players: array( $player ),
            active: 'true',
            gender: 'M',
            club_id: 10
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/club/show-club-players', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Players', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'LLTC', $html );
        self::assertStringContainsString( 'Roger Federer', $html );
        self::assertStringContainsString( '12345678', $html );
        self::assertStringContainsString( 'value="101"', $html );
        self::assertStringContainsString( 'clubPlayer[101]', $html );
        self::assertStringContainsString( 'page=racketmanager-clubs&amp;view=player&amp;club_id=10&amp;player_id=501', $html );
        self::assertStringContainsString( 'doClubPlayerDel', $html );
        self::assertStringContainsString( 'doPlayerRatings', $html );
    }

    public function test_render_club_teams_template_with_rows_and_capture_warnings(): void {
        $club = Club_Test_Factory::create( id: 10, name: 'Leigh LTC', shortcode: 'LLTC' );

        $team = (object) array(
            'id'      => 201,
            'title'   => "Men's 1st Team",
            'stadium' => 'Court 1',
            'club_id' => 10,
        );

        $vm = new Club_Teams_Page_View_Model(
            club: $club,
            teams: array( $team ),
            club_id: 10
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/club/show-teams', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Teams', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'LLTC', $html );
        self::assertStringContainsString( 'Court 1', $html );
        self::assertStringContainsString( 'value="201"', $html );
        self::assertStringContainsString( 'team[201]', $html );
        self::assertStringContainsString( 'page=racketmanager-clubs&amp;view=team&amp;edit=201&amp;club_id=10', $html );
        self::assertStringContainsString( 'doTeamDel', $html );
    }

    public function test_render_club_roles_template_with_rows_and_capture_warnings(): void {
        $club = Club_Test_Factory::create( id: 10, name: 'Leigh LTC', shortcode: 'LLTC' );

        $role = (object) array(
            'id'   => 301,
            'role' => (object) array(
                'name' => 'treasurer',
                'desc' => 'Treasurer',
            ),
            'user' => (object) array(
                'id'           => 502,
                'display_name' => 'Alice Treasurer',
            ),
        );

        $club_player = (object) array(
            'user_id'      => 502,
            'display_name' => 'Alice Treasurer',
        );

        $vm = new Club_Roles_Page_View_Model(
            club: $club,
            roles: array( $role ),
            club_players: array( $club_player ),
            club_id: 10
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/club/show-roles', $vm ),
            $warnings
        );

        self::assertSame( array(), $warnings );
        self::assertStringContainsString( 'Roles', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'LLTC', $html );
        self::assertStringContainsString( 'Treasurer', $html );
        self::assertStringContainsString( 'Alice Treasurer', $html );
        self::assertStringContainsString( 'value="301"', $html );
        self::assertStringContainsString( 'role[301]', $html );
        self::assertStringContainsString( 'delRole', $html );
    }
}
