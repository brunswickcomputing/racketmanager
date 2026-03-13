<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Contact_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model;
use Racketmanager\Domain\Tournament;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\RacketManager;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

require_once __DIR__ . '/../../../wp-stubs.php';

final class Tournament_Contact_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    /**
     * @throws ReflectionException
     */
    public function test_get_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 15, 'Spring Open', '2026' );

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 15 )
            ->willReturn( $tournament );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );
        $guard
            ->expects( self::never() )
            ->method( 'assert_allowed' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Contact_Admin_Controller(
            $tournament_service,
            $dispatcher,
            $guard
        );

        $result = $controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '15',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Tournament_Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'compose', $result['view_model']->tab );
        self::assertSame( 'Spring Open', $result['view_model']->tournament->name );
        self::assertSame( '2026', $result['view_model']->season );
    }

    /**
     * @throws ReflectionException
     */
    public function test_preview_post_returns_view_model_and_no_redirect(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament = $this->create_tournament_instance( 19, 'Autumn Open', '2025' );
        $rendered_preview = '<html lang=""><body>Preview body</body></html>';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'get_tournament' )
            ->with( 19 )
            ->willReturn( $tournament );
        $tournament_service
            ->expects( self::once() )
            ->method( 'get_contact_preview' )
            ->with(
                19,
                '2025',
                'Final reminder',
                'Hello players',
                array(
                    1 => 'Paragraph 1',
                    2 => 'Paragraph 2',
                ),
                'Regards'
            )
            ->willReturn( $rendered_preview );
        $tournament_service
            ->expects( self::never() )
            ->method( 'contact_teams' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );
        $guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Contact_Admin_Controller(
            $tournament_service,
            $dispatcher,
            $guard
        );

        $result = $controller->contact_page(
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
                'contactBody'        => array(
                    1 => 'Paragraph 1',
                    2 => 'Paragraph 2',
                ),
                'contactClose'       => 'Regards',
            )
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertArrayNotHasKey( 'redirect', $result );
        self::assertInstanceOf( Tournament_Contact_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'preview', $result['view_model']->tab );
        self::assertSame( 'Final reminder', $result['view_model']->email_title );
        self::assertSame( 'Hello players', $result['view_model']->email_intro );
        self::assertSame( 'Paragraph 1', $result['view_model']->email_body[1] );
        self::assertSame( 'Regards', $result['view_model']->email_close );
        self::assertSame( '<html lang=""><body>Preview body</body></html>', $result['view_model']->email_message );
    }

    /**
     * @throws ReflectionException
     */
    public function test_send_post_returns_redirect_result(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $tournament_service
            ->expects( self::once() )
            ->method( 'contact_teams' )
            ->with( 27, '<p>Rendered preview</p>', false )
            ->willReturn( true );
        $tournament_service
            ->expects( self::never() )
            ->method( 'get_tournament' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_teams' );
        $guard
            ->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_contact-teams-preview', 'edit_teams' );

        $dispatcher = new Tournament_Contact_Action_Dispatcher( $tournament_service, $guard );

        $controller = new Tournament_Contact_Admin_Controller(
            $tournament_service,
            $dispatcher,
            $guard
        );

        $result = $controller->contact_page(
            array(
                'page'       => 'racketmanager-tournaments',
                'view'       => 'contact',
                'tournament' => '27',
            ),
            array(
                'tournament_id' => '27',
                'contactTeam'   => '1',
                'emailMessage'  => '<p>Rendered preview</p>',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertSame(
            'https://example.test/wp-admin/admin.php?page=racketmanager-tournaments&view=contact&tournament=27',
            $result['redirect']
        );
        self::assertSame( 'Email sent to players', $result['message'] );
        self::assertFalse( $result['message_type'] );
        self::assertArrayNotHasKey( 'view_model', $result );
    }

    /**
     * @throws ReflectionException
     */
    private function create_tournament_instance( int $id, string $name, string $season ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );

        /** @var Tournament $tournament */
        $tournament = $reflection->newInstanceWithoutConstructor();

        $this->set_property( $tournament, 'id', $id );
        $this->set_property( $tournament, 'name', $name );
        $this->set_season_property_if_present( $tournament, $season );

        return $tournament;
    }

    private function set_season_property_if_present( Tournament $tournament, string $season ): void {
        foreach ( array( 'season', 'current_season' ) as $property_name ) {
            if ( $this->has_property( $tournament, $property_name ) ) {
                $this->set_property( $tournament, $property_name, $season );
                break;
            }
        }
    }

    private function has_property( object $object, string $property_name ): bool {
        $reflection = new ReflectionObject( $object );

        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                return true;
            }

            $reflection = $reflection->getParentClass();
        }

        return false;
    }

    private function set_property( object $object, string $property_name, mixed $value ): void {
        $reflection = new ReflectionObject( $object );

        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                $property = $reflection->getProperty( $property_name );
                $property->setValue( $object, $value );
                return;
            }

            $reflection = $reflection->getParentClass();
        }

        self::fail( sprintf( 'Property "%s" not found on %s', $property_name, $object::class ) );
    }
}