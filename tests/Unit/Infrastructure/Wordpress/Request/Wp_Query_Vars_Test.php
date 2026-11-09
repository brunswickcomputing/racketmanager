<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Request;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Infrastructure\Wordpress\Request\Wp_Query_Vars;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

final class Wp_Query_Vars_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Wp_Query_Vars $query_vars;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->query_vars = new Wp_Query_Vars();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_implements_query_vars_interface(): void {
        $this->assertInstanceOf( Query_Vars_Interface::class, $this->query_vars );
    }

    public function test_get_returns_from_wp_query_vars(): void {
        global $wp;
        $wp = new stdClass();
        $wp->query_vars = [ 'season' => '2025' ];

        $this->assertSame( '2025', $this->query_vars->get( 'season' ) );
    }

    public function test_get_falls_back_to_get_query_var_when_not_in_wp_query_vars(): void {
        global $wp, $wp_query;
        $wp = new stdClass();
        $wp->query_vars = [];

        $wp_query = new class {
            public function get( string $key, mixed $default = '' ): mixed {
                return 'season' === $key ? '2026' : $default;
            }
        };

        $this->assertSame( '2026', $this->query_vars->get( 'season' ) );
    }

    public function test_get_returns_default_when_missing(): void {
        global $wp;
        $wp = new stdClass();
        $wp->query_vars = [];

        $this->assertSame( 'default_season', $this->query_vars->get( 'non_existent_key', 'default_season' ) );
        $this->assertNull( $this->query_vars->get( 'non_existent_key' ) );
    }

    public function test_has_returns_true_when_in_wp_query_vars(): void {
        global $wp;
        $wp = new stdClass();
        $wp->query_vars = [ 'tab' => 'draw' ];

        $this->assertTrue( $this->query_vars->has( 'tab' ) );
        $this->assertFalse( $this->query_vars->has( 'unknown' ) );
    }

    public function test_has_returns_true_when_in_get_query_var(): void {
        global $wp, $wp_query;
        $wp = new stdClass();
        $wp->query_vars = [];

        $wp_query = new class {
            public function get( string $key, mixed $default = '' ): mixed {
                return 'tab' === $key ? 'draw' : $default;
            }
        };

        $this->assertTrue( $this->query_vars->has( 'tab' ) );
        $this->assertFalse( $this->query_vars->has( 'unknown' ) );
    }

    public function test_set_updates_wp_and_wordpress_state(): void {
        global $wp;
        $wp = new class {
            public array $query_vars = [];
            public function set_query_var( string $key, mixed $value ): void {
                $this->query_vars[ $key ] = $value;
            }
        };

        $this->query_vars->set( 'season', '2027' );

        $this->assertSame( '2027', $wp->query_vars['season'] );
        $this->assertSame( '2027', $this->query_vars->get( 'season' ) );
    }

    public function test_set_handles_wp_without_set_query_var_method(): void {
        global $wp;
        $wp = new stdClass();
        $wp->query_vars = [];

        $this->query_vars->set( 'league', 'premier' );

        $this->assertSame( 'premier', $wp->query_vars['league'] );
    }

    public function test_all_returns_full_query_vars_array(): void {
        global $wp;
        $wp = new stdClass();
        $wp->query_vars = [
            'season' => '2025',
            'tab'    => 'fixtures',
        ];

        $this->assertSame( [
            'season' => '2025',
            'tab'    => 'fixtures',
        ], $this->query_vars->all() );
    }

    public function test_all_returns_empty_array_when_wp_uninitialized(): void {
        global $wp;
        $wp = null;

        $this->assertSame( [], $this->query_vars->all() );
    }
}
