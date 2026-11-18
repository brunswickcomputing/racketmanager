<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Routing;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;

final class Manage_Route_Mapper_Test extends TestCase {

    private Manage_Route_Mapper $mapper;

    protected function setUp(): void {
        parent::setUp();
        $this->mapper = new Manage_Route_Mapper();
    }

    public function test_normalizes_section_and_aliases(): void {
        $this->assertSame( 'documentation', $this->mapper->normalize_section( 'doc' ) );
        $this->assertSame( 'documentation', $this->mapper->normalize_section( 'DOCUMENTATION' ) );
        $this->assertSame( 'clubs', $this->mapper->normalize_section( ' clubs ' ) );
    }

    public function test_maps_section_to_admin_page(): void {
        $this->assertSame( 'racketmanager-overview', $this->mapper->section_to_admin_page( 'overview' ) );
        $this->assertSame( 'racketmanager', $this->mapper->section_to_admin_page( 'competitions' ) );
        $this->assertSame( 'racketmanager-clubs', $this->mapper->section_to_admin_page( 'clubs' ) );
        $this->assertSame( 'racketmanager-leagues', $this->mapper->section_to_admin_page( 'leagues' ) );
        $this->assertSame( 'racketmanager-doc', $this->mapper->section_to_admin_page( 'documentation' ) );
        $this->assertSame( 'racketmanager-doc', $this->mapper->section_to_admin_page( 'doc' ) );
        $this->assertSame( 'racketmanager', $this->mapper->section_to_admin_page( 'unknown' ) );
    }

    public function test_maps_admin_page_to_section(): void {
        $this->assertSame( 'overview', $this->mapper->admin_page_to_section( 'racketmanager-overview' ) );
        $this->assertSame( 'competitions', $this->mapper->admin_page_to_section( 'racketmanager' ) );
        $this->assertSame( 'clubs', $this->mapper->admin_page_to_section( 'racketmanager-clubs' ) );
        $this->assertSame( 'leagues', $this->mapper->admin_page_to_section( 'racketmanager-leagues' ) );
        $this->assertSame( 'documentation', $this->mapper->admin_page_to_section( 'racketmanager-doc' ) );
        $this->assertSame( 'competitions', $this->mapper->admin_page_to_section( 'unknown-page' ) );
    }

    public function test_resolves_screen_classes(): void {
        $this->assertSame( Overview_Page_View_Model::class, $this->mapper->section_to_screen_class( 'overview' ) );
        $this->assertSame( Admin_Index::class, $this->mapper->section_to_screen_class( 'competitions' ) );
        $this->assertSame( Admin_Club::class, $this->mapper->section_to_screen_class( 'clubs' ) );
        $this->assertSame( Admin_League::class, $this->mapper->section_to_screen_class( 'leagues' ) );
        $this->assertSame( Documentation_Page_View_Model::class, $this->mapper->section_to_screen_class( 'doc' ) );
        $this->assertNull( $this->mapper->section_to_screen_class( 'nonexistent' ) );
    }

    public function test_validates_known_sections(): void {
        $this->assertTrue( $this->mapper->is_valid_section( 'overview' ) );
        $this->assertTrue( $this->mapper->is_valid_section( 'competitions' ) );
        $this->assertTrue( $this->mapper->is_valid_section( 'clubs' ) );
        $this->assertTrue( $this->mapper->is_valid_section( 'leagues' ) );
        $this->assertTrue( $this->mapper->is_valid_section( 'doc' ) );
        $this->assertFalse( $this->mapper->is_valid_section( 'unknown' ) );
    }
}
