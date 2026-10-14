<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Public\Shortcodes_Email;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

#[AllowMockObjectsWithoutExpectations]
class Email_Shortcode_Adapter_Test extends TestCase {
    private Fixture_Repository_Interface $fixture_repository;
    private Email_Shortcode_Presenter $presenter;
    private Shortcode_Template_Renderer|MockObject $template_renderer;
    private Email_Shortcode_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->presenter          = $this->createStub( Email_Shortcode_Presenter::class );
        $this->template_renderer  = $this->createMock( Shortcode_Template_Renderer::class );

        $this->adapter = new Email_Shortcode_Adapter(
            $this->fixture_repository,
            $this->presenter,
            $this->template_renderer
        );
    }

    public function test_show_match_notification_returns_error_when_match_not_found(): void {
        $this->template_renderer->method( 'render_alert' )->willReturn( '<div class="alert--danger">Match not found</div>' );

        $output = $this->adapter->show_match_notification( [ 'match' => 99999 ] );
        $this->assertStringContainsString( 'Match not found', $output );

        $output_empty = $this->adapter->show_match_notification( [] );
        $this->assertStringContainsString( 'Match not found', $output_empty );
    }

    public function test_show_match_notification_renders_template_with_presented_vars(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 101, 'league_id' => 1 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $this->presenter->method( 'present_match_notification' )->willReturn( [
            'match'        => $fixture,
            'template'     => 'custom',
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'match-notification-custom', $this->isArray(), 'email' )
            ->willReturn( 'rendered_match_notification_html' );

        $output = $this->adapter->show_match_notification( [ 'match' => 101, 'template' => 'custom' ] );
        $this->assertSame( 'rendered_match_notification_html', $output );
    }

    public function test_show_result_notification_returns_error_when_match_not_found(): void {
        $this->template_renderer->method( 'render_alert' )->willReturn( '<div class="alert--danger">Match not found</div>' );

        $output = $this->adapter->show_result_notification( [ 'match' => 99999 ] );
        $this->assertStringContainsString( 'Match not found', $output );
    }

    public function test_show_result_notification_renders_template(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 202, 'league_id' => 1 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $this->presenter->method( 'present_result_notification' )->willReturn( [
            'match'        => $fixture,
            'organisation' => 'Test League',
            'action_url'   => 'https://example.com/admin',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'result-notification', $this->isArray(), 'email' )
            ->willReturn( 'rendered_result_notification_html' );

        $output = $this->adapter->show_result_notification( [ 'match' => 202 ] );
        $this->assertSame( 'rendered_result_notification_html', $output );
    }

    public function test_show_captain_result_notification_and_alias_render_template(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 303, 'league_id' => 1 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $this->presenter->method( 'present_captain_result_notification' )->willReturn( [
            'match'        => $fixture,
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->exactly( 2 ) )
            ->method( 'render' )
            ->with( 'result-notification', $this->isArray(), 'email' )
            ->willReturn( 'rendered_captain_result_html' );

        $output1 = $this->adapter->show_captain_result_notification( [ 'match' => 303 ] );
        $this->assertSame( 'rendered_captain_result_html', $output1 );

        $output2 = $this->adapter->show_result_notification_captain( [ 'match' => 303 ] );
        $this->assertSame( 'rendered_captain_result_html', $output2 );
    }

    public function test_show_result_outstanding_notification_renders_template_with_tournament_fallback(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 404, 'league_id' => 1 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $match_obj = (object) [
            'league' => (object) [
                'event' => (object) [
                    'competition' => (object) [
                        'type' => 'tournament',
                    ],
                ],
            ],
        ];

        $this->presenter->method( 'present_result_outstanding_notification' )->willReturn( [
            'match'        => $match_obj,
            'organisation' => 'Test League',
            'template'     => 'tournament',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'match-result-pending-tournament', $this->isArray(), 'email' )
            ->willReturn( 'rendered_outstanding_html' );

        $output = $this->adapter->show_result_outstanding_notification( [ 'match' => 404 ] );
        $this->assertSame( 'rendered_outstanding_html', $output );
    }

    public function test_show_club_player_notification_renders_template(): void {
        $this->presenter->method( 'present_club_player_notification' )->willReturn( [
            'club'         => 'leigh-tennis-club',
            'player'       => 'john-doe',
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'club-player-notification', $this->isArray(), 'email' )
            ->willReturn( 'rendered_club_player_html' );

        $output = $this->adapter->show_club_player_notification( [
            'club'   => 'leigh-tennis-club',
            'player' => 'john-doe',
        ] );
        $this->assertSame( 'rendered_club_player_html', $output );
    }

    public function test_show_match_date_change_notification_renders_template(): void {
        $fixture = Fixture_Factory::from_object( (object) [ 'id' => 505, 'league_id' => 1 ] );
        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $this->presenter->method( 'present_match_date_change_notification' )->willReturn( [
            'match'        => $fixture,
            'organisation' => 'Test League',
            'delay'        => true,
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'match-date-change-notification', $this->isArray(), 'email' )
            ->willReturn( 'rendered_date_change_html' );

        $output = $this->adapter->show_match_date_change_notification( [ 'match' => 505, 'delay' => true ] );
        $this->assertSame( 'rendered_date_change_html', $output );
    }

    public function test_show_team_withdrawn_validates_attributes_and_renders(): void {
        $this->template_renderer->method( 'render_alert' )->willReturnCallback(
            fn( string $msg ): string => '<div class="alert--danger">' . $msg . '</div>'
        );

        $this->assertStringContainsString( 'Team not supplied', $this->adapter->show_team_withdrawn( [] ) );
        $this->assertStringContainsString( 'Team not found', $this->adapter->show_team_withdrawn( [ 'team' => 99999 ] ) );

        $team   = $this->createStub( Team::class );
        $league = $this->createStub( League::class );
        $GLOBALS['wp_stubs_teams'][10]   = $team;
        $GLOBALS['wp_stubs_leagues'][20] = $league;

        $this->assertStringContainsString( 'League not supplied', $this->adapter->show_team_withdrawn( [ 'team' => 10 ] ) );
        $this->assertStringContainsString( 'Season not supplied', $this->adapter->show_team_withdrawn( [ 'team' => 10, 'league' => 20 ] ) );

        $this->presenter->method( 'present_team_withdrawn_email' )->willReturn( [
            'team'         => $team,
            'league'       => $league,
            'season'       => '2026',
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->exactly( 2 ) )
            ->method( 'render' )
            ->with( 'team-withdrawn', $this->isArray(), 'email' )
            ->willReturn( 'rendered_withdrawn_html' );

        $output1 = $this->adapter->show_team_withdrawn( [ 'team' => 10, 'league' => 20, 'season' => '2026' ] );
        $this->assertSame( 'rendered_withdrawn_html', $output1 );

        $output2 = $this->adapter->show_withdrawn_team( [ 'team' => 10, 'league' => 20, 'season' => '2026' ] );
        $this->assertSame( 'rendered_withdrawn_html', $output2 );

        unset( $GLOBALS['wp_stubs_teams'][10], $GLOBALS['wp_stubs_leagues'][20] );
    }

    public function test_show_withdrawn_team_match_renders_template(): void {
        $this->presenter->method( 'present_withdrawn_team_match' )->willReturn( [
            'event'        => 'Division 1',
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'match-team-withdrawn', $this->isArray(), 'email' )
            ->willReturn( 'rendered_withdrawn_match_html' );

        $output = $this->adapter->show_withdrawn_team_match( [ 'event' => 'Division 1' ] );
        $this->assertSame( 'rendered_withdrawn_match_html', $output );
    }

    public function test_show_event_constitution_validates_event_and_renders(): void {
        $this->template_renderer->method( 'render_alert' )->willReturnCallback(
            fn( string $msg ): string => '<div class="alert--danger">' . $msg . '</div>'
        );

        $this->assertStringContainsString( 'Event not found', $this->adapter->show_event_constitution( [] ) );

        $event = $this->createStub( Event::class );
        $event->method( 'get_leagues' )->willReturn( [] );
        $GLOBALS['wp_stubs_events'][30] = $event;

        $this->presenter->method( 'present_event_constitution' )->willReturn( [
            'event'        => $event,
            'organisation' => 'Test League',
        ] );

        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'constitution', $this->isArray(), 'event' )
            ->willReturn( 'rendered_constitution_html' );

        $output = $this->adapter->show_event_constitution( [ 'id' => 30, 'season' => '2026' ] );
        $this->assertSame( 'rendered_constitution_html', $output );

        unset( $GLOBALS['wp_stubs_events'][30] );
    }

    public function test_shortcodes_email_facade_delegates_to_container_adapter(): void {
        $app       = $this->createStub( RacketManager::class );
        $container = new Simple_Container();
        $adapter   = $this->createMock( Email_Shortcode_Adapter::class );

        $adapter->expects( $this->once() )->method( 'show_match_notification' )->with( [ 'match' => 1 ] )->willReturn( 'match_notif' );
        $adapter->expects( $this->once() )->method( 'show_result_notification' )->with( [ 'match' => 2 ] )->willReturn( 'result_notif' );
        $adapter->expects( $this->once() )->method( 'show_captain_result_notification' )->with( [ 'match' => 3 ] )->willReturn( 'capt_notif' );
        $adapter->expects( $this->once() )->method( 'show_result_outstanding_notification' )->with( [ 'match' => 4 ] )->willReturn( 'out_notif' );
        $adapter->expects( $this->once() )->method( 'show_club_player_notification' )->with( [ 'club' => 'a' ] )->willReturn( 'player_notif' );
        $adapter->expects( $this->once() )->method( 'show_match_date_change_notification' )->with( [ 'match' => 5 ] )->willReturn( 'date_notif' );
        $adapter->expects( $this->once() )->method( 'show_team_withdrawn' )->with( [ 'team' => 6 ] )->willReturn( 'team_notif' );
        $adapter->expects( $this->once() )->method( 'show_withdrawn_team_match' )->with( [ 'event' => 'b' ] )->willReturn( 'withdrawn_match' );
        $adapter->expects( $this->once() )->method( 'show_event_constitution' )->with( [ 'id' => 7 ] )->willReturn( 'constitution' );

        $container->set( 'email_shortcode_adapter', fn() => $adapter );
        $app->container = $container;

        $facade = new Shortcodes_Email( $app );
        $this->assertSame( 'match_notif', $facade->show_match_notification( [ 'match' => 1 ] ) );
        $this->assertSame( 'result_notif', $facade->show_result_notification( [ 'match' => 2 ] ) );
        $this->assertSame( 'capt_notif', $facade->show_captain_result_notification( [ 'match' => 3 ] ) );
        $this->assertSame( 'out_notif', $facade->show_result_outstanding_notification( [ 'match' => 4 ] ) );
        $this->assertSame( 'player_notif', $facade->show_club_player_notification( [ 'club' => 'a' ] ) );
        $this->assertSame( 'date_notif', $facade->show_match_date_change_notification( [ 'match' => 5 ] ) );
        $this->assertSame( 'team_notif', $facade->show_team_withdrawn( [ 'team' => 6 ] ) );
        $this->assertSame( 'withdrawn_match', $facade->show_withdrawn_team_match( [ 'event' => 'b' ] ) );
        $this->assertSame( 'constitution', $facade->show_event_constitution( [ 'id' => 7 ] ) );
    }

    public function test_adapter_and_facade_have_no_legacy_cycle_or_service_locator(): void {
        $adapter = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Shortcodes/Email_Shortcode_Adapter.php' );
        $facade  = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Public/Shortcodes_Email.php' );

        $this->assertStringNotContainsString( 'Racketmanager\\Public\\', $adapter );
        $this->assertStringNotContainsString( 'legacy_handler', $adapter );
        $this->assertStringNotContainsString( 'container', $adapter );
        $this->assertSame( 0, substr_count( $facade, 'load_template' ) );
        $this->assertSame( 0, substr_count( $facade, 'fixture_repository' ) );
    }
}
