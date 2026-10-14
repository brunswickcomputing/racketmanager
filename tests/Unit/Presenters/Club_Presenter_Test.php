<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club;
use Racketmanager\Presenters\Club_Presenter;

class Club_Presenter_Test extends TestCase {
    private Club_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Club_Presenter();
    }

    public function test_get_link_with_domain_club(): void {
        $club = new Club( (object) array(
            'id'        => 5,
            'name'      => 'Leigh Tennis Club',
            'shortcode' => 'leigh-tc',
        ) );

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
}
