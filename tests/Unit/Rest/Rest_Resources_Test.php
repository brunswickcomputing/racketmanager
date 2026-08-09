<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use Racketmanager\Rest\Rest_Resources;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Domain\Competition\Competition;
use WP_REST_Request;

use Racketmanager\Services\Container\Simple_Container;

class Rest_Resources_Test extends TestCase {
    private $plugin;
    private $competition_service;
    private $rest_resources;

    protected function setUp(): void {
        parent::setUp();
        
        $this->plugin = $this->createStub( RacketManager::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        
        // Mock the container
        $container = $this->createStub(Simple_Container::class);
        
        $this->plugin->container = $container;
        
        // Set global $racketmanager for Validator constructor
        global $racketmanager;
        $racketmanager = $this->plugin;

        $container->method('get')->willReturnMap([
            ['competition_service', $this->competition_service],
            ['club_service', $this->createStub(\Racketmanager\Services\Club_Service::class)],
            ['season_service', $this->createStub(\Racketmanager\Services\Season_Service::class)],
            ['player_service', $this->createStub(\Racketmanager\Services\Player_Service::class)],
            ['registration_service', $this->createStub(\Racketmanager\Services\Registration_Service::class)],
        ]);
        
        $this->rest_resources = new Rest_Resources( $this->plugin );
    }

    public function test_get_matches_uses_competition_service() {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'competition', 'test-competition' );
        $request->set_param( 'season', '2024' );

        $competition = $this->createStub( Competition::class );
        $competition->id = 123;
        $competition->method('get_seasons')->willReturn(['2024' => ['id' => 1]]);

        $this->competition_service->expects( $this->once() )
            ->method( 'get_competition' )
            ->with( 'test competition', 'name' )
            ->willReturn( $competition );

        // Mock racketmanager->get_matches to return empty array to avoid further logic
        $this->plugin->method( 'get_matches' )->willReturn( [] );

        $response = $this->rest_resources->get_matches( $request );
        if ($response instanceof \WP_Error) {
            $this->fail('REST request failed with WP_Error: ' . $response->get_error_message() . ' Data: ' . print_r($response->get_error_data(), true));
        }
        $this->assertInstanceOf( \WP_REST_Response::class, $response );
        $this->assertEquals( 200, $response->get_status() );
    }
}
