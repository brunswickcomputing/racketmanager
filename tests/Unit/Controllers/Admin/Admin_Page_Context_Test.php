<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Admin_Page_Context;

final class Admin_Page_Context_Test extends TestCase {

    public function test_factories_expose_page_and_target_type(): void {
        $league     = Admin_Page_Context::league();
        $cup        = Admin_Page_Context::cup();
        $tournament = Admin_Page_Context::tournament();

        self::assertSame( 'racketmanager-leagues', $league->page );
        self::assertSame( 'league', $league->target_type );
        self::assertSame( 'racketmanager-cups', $cup->page );
        self::assertSame( 'cup', $cup->target_type );
        self::assertSame( 'racketmanager-tournaments', $tournament->page );
        self::assertSame( 'tournament', $tournament->target_type );
    }
}
