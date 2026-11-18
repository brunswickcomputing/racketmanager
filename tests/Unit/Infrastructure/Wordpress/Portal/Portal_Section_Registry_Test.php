<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Config;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Section_Registry;

final class Portal_Section_Registry_Test extends TestCase {

    private Portal_Section_Registry $registry;

    protected function setUp(): void {
        parent::setUp();
        $this->registry = new Portal_Section_Registry();
    }

    public function test_registers_thirteen_default_sections(): void {
        $expected_slugs = [
            'overview',
            'competitions',
            'clubs',
            'leagues',
            'cups',
            'tournaments',
            'results',
            'players',
            'seasons',
            'finances',
            'settings',
            'import',
            'documentation',
        ];

        $this->assertCount( 13, $this->registry->all() );
        foreach ( $expected_slugs as $slug ) {
            $this->assertTrue( $this->registry->has( $slug ), "Expected section {$slug} to be registered" );
            $config = $this->registry->get( $slug );
            $this->assertNotNull( $config );
            $this->assertSame( $slug, $config->slug );
            $this->assertTrue( $config->is_ported, "Expected {$slug} to be ported in Increment 4" );
        }
    }

    public function test_normalizes_doc_alias_to_documentation(): void {
        $this->assertTrue( $this->registry->has( 'doc' ) );
        $config = $this->registry->get( 'doc' );
        $this->assertNotNull( $config );
        $this->assertSame( 'documentation', $config->slug );
    }

    public function test_toggles_ported_status(): void {
        $this->assertTrue( $this->registry->is_ported( 'seasons' ) );
        $initial_ported = $this->registry->get_ported_sections();
        $this->assertArrayHasKey( 'seasons', $initial_ported );

        $this->registry->set_ported( 'seasons', false );
        $this->assertFalse( $this->registry->is_ported( 'seasons' ) );
        $this->assertArrayNotHasKey( 'seasons', $this->registry->get_ported_sections() );

        $this->registry->set_ported( 'seasons', true );
        $this->assertTrue( $this->registry->is_ported( 'seasons' ) );
        $this->assertArrayHasKey( 'seasons', $this->registry->get_ported_sections() );
    }

    public function test_accepts_custom_sections(): void {
        $custom   = [
            'custom' => new Portal_Section_Config( 'custom', 'custom-page', 'Custom_Class', 'read', 'default', true, 'Custom' ),
        ];
        $registry = new Portal_Section_Registry( $custom );

        $this->assertCount( 1, $registry->all() );
        $this->assertTrue( $registry->has( 'custom' ) );
        $this->assertTrue( $registry->is_ported( 'custom' ) );
        $this->assertFalse( $registry->has( 'clubs' ) );
    }
}
