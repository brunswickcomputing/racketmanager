<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util_Lookup;

#[AllowMockObjectsWithoutExpectations]
final class Util_Lookup_Query_Vars_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET = [];
        Util_Lookup::set_query_vars( null );
    }

    protected function tearDown(): void {
        Util_Lookup::set_query_vars( null );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_player_id_returns_explicit_id(): void {
        $this->assertSame( '123', Util_Lookup::get_player_id( 123 ) );
        $this->assertSame( 'roger-federer', Util_Lookup::get_player_id( 'roger-federer' ) );
    }

    public function test_get_player_id_prefers_get_param_over_query_vars(): void {
        $_GET['player'] = 'rafael-nadal';

        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->never() )->method( 'get' );

        $result = Util_Lookup::get_player_id( null, $query_vars );
        $this->assertSame( 'rafael nadal', $result );
    }

    public function test_get_player_id_reads_from_passed_query_vars(): void {
        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->once() )
            ->method( 'has' )
            ->with( 'player' )
            ->willReturn( true );
        $query_vars->expects( $this->once() )
            ->method( 'get' )
            ->with( 'player' )
            ->willReturn( 'novak-djokovic' );

        $result = Util_Lookup::get_player_id( null, $query_vars );
        $this->assertSame( 'novak djokovic', $result );
    }

    public function test_get_player_id_reads_from_static_query_vars_when_not_passed(): void {
        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->once() )
            ->method( 'has' )
            ->with( 'player' )
            ->willReturn( true );
        $query_vars->expects( $this->once() )
            ->method( 'get' )
            ->with( 'player' )
            ->willReturn( 'carlos-alcaraz' );

        Util_Lookup::set_query_vars( $query_vars );

        $result = Util_Lookup::get_player_id();
        $this->assertSame( 'carlos alcaraz', $result );
    }

    public function test_get_player_id_returns_null_when_not_found(): void {
        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->method( 'has' )->willReturn( false );

        $result = Util_Lookup::get_player_id( null, $query_vars );
        $this->assertNull( $result );
    }

    public function test_get_tab_prefers_get_param_over_query_vars(): void {
        $_GET['tab'] = 'matches';

        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->never() )->method( 'get' );

        $result = Util_Lookup::get_tab( $query_vars );
        $this->assertSame( 'matches', $result );
    }

    public function test_get_tab_reads_from_passed_query_vars(): void {
        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->once() )
            ->method( 'has' )
            ->with( 'tab' )
            ->willReturn( true );
        $query_vars->expects( $this->once() )
            ->method( 'get' )
            ->with( 'tab' )
            ->willReturn( 'draws' );

        $result = Util_Lookup::get_tab( $query_vars );
        $this->assertSame( 'draws', $result );
    }

    public function test_get_tab_reads_from_static_query_vars_when_not_passed(): void {
        $query_vars = $this->createMock( Query_Vars_Interface::class );
        $query_vars->expects( $this->once() )
            ->method( 'has' )
            ->with( 'tab' )
            ->willReturn( true );
        $query_vars->expects( $this->once() )
            ->method( 'get' )
            ->with( 'tab' )
            ->willReturn( 'overview' );

        Util_Lookup::set_query_vars( $query_vars );

        $result = Util_Lookup::get_tab();
        $this->assertSame( 'overview', $result );
    }

    public function test_get_tab_returns_null_when_not_found(): void {
        $this->assertNull( Util_Lookup::get_tab() );
    }
}
