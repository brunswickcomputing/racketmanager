<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_Test_Container;

#[AllowMockObjectsWithoutExpectations]
class Email_Shortcode_Adapter_Test extends TestCase {
    use Creates_Test_Container;

    private Fixture_Repository_Interface $fixture_repository;
    private Email_Shortcode_Presenter $presenter;
    private Shortcode_Template_Renderer|MockObject $template_renderer;
    private Email_Shortcode_Adapter $adapter;
    private \Racketmanager\Services\Competition\Event_Query_Service|MockObject $event_query_service;
    private Team_Repository_Interface $team_repository;
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private Competition_Repository_Interface $competition_repository;

    protected function setUp(): void {
        parent::setUp();

        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $this->presenter          = $this->createStub( Email_Shortcode_Presenter::class );
        $this->template_renderer  = $this->createMock( Shortcode_Template_Renderer::class );
        $this->event_query_service = $this->createMock( \Racketmanager\Services\Competition\Event_Query_Service::class );
        $this->team_repository    = $this->createStub( Team_Repository_Interface::class );
        $this->league_repository  = $this->createStub( League_Repository_Interface::class );
        $this->event_repository   = $this->createStub( Event_Repository_Interface::class );
        $this->competition_repository = $this->createMock( Competition_Repository_Interface::class );

        $this->adapter = new Email_Shortcode_Adapter(
            $this->fixture_repository,
            $this->presenter,
            $this->template_renderer,
            $this->event_query_service,
            $this->createStub( \Racketmanager\Services\Competition\League_Query_Service::class ),
            $this->team_repository,
            $this->league_repository,
            $this->event_repository,
            $this->competition_repository
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
        $this->team_repository->method( 'find_by_id' )->willReturnCallback(
            static fn( int|string|null $id ) => 10 === (int) $id ? $team : null
        );
        $this->league_repository->method( 'find_by_id' )->willReturnCallback(
            static fn( $id ) => 20 === (int) $id ? $league : null
        );

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
    }

    public function test_show_team_withdrawn_resolves_league_by_name_and_passes_entities_through(): void {
        $team   = $this->createStub( Team::class );
        $league = $this->createStub( League::class );
        $this->league_repository->method( 'find_by_name' )->willReturnCallback(
            static fn( string $name ) => 'Division 1' === $name ? $league : null
        );
        $this->presenter->method( 'present_team_withdrawn_email' )->willReturnCallback(
            static fn( $passed_team, $passed_league ) => [ 'team' => $passed_team, 'league' => $passed_league ]
        );
        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'team-withdrawn', [ 'team' => $team, 'league' => $league ], 'email' )
            ->willReturn( 'ok' );

        $this->assertSame( 'ok', $this->adapter->show_team_withdrawn( [ 'team' => $team, 'league' => 'Division 1', 'season' => '2026' ] ) );
    }

    public function test_show_team_withdrawn_hydrates_raw_objects_like_the_legacy_helpers(): void {
        $league_query_service = $this->createMock( \Racketmanager\Services\Competition\League_Query_Service::class );
        $adapter              = new Email_Shortcode_Adapter(
            $this->fixture_repository,
            $this->presenter,
            $this->template_renderer,
            $this->event_query_service,
            $league_query_service,
            $this->team_repository,
            $this->league_repository,
            $this->event_repository,
            $this->competition_repository
        );

        $event = $this->createStub( Event::class );
        $event->id = 30;
        $this->event_repository->method( 'find_by_id' )->willReturnCallback(
            static fn( $id ) => 30 === (int) $id ? $event : null
        );

        $captured = [];
        $this->presenter->method( 'present_team_withdrawn_email' )->willReturnCallback(
            static function ( $passed_team, $passed_league ) use ( &$captured ) {
                $captured = [ $passed_team, $passed_league ];
                return [];
            }
        );
        $this->template_renderer->method( 'render' )->willReturn( 'ok' );

        // Object league: League_Factory::from_object() + event via event_id + set_season( $league, false, true ),
        // followed by the shortcode's own set_season( $league, '2026' ).
        $set_season_calls = [];
        $league_query_service->expects( $this->exactly( 2 ) )
            ->method( 'set_season' )
            ->willReturnCallback( static function ( object $league, false|string $season = false, bool $force = false ) use ( &$set_season_calls ): void {
                $set_season_calls[] = [ $league, $season, $force ];
            } );

        $raw_team   = (object) [ 'id' => 7, 'title' => 'Raw Team' ];
        $raw_league = (object) [ 'id' => 5, 'title' => 'Raw League', 'event_id' => 30 ];

        $this->assertSame( 'ok', $adapter->show_team_withdrawn( [ 'team' => $raw_team, 'league' => $raw_league, 'season' => '2026' ] ) );
        $this->assertInstanceOf( Team::class, $captured[0] );
        $this->assertSame( 7, (int) $captured[0]->id );
        $this->assertInstanceOf( League::class, $captured[1] );
        $this->assertSame( 5, (int) $captured[1]->id );
        $this->assertSame( $event, $captured[1]->event );
        $this->assertCount( 2, $set_season_calls );
        $this->assertSame( [ $captured[1], false, true ], $set_season_calls[0] );
        $this->assertSame( [ $captured[1], '2026', false ], $set_season_calls[1] );
    }

    public function test_show_team_withdrawn_reports_league_object_without_resolvable_event_as_not_found(): void {
        $this->template_renderer->method( 'render_alert' )->willReturnCallback(
            fn( string $msg ): string => '<div class="alert--danger">' . $msg . '</div>'
        );
        $this->event_repository->method( 'find_by_id' )->willReturn( null );

        $raw_league = (object) [ 'id' => 5, 'title' => 'Raw League', 'event_id' => 99 ];
        $output     = $this->adapter->show_team_withdrawn( [ 'team' => $this->createStub( Team::class ), 'league' => $raw_league, 'season' => '2026' ] );

        $this->assertStringContainsString( 'League not found', $output );
    }

    public function test_show_event_constitution_hydrates_raw_event_object(): void {
        $this->event_query_service->method( 'get_leagues' )->willReturn( [] );
        $this->competition_repository->expects( $this->never() )->method( 'find_by_id' );
        $captured = null;
        $this->presenter->method( 'present_event_constitution' )->willReturnCallback(
            static function ( $passed_event ) use ( &$captured ) {
                $captured = $passed_event;
                return [];
            }
        );
        $this->template_renderer->expects( $this->once() )->method( 'render' )->willReturn( 'ok' );

        $competition     = $this->createStub( Competition::class );
        $competition->id = 42;
        $raw_event       = (object) [
            'id'          => 30,
            'name'        => 'Raw Event',
            'type'        => 'league',
            'competition' => $competition,
        ];

        $this->assertSame( 'ok', $this->adapter->show_event_constitution( [ 'id' => $raw_event ] ) );
        $this->assertInstanceOf( Event::class, $captured );
        $this->assertSame( 30, (int) $captured->id );
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
        $this->event_repository->method( 'find_by_id' )->willReturnCallback(
            static fn( $id ) => 30 === (int) $id ? $event : null
        );
        $this->event_query_service->expects( $this->once() )->method( 'get_leagues' )->with( $event )->willReturn( [] );

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
    }

    public function test_show_event_constitution_hydrates_raw_event_with_competition(): void {
        $competition_id = 42;
        $competition = new Competition(
            'Test Championship',
            'league',
            'Open',
            [
                '2025' => [ 'name' => '2025' ],
                '2026' => [ 'name' => '2026' ],
            ],
            [ 'mode' => 'championship' ],
            $competition_id
        );
        $competition->set_current_season( '2025' );
        $raw_event = (object) [
            'id'             => 10,
            'name'           => 'Raw Championship Event',
            'type'           => 'league',
            'competition_id' => (string) $competition_id,
            'seasons'        => [
                '2025' => [ 'name' => '2025' ],
                '2026' => [ 'name' => '2026' ],
            ],
        ];

        $this->competition_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( $competition_id )
            ->willReturn( $competition );

        $this->event_query_service->expects( $this->once() )
            ->method( 'get_leagues' )
            ->willReturn( [] );
        $captured = null;
        $this->presenter->method( 'present_event_constitution' )
            ->willReturnCallback(
                static function ( Event $event ) use ( &$captured ): array {
                    $captured = $event;
                    return [];
                }
            );
        $this->template_renderer->expects( $this->once() )
            ->method( 'render' )
            ->with( 'constitution', $this->isArray(), 'event' )
            ->willReturn( 'rendered_constitution_html' );

        $output = $this->adapter->show_event_constitution( [ 'id' => $raw_event ] );

        $this->assertSame( 'rendered_constitution_html', $output );
        $this->assertInstanceOf( Event::class, $captured );
        $this->assertSame( $competition, $captured->competition );
        $this->assertSame( '2025', $captured->current_season['name'] );
        $this->assertTrue( $captured->is_championship );
    }

    public function test_show_event_constitution_reports_unknown_event_id(): void {
        $this->template_renderer->method( 'render_alert' )->willReturnCallback(
            fn( string $msg ): string => '<div class="alert--danger">' . $msg . '</div>'
        );
        $this->template_renderer->expects( $this->never() )->method( 'render' );

        $this->assertStringContainsString( 'Event not found', $this->adapter->show_event_constitution( [ 'id' => 404 ] ) );
    }

    public function test_adapter_has_no_legacy_cycle_or_service_locator(): void {
        $adapter = (string) file_get_contents( RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Shortcodes/Email_Shortcode_Adapter.php' );

        $this->assertStringNotContainsString( 'Racketmanager\\Public\\', $adapter );
        $this->assertStringNotContainsString( 'legacy_handler', $adapter );
        $this->assertStringNotContainsString( 'container', $adapter );
    }
}
