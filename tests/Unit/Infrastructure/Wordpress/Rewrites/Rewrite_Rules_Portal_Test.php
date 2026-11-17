<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Rewrites;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Rewrites\Rewrite_Rules;

final class Rewrite_Rules_Portal_Test extends TestCase {

    public function test_registers_portal_query_vars(): void {
        $rewrites = new Rewrite_Rules();
        $vars     = $rewrites->register_query_vars( [ 'existing_var' ] );

        $this->assertContains( 'rm_manage', $vars );
        $this->assertContains( 'rm_section', $vars );
        $this->assertContains( 'rm_view', $vars );
    }

    public function test_registers_portal_rewrite_rules(): void {
        $GLOBALS['wp_rewrite_rules_added'] = [];

        $rewrites = new Rewrite_Rules();
        $rewrites->racketmanager_rewrites();

        $registered_regexes = array_column( $GLOBALS['wp_rewrite_rules_added'] ?? [], 'regex' );

        $this->assertContains( 'manage/?$', $registered_regexes );
        $this->assertContains( 'manage/([^/]+)/?$', $registered_regexes );
        $this->assertContains( 'manage/([^/]+)/([^/]+)/?$', $registered_regexes );
    }
}
