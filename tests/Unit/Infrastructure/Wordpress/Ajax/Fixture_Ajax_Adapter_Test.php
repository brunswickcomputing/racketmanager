<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;

class Fixture_Ajax_Adapter_Test extends TestCase {
    private Simple_Container $container;
    private Security_Service_Interface $security_service;
    private MockObject|Json_Response_Factory_Interface $response_factory;
    private Fixture_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->container        = new Simple_Container();
        $this->security_service = $this->createStub(Security_Service_Interface::class);
        $this->response_factory = $this->createMock(Json_Response_Factory_Interface::class);
        $this->adapter          = new Fixture_Ajax_Adapter(
            $this->container,
            $this->security_service,
            $this->response_factory
        );
        $_POST = [];
    }

    public function test_update_fixture_result_fails_security_check(): void {
        $this->security_service->method('verify_nonce')->willReturn(false);
        
        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Security');
            }), 403);

        $this->adapter->update_fixture_result();
    }

    public function test_update_fixture_result_fixture_not_found(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['current_match_id'] = 123;

        $fixture_repo = $this->createStub(Fixture_Repository_Interface::class);
        $fixture_repo->method('find_by_id')->willReturn(null);
        $this->container->set('fixture_repository', $fixture_repo);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'not found');
            }), 404);

        $this->adapter->update_fixture_result();
    }
}
