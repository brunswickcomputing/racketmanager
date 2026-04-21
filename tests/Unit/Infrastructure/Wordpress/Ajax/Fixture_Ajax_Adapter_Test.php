<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use stdClass;

class Fixture_Ajax_Adapter_Test extends TestCase {
    private Simple_Container $container;
    private Security_Service_Interface $security_service;
    private MockObject|Json_Response_Factory_Interface $response_factory;
    private MockObject|Fixture_Detail_Service $fixture_detail_service;
    private MockObject|View_Renderer_Interface $view_renderer;
    private Fixture_Ajax_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->container              = new Simple_Container();
        $this->security_service       = $this->createStub( Security_Service_Interface::class );
        $this->response_factory       = $this->createMock( Json_Response_Factory_Interface::class );
        $this->fixture_detail_service = $this->createMock( Fixture_Detail_Service::class );
        $this->view_renderer          = $this->createMock( View_Renderer_Interface::class );
        $this->adapter                = new Fixture_Ajax_Adapter(
            $this->container,
            $this->security_service,
            $this->response_factory,
            $this->fixture_detail_service,
            $this->view_renderer
        );
        $_POST = [];
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_fails_security_check(): void {
        $this->security_service->method('verify_nonce')->willReturn(false);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Security');
            }), 403);

        $this->adapter->print_match_card();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_missing_id(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'not supplied');
            }), 400);

        $this->adapter->print_match_card();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_print_match_card_not_found(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId'] = 123;

        $this->fixture_detail_service->expects($this->once())
            ->method('get_fixture_with_details')
            ->with(123)
            ->willReturn(null);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'not found');
            }), 404);

        $this->adapter->print_match_card();
    }

    public function test_print_match_card_success_no_rubbers(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId'] = 123;

        $fixture = $this->createStub(Fixture::class);
        $league = $this->createStub(League::class);
        $league->num_rubbers = 0;

        $dto = new Fixture_Details_DTO(
            $fixture,
            $league,
            $this->createStub(\Racketmanager\Domain\Competition\Event::class),
            $this->createStub(\Racketmanager\Domain\Competition\Competition::class)
        );

        $this->fixture_detail_service->expects($this->once())
            ->method('get_fixture_with_details')
            ->with(123)
            ->willReturn($dto);

        $this->view_renderer->expects($this->once())
            ->method('render_to_string')
            ->with('match/match-card', $this->callback(function($args) use ($dto, $fixture) {
                return $args['dto'] === $dto && $args['match'] === $fixture;
            }))
            ->willReturn('Rendered Content');

        $this->response_factory->expects($this->once())
            ->method('send_success')
            ->with('Rendered Content');

        $this->adapter->print_match_card();
    }

    public function test_print_match_card_success_with_rubbers(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['racketmanager_nonce'] = 'valid';
        $_POST['matchId'] = 123;

        $fixture = $this->createMock(Fixture::class);
        $fixture->expects($this->once())->method('get_rubbers')->willReturn(['rubber1']);
        
        $league = $this->createStub(League::class);
        $league->num_rubbers = 3;

        $dto = new Fixture_Details_DTO(
            $fixture,
            $league,
            $this->createStub(\Racketmanager\Domain\Competition\Event::class),
            $this->createStub(\Racketmanager\Domain\Competition\Competition::class)
        );

        $this->fixture_detail_service->expects($this->once())
            ->method('get_fixture_with_details')
            ->with(123)
            ->willReturn($dto);

        $this->view_renderer->expects($this->once())
            ->method('render_to_string')
            ->with('match/match-card-rubbers', $this->callback(function($args) use ($dto, $fixture) {
                return $args['dto'] === $dto && $args['match'] === $fixture && $fixture->rubbers === ['rubber1'];
            }))
            ->willReturn('Rendered Rubbers Content');

        $this->response_factory->expects($this->once())
            ->method('send_success')
            ->with('Rendered Rubbers Content');

        $this->adapter->print_match_card();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_update_fixture_result_fails_security_check(): void {
        $this->security_service->method('verify_nonce')->willReturn(false);
        
        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Security');
            }), 403);

        $this->adapter->update_fixture_result();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
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

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_set_match_status_fails_security_check(): void {
        $this->security_service->method('verify_nonce')->willReturn(false);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Security');
            }), 403);

        $this->adapter->set_match_status();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
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

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
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

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
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
