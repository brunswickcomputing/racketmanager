<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club;
use Racketmanager\Presenters\Club_Presenter;

class Club_Template_Test extends TestCase {
    private string $club_template_path;
    private string $club_header_template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->club_template_path        = dirname( __DIR__, 3 ) . '/templates/club.php';
        $this->club_header_template_path = dirname( __DIR__, 3 ) . '/templates/includes/club-header.php';

        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 3 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }

        global $racketmanager;
        $racketmanager = new class {
            public function is_user_favourite( $type = null, $id = null ): bool {
                return false;
            }
        };
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
        $club = new Club( (object) array(
            'id'        => 2,
            'name'      => 'Westcliff Hardcourt',
            'shortcode' => 'westcliff-hard',
        ) );
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
