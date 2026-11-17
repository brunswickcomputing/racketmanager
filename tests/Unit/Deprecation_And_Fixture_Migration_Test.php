<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Results_Fixtures_Tab_Handler;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Validator\Validator_Fixture;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionMethod;

class Deprecation_And_Fixture_Migration_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_validator_fixture_match_with_repository_found(): void {
        $fixture = new Fixture();
        $fixture->set_id( 123 );

        $repo = $this->createMock( Fixture_Repository_Interface::class );
        $repo->expects( $this->once() )->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );

        $validator = new Validator_Fixture( fixture_repository: $repo );
        $result    = $validator->match( 123 );

        $this->assertFalse( $result->error );
        $this->assertEmpty( $result->err_msgs );
    }

    public function test_validator_fixture_match_with_repository_not_found(): void {
        $repo = $this->createMock( Fixture_Repository_Interface::class );
        $repo->expects( $this->once() )->method( 'find_by_id' )->with( 123 )->willReturn( null );

        $validator = new Validator_Fixture( fixture_repository: $repo );
        $result    = $validator->match( 123 );

        $this->assertTrue( $result->error );
        $this->assertContains( 'Match not found', $result->err_msgs );
    }

    public function test_validator_fixture_match_without_repository(): void {
        $validator = new Validator_Fixture( fixture_repository: null );

        $this->expectException( \LogicException::class );
        $this->expectExceptionMessage( 'Fixture repository has not been configured on this Validator_Fixture instance.' );

        $validator->match( 123 );
    }

    public function test_validator_fixture_match_missing_id(): void {
        $validator = new Validator_Fixture();
        $result    = $validator->match( null );

        $this->assertTrue( $result->error );
        $this->assertContains( 'Match id not supplied', $result->err_msgs );
    }

    public function test_admin_result_hydrate_match_with_container(): void {
        $fixture = new Fixture();
        $fixture->set_id( 123 );

        $repo = $this->createMock( Fixture_Repository_Interface::class );
        $repo->expects( $this->once() )->method( 'find_by_id' )->with( 123 )->willReturn( $fixture );

        $handler = new Results_Fixtures_Tab_Handler(
            $repo,
            new Admin_Results_Match_Presenter(
                new Admin_Date_Presenter(),
                $this->createStub( League_Repository_Interface::class )
            ),
            $this->createStub( Option_Service::class )
        );

        $method = new ReflectionMethod( Results_Fixtures_Tab_Handler::class, 'hydrate_match' );
        $hydrated = $method->invoke( $handler, 123 );

        $this->assertSame( $fixture, $hydrated );
    }

    public function test_admin_result_hydrate_match_without_container(): void {
        $repo = $this->createMock( Fixture_Repository_Interface::class );
        $repo->expects( self::never() )->method( 'find_by_id' );

        $handler = new Results_Fixtures_Tab_Handler(
            $repo,
            new Admin_Results_Match_Presenter(
                new Admin_Date_Presenter(),
                $this->createStub( League_Repository_Interface::class )
            ),
            $this->createStub( Option_Service::class )
        );

        $method = new ReflectionMethod( Results_Fixtures_Tab_Handler::class, 'hydrate_match' );
        $raw_obj  = (object) [ 'title' => 'Raw match' ];
        $hydrated = $method->invoke( $handler, $raw_obj );

        $this->assertSame( $raw_obj, $hydrated );
    }

    public function test_render_order_of_play_court_template(): void {
        $match_vm = new Order_Of_Play_Match_View_Model(
            id: 10,
            league_name: 'Division 1',
            home_match_title: 'Home Team',
            away_match_title: 'Away Team',
            is_home_pending: false,
            is_away_pending: false,
        );

        $court_times = [
            [ $match_vm ],
        ];
        $is_expanded = false;

        ob_start();
        try {
            include dirname( __DIR__, 2 ) . '/templates/includes/order-of-play-court.php';
            $output = (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }

        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Home Team', $output );
        $this->assertStringContainsString( 'Away Team', $output );
    }

    public function test_render_order_of_play_court_template_no_container(): void {
        unset( $GLOBALS['racketmanager'] );

        $court_times = [
            [],
        ];
        $is_expanded = false;

        ob_start();
        try {
            include dirname( __DIR__, 2 ) . '/templates/includes/order-of-play-court.php';
            $output = (string) ob_get_clean();
        } catch ( \Throwable $e ) {
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            throw $e;
        }

        $this->assertStringContainsString( 'match-group__item-wrapper', $output );
    }
}
