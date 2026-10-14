<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presenters\Club_Presenter;
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
}
