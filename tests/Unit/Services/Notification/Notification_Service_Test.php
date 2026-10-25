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
}

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\result_notification' ) ) {
        function result_notification( $match_id, $args ): string {
            return 'Result Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\captain_result_notification' ) ) {
        function captain_result_notification( $match_id, $args ): string {
            return 'Captain Result Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_notification' ) ) {
        function match_notification( $match_id, $args ): string {
            return 'Match Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_date_change_notification' ) ) {
        function match_date_change_notification( $match_id, $args ): string {
            return 'Date Change Notification';
        }
    }
    if ( ! function_exists( 'Racketmanager\match_team_withdrawn_notification' ) ) {
        function match_team_withdrawn_notification( $match_id, $args ): string {
            return 'Withdrawn Notification';
        }
    }
}

namespace Racketmanager\Tests\Unit\Services\Notification {

    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\MockObject\Stub;
    use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use Racketmanager\Domain\Club\Club;
    use Racketmanager\Domain\Competition\Competition_Type;
    use Racketmanager\Domain\Competition\Event;
    use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\Presentation\View_Models\Send_Fixtures_Match_Row_View_Model;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Settings_Service;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
    use Racketmanager\Services\Competition\League_Query_Service;
    use stdClass;

class Fake_Mailer implements Mailer_Interface {
    public function send(
        string|array|null $to,
        ?string $subject,
        ?string $message,
        string|array|null $headers = '',
        ?array $attachments = []
    ): bool {
        $GLOBALS['wp_mail_calls'][] = [
            'to'          => $to,
            'subject'     => $subject,
            'message'     => $message,
            'headers'     => $headers,
            'attachments' => $attachments,
        ];
        return true;
    }
}

#[AllowMockObjectsWithoutExpectations]
class Notification_Service_Test extends TestCase {
    private Event_Repository_Interface|MockObject $event_repository;
    private MockObject|League_Repository_Interface $league_repository;
    private MockObject|League_Team_Repository_Interface $league_team_repository;
    private Team_Repository_Interface|MockObject $team_repository;
    private Player_Repository_Interface|MockObject $player_repository;
    private Club_Repository_Interface|MockObject $club_repository;
    private Settings_Service|Stub $settings_service;
    private Notification_Presenter|MockObject $presenter;
    private View_Renderer_Interface|MockObject $view_renderer;
    private RacketManager|MockObject $app;
    private Fake_Mailer $mailer;
    private Notification_Service $service;
    private League_Query_Service|MockObject $league_query_service;

    protected function setUp(): void {
        parent::setUp();
        $this->event_repository = $this->createMock(Event_Repository_Interface::class);
        $this->league_repository = $this->createMock(League_Repository_Interface::class);
        $this->league_team_repository = $this->createMock(League_Team_Repository_Interface::class);
        $this->team_repository = $this->createMock(Team_Repository_Interface::class);
        $this->player_repository = $this->createMock(Player_Repository_Interface::class);
        $this->club_repository = $this->createMock(Club_Repository_Interface::class);
        $this->settings_service = $this->createStub(Settings_Service::class);
        $this->presenter = $this->createMock(Notification_Presenter::class);
        $this->view_renderer = $this->createMock( View_Renderer_Interface::class);
        $this->app = $this->createMock(RacketManager::class);
        $this->league_query_service = $this->createMock( League_Query_Service::class );

        $repository_provider = $this->createStub(Repository_Provider::class);
        $repository_provider->method('get_event_repository')->willReturn($this->event_repository);
        $repository_provider->method('get_league_repository')->willReturn($this->league_repository);
        $repository_provider->method('get_league_team_repository')->willReturn($this->league_team_repository);
        $repository_provider->method('get_team_repository')->willReturn($this->team_repository);
        $repository_provider->method('get_player_repository')->willReturn($this->player_repository);
        $repository_provider->method('get_club_repository')->willReturn($this->club_repository);

        $this->mailer = new Fake_Mailer();
        $this->service = new Notification_Service(
            $repository_provider,
            $this->settings_service,
            $this->presenter,
            $this->view_renderer,
            $this->app,
            $this->mailer,
            league_query_service: $this->league_query_service
        );

        $this->app->site_name = 'Test Site';

        $this->view_renderer->method('render_to_string')->willReturn('Rendered notification');

        $this->presenter->method('present_match_notification')->willReturn(['template' => 'match-notification']);
        $this->presenter->method('present_result_notification')->willReturn([]);
        $this->presenter->method('present_date_change_notification')->willReturn([]);
        $this->presenter->method('present_team_withdrawn_notification')->willReturn([]);

        // Keep global mock for any remaining legacy calls or functions that use it
        $GLOBALS['racketmanager'] = $this->app;
    }

    private function create_test_fixture( array $overrides = [] ): Fixture {
        $raw = array_merge( [
            'id'        => 123,
            'league_id' => 456,
            'home_team' => '100',
            'away_team' => '200',
            'season'    => '2026',
        ], $overrides );
        foreach ( $overrides as $key => $value ) {
            if ( null === $value ) {
                unset( $raw[ $key ] );
            }
        }
        return Fixture_Factory::from_object( (object) $raw );
    }

    private function create_mock_league(
        int $id = 456,
        string $title = 'Division 1',
        bool $is_championship = false,
        string $comp_type = 'league',
        ?string $comp_name = null,
        bool $is_tournament = false,
        int $competition_id = 123
    ): League {
        $league                  = $this->createMock( League::class );
        $league->id              = $id;
        $league->title           = $title;
        $league->is_championship = $is_championship;

        $competition = (object) [
            'type'          => $comp_type,
            'is_tournament' => $is_tournament,
        ];
        if ( null !== $comp_name ) {
            $competition->name = $comp_name;
        }

        $event                 = $this->createMock( Event::class );
        $event->competition    = $competition;
        $event->competition_id = $competition_id;
        $league->event         = $event;

        return $league;
    }

    private function setup_teams_mock(
        string $home_name = 'Home Team',
        string $away_name = 'Away Team',
        int $home_id = 100,
        int $away_id = 200
    ): array {
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_id' )->willReturn( $home_id );
        $home_team->method( 'get_name' )->willReturn( $home_name );

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_id' )->willReturn( $away_id );
        $away_team->method( 'get_name' )->willReturn( $away_name );

        $this->team_repository->method( 'find_by_id' )
            ->willReturnMap( [
                [ $home_id, $home_team ],
                [ $away_id, $away_team ],
            ] );

        return [ $home_team, $away_team ];
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
        $fixture = $this->create_test_fixture( [ 'final' => 'Final' ] );
        $league  = $this->create_mock_league( 456, 'Championship League', true, 'tournament' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn([
            'resultNotification' => 'admin',
            'confirmationRequired' => false,
            'confirmationTimeout' => 0
        ]);
        $this->app->method('get_from_user_email')->willReturn('From: User <user@example.com>');

        $this->setup_teams_mock();

        // Capture wp_mail calls
        $GLOBALS['wp_mail_calls'] = [];

        $this->service->send_result_notification($fixture, 'Y', 'Result confirmed');

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals('admin@example.com', $mail['to']);
        $this->assertStringContainsString('Championship League - Home Team v Away Team - Result confirmed', $mail['subject']);
        $this->assertStringContainsString('Fixture complete', $mail['subject']);
    }

    public function test_send_result_notification_with_confirmation_recipient(): void {
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'league' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn([
            'resultNotification' => 'captain',
            'confirmationRequired' => true,
            'confirmationTimeout' => 48
        ]);
        $this->app->method('get_from_user_email')->willReturn('From: User <user@example.com>');

        $this->setup_teams_mock();

        // For get_confirmation_email resolution (it uses league_team_repository and player_repository)
        $home_league_team = new stdClass();
        $home_league_team->captain = 5;
        $home_league_team_domain = new League_Team($home_league_team);

        $this->league_team_repository->expects( $this->once() )
            ->method('find_by_team_league_and_season')
            ->with(100, 456, 2026)
            ->willReturn($home_league_team_domain);

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home-captain@example.com');

        $this->player_repository->expects( $this->once() )
            ->method('find')
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
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'league' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->setup_teams_mock();

        $away_league_team = new stdClass();
        $away_league_team->captain = 6;
        $away_league_team_domain = new League_Team($away_league_team);

        $this->league_team_repository->expects( $this->once() )
            ->method('find_by_team_league_and_season')
            ->with(200, 456, 2026) // Assuming fixture season is derived correctly, but in test it is just data.
            ->willReturn($away_league_team_domain);

        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away-captain@example.com');

        $this->player_repository->expects( $this->once() )
            ->method('find')
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
        $league  = $this->create_mock_league();
        $this->league_repository->method('find_by_id')->willReturn($league);
        $this->app->method('get_confirmation_email')->willReturn('');

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'Y', 'Test');
        $this->assertEmpty($GLOBALS['wp_mail_calls']);
    }

    public function test_send_result_notification_regular_league_matchday(): void {
        $fixture = $this->create_test_fixture( [ 'match_day' => '5' ] );
        $league  = $this->create_mock_league();
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
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league();
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
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league();
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->settings_service->method('get_category')->willReturn(['resultNotification' => 'secretary', 'confirmationRequired' => false, 'confirmationTimeout' => 0]);

        $this->team_repository->method('find_by_id')->willReturn($this->createStub(Team::class));

        // Mock League_Team to avoid call to get_club() in constructor/initialization
        $league_team = $this->getMockBuilder(League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->club_id = 10;
        $this->league_team_repository->expects( $this->once() )->method('find_by_id')->with(200)->willReturn($league_team);

        $club = $this->createStub( Club::class);
        $club->match_secretary = (object)['email' => 'secretary@example.com'];
        $this->club_repository->expects( $this->once() )->method( 'find_by_id' )->with(10)->willReturn($club);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $this->assertEquals('secretary@example.com', $GLOBALS['wp_mail_calls'][0]['to']);
    }

    public function test_get_confirmation_email_not_sent_if_not_pending(): void {
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league();
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
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league();
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
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league();
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
        $this->club_repository->method( 'find_by_id' )->willReturn(null);
        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_result_notification($fixture, 'P', 'Test', 'home');
        $this->assertEquals('admin@example.com', $GLOBALS['wp_mail_calls'][0]['to']);

        // Case 3: Club has no match secretary email
        $club = $this->createStub( Club::class);
        $club->match_secretary = null;
        $this->club_repository->method( 'find_by_id' )->willReturn($club);
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

        // Case 2: Admin email is empty
        $league = $this->getMockBuilder(League::class)->disableOriginalConstructor()->getMock();
        $event = $this->getMockBuilder( Event::class)->disableOriginalConstructor()->getMock();
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
        $fixture = $this->create_test_fixture( [ 'leg' => 1, 'host' => 'home' ] );
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'cup', 'Cup Name' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away@example.com');

        $league_team_home = $this->createMock(League_Team::class);
        $league_team_home->method('get_captain')->willReturn(1);
        $league_team_away = $this->createMock(League_Team::class);
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
        $result = $this->service->send_next_fixture_notification($fixture);

        $this->assertTrue($result);
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertContains('home@example.com', $mail['to']);
        $this->assertContains('away@example.com', $mail['to']);
        $this->assertStringContainsString('Fixture Details', $mail['subject']);
        $this->assertStringContainsString('Leg 1', $mail['subject']);
    }

    public function test_send_next_fixture_notification_tournament_round(): void {
        $fixture = $this->create_test_fixture( [ 'host' => 'home', 'final' => 'semi' ] );
        $league  = $this->create_mock_league( 456, 'Tournament 1', false, 'tournament', 'Tournament Name' );
        $league->method('get_competition_type')->willReturn('tournament');
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        // Mock players for tournament
        $player1 = $this->createMock(Player::class);
        $player1->method('get_email')->willReturn('player1@example.com');
        $player2 = $this->createMock(Player::class);
        $player2->method('get_email')->willReturn('player2@example.com');
        $this->player_repository->method('find_by_team')->willReturnMap([
            [100, [$player1]],
            [200, [$player2]]
        ]);

        $GLOBALS['wp_mail_calls'] = [];
        $result = $this->service->send_next_fixture_notification($fixture);

        $this->assertTrue($result);
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('Semi Final', $mail['subject']);
    }

    public function test_send_next_fixture_notification_no_host_returns_false(): void {
        $fixture = $this->create_test_fixture( [ 'host' => null ] );
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'cup', 'Cup Name' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $result = $this->service->send_next_fixture_notification($fixture);

        $this->assertFalse($result);
    }

    public function test_send_date_change_notification_success(): void {
        $fixture = $this->create_test_fixture( [ 'date' => '2026-05-01', 'date_original' => '2026-04-01' ] );
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'league', 'League Name', false );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $this->player_repository->method('find')->willReturn($home_captain);
        $league_team_home = $this->createMock(League_Team::class);
        $league_team_home->method('get_captain')->willReturn(1);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_home);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_date_change_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('Fixture Date Change', $mail['subject']);
    }

    public function test_send_date_change_notification_tournament_delay(): void {
        $fixture = $this->create_test_fixture( [ 'date' => '2026-05-01', 'date_original' => '2026-04-01' ] );
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'tournament', 'Tournament Name', true );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $this->player_repository->method('find')->willReturn($home_captain);
        $league_team_home = $this->createMock(League_Team::class);
        $league_team_home->method('get_captain')->willReturn(1);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_home);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_date_change_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('DELAY', $mail['subject']);
    }

    public function test_send_date_change_notification_tournament_round(): void {
        $fixture = $this->create_test_fixture( [ 'date' => '2026-05-01', 'date_original' => '2026-04-01', 'final' => 'quarter' ] );
        $league  = $this->create_mock_league( 456, 'Tournament 1', false, 'tournament', 'Tournament Name', true );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('From: Admin <admin@example.com>');

        $home_captain = $this->createStub(Player::class);
        $home_captain->method('get_email')->willReturn('home@example.com');
        $this->player_repository->method('find')->willReturn($home_captain);
        $league_team_home = $this->createMock(League_Team::class);
        $league_team_home->method('get_captain')->willReturn(1);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team_home);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_date_change_notification($fixture);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertStringContainsString('Quarter Final', $mail['subject']);
    }

    public function test_notify_team_withdrawal_enhanced(): void {
        $fixture = $this->create_test_fixture();
        $league  = $this->create_mock_league( 456, 'Division 1', false, 'league' );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $this->setup_teams_mock();

        $away_captain = $this->createStub(Player::class);
        $away_captain->method('get_email')->willReturn('away-captain@example.com');
        $this->player_repository->method('find')->willReturn($away_captain);
        $league_team_away = $this->createMock(League_Team::class);
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

    public function test_send_result_error_notification_enhanced(): void {
        $checker = new Results_Checker();
        $checker->description = 'Penalty for late result';
        $checker->league_id   = 456;

        $fixture = $this->create_test_fixture( [ 'match_title' => 'Home v Away', 'home_team' => '100' ] );
        $fixture->fixture_title = 'Home v Away';

        $league = $this->create_mock_league();
        $this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );

        $this->app->method( 'get_confirmation_email' )->willReturn( 'admin@example.com' );

        // Mock Captain
        $league_team = $this->getMockBuilder( League_Team::class )->disableOriginalConstructor()->getMock();
        $league_team->method( 'get_captain' )->willReturn( 5 );
        $league_team->club_id = 10;
        $this->league_team_repository->expects( $this->once() )->method( 'find_by_team_league_and_season' )
            ->with( 100, 456, 2026 )
            ->willReturn( $league_team );
        
        // Match secretary lookup uses find_by_id(team_id)
        $this->league_team_repository->expects( $this->once() )->method( 'find_by_id' )
            ->with( 100 )
            ->willReturn( $league_team );

        $captain = $this->createStub( Player::class );
        $captain->method( 'get_email' )->willReturn( 'captain@example.com' );
        $this->player_repository->expects( $this->once() )->method( 'find' )->with( 5, 'id' )->willReturn( $captain );

        // Mock Match Secretary
        $club = $this->getMockBuilder( Club::class )->disableOriginalConstructor()->getMock();
        $club->match_secretary = (object)[
            'display_name' => 'Match Sec',
            'email' => 'secretary@example.com'
        ];
        $this->club_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( $club );

        $GLOBALS['wp_mail_calls'] = [];

        $this->service->send_result_error_notification( $checker, $fixture, 2.0 );

        $this->assertCount( 1, $GLOBALS['wp_mail_calls'] );
        $mail = $GLOBALS['wp_mail_calls'][0];
        
        $this->assertContains( 'captain@example.com', $mail['to'] );
        $found_cc = false;
        foreach ( $mail['headers'] as $header ) {
            if ( str_contains( $header, 'secretary@example.com' ) ) {
                $found_cc = true;
                break;
            }
        }
        $this->assertTrue( $found_cc, 'Secretary email not found in headers' );
    }

    public function test_send_result_error_notification(): void {
        $checker = $this->createMock( Results_Checker::class );
        $checker->description = 'Test error description';
        $checker->league_id   = 456;

        $fixture = $this->create_test_fixture( [
            'match_title'  => 'Home v Away',
            'home_captain' => 5,
            'home_team'    => null,
            'away_team'    => null,
            'season'       => null,
        ] );
        $fixture->fixture_title = 'Home v Away';

        $league = $this->create_mock_league();
        $this->league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 456 )->willReturn( $league );

        $this->app->method( 'get_confirmation_email' )->willReturn( 'admin@example.com' );
        $this->app->site_name = 'Test Site';

        // Mock get_userdata for wp_mail recipient resolution
        if ( ! function_exists( 'Racketmanager\Tests\Unit\Services\Notification\get_userdata' ) ) {
            eval( 'namespace Racketmanager\Services\Notification { function get_userdata($id) { 
                if ($id === 5) {
                    return (object)["user_email" => "captain@example.com"];
                }
                return null;
            } }' );
        }

        $GLOBALS['wp_mail_calls'] = [];

        $this->service->send_result_error_notification( $checker, $fixture, 2.0 );

        $this->assertCount( 1, $GLOBALS['wp_mail_calls'] );
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertEquals( [ 'captain@example.com' ], $mail['to'] );
        $this->assertStringContainsString( '[Test Site] Result Error: Home v Away', $mail['subject'] );
        $this->assertStringContainsString( 'Test error description', $mail['message'] );
        $this->assertStringContainsString( '2 points', $mail['message'] );
        $this->assertStringContainsString( 'From: Test Site <admin@example.com>', $mail['headers'][0] );
    }

    public function test_send_chase_approval_notification(): void {
        $fixture = $this->getMockBuilder(Fixture::class)->disableOriginalConstructor()->getMock();
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_league_id')->willReturn(456);
        $fixture->method('get_home_team')->willReturn('100');
        $fixture->method('get_away_team')->willReturn('200');
        $fixture->method('get_updated_by')->willReturn('home');
        $fixture->method('get_season')->willReturn('2024');

        $league = $this->create_mock_league();
        $this->league_repository->method('find_by_id')->willReturn($league);

        $team = $this->getMockBuilder(Team::class)->disableOriginalConstructor()->getMock();
        $team->method('get_id')->willReturn(200);
        $team->method('get_club_id')->willReturn(10);
        $this->team_repository->method('find_by_id')->willReturn($team);

        $league_team = $this->getMockBuilder( League_Team::class)->disableOriginalConstructor()->getMock();
        $league_team->method('get_captain')->willReturn(999);
        $this->league_team_repository->method('find_by_team_league_and_season')->willReturn($league_team);

        $player = $this->getMockBuilder( Player::class)->disableOriginalConstructor()->getMock();
        $player->method('get_email')->willReturn('captain@example.com');
        $this->player_repository->method('find')->willReturn($player);

        $this->app->method('get_confirmation_email')->willReturn('admin@example.com');
        $this->app->method('get_from_user_email')->willReturn('from@example.com');
        
        $this->presenter->method('present_captain_result_approval_notification')->willReturn(['match' => new stdClass()]);

        $GLOBALS['wp_mail_calls'] = [];
        $this->service->send_chase_approval_notification($fixture, ['from_email' => 'admin@example.com']);
        
        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $this->assertStringContainsString('Fixture result approval', $GLOBALS['wp_mail_calls'][0]['subject']);
    }

    public function test_get_fixture_emails_non_tournament(): void {
        $fixture = $this->create_test_fixture();

        $league = $this->createMock(League::class);
        $league->method('get_competition_type')->willReturn('league');
        $this->league_repository->method('find_by_id')->willReturn($league);

        $league_team_home = $this->createMock(League_Team::class);
        $league_team_home->method('get_captain')->willReturn(1);
        $league_team_away = $this->createMock(League_Team::class);
        $league_team_away->method('get_captain')->willReturn(2);

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [100, 456, 2026, $league_team_home],
                [200, 456, 2026, $league_team_away],
            ]);

        $captain1 = $this->createMock(Player::class);
        $captain1->method('get_email')->willReturn('captain1@example.com');
        $captain2 = $this->createMock(Player::class);
        $captain2->method('get_email')->willReturn('captain2@example.com');

        $this->player_repository->method('find')
            ->willReturnMap([
                [1, 'id', $captain1],
                [2, 'id', $captain2],
            ]);

        $emails = $this->service->get_fixture_emails($fixture);
        $this->assertEquals(['captain1@example.com', 'captain2@example.com'], $emails);
    }

    public function test_get_fixture_emails_tournament(): void {
        $fixture = $this->create_test_fixture();

        $league = $this->createMock(League::class);
        $league->method('get_competition_type')->willReturn('tournament');
        $this->league_repository->method('find_by_id')->willReturn($league);

        $player1 = $this->createMock(Player::class);
        $player1->method('get_email')->willReturn('player1@example.com');
        $player2 = $this->createMock(Player::class);
        $player2->method('get_email')->willReturn('player2@example.com');
        $player3 = $this->createMock(Player::class);
        $player3->method('get_email')->willReturn('player3@example.com');

        $this->player_repository->method('find_by_team')
            ->willReturnMap([
                [100, [$player1, $player2]],
                [200, [$player3]],
            ]);

        $emails = $this->service->get_fixture_emails($fixture);
        $this->assertEquals(['player1@example.com', 'player2@example.com', 'player3@example.com'], $emails);
    }

    public function test_get_fixture_emails_tournament_home_only(): void {
        $fixture = $this->create_test_fixture();

        $league = $this->createMock(League::class);
        $league->method('get_competition_type')->willReturn('tournament');
        $this->league_repository->method('find_by_id')->willReturn($league);

        $player1 = $this->createMock(Player::class);
        $player1->method('get_email')->willReturn('player1@example.com');
        $player2 = $this->createMock(Player::class);
        $player2->method('get_email')->willReturn('player2@example.com');

        $this->player_repository->method('find_by_team')
            ->willReturnMap([
                [100, [$player1, $player2]],
            ]);

        $emails = $this->service->get_fixture_emails($fixture, 'home');
        $this->assertEquals(['player1@example.com', 'player2@example.com'], $emails);
    }

    public function test_send_event_fixtures_sends_mail_contract_and_skips_teams_without_email(): void {
        $this->app->site_name = 'Test Site';
        $this->app->site_url  = 'https://example.com';
        $this->app->expects($this->once())
            ->method('get_confirmation_email')
            ->with('league')
            ->willReturn('secretary@example.com');

        $league        = $this->create_mock_league();
        $team_with_email = (object) [
            'id'           => 1,
            'contactemail' => 'captain@example.com',
            'captain'      => 55,
            'captain_name' => 'Jane Captain',
            'season'       => '2026',
            'title'        => 'Leigh A',
            'club_id'      => 10,
        ];
        $team_without_email = (object) [
            'id'           => 2,
            'contactemail' => '',
            'captain'      => 56,
            'captain_name' => 'No Email',
            'season'       => '2026',
            'title'        => 'Leigh B',
            'club_id'      => 11,
        ];
        $this->league_query_service->expects( $this->once() )
            ->method( 'get_league_teams' )
            ->with( $league, [ 'get_details' => true ] )
            ->willReturn( [ $team_with_email, $team_without_email ] );
        $fixture = Fixture_Factory::from_object( (object) [
            'id'         => 9001,
            'league_id'  => 456,
            'home_team'  => '1',
            'away_team'  => '3',
            'date'       => '2026-10-02 00:00:00',
            'start_time' => '19:30',
            'season'     => '2026',
            'match_day'  => 1,
        ] );
        $this->league_query_service->expects( $this->once() )
            ->method( 'get_matches' )
            ->with( $league, [ 'final' => '', 'team_id' => 1 ] )
            ->willReturn( [ $fixture ] );
        $this->league_repository->method('find_by_id')->willReturn($league);

        $row = new Send_Fixtures_Match_Row_View_Model( round_display: '1', date_display: '02 Oct 26', is_home: true );
        $this->presenter->expects($this->once())
            ->method('present_send_fixtures_match')
            ->with($this->identicalTo($fixture), 1)
            ->willReturn($row);

        $club                   = Club_Test_Factory::create(id: 10, name: 'Leigh Tennis Club');
        $club->match_secretary  = (object) [
            'email'        => 'ms@example.com',
            'display_name' => 'Match Sec',
        ];
        $this->club_repository->method('find_by_id')->willReturn($club);

        $event = new Event( (object) [ 'id' => 93, 'name' => 'Summer League', 'seasons' => '{"2026":{"name":"2026"}}' ] );
        $event->competition = (object) ['type' => Competition_Type::LEAGUE];
        $this->event_repository->expects($this->once())->method('query_leagues')->with(93, $this->anything())
            ->willReturn([(object) ['id' => 456]]);

        $captured_vars = null;
        $captured_template = null;
        $this->view_renderer->method('render_to_string')
            ->willReturnCallback(function(string $template, array $vars) use (&$captured_template, &$captured_vars) {
                $captured_template = $template;
                $captured_vars     = $vars;
                return 'Rendered notification';
            });

        $GLOBALS['wp_mail_calls'] = [];
        $sent = $this->service->send_event_fixtures($event);

        $this->assertTrue($sent);
        $this->assertSame('email/send-fixtures', $captured_template);
        $this->assertIsArray($captured_vars);
        $this->assertSame('Summer League', $captured_vars['competition']);
        $this->assertSame('Jane Captain', $captured_vars['captain']);
        $this->assertSame('2026', $captured_vars['season']);
        $this->assertSame([$row], $captured_vars['matches']);
        $this->assertSame('https://example.com', $captured_vars['site_url']);
        $this->assertSame('https://example.com/league/division-1/2026/team/leigh-a', $captured_vars['action_url']);
        $this->assertSame('Test Site', $captured_vars['organisation']);
        $this->assertSame('secretary@example.com', $captured_vars['contact_email']);

        $this->assertCount(1, $GLOBALS['wp_mail_calls']);
        $mail = $GLOBALS['wp_mail_calls'][0];
        $this->assertSame('Jane Captain <captain@example.com>', $mail['to']);
        $this->assertSame('Test Site - Division 1 - Season 2026 - Fixtures - Leigh A', $mail['subject']);
        $this->assertSame('Rendered notification', $mail['message']);
        $this->assertContains('From: League Secretary <secretary@example.com>', $mail['headers']);
        $this->assertContains('cc: League Secretary <secretary@example.com>', $mail['headers']);
        $this->assertContains('cc: Match Sec <ms@example.com>', $mail['headers']);
    }

    public function test_send_event_fixtures_returns_false_when_no_team_has_email(): void {
        $this->app->method('get_confirmation_email')->willReturn('secretary@example.com');

        $league = $this->create_mock_league();
        $this->league_query_service->expects( $this->once() )
            ->method( 'get_league_teams' )
            ->with( $league, [ 'get_details' => true ] )
            ->willReturn([
            (object) [
                'id'           => 2,
                'contactemail' => '',
                'captain'      => 'No Email',
                'season'       => '2026',
                'title'        => 'Leigh B',
                'club_id'      => 11,
            ],
        ]);
        $this->league_repository->method('find_by_id')->willReturn($league);

        $event              = $this->createMock(Event::class);
        $event->id           = 93;
        $event->name        = 'Summer League';
        $event->competition = (object) ['type' => 'league'];
        $this->event_repository->method('query_leagues')->willReturn([(object) ['id' => 456]]);

        $GLOBALS['wp_mail_calls'] = [];
        $this->assertFalse($this->service->send_event_fixtures($event));
        $this->assertSame([], $GLOBALS['wp_mail_calls']);
    }

    public function test_send_event_fixtures_skips_league_when_repository_returns_null(): void {
        $this->league_repository->method('find_by_id')->willReturn(null);

        $event              = $this->createMock(Event::class);
        $event->id           = 93;
        $event->competition = (object) ['type' => 'league'];
        $this->event_repository->method('query_leagues')->willReturn([(object) ['id' => 99]]);

        $GLOBALS['wp_mail_calls'] = [];
        $this->assertFalse($this->service->send_event_fixtures($event));
        $this->assertSame([], $GLOBALS['wp_mail_calls']);
    }
}
}
