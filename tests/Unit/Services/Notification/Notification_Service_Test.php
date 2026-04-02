<?php
declare(strict_types=1);

namespace Racketmanager\Domain\Fixture {
    if ( ! function_exists( 'Racketmanager\Domain\Fixture\maybe_unserialize' ) ) {
        function maybe_unserialize( $data ) {
            return $data;
        }
    }
}

namespace Racketmanager\Domain\Competition {
    if ( ! function_exists( 'Racketmanager\Domain\Competition\maybe_unserialize' ) ) {
        function maybe_unserialize( $data ) {
            return $data;
        }
    }
}

namespace Racketmanager\Services\Notification {
    if ( ! function_exists( 'Racketmanager\Services\Notification\__' ) ) {
        function __( $text, $domain ) {
            return $text;
        }
    }
    if ( ! defined( 'RACKETMANAGER_CC_EMAIL' ) ) {
        define( 'RACKETMANAGER_CC_EMAIL', 'cc@example.com' );
    }
    if ( ! function_exists( 'Racketmanager\Services\Notification\wp_mail' ) ) {
        function wp_mail( $to, $subject, $message, $headers ) {
            $GLOBALS['wp_mail_calls'][] = [
                'to'      => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
            ];
            return true;
        }
    }
}

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\result_notification' ) ) {
        function result_notification( $match_id, $args ) {
            return 'Result Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\captain_result_notification' ) ) {
        function captain_result_notification( $match_id, $args ) {
            return 'Captain Result Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_notification' ) ) {
        function match_notification( $match_id, $args ) {
            return 'Match Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_date_change_notification' ) ) {
        function match_date_change_notification( $match_id, $args ) {
            return 'Date Change Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_team_withdrawn_notification' ) ) {
        function match_team_withdrawn_notification( $match_id, $args ) {
            return 'Withdrawn Notification';
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Notification {

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\RacketManager;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Notification_Service_Test extends TestCase {
    private $league_repository;
    private $league_team_repository;
    private $team_repository;
    private $player_repository;
    private $club_repository;
    private $settings_service;
    private $app;
    private $service;

    protected function setUp(): void {
        parent::setUp();
        $this->league_repository = $this->createMock(League_Repository_Interface::class);
        $this->league_team_repository = $this->createMock(League_Team_Repository_Interface::class);
        $this->team_repository = $this->createMock(Team_Repository_Interface::class);
        $this->player_repository = $this->createMock(Player_Repository_Interface::class);
        $this->club_repository = $this->createMock(Club_Repository_Interface::class);
        $this->settings_service = $this->createStub(Settings_Service::class);
        $this->app = $this->createMock(RacketManager::class);

        $this->service = new Notification_Service(
            $this->league_repository,
            $this->league_team_repository,
            $this->team_repository,
            $this->player_repository,
            $this->club_repository,
            $this->settings_service,
            $this->app
        );

        $this->app->site_name = 'Test Site';

        // Keep global mock for any remaining legacy calls or functions that use it
        $GLOBALS['racketmanager'] = $this->app;
    }

    public function test_send_result_notification_league_not_found(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_league_id')->willReturn(1);
        $this->league_repository->method('find_by_id')->willReturn(null);

        // Should just return without error
        $this->service->send_result_notification($fixture, 'Y', 'Test Message');
        $this->assertTrue(true);
    }

    public function test_send_result_notification_success_championship(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->final = 'Final';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)
            ->disableOriginalConstructor()
            ->getMock();
        $league->id = 456;
        $league->title = 'Championship League';
        $league->is_championship = true;
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->competition = (object)['type' => 'tournament'];
        $league->event = $event;

        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn([
            'resultNotification' => 'admin',
            'confirmationRequired' => false,
            'confirmationTimeout' => 0
        ]);
        $this->app->method('get_from_user_email')->willReturn('From: User <user@example.com>');

        $home_team = $this->createStub(Team::class);
        $home_team->method('get_name')->willReturn('Home Team');
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_name')->willReturn('Away Team');

        $this->team_repository->method('find_by_id')
            ->willReturnMap([
                [100, $home_team],
                [200, $away_team]
            ]);

        // Capture wp_mail calls
        $GLOBALS['wp_mail_calls'] = [];

        $this->service->send_result_notification($fixture, 'Y', 'Result confirmed');

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals('admin@example.com', $mail['to']);
        $this->assertStringContainsString('Championship League - Home Team v Away Team - Result confirmed', $mail['subject']);
        $this->assertStringContainsString('Match complete', $mail['subject']);
    }

    public function test_send_result_notification_with_confirmation_recipient(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)
            ->disableOriginalConstructor()
            ->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $league->is_championship = false;
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn([
            'resultNotification' => 'captain',
            'confirmationRequired' => true,
            'confirmationTimeout' => 48
        ]);
        $this->app->method('get_from_user_email')->willReturn('From: User <user@example.com>');

        $home_team = $this->createStub(Team::class);
        $home_team->method('get_name')->willReturn('Home Team');
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_name')->willReturn('Away Team');

        $this->team_repository->method('find_by_id')
            ->willReturnMap([
                [100, $home_team],
                [200, $away_team]
            ]);

        // For get_confirmation_email resolution (it uses league_team_repository and player_repository)
        $home_league_team = new stdClass();
        $home_league_team->captain = 5;
        $home_league_team_domain = new League_Team($home_league_team);

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->with(100, 456, 2026)
            ->willReturn($home_league_team_domain);

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home-captain@example.com');

        $this->player_repository->method('find')
            ->with(5, 'id')
            ->willReturn($home_captain);

        $GLOBALS['wp_mail_calls'] = [];

        // Match updated by away, so home captain should get confirmation email
        $this->service->send_result_notification($fixture, 'P', 'Result entered', 'away');

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals('home-captain@example.com', $mail['to']);
        $this->assertStringContainsString('Confirmation required', $mail['subject']);
    }

    public function test_notify_team_withdrawal(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)
            ->disableOriginalConstructor()
            ->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;

        $this->league_repository->method('find_by_id')->willReturn($league);

        $home_team = $this->createStub(Team::class);
        $home_team->method('get_id')->willReturn(100);
        $home_team->method('get_name')->willReturn('Home Team');
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_id')->willReturn(200);
        $away_team->method('get_name')->willReturn('Away Team');

        $this->team_repository->method('find_by_id')
            ->willReturnMap([
                [100, $home_team],
                [200, $away_team]
              ]);

        $away_league_team = new stdClass();
        $away_league_team->captain = 6;
        $away_league_team_domain = new League_Team($away_league_team);

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->with(200, 456, 2026) // Assuming fixture season is derived correctly, but in test it is just data.
            ->willReturn($away_league_team_domain);

        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away-captain@example.com');

        $this->player_repository->method('find')
            ->with(6, 'id')
            ->willReturn($away_captain);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $GLOBALS['wp_mail_calls'] = [];

        $this->service->notify_team_withdrawal($fixture, 100);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals('away-captain@example.com', $mail['to']);
        $this->assertStringContainsString('Team withdrawn', $mail['subject']);
        $this->assertStringContainsString('Home Team v Away Team', $mail['subject']);
    }

    public function test_send_result_notification_admin_email_empty(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_league_id')->willReturn(1);
        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);
        $this->app->method('get_confirmation_email')->willReturn('');

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'Y', 'Test');
        $this->assertEmpty($GLOBALS['wp_mail_calls']);
    }

    public function test_send_result_notification_regular_league_matchday(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->match_day = '5';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $league->is_championship = false;
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'admin', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);
        $this->app->method('get_from_user_email')->willReturn('from@example.com');

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'Y', 'Test');
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
    }

    public function test_send_result_notification_challenge_status(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'admin', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);
        $this->app->method('get_from_user_email')->willReturn('from@example.com');

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'C', 'Test');
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
    }

    public function test_get_confirmation_email_secretary_recipient(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'secretary', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        $league_team_data = new stdClass();
        $league_team_data->club_id = 10;
        // Mock League_Team to avoid call to get_club() in constructor/initialization
        $league_team = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->club_id = 10;
        $this->league_team_repository->method('find_by_id')->with(200)->willReturn($league_team);

        $club = $this->createStub(\Racketmanager\Domain\Club::class);
        $club->match_secretary = (object)['email' => 'secretary@example.com'];
        $this->club_repository->method('find')->with(10)->willReturn($club);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $this->assertEquals('secretary@example.com', $GLOBALS['wp_mail_calls'][0]['to']);
    }

    public function test_get_confirmation_email_not_sent_if_not_pending(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'captain', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        $GLOBALS['wp_mail_calls'] = [];
        // Status 'Y' (Complete), updated by 'home'. get_confirmation_email should return null.
        $this->service->send_result_notification($fixture, 'Y', 'Test', 'home');
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);
    }

    public function test_get_team_captain_email_null_cases(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'captain', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        // Case 1: League team not found
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);

        // Case 2: League team has no captain
        $league_team = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->method('get_captain')->willReturn(null);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);

        // Case 3: Player not found
        $league_team = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->method('get_captain')->willReturn(5);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team);
        $this->player_repository->method('find')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);
    }

    public function test_get_club_secretary_email_null_cases(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'League 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $GLOBALS['racketmanager']->method('get_confirmation_email')->willReturn('admin@example.com');
        $GLOBALS['racketmanager']->method('get_options')->willReturn(['league' => ['resultNotification' => 'secretary', 'confirmationRequired' => false, 'confirmationTimeout' => 0]]);

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        // Case 1: League team not found (using find_by_id as per implementation of get_club_secretary_email)
        $this->league_team_repository->method('find_by_id')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);

        // Case 2: Club not found
        $league_team = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->club_id = 10;
        $this->league_team_repository->method('find_by_id')->willReturn($league_team);
        $this->club_repository->method('find')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);

        // Case 3: Club has no match secretary email
        $club = $this->createStub(\Racketmanager\Domain\Club::class);
        $club->match_secretary = null;
        $this->club_repository->method('find')->willReturn($club);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);
    }

    public function test_notify_team_withdrawal_null_cases(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_league_id')->willReturn(1);

        // Case 1: League not found
        $this->league_repository->method('find_by_id')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->notify_team_withdrawal($fixture, 100);
        $this->assertEmpty($GLOBALS['wp_mail_calls']);

        // Case 2: Admin email empty
        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);
        $GLOBALS['racketmanager']->method('get_confirmation_email')->willReturn('');
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->notify_team_withdrawal($fixture, 100);
        $this->assertEmpty($GLOBALS['wp_mail_calls']);

        // Case 3: Team not found
        $GLOBALS['racketmanager']->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->team_repository->method('find_by_id')->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->notify_team_withdrawal($fixture, 100);
        $this->assertEmpty($GLOBALS['wp_mail_calls']);
    }
    public function test_send_next_match_notification_success(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture_data->leg = 1;
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'cup', 'name' => 'Cup Name'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away@example.com');

        $league_team_home = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team_home->method('get_captain')->willReturn(1);
        $league_team_away = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team_away->method('get_captain')->willReturn(2);

        $this->league_team_repository->method('find_by_team_league_and_season')->willReturnMap([
            [100, 456, 2026, $league_team_home],
            [200, 456, 2026, $league_team_away]
        ]);

        $this->player_repository->method('find')->willReturnMap([
            [1, 'id', $home_captain],
            [2, 'id', $away_captain]
        ]);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_next_match_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertContains('home@example.com', $mail['to']);
        $this->assertContains('away@example.com', $mail['to']);
        $this->assertStringContainsString('Match Details', $mail['subject']);
        $this->assertStringContainsString('Leg 1', $mail['subject']);
    }

    public function test_send_date_change_notification_success(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture_data->date = '2026-05-01';
        $fixture_data->date_original = '2026-04-01';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league', 'name' => 'League Name', 'is_tournament' => false];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $this->player_repository->method('find')->willReturn($home_captain);
        $league_team_home = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team_home->method('get_captain')->willReturn(1);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_home);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_date_change_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('Match Date Change', $mail['subject']);
    }

    public function test_send_date_change_notification_tournament_delay(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture_data->date = '2026-05-01';
        $fixture_data->date_original = '2026-04-01';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'tournament', 'name' => 'Tournament Name', 'is_tournament' => true];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $this->player_repository->method('find')->willReturn($home_captain);
        $league_team_home = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team_home->method('get_captain')->willReturn(1);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_home);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_date_change_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('DELAY', $mail['subject']);
    }

    public function test_notify_team_withdrawal_enhanced(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '100';
        $fixture_data->away_team = '200';
        $fixture_data->season = '2026';
        $fixture = new Fixture($fixture_data);

        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $league->id = 456;
        $league->title = 'Division 1';
        $event = $this->getMockBuilder(\Racketmanager\Domain\Competition\Event::class)->disableOriginalConstructor()->getMock();
        $event->competition = (object)['type' => 'league'];
        $league->event = $event;
        $this->league_repository->method('find_by_id')->willReturn($league);

        $home_team = $this->createStub(Team::class);
        $home_team->method('get_id')->willReturn(100);
        $home_team->method('get_name')->willReturn('Home Team');
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_id')->willReturn(200);
        $away_team->method('get_name')->willReturn('Away Team');

        $this->team_repository->method('find_by_id')->willReturnMap([
            [100, $home_team],
            [200, $away_team]
        ]);

        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away-captain@example.com');
        $this->player_repository->method('find')->willReturn($away_captain);
        $league_team_away = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team_away->method('get_captain')->willReturn(2);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_away);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->notify_team_withdrawal($fixture, 100);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals('away-captain@example.com', $mail['to']);
    }
}
}
