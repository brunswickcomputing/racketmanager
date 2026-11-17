<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Routing;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;

final class Manage_Url_Resolver_Test extends TestCase {

    private Manage_Route_Mapper $mapper;

    protected function setUp(): void {
        parent::setUp();
        $this->mapper = new Manage_Route_Mapper();
    }

    public function test_resolves_portal_urls(): void {
        $resolver = new Manage_Url_Resolver( $this->mapper, force_portal_context: true );

        $this->assertSame( home_url( '/manage/clubs' ), $resolver->get_url( 'clubs' ) );
        $this->assertSame( home_url( '/manage/clubs/club' ), $resolver->get_url( 'clubs', 'club' ) );
        $this->assertSame(
            home_url( '/manage/clubs/club?club_id=12' ),
            $resolver->get_url( 'clubs', 'club', [ 'club_id' => 12 ] )
        );
    }

    public function test_resolves_admin_urls(): void {
        $resolver = new Manage_Url_Resolver( $this->mapper, force_portal_context: false );

        $this->assertSame( admin_url( 'admin.php?page=racketmanager-clubs' ), $resolver->get_url( 'clubs' ) );
        $this->assertSame(
            admin_url( 'admin.php?page=racketmanager-clubs&view=club' ),
            $resolver->get_url( 'clubs', 'club' )
        );
        $this->assertSame(
            admin_url( 'admin.php?page=racketmanager-clubs&view=club&club_id=12' ),
            $resolver->get_url( 'clubs', 'club', [ 'club_id' => 12 ] )
        );
    }

    public function test_detects_portal_context_from_query_vars(): void {
        $query_vars = $this->createStub( Query_Vars_Interface::class );
        $query_vars->method( 'get' )
            ->willReturn( '1' );

        $resolver = new Manage_Url_Resolver( $this->mapper, $query_vars );
        $this->assertTrue( $resolver->is_portal_context() );
        $this->assertSame( home_url( '/manage/competitions' ), $resolver->get_url( 'competitions' ) );
    }

    public function test_with_portal_context_returns_new_instance(): void {
        $resolver  = new Manage_Url_Resolver( $this->mapper, force_portal_context: false );
        $mutated   = $resolver->with_portal_context( true );

        $this->assertFalse( $resolver->is_portal_context() );
        $this->assertTrue( $mutated->is_portal_context() );
    }
}
