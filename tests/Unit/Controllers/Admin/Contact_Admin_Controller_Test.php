<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Admin_Page_Context;
use Racketmanager\Admin\Controllers\Contact_Admin_Controller;
use Racketmanager\Admin\View_Models\Contact_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Contact_Admin_Controller_Test extends TestCase {

    private Tournament_Service $tournament_service;
    private League_Service $league_service;
    private Competition_Service $competition_service;
    private Tournament_Contact_Action_Dispatcher $tournament_dispatcher;
    private League_Contact_Action_Dispatcher $league_dispatcher;
    private Action_Guard_Interface $guard;
    private Competition_Notification_Service $notification_service;
    private Contact_Admin_Controller $controller;

    protected function setUp(): void {
        $this->tournament_service   = $this->createMock( Tournament_Service::class );
        $this->league_service       = $this->createMock( League_Service::class );
        $this->competition_service  = $this->createMock( Competition_Service::class );
        $this->guard                = $this->createMock( Action_Guard_Interface::class );
        $this->notification_service = $this->createMock( Competition_Notification_Service::class );

        $this->tournament_dispatcher = new Tournament_Contact_Action_Dispatcher(
            $this->tournament_service,
            $this->guard
        );

        $this->league_dispatcher = new League_Contact_Action_Dispatcher(
            $this->league_service,
            $this->competition_service,
            $this->guard,
            $this->notification_service
        );

        $this->controller = new Contact_Admin_Controller(
            $this->tournament_service,
            $this->league_service,
            $this->competition_service,
            $this->tournament_dispatcher,
            $this->league_dispatcher,
            $this->guard
        );
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    private function tournament( int $id = 15, string $name = 'Spring Open', string $season = '2026' ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );
        /** @var Tournament $tournament */
        $tournament = $reflection->newInstanceWithoutConstructor();

        $ref_id = $reflection->getProperty( 'id' );
        $ref_id->setValue( $tournament, $id );

        $ref_name = $reflection->getProperty( 'name' );
        $ref_name->setValue( $tournament, $name );

        $ref_season = $reflection->getProperty( 'season' );
        $ref_season->setValue( $tournament, $season );

        return $tournament;
    }

    private function league( int $id = 5, string $name = 'Premier League', string $season = '2025' ): League {
        $league = $this->createMock( League::class );
        $league->id = $id;
        $league->name = $name;
        $league->method( 'get_season' )->willReturn( $season );
        return $league;
    }

    private function competition( int $id = 12, string $name = 'Main Competition', string $season = '2025' ): Competition {
        $comp = $this->createMock( Competition::class );
        $comp->id = $id;
        $comp->name = $name;
        $comp->method( 'get_season' )->willReturn( $season );
        return $comp;
    }

    public function test_tournament_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $tournament = $this->tournament( 15, 'Spring Open', '2026' );

        $this->tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 15 )
            ->willReturn( $tournament );

        $this->guard->expects( self::once() )->method( 'assert_capability' )->with( 'edit_teams' );

        $result = $this->controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '15',
            ),
            array(),
            Admin_Page_Context::tournament()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'compose', $result['view_model']->tab );
        self::assertSame( 'Spring Open', $result['view_model']->tournament->name );
        self::assertSame( '2026', $result['view_model']->season );
    }

    public function test_tournament_preview_post_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $tournament = $this->tournament( 19, 'Autumn Open', '2025' );
        $rendered_preview = '<html lang=""><body>Preview body</body></html>';

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

        $this->tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 19 )
            ->willReturn( $tournament );

        $this->tournament_service
            ->expects( self::once() )
            ->method( 'get_contact_preview' )
            ->with( 19, '2025', 'Final reminder', 'Hello players', array( 1 => 'Paragraph 1' ), 'Regards' )
            ->willReturn( $rendered_preview );

        $result = $this->controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '19',
            ),
            array(
                'tournament_id'      => '19',
                'contactTeamPreview' => '1',
                'season'             => '2025',
                'contactTitle'       => 'Final reminder',
                'contactIntro'       => 'Hello players',
                'contactBody'        => array( 1 => 'Paragraph 1' ),
                'contactClose'       => 'Regards',
            ),
            Admin_Page_Context::tournament()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'preview', $result['view_model']->tab );
        self::assertSame( 'Final reminder', $result['view_model']->email_title );
        self::assertSame( $rendered_preview, $result['view_model']->email_message );
    }

    public function test_tournament_send_post_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $this->tournament_service
            ->expects( self::once() )
            ->method( 'contact_teams' )
            ->with( 27, '<p>Rendered preview</p>', false )
            ->willReturn( true );

        $result = $this->controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '27',
            ),
            array(
                'tournament_id' => '27',
                'contactTeam'   => '1',
                'emailMessage'  => '<p>Rendered preview</p>',
            ),
            Admin_Page_Context::tournament()
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=contact', $result['redirect'] );
        self::assertStringContainsString( 'tournament=27', $result['redirect'] );
        self::assertSame( 'Email sent to players', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_tournament_send_active_post_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $this->tournament_service
            ->expects( self::once() )
            ->method( 'contact_teams' )
            ->with( 27, '<p>Rendered preview</p>', true )
            ->willReturn( true );

        $result = $this->controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '27',
            ),
            array(
                'tournament_id'     => '27',
                'contactTeamActive' => '1',
                'emailMessage'      => '<p>Rendered preview</p>',
            ),
            Admin_Page_Context::tournament()
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-tournaments', $result['redirect'] );
        self::assertStringContainsString( 'view=contact', $result['redirect'] );
        self::assertStringContainsString( 'tournament=27', $result['redirect'] );
        self::assertSame( 'Email sent to players', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_capability_failure_never_reaches_dispatcher(): void {
        $this->guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $this->guard->expects( self::never() )->method( 'assert_allowed' );
        $this->tournament_service->expects( self::never() )->method( 'contact_teams' );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Permission denied' );

        $this->controller->contact_page(
            array( 'page' => 'racketmanager-tournaments', 'tournament' => '27' ),
            array( 'contactTeam' => '1' ),
            Admin_Page_Context::tournament()
        );
    }

    public function test_league_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $league = $this->league( 5, 'Premier League', '2025' );

        $this->league_service
            ->expects( self::once() )
            ->method( 'get_league' )
            ->with( 5 )
            ->willReturn( $league );

        $result = $this->controller->contact_page(
            array(
                'page'      => 'racketmanager-leagues',
                'view'      => 'contact',
                'league_id' => '5',
            ),
            array(),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'compose', $result['view_model']->tab );
        self::assertSame( $league, $result['view_model']->league );
        self::assertSame( '2025', $result['view_model']->season );
    }

    public function test_league_preview_post_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league = $this->league( 5, 'Premier League', '2025' );
        $preview_html = '<p>League Preview</p>';

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

        $this->league_service
            ->expects( self::once() )
            ->method( 'get_league' )
            ->with( 5 )
            ->willReturn( $league );

        $this->league_service
            ->expects( self::once() )
            ->method( 'get_contact_preview' )
            ->with( $league, '2025', 'League Notice', 'Hello Captains', array(), 'Thanks' )
            ->willReturn( $preview_html );

        $result = $this->controller->contact_page(
            array(
                'page'      => 'racketmanager-leagues',
                'view'      => 'contact',
                'league_id' => '5',
            ),
            array(
                'league_id'          => '5',
                'contactTeamPreview' => '1',
                'season'             => '2025',
                'contactTitle'       => 'League Notice',
                'contactIntro'       => 'Hello Captains',
                'contactBody'        => array(),
                'contactClose'       => 'Thanks',
            ),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'preview', $result['view_model']->tab );
        self::assertSame( $preview_html, $result['view_model']->email_message );
    }

    public function test_league_send_post_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $league = $this->league( 5, 'Premier League', '2025' );

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $this->league_service
            ->expects( self::once() )
            ->method( 'get_league' )
            ->with( 5 )
            ->willReturn( $league );

        $this->notification_service
            ->expects( self::once() )
            ->method( 'contact_league_teams' )
            ->with( $league, '2025', '<p>League Preview</p>' )
            ->willReturn( true );

        $result = $this->controller->contact_page(
            array(
                'page'      => 'racketmanager-leagues',
                'view'      => 'contact',
                'league_id' => '5',
            ),
            array(
                'league_id'    => '5',
                'contactTeam'  => '1',
                'season'       => '2025',
                'emailMessage' => '<p>League Preview</p>',
            ),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertStringContainsString( 'view=contact', $result['redirect'] );
        self::assertStringContainsString( 'league_id=5', $result['redirect'] );
        self::assertSame( 'Email sent to captains', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_competition_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $competition = $this->competition( 12, 'Main Competition', '2025' );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 12 )
            ->willReturn( $competition );

        $result = $this->controller->contact_page(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'contact',
                'competition_id' => '12',
            ),
            array(),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'compose', $result['view_model']->tab );
        self::assertSame( $competition, $result['view_model']->competition );
        self::assertSame( '2025', $result['view_model']->season );
    }

    public function test_competition_preview_post_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $competition = $this->competition( 12, 'Main Competition', '2025' );
        $preview_html = '<p>Comp Preview</p>';

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 12 )
            ->willReturn( $competition );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_contact_preview' )
            ->with( $competition, '2025', 'Comp Notice', 'Hello all', array(), 'Thanks' )
            ->willReturn( $preview_html );

        $result = $this->controller->contact_page(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'contact',
                'competition_id' => '12',
            ),
            array(
                'competition_id'     => '12',
                'contactTeamPreview' => '1',
                'season'             => '2025',
                'contactTitle'       => 'Comp Notice',
                'contactIntro'       => 'Hello all',
                'contactBody'        => array(),
                'contactClose'       => 'Thanks',
            ),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'preview', $result['view_model']->tab );
        self::assertSame( $preview_html, $result['view_model']->email_message );
    }

    public function test_competition_send_post_returns_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $competition = $this->competition( 12, 'Main Competition', '2025' );

        $this->guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $this->competition_service
            ->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 12 )
            ->willReturn( $competition );

        $this->notification_service
            ->expects( self::once() )
            ->method( 'contact_teams' )
            ->with( $competition, '2025', '<p>Comp Preview</p>' )
            ->willReturn( true );

        $result = $this->controller->contact_page(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'contact',
                'competition_id' => '12',
            ),
            array(
                'competition_id' => '12',
                'contactTeam'    => '1',
                'season'         => '2025',
                'emailMessage'   => '<p>Comp Preview</p>',
            ),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'page=racketmanager-leagues', $result['redirect'] );
        self::assertStringContainsString( 'view=contact', $result['redirect'] );
        self::assertStringContainsString( 'competition_id=12', $result['redirect'] );
        self::assertSame( 'Email sent to captains', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_league_id_prioritized_over_competition_id_when_both_present(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $league = $this->league( 5, 'Premier League', '2025' );

        $this->league_service
            ->expects( self::once() )
            ->method( 'get_league' )
            ->with( 5 )
            ->willReturn( $league );

        $this->competition_service
            ->expects( self::never() )
            ->method( 'get_competition' );

        $result = $this->controller->contact_page(
            array(
                'page'           => 'racketmanager-leagues',
                'view'           => 'contact',
                'league_id'      => '5',
                'competition_id' => '12',
            ),
            array(),
            Admin_Page_Context::league()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( $league, $result['view_model']->league );
    }

    public function test_throws_when_tournament_id_missing(): void {
        $this->expectException( Tournament_Not_Found_Exception::class );
        $this->controller->contact_page(
            array( 'page' => 'racketmanager-tournaments', 'view' => 'contact' ),
            array(),
            Admin_Page_Context::tournament()
        );
    }

    public function test_throws_when_tournament_not_found(): void {
        $this->tournament_service->method( 'get_tournament' )->willReturn( null );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $this->controller->contact_page(
            array( 'page' => 'racketmanager-tournaments', 'view' => 'contact', 'tournament' => '99' ),
            array(),
            Admin_Page_Context::tournament()
        );
    }

    public function test_throws_when_league_id_missing(): void {
        $this->expectException( Invalid_Status_Exception::class );
        $this->controller->contact_page(
            array( 'page' => 'racketmanager-leagues', 'view' => 'contact' ),
            array(),
            Admin_Page_Context::league()
        );
    }

    public function test_throws_when_league_not_found(): void {
        $this->league_service->method( 'get_league' )->willReturn( null );

        $this->expectException( Invalid_Status_Exception::class );
        $this->controller->contact_page(
            array( 'page' => 'racketmanager-leagues', 'view' => 'contact', 'league_id' => '99' ),
            array(),
            Admin_Page_Context::league()
        );
    }
}
