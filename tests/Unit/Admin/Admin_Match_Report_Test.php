<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin;
use Racketmanager\Admin\Admin_Match_Report;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionMethod;
use stdClass;

require_once __DIR__ . '/../../wp-stubs.php';
require_once __DIR__ . '/../../wp-menu-stubs.php';

/** @noinspection PhpUnusedArrayKeyInspection */
#[AllowMockObjectsWithoutExpectations]
final class Admin_Match_Report_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can']       = true;
        $GLOBALS['wp_stubs_wp_verify_nonce_calls']  = [];
        $GLOBALS['wp_stubs_current_user_can_calls'] = [];
        unset( $GLOBALS['wp_stubs_valid_nonce_actions'] );
        unset( $GLOBALS['wp_stubs_current_user_can_callback'] );
        unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
        Admin::reset_instance();
    }

    protected function tearDown(): void {
        Admin::reset_instance();
        unset( $GLOBALS['wp_stubs_current_user_can'] );
        unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_admin_constructs_default_match_report_collaborator(): void {
        $admin    = new Admin();
        $post     = new stdClass();
        $post->ID = 0;

        ob_start();
        $admin->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertSame( '', $output );
    }

    public function test_admin_constructor_has_seven_parameters(): void {
        $reflection = new ReflectionMethod( Admin::class, '__construct' );
        self::assertSame( 7, $reflection->getNumberOfParameters() );
    }

    public function test_admin_forwards_add_meta_box_to_match_report(): void {
        $league       = $this->createStub( League::class );
        $league->id   = 10;
        $league->name = 'Test League';

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn( [ $league ] );

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 123 )
            ->willReturn( null );

        $admin = new Admin(
            null,
            null,
            null,
            null,
            null,
            $league_repo,
            $fixture_repo
        );

        $post     = new stdClass();
        $post->ID = 123;

        ob_start();
        $admin->add_meta_box( $post );
        $output = (string) ob_get_clean();

        self::assertStringContainsString( 'Test League', $output );
    }

    public function test_admin_forwards_edit_match_report_to_match_report(): void {
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $curr_fixture     = $this->createStub( Fixture::class );
        $curr_fixture->id = 88;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 60 )
            ->willReturn( $curr_fixture );

        $target_fixture          = $this->createStub( Fixture::class );
        $target_fixture->post_id = 0;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 102 )
            ->willReturn( $target_fixture );

        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'update_post_id' )
            ->willReturnCallback(
                function ( int $match_id, int $post_id ): bool {
                    static $step = 0;
                    $step++;
                    if ( 1 === $step ) {
                        self::assertSame( 102, $match_id );
                        self::assertSame( 60, $post_id );
                    } else {
                        self::assertSame( 88, $match_id );
                        self::assertSame( 0, $post_id );
                    }
                    return true;
                }
            );

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = 1;

        $this->set_post_data(
            [
                'post_ID'                          => '60',
                'match_id'                         => '102',
                'curr_match_id'                    => '88',
                'racketmanager_match_report_nonce' => 'valid_nonce',
            ]
        );

        $admin = new Admin(
            null,
            null,
            null,
            null,
            null,
            null,
            $fixture_repo
        );

        $admin->edit_match_report( 60 );
    }

    public function test_add_meta_box_renders_nothing_when_no_leagues_found(): void {
        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn( [] );

        $report = new Admin_Match_Report( $league_repo );
        $post   = new stdClass();
        $post->ID = 1;

        ob_start();
        $report->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertSame( '', $output );
    }

    public function test_add_meta_box_renders_league_options_and_nonce(): void {
        $league_a       = new stdClass();
        $league_a->id   = 1;
        $league_a->name = 'Premier League';

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn( [ $league_a ] );

        $report = new Admin_Match_Report( $league_repo );
        $post   = new stdClass();
        $post->ID = 77;

        ob_start();
        $report->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( 'Premier League', $output );
        self::assertStringContainsString( 'name="racketmanager_match_report_nonce"', $output );
        self::assertStringContainsString( 'value="0"', $output );
    }

    public function test_add_meta_box_renders_season_and_match_dropdowns_when_fixture_linked(): void {
        $league = $this->createStub( League::class );
        $league->id = 10;
        $league->method( 'get_season' )->willReturn( '2026' );

        $league_summary       = new stdClass();
        $league_summary->id   = 10;
        $league_summary->name = 'Division 1';

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'find_names' )
            ->willReturn( [ $league_summary ] );
        $league_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $league );

        $fixture            = $this->createStub( Fixture::class );
        $fixture->id        = 99;
        $fixture->league_id = 10;
        $fixture->season    = '2026';

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 42 )
            ->willReturn( $fixture );

        $report = new Admin_Match_Report( $league_repo, $fixture_repo );
        $post   = new stdClass();
        $post->ID = 42;

        ob_start();
        $report->add_meta_box( $post );
        $output = ob_get_clean();

        self::assertStringContainsString( 'Division 1', $output );
        self::assertStringContainsString( 'value="99"', $output );
        self::assertStringContainsString( "<select name='season'></select>", $output );
    }

    public function test_edit_match_report_ignores_when_nonce_is_missing(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $this->set_post_data(
            [
                'post_ID'       => '50',
                'match_id'      => '10',
                'curr_match_id' => '0',
            ]
        );

        $report = new Admin_Match_Report( null, $fixture_repo );
        $report->edit_match_report( 50 );
    }

    public function test_edit_match_report_ignores_when_nonce_is_invalid(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::never() )->method( 'update_post_id' );

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;

        $this->set_post_data(
            [
                'post_ID'                          => '50',
                'match_id'                         => '10',
                'curr_match_id'                    => '0',
                'racketmanager_match_report_nonce' => 'invalid_nonce',
            ]
        );

        $report = new Admin_Match_Report( null, $fixture_repo );
        $report->edit_match_report( 50 );
    }

    public function test_edit_match_report_transfers_fixture_when_valid(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 55 )
            ->willReturn( null );

        $target_fixture          = $this->createStub( Fixture::class );
        $target_fixture->post_id = 0;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 101 )
            ->willReturn( $target_fixture );

        $fixture_repo->expects( self::once() )
            ->method( 'update_post_id' )
            ->with( 101, 55 );

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = 1;

        $this->set_post_data(
            [
                'post_ID'                          => '55',
                'match_id'                         => '101',
                'curr_match_id'                    => '0',
                'racketmanager_match_report_nonce' => 'valid_nonce',
            ]
        );

        $report = new Admin_Match_Report( null, $fixture_repo );
        $report->edit_match_report( 55 );
    }

    public function test_edit_match_report_detaches_previous_fixture_when_switching(): void {
        $curr_fixture     = $this->createStub( Fixture::class );
        $curr_fixture->id = 88;

        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_repo->expects( self::once() )
            ->method( 'find_by_post' )
            ->with( 60 )
            ->willReturn( $curr_fixture );

        $target_fixture          = $this->createStub( Fixture::class );
        $target_fixture->post_id = 0;

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 102 )
            ->willReturn( $target_fixture );

        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'update_post_id' )
            ->willReturnCallback(
                function ( int $match_id, int $post_id ): bool {
                    static $step = 0;
                    $step++;
                    if ( 1 === $step ) {
                        self::assertSame( 102, $match_id );
                        self::assertSame( 60, $post_id );
                    } else {
                        self::assertSame( 88, $match_id );
                        self::assertSame( 0, $post_id );
                    }
                    return true;
                }
            );

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = 1;

        $this->set_post_data(
            [
                'post_ID'                          => '60',
                'match_id'                         => '102',
                'curr_match_id'                    => '88',
                'racketmanager_match_report_nonce' => 'valid_nonce',
            ]
        );

        $report = new Admin_Match_Report( null, $fixture_repo );
        $report->edit_match_report( 60 );
    }

    /**
     * Populate $_POST superglobal for test execution.
     *
     * @param array<string, string> $payload Superglobal POST payload.
     *
     * @return void
     */
    private function set_post_data( array $payload ): void {
        $_POST = $payload;
    }
}
