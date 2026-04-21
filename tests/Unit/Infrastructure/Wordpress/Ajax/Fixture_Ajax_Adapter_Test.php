<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Infrastructure\Wordpress\Ajax\Fixture_Ajax_Adapter;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
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
        $presenter = new Fixture_Presenter();
        $this->adapter                = new Fixture_Ajax_Adapter(
            $this->container,
            $this->security_service,
            $this->response_factory,
            $this->fixture_detail_service,
            $this->view_renderer,
            $presenter
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
            $this->createStub( Event::class),
            $this->createStub( Competition::class)
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
            $this->createStub( Event::class),
            $this->createStub( Competition::class)
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
                return !empty($data['err_msgs']);
            }), 400);

        $this->adapter->set_match_status();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_success(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['security'] = 'valid';
        $_POST['match_id'] = 123;
        $_POST['modal'] = 'test-modal';

        $fixture = $this->createStub(Fixture::class);
        $league = $this->createStub(League::class);
        $event = $this->createStub( Event::class);
        $competition = $this->createStub( Competition::class);
        $dto = new Fixture_Details_DTO($fixture, $league, $event, $competition);

        $this->fixture_detail_service->expects($this->once())
            ->method('get_fixture_with_details')
            ->with(123)
            ->willReturn($dto);

        $this->view_renderer->expects($this->once())
            ->method('render_to_string')
            ->with('match/match-status-modal', $this->callback(function($vars) use ($dto) {
                return $vars['dto'] === $dto && $vars['modal'] === 'test-modal';
            }))
            ->willReturn('<html lang="">modal content</html>');

        $this->response_factory->expects($this->once())
            ->method('send_raw')
            ->with('<html lang="">modal content</html>');

        $this->adapter->match_status_options();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_fails_validation(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['security'] = 'valid';
        // Missing match_id and modal

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Match id not found');
            }), 400);

        $this->adapter->match_status_options();
    }

    /**
     * @return void
     */
    #[AllowMockObjectsWithoutExpectations]
    public function test_match_status_options_match_not_found(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['security'] = 'valid';
        $_POST['match_id'] = 999;
        $_POST['modal'] = 'test-modal';

        $this->fixture_detail_service->method('get_fixture_with_details')->willReturn(null);

        $this->response_factory->expects($this->once())
            ->method('send_error')
            ->with($this->callback(function($data) {
                return isset($data['msg']) && str_contains($data['msg'], 'Match not found');
            }), 404);

        $this->adapter->match_status_options();
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
    public function test_match_rubber_status_options_success(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['security'] = 'valid';
        $_POST['rubber_id'] = 456;
        $_POST['modal'] = 'rubberStatusModal';

        $rubber = new stdClass();
        $rubber->match_id = 123;

        // Eval-based stub for get_rubber since it is a global function
        $GLOBALS['wp_stubs_rubbers'] = [456 => $rubber];
        if (!function_exists('Racketmanager\get_rubber')) {
            eval('namespace Racketmanager { function get_rubber($id) { return $GLOBALS["wp_stubs_rubbers"][$id] ?? null; } }');
        }

        $league = $this->createStub(League::class);
        $fixture = $this->createStub(Fixture::class);
        $event = $this->createStub(Event::class);
        $competition = $this->createStub(Competition::class);
        $dto = new Fixture_Details_DTO($fixture, $league, $event, $competition);

        // Stub for show_alert
        if (!function_exists('show_alert')) {
            eval('function show_alert($msg, $type, $template = null) { return "ALERT: " . $msg; }');
        }

        $this->fixture_detail_service->expects($this->once())
            ->method('get_fixture_with_details')
            ->with(123)
            ->willReturn($dto);

        $this->view_renderer->expects($this->once())
            ->method('render_to_string')
            ->with('match/rubber-status-modal', $this->callback(function($vars) use ($dto, $rubber) {
                return $vars['dto'] === $dto &&
                    $vars['rubber'] === $rubber &&
                    $vars['not_played'] === 'Not played';
            }))
            ->willReturn('<html lang="">Modal Content</html>');

        $this->response_factory->expects($this->once())
            ->method('send_raw')
            ->with('<html lang="">Modal Content</html>');

        $this->adapter->match_rubber_status_options();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_match_rubber_status_options_fails_validation(): void {
        $this->security_service->method('verify_nonce')->willReturn(true);
        $_POST['security'] = 'valid';
        // Missing rubber_id

        // Ensure show_alert is defined in a way guaranteed to be available
        if (!function_exists('show_alert')) {
            eval('function show_alert($msg, $type, $template = null) { return "ALERT: " . $msg; }');
        }

        $this->response_factory->expects($this->once())
            ->method('send_raw')
            ->with($this->callback(function() {
                return true; 
            }), 400);

        $this->adapter->match_rubber_status_options();
    }
}
