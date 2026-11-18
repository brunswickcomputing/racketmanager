<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Controllers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;

final class Admin_Redirect_Url_Builder_Test extends TestCase {

    protected function tearDown(): void {
        Admin_Redirect_Url_Builder::set_resolver( null );
        parent::tearDown();
    }

    public function test_club_screen_in_admin_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: false );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::club_screen(
            [
                'page'    => 'racketmanager-clubs',
                'view'    => 'club',
                'club_id' => '15',
            ]
        );

        $this->assertStringContainsString( 'admin.php', $url );
        $this->assertStringContainsString( 'page=racketmanager-clubs', $url );
        $this->assertStringContainsString( 'view=club', $url );
        $this->assertStringContainsString( 'club_id=15', $url );
    }

    public function test_club_screen_in_portal_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::club_screen(
            [
                'page'    => 'racketmanager-clubs',
                'view'    => 'club',
                'club_id' => '15',
            ]
        );

        $this->assertSame( home_url( '/manage/clubs/club?club_id=15' ), $url );
    }

    public function test_index_screen_in_admin_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: false );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::index_screen(
            [
                'page'      => 'racketmanager',
                'age_group' => 'seniors',
            ]
        );

        $this->assertStringContainsString( 'admin.php', $url );
        $this->assertStringContainsString( 'page=racketmanager', $url );
        $this->assertStringContainsString( 'age_group=seniors', $url );
    }

    public function test_index_screen_in_portal_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::index_screen(
            [
                'page'      => 'racketmanager',
                'age_group' => 'seniors',
            ]
        );

        $this->assertSame( home_url( '/manage/competitions?age_group=seniors' ), $url );
    }

    public function test_players_screen_in_admin_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: false );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::players_screen(
            [
                'page'      => 'racketmanager-players',
                'view'      => 'player',
                'player_id' => '42',
            ]
        );

        $this->assertStringContainsString( 'admin.php', $url );
        $this->assertStringContainsString( 'page=racketmanager-players', $url );
        $this->assertStringContainsString( 'view=player', $url );
        $this->assertStringContainsString( 'player_id=42', $url );
    }

    public function test_players_screen_in_portal_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::players_screen(
            [
                'page'      => 'racketmanager-players',
                'view'      => 'player',
                'player_id' => '42',
            ]
        );

        $this->assertSame( home_url( '/manage/players/player?player_id=42' ), $url );
    }

    public function test_results_screen_in_admin_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: false );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::results_screen(
            [
                'page' => 'racketmanager-results',
                'tab'  => 'resultschecker',
            ]
        );

        $this->assertStringContainsString( 'admin.php', $url );
        $this->assertStringContainsString( 'page=racketmanager-results', $url );
        $this->assertStringContainsString( 'tab=resultschecker', $url );
    }

    public function test_results_screen_in_portal_context(): void {
        $resolver = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        Admin_Redirect_Url_Builder::set_resolver( $resolver );

        $url = Admin_Redirect_Url_Builder::results_screen(
            [
                'page' => 'racketmanager-results',
                'tab'  => 'resultschecker',
            ]
        );

        $this->assertSame( home_url( '/manage/results?tab=resultschecker' ), $url );
    }
}
