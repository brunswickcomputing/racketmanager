<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Result_Service;
use stdClass;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
class Result_Service_Test extends TestCase {
    private $fixture_repository;
    private $team_repository;
    private $league_repository;
    private $template_renderer;
    private $mailer;
    private $service;

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_repository = $this->createMock(Fixture_Repository_Interface::class);
        $this->team_repository    = $this->createMock(Team_Repository_Interface::class);
        $this->league_repository  = $this->createMock(League_Repository_Interface::class);
        $this->template_renderer  = $this->createMock(Template_Renderer_Interface::class);
        $this->mailer             = $this->createMock(Mailer_Interface::class);
        $this->service = new Result_Service(
            $this->fixture_repository,
            $this->team_repository,
            $this->make_option_service( [
                'league' => [ 'resultConfirmation' => 'manual', 'resultConfirmationEmail' => 'test@example.com' ],
                'championship' => [ 'resultConfirmation' => 'manual' ],
            ] ),
            $this->template_renderer,
            $this->mailer,
            $this->league_repository
        );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_get_users'] );
        parent::tearDown();
    }

    private function make_option_service( array $racketmanager_options ): Option_Service {
        $option_gateway = new class( $racketmanager_options ) implements Option_Gateway_Interface {
            public function __construct( private array $racketmanager_options ) {}
            public function get_option( string $option, mixed $default_value = false ): mixed {
                return match ( $option ) {
                    'racketmanager' => $this->racketmanager_options,
                    'blogname'      => 'Test Site',
                    'siteurl'       => 'http://example.com',
                    default         => $default_value,
                };
            }
            public function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool { return true; }
            public function delete_option( string $option ): bool { return true; }
        };
        return new Option_Service( $option_gateway );
    }

    public function test_apply_to_fixture_saves_fixture(): void {
        $fixture = $this->createMock(Fixture::class);
        $result  = $this->createMock(Result::class);

        $fixture->expects($this->once())
                ->method('set_result')
                ->with($result);

        $this->fixture_repository->expects($this->once())
                                 ->method('save')
                                 ->with($fixture);

        $this->service->apply_to_fixture($fixture, $result);
    }

    public function test_apply_to_fixture_sets_confirmed_to_null_on_reset(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->confirmed = 'Y';
        $fixture = Fixture_Factory::from_object($fixture_data);

        $result = new Result(0, 0, null, null, null, false, [], []);
        $this->assertTrue($result->is_reset());

        $this->service->apply_to_fixture($fixture, $result, null);

        $this->assertNull($fixture->get_confirmed());
    }

    public function test_apply_to_fixture_does_not_auto_confirm_on_reset(): void {
        $this->service = new Result_Service(
            $this->fixture_repository,
            $this->team_repository,
            $this->make_option_service( [ 'league' => [ 'resultConfirmation' => 'auto' ] ] ),
            $this->template_renderer,
            $this->createStub( Mailer_Interface::class ),
            $this->league_repository
        );

        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture = Fixture_Factory::from_object($fixture_data);

        $result = new Result(0, 0, null, null, null, false, [], []);

        $this->service->apply_to_fixture($fixture, $result, null);

        $this->assertNull($fixture->get_confirmed());
    }

    public function test_apply_to_fixture_triggers_notifications_for_non_reset(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '1';
        $fixture_data->away_team = '2';
        $fixture = Fixture_Factory::from_object($fixture_data);

        $result = new Result(3, 1, 1, 2, 0, false, [], []);
        $this->assertFalse($result->is_reset());

        $service = $this->getMockBuilder(Result_Service::class)
            ->setConstructorArgs([
                $this->fixture_repository,
                $this->team_repository,
                $this->make_option_service( [] ),
                $this->template_renderer,
                $this->createStub( Mailer_Interface::class ),
            ])
            ->onlyMethods(['notify_favourites'])
            ->getMock();

        $service->expects($this->once())
            ->method('notify_favourites')
            ->with($fixture);

        $service->apply_to_fixture($fixture, $result, null);
    }

    public function test_apply_to_fixture_suppresses_notifications_on_reset(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture = Fixture_Factory::from_object($fixture_data);

        $result = new Result(0, 0, null, null, null, false, [], []);

        $service = $this->getMockBuilder(Result_Service::class)
            ->setConstructorArgs([
                $this->fixture_repository,
                $this->team_repository,
                $this->make_option_service( [] ),
                $this->template_renderer,
                $this->createStub( Mailer_Interface::class ),
            ])
            ->onlyMethods(['notify_favourites'])
            ->getMock();

        $service->expects($this->never())
            ->method('notify_favourites');

        $service->apply_to_fixture($fixture, $result, null);
    }

    public function test_notify_favourites_returns_early_when_league_unknown(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture = Fixture_Factory::from_object($fixture_data);

        $this->league_repository->expects($this->once())->method('find_by_id')->with(456)->willReturn(null);
        $this->template_renderer->expects($this->never())->method('render');

        $this->service->notify_favourites($fixture);
    }

    public function test_apply_to_fixture_handles_enum_competition_type(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture = Fixture_Factory::from_object( $fixture_data );

        $event = new class extends Event {
            public function __construct() {
                $this->competition = (object) [ 'type' => Competition_Type::LEAGUE ];
            }
        };

        $league = $this->createMock( League::class );
        $league->event = $event;

        $this->league_repository->expects( $this->atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 456 )
            ->willReturn( $league );

        $result = new Result( 3, 1, 1, 2, 0, false, [], [] );

        $this->service->apply_to_fixture( $fixture, $result );
        $this->assertSame( 'P', $fixture->get_confirmed() );
    }

    public function test_notify_favourites_sends_email_to_favourited_users(): void {
        $GLOBALS['wp_stubs_get_users'] = [ 42 ];

        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = '10';
        $fixture_data->away_team = '20';
        $fixture = Fixture_Factory::from_object( $fixture_data );

        $event = new class extends Event {
            public function __construct() {
                $this->id = 789;
                $this->competition = (object) [ 'type' => Competition_Type::LEAGUE ];
            }
        };

        $league = $this->createMock( League::class );
        $league->id = 456;
        $league->title = 'Division 1';
        $league->event = $event;

        $home_team = $this->createMock( Team::class );
        $home_team->method( 'get_id' )->willReturn( 10 );
        $home_team->method( 'get_club_id' )->willReturn( null );

        $away_team = $this->createMock( Team::class );
        $away_team->method( 'get_id' )->willReturn( 20 );
        $away_team->method( 'get_club_id' )->willReturn( null );

        $this->league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 456 )
            ->willReturn( $league );

        $this->team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnMap( [
                [ 10, $home_team ],
                [ 20, $away_team ],
            ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with(
                'favourite-notification',
                $this->callback( function ( array $payload ) use ( $fixture, $league, $home_team, $away_team ): bool {
                    $this->assertSame( 'Test Site - Division 1 Result Notification', $payload['email_subject'] );
                    $this->assertSame( 'test@example.com', $payload['from_email'] );
                    $this->assertSame( 'http://example.com/fixture/123', $payload['match_url'] );
                    $this->assertSame( 'http://example.com/member-account/favourites', $payload['favourite_url'] );
                    $this->assertSame( 'Test Site', $payload['organisation'] );
                    $this->assertSame( 'user42@example.test', $payload['user']->user_email );
                    $this->assertSame( $fixture, $payload['fixture'] );
                    $this->assertSame( $league, $payload['league'] );
                    $this->assertSame( $home_team, $payload['home_team'] );
                    $this->assertSame( $away_team, $payload['away_team'] );
                    return true;
                } ),
                'email'
            )
            ->willReturn( '<p>Result Notification Body</p>' );

        $this->mailer->expects( $this->once() )
            ->method( 'send' )
            ->with(
                'User 42 <user42@example.test>',
                'Test Site - Division 1 Result Notification',
                '<p>Result Notification Body</p>',
                $this->callback( function ( array $headers ): bool {
                    $this->assertContains( 'From: League Secretary <test@example.com>', $headers );
                    return true;
                } )
            );

        $this->service->notify_favourites( $fixture );
    }
}
