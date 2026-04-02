<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Services\Fixture;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Service_Provider as Fixture_Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Maintenance_Service_Integration_Test extends TestCase {
    private $notification_service;
    private $settings_service;
    private $fixture_result_manager;
    private $league_repository;
    private $results_checker_repository;
    private $service;

    protected function setUp(): void {
        parent::setUp();
        
        $this->notification_service = $this->createMock(Notification_Service::class);
        $this->settings_service = $this->createMock(Settings_Service::class);
        $this->fixture_result_manager = $this->createMock(Fixture_Result_Manager::class);
        $this->league_repository = $this->createMock(League_Repository_Interface::class);
        $this->results_checker_repository = $this->createMock(Results_Checker_Repository_Interface::class);

        $service_provider = new Fixture_Service_Provider(
            result_service: $this->createMock(\Racketmanager\Services\Result_Service::class),
            progression_service: $this->createMock(\Racketmanager\Services\Competition\Knockout_Progression_Service::class),
            league_service: $this->createMock(\Racketmanager\Services\League_Service::class),
            score_validator: $this->createMock(\Racketmanager\Services\Validator\Score_Validation_Service::class),
            player_validator: $this->createMock(\Racketmanager\Services\Validator\Player_Validation_Service::class),
            notification_service: $this->notification_service,
            registration_service: $this->createMock(\Racketmanager\Services\Registration_Service::class)
        );
        $service_provider->set_settings_service( $this->settings_service );

        $repository_provider = new Repository_Provider(
            league_repository: $this->league_repository,
            results_checker_repository: $this->results_checker_repository
        );

        $this->service = new Fixture_Maintenance_Service(
            $service_provider,
            $repository_provider,
            $this->fixture_result_manager
        );

        // Mock global $racketmanager
        $container = new \Racketmanager\Services\Container\Simple_Container();
        $racketmanager_instance = new class($container) {
            public $container;
            public function __construct($container) { $this->container = $container; }
        };
        $GLOBALS['racketmanager'] = $racketmanager_instance;
    }

    public function test_chase_match_result_integration(): void {
        $fixture = $this->createMock(Fixture::class);
        $fixture->method('get_league_id')->willReturn(1);

        $league = $this->createMock(League::class);
        $event = $this->createMock(Event::class);
        $event->competition = (object) ['type' => 'league'];
        $league->event = $event;

        $this->league_repository->method('find_by_id')->with(1)->willReturn($league);
        $this->settings_service->method('get_option')->willReturn('admin@test.com');

        $this->notification_service->expects($this->once())
            ->method('send_chase_result_notification')
            ->with($fixture, $this->callback(function($args) {
                return $args['from_email'] === 'admin@test.com' && $args['time_period'] === '2 days';
            }));

        $result = $this->service->chase_match_result($fixture, '2 days');
        $this->assertTrue($result);
    }

    public function test_complete_result_integration(): void {
        $fixture = $this->createMock(Fixture::class);
        $fixture->method('get_league_id')->willReturn(1);
        $fixture->method('get_home_points')->willReturn('5.0');
        $fixture->method('get_away_points')->willReturn('3.0');

        $league = $this->createMock(League::class);
        $event = $this->createMock(Event::class);
        $event->competition = (object) ['type' => 'league'];
        $league->event = $event;

        $this->league_repository->method('find_by_id')->with(1)->willReturn($league);
        $this->settings_service->method('get_option')->willReturn('admin@test.com');

        $response = new Fixture_Update_Response([Fixture_Update_Status::TABLE_UPDATED]);
        $this->fixture_result_manager->method('confirm_result')->willReturn($response);

        $result = $this->service->complete_result($fixture, 48);
        $this->assertEquals(1, $result);
    }
}
