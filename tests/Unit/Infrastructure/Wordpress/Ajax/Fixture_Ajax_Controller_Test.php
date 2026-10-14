<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Controller;
use Racketmanager\Infrastructure\Wordpress\Response\Response;
use Racketmanager\RacketManager;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Fixture_Ajax_Controller_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET  = array();
        $_POST = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_request_merges_get_and_post_with_post_precedence(): void {
        $_GET  = array( 'shared' => 'from_get', 'only_get' => 'get_val' );
        $_POST = array( 'shared' => 'from_post', 'only_post' => 'post_val' );

        $controller = new Fixture_Ajax_Controller( $this->createStub( RacketManager::class ) );

        $this->assertSame(
            array( 'shared' => 'from_post', 'only_get' => 'get_val', 'only_post' => 'post_val' ),
            $controller->get_request()
        );
    }

    public function test_fixture_handlers_forward_merged_request_to_adapter(): void {
        $_GET  = array( 'shared' => 'from_get', 'query' => 'get_value' );
        $_POST = array( 'shared' => 'from_post', 'match_id' => 123, 'security' => 'nonce' );
        $request = array_merge( $_GET, $_POST );

        $adapter = $this->createMock( Fixture_Ajax_Adapter::class );
        foreach (
            array(
                'update_fixture_result',
                'set_fixture_status',
                'set_rubber_status',
                'update_team_match',
                'update_fixture_header',
                'match_rubber_status_options',
            ) as $method
        ) {
            $adapter->expects( $this->once() )
                ->method( $method )
                ->with( $request )
                ->willReturn( new Response( array( 'msg' => 'ok' ), 200 ) );
        }

        $controller = new Fixture_Ajax_Controller( $this->createStub( RacketManager::class ), $adapter );
        $this->expectOutputString( str_repeat( '{"msg":"ok"}', 6 ) );
        $controller->update_fixture_result();
        $controller->set_fixture_status();
        $controller->set_rubber_status();
        $controller->update_team_match();
        $controller->update_fixture_header();
        $controller->rubber_status_options();
    }
}
