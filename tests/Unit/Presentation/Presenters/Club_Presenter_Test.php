<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

class Club_Presenter_Test extends TestCase {
    private Club_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Club_Presenter();
    }

    public function test_get_link_with_domain_club(): void {
        $club = Club_Test_Factory::create(
            id: 5,
            name: 'Leigh Tennis Club',
            shortcode: 'leigh-tc'
        );

        $link = $this->presenter->get_link( $club );
        $this->assertEquals( '/clubs/leigh_tc/', $link );

        $roles_link = $this->presenter->get_link( $club, 'roles' );
        $this->assertEquals( '/clubs/leigh_tc/roles/', $roles_link );

        $comp_link = $this->presenter->get_link( $club, 'competitions' );
        $this->assertEquals( '/clubs/leigh_tc/competitions/', $comp_link );
    }

    public function test_get_display_name(): void {
        $club = (object) array(
            'name' => 'Westcliff Hardcourt',
        );

        $name = $this->presenter->get_display_name( $club );
        $this->assertEquals( 'Westcliff Hardcourt', $name );
    }

    public function test_get_heading_tag(): void {
        $this->assertEquals( 'h1', $this->presenter->get_heading_tag( true ) );
        $this->assertEquals( 'h2', $this->presenter->get_heading_tag( false ) );
        $this->assertEquals( 'h3', $this->presenter->get_heading_tag( false, 3 ) );
        $this->assertEquals( 'h4', $this->presenter->get_heading_tag( true, 4 ) );
    }

    public function test_get_map_url(): void {
        $club = (object) array(
            'address' => '123 Tennis Lane, SS0 9SB',
        );

        $map_url = $this->presenter->get_map_url( $club, 'TEST_MAP_KEY' );
        $this->assertStringContainsString( 'key=' . rawurlencode( 'TEST_MAP_KEY' ), $map_url );
        $this->assertStringContainsString( 'q=' . rawurlencode( '123 Tennis Lane, SS0 9SB' ), $map_url );

        $this->assertNull( $this->presenter->get_map_url( (object) array( 'address' => '' ), 'TEST_MAP_KEY' ) );
        $this->assertNull( $this->presenter->get_map_url( $club, null ) );
    }

    public function test_get_subpage_links(): void {
        $club = (object) array(
            'shortcode' => 'westcliff-hard',
        );

        $links = $this->presenter->get_subpage_links( $club );
        $this->assertArrayHasKey( 'roles', $links );
        $this->assertArrayHasKey( 'competitions', $links );
        $this->assertArrayHasKey( 'players', $links );
        $this->assertEquals( '/clubs/westcliff_hard/roles/', $links['roles'] );
        $this->assertEquals( '/clubs/westcliff_hard/competitions/', $links['competitions'] );
        $this->assertEquals( '/clubs/westcliff_hard/players/', $links['players'] );
    }

    public function test_get_initials(): void {
        $this->assertEquals( 'LTC', $this->presenter->get_initials( 'Leigh Tennis Club' ) );
        $this->assertEquals( 'LT', $this->presenter->get_initials( 'Leigh TC' ) );
        $this->assertEquals( 'T', $this->presenter->get_initials( 'TBLTC' ) );
        // Hyphenated shortcodes split on space only (preserving HEAD semantics):
        $this->assertEquals( 'L', $this->presenter->get_initials( 'Leigh-on-Sea' ) );
        // Lowercase shortcodes preserve case:
        $this->assertEquals( 'wt', $this->presenter->get_initials( 'wickford tc' ) );
        $this->assertEquals( 'WL', $this->presenter->get_initials( 'Westcliff Lawn' ) );
    }

    public function test_error_view_model(): void {
        $error_vm = \Racketmanager\Presentation\View_Models\Club_View_Model::for_error( 'Club not found' );
        $this->assertEquals( 'alert', $error_vm->template );
        $this->assertEquals( 'Club not found', $error_vm->error_message );
    }

    public function test_present_creates_immutable_view_model(): void {
        $club = (object) array(
            'id'              => 12,
            'name'            => 'Rayleigh LTC',
            'shortcode'       => 'Rayleigh',
            'address'         => 'Watchfield Common, Rayleigh',
            'roles'           => array( 'secretary' => 'Jane' ),
            'teams'           => array( 'A', 'B' ),
            'match_secretary' => (object) array(
                'id'           => 99,
                'display_name' => 'Jane Smith',
                'email'        => 'jane@example.com',
                'contactno'    => '07123456789',
            ),
            'contact_no'      => '01268000000',
            'website'         => 'https://rayleightc.local',
            'founded'         => '1950',
            'facilities'      => '4 hard courts',
        );

        $vm = $this->presenter->present(
            club: $club,
            standalone: true,
            google_maps_key: 'MAP_KEY',
            user_can_update_club: true,
            header_level: 2,
            template: 'club-custom'
        );

        $this->assertEquals( 'club-custom', $vm->template );
        $this->assertEquals( 12, $vm->id );
        $this->assertEquals( 'Rayleigh LTC', $vm->name );
        $this->assertEquals( 'Rayleigh', $vm->shortcode );
        $this->assertEquals( 'rayleigh', $vm->slug );
        $this->assertEquals( 'Watchfield Common, Rayleigh', $vm->address );
        $this->assertEquals( '/clubs/rayleigh/', $vm->link );
        $this->assertEquals( 'R', $vm->initials );
        $this->assertEquals( 'h2', $vm->heading_tag );
        $this->assertTrue( $vm->user_can_update_club );
        $this->assertTrue( $vm->standalone );
        $this->assertEquals( 'Jane Smith', $vm->match_secretary_name );
        $this->assertEquals( 99, $vm->match_secretary_id );
        $this->assertEquals( 'jane@example.com', $vm->match_secretary_email );
        $this->assertEquals( '07123456789', $vm->match_secretary_contact_no );
        $this->assertEquals( '01268000000', $vm->contact_no );
        $this->assertEquals( 'https://rayleightc.local', $vm->website );
        $this->assertEquals( '1950', $vm->founded );
        $this->assertEquals( '4 hard courts', $vm->facilities );
        $this->assertStringContainsString( 'key=MAP_KEY', $vm->map_url );

        $vars = $vm->template_vars();
        $this->assertSame( $vm, $vars['vm'] );
        $this->assertEquals( 'Rayleigh LTC', $vars['club_name'] );
        $this->assertEquals( 'Jane Smith', $vars['match_secretary_name'] );
    }
}
