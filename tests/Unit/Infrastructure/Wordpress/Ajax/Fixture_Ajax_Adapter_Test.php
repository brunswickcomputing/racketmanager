<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;

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

    public function test_set_match_status_fails_security_check(): void {
        $this->security_service->method('verify_nonce')->willReturn(false);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Security');
            }), 403);

        $this->adapter->set_match_status();
    }

    public function test_set_match_status_invalid_data(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']           = 0;

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['err_msgs']) && !empty($data['err_msgs']);
            }), 400);

        $this->adapter->set_match_status();
    }

    public function test_set_match_status_success(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']           = 123;
        $_POST['score_status']       = 'walkover_player1';
        $_POST['modal']              = 'some_modal';

        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');
        $fixture->method('get_league_id')->willReturn(456);

        $fixture_repo = $this->createMock(Fixture_Repository_Interface::class);
        $fixture_repo->expects($this->once())->method('find_by_id')->with(123)->willReturn($fixture);
        $this->container->set('fixture_repository', $fixture_repo);

        $league = $this->createStub(League::class);
        $league->num_rubbers = 3;
        $league_repo = $this->createMock(League_Repository_Interface::class);
        $league_repo->expects($this->once())->method('find_by_id')->with(456)->willReturn($league);
        $this->container->set('league_repository', $league_repo);

        $this->response_factory->expects($this->once())
            ->method('send_success')
            ->with($this->callback(function($data) {
                return $data['match_id'] === 123 && 
                       $data['match_status'] === 'walkover_player1' &&
                       isset($data['status_message'][1]) &&
                       $data['status_class'][1] === 'winner' &&
                       $data['status_class'][2] === 'loser' &&
                       $data['num_rubbers'] === 3;
            }));

        $this->adapter->set_match_status();
    }

    public function test_set_match_rubber_status_success(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['match_id']           = 123;
        $_POST['score_status']       = 'retired_player2';
        $_POST['rubber_number']      = 1;

        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');
        $fixture->method('get_league_id')->willReturn(456);

        $fixture_repo = $this->createMock(Fixture_Repository_Interface::class);
        $fixture_repo->expects($this->once())->method('find_by_id')->with(123)->willReturn($fixture);
        $this->container->set('fixture_repository', $fixture_repo);

        $league = $this->createStub(League::class);
        $league->num_rubbers = 3;
        $league_repo = $this->createMock(League_Repository_Interface::class);
        $league_repo->expects($this->once())->method('find_by_id')->with(456)->willReturn($league);
        $this->container->set('league_repository', $league_repo);

        $this->response_factory->expects($this->once())
            ->method('send_success')
            ->with($this->callback(function($data) {
                return $data['match_id'] === 123 && 
                       $data['rubber_number'] === 1 &&
                       $data['match_status'] === 'retired_player2' &&
                       $data['status_class'][1] === 'winner' &&
                       $data['status_class'][2] === 'loser' &&
                       $data['num_rubbers'] === 3;
            }));

        $this->adapter->set_match_rubber_status();
    }
}
