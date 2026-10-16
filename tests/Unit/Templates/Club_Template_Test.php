<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Club_Template_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private string $club_template_path;
    private string $club_header_template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->club_template_path        = dirname( __DIR__, 3 ) . '/templates/club.php';
        $this->club_header_template_path = dirname( __DIR__, 3 ) . '/templates/includes/club-header.php';

        global $racketmanager;
        $racketmanager = new class {
            public function is_user_favourite( $type = null, $id = null ): bool {
                return false;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_club_header_renders_standalone(): void {
        $club = (object) array(
            'id'        => 1,
            'name'      => 'Leigh Tennis Club',
            'shortcode' => 'Leigh TC',
        );
        $standalone      = true;
        $club_presenter  = new Club_Presenter();

        ob_start();
        include $this->club_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( '<h1 class="media__title">', $output );
        $this->assertStringContainsString( 'Leigh Tennis Club', $output );
        $this->assertStringNotContainsString( '<a href="/clubs/', $output );
        $this->assertStringContainsString( 'LT', $output ); // initials
    }

    public function test_club_header_renders_non_standalone_with_link(): void {
        $club = Club_Test_Factory::create(
            id: 2,
            name: 'Westcliff Hardcourt',
            shortcode: 'westcliff-hard'
        );
        $standalone      = false;
        $header_level    = 3;
        $club_presenter  = new Club_Presenter();

        ob_start();
        include $this->club_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( '<h3 class="media__title">', $output );
        $this->assertStringContainsString( '<a href="/clubs/westcliff_hard/">', $output );
        $this->assertStringContainsString( 'Westcliff Hardcourt', $output );
    }

    public function test_club_renders_map_when_key_and_address_present(): void {
        $club = (object) array(
            'id'        => 3,
            'name'      => 'Thorpe Bay Lawn Tennis Club',
            'shortcode' => 'TBLTC',
            'address'   => '100 Thorpe Bay Gardens, SS1 3SL',
            'roles'     => array(),
            'teams'     => array(),
        );
        $standalone           = true;
        $google_maps_key      = 'TEST_SECRET_KEY';
        $user_can_update_club = false;
        $club_presenter       = new Club_Presenter();

        ob_start();
        include $this->club_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'iframe title="Club map"', $output );
        $this->assertStringContainsString( 'key=' . rawurlencode( 'TEST_SECRET_KEY' ), $output );
        $this->assertStringContainsString( 'q=' . rawurlencode( '100 Thorpe Bay Gardens, SS1 3SL' ), $output );
    }

    public function test_club_omits_map_when_key_is_missing(): void {
        $club = (object) array(
            'id'        => 4,
            'name'      => 'Hadleigh Tennis Club',
            'shortcode' => 'Hadleigh',
            'address'   => 'Rectory Road, SS7 2ND',
            'roles'     => array(),
            'teams'     => array(),
        );
        $standalone           = true;
        $google_maps_key      = '';
        $user_can_update_club = false;
        $club_presenter       = new Club_Presenter();

        ob_start();
        include $this->club_template_path;
        $output = ob_get_clean();

        $this->assertStringNotContainsString( 'iframe title="Club map"', $output );
    }

    public function test_club_header_renders_from_view_model(): void {
        $presenter = new Club_Presenter();
        $club      = (object) array(
            'id'        => 99,
            'name'      => 'Wickford Tennis Club',
            'shortcode' => 'Wickford TC',
        );
        $vm = $presenter->present( $club, standalone: false, header_level: 3 );

        ob_start();
        include $this->club_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( '<h3 class="media__title">', $output );
        $this->assertStringContainsString( 'Wickford Tennis Club', $output );
        $this->assertStringContainsString( '<a href="/clubs/wickford-tc/">', $output );
        $this->assertStringContainsString( 'WT', $output );
    }

    public function test_club_header_initials_preserves_head_semantics(): void {
        $presenter = new Club_Presenter();
        $club      = (object) array(
            'id'        => 50,
            'name'      => 'Leigh on Sea Tennis Club',
            'shortcode' => 'Leigh-on-Sea',
        );
        $vm = $presenter->present( $club, standalone: false );

        ob_start();
        include $this->club_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'L', $output );

        $club_lower = (object) array(
            'id'        => 51,
            'name'      => 'wickford tennis club',
            'shortcode' => 'wickford tc',
        );
        $vm = $presenter->present( $club_lower, standalone: false );

        ob_start();
        include $this->club_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'wt', $output );
    }

    public function test_club_renders_from_view_model(): void {
        $presenter = new Club_Presenter();
        $club      = (object) array(
            'id'        => 88,
            'name'      => 'Hockley Lawn Tennis Club',
            'shortcode' => 'Hockley',
            'address'   => 'Main Road, Hockley',
            'roles'     => array(),
            'teams'     => array(),
        );
        $vm = $presenter->present( $club, standalone: true, google_maps_key: 'VALID_KEY', user_can_update_club: true );
        $vars = $vm->template_vars();
        extract( $vars );

        ob_start();
        include $this->club_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Hockley Lawn Tennis Club', $output );
        $this->assertStringContainsString( 'iframe title="Club map"', $output );
        $this->assertStringContainsString( 'VALID_KEY', $output );
        $this->assertStringContainsString( 'updateClubSubmit', $output );
    }

    public function test_club_renders_through_production_shortcode_template_renderer(): void {
        $base_path         = dirname( __DIR__, 3 ) . '/templates/';
        $php_renderer      = new \Racketmanager\Services\View\Php_View_Renderer( $base_path );
        $template_renderer = new \Racketmanager\Services\View\Shortcode_Template_Renderer( $php_renderer );

        $presenter = new Club_Presenter();
        $club      = (object) array(
            'id'              => 77,
            'name'            => 'Westcliff Lawn Tennis Club',
            'shortcode'       => 'Westcliff Lawn',
            'address'         => 'London Road, Westcliff',
            'roles'           => array(),
            'teams'           => array(),
            'match_secretary' => (object) array(
                'id'           => 12,
                'display_name' => 'Bob Secretary',
                'email'        => 'bob@example.com',
                'contactno'    => '01234567890',
            ),
            'contact_no'      => '01702000000',
            'website'         => 'https://westclifflawn.local',
            'founded'         => '1920',
            'facilities'      => '5 courts',
        );

        $vm       = $presenter->present( $club, standalone: true, google_maps_key: 'MAP_API_KEY', user_can_update_club: true );
        $rendered = $template_renderer->render( 'club', $vm->template_vars() );

        $this->assertStringContainsString( 'Westcliff Lawn Tennis Club', $rendered );
        $this->assertStringContainsString( 'WL', $rendered );
        $this->assertStringContainsString( 'Bob Secretary', $rendered );
        $this->assertStringContainsString( '01702000000', $rendered );
        $this->assertStringContainsString( 'https://westclifflawn.local', $rendered );
        $this->assertStringContainsString( '1920', $rendered );
        $this->assertStringContainsString( '5 courts', $rendered );
    }
}
