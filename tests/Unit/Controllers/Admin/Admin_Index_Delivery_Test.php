<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Index_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Summary_DTO;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Index\Index_Action_Dispatcher_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Admin_Index_Delivery_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_SERVER['REQUEST_METHOD']         = 'GET';
        $GLOBALS['wp_stubs_current_user_can'] = true;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_post_dispatches_and_redirects_with_flash_and_query_context_without_rendering(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = array(
            'page'      => 'racketmanager',
            'age_group' => 'junior',
        );
        $_POST = array( 'addCompetition' => 'competition', 'competition_name' => 'Summer Cup' );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::never() )->method( 'pop' );
        $flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Competition added', false );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with(
                self::callback(
                    static function ( string $url ): bool {
                        return str_contains( $url, 'page=racketmanager' ) && str_contains( $url, 'age_group=junior' );
                    }
                )
            );

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );
        $dispatcher = $this->createMock( Index_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $_GET, $_POST )
            ->willReturn(
                new Action_Result_DTO(
                    message: 'Competition added',
                    message_type: Admin_Message_Type::SUCCESS,
                )
            );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'find_competitions_with_summary' );

        $screen = $this->create_screen(
            $competition_service,
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        $screen->display_index_page();
    }

    public function test_get_rehydrates_flash_errors_into_typed_vm_and_renders_real_template(): void {
        $_GET = array( 'page' => 'racketmanager', 'age_group' => 'junior' );

        $competition = new Competition_Summary_DTO(
            array(
                'id'           => 17,
                'name'         => 'Existing Junior League',
                'age_group'    => 'junior',
                'type'         => 'league',
                'season_count' => 2,
                'event_count'  => 3,
            )
        );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'find_competitions_with_summary' )
            ->with( 'junior', null )
            ->willReturn( array( $competition ) );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn(
                array(
                    'message'      => 'Competition already exists',
                    'message_type' => true,
                    'flash_data'   => array(
                        'errors'           => array( 'competition' => 'Competition already exists' ),
                        'competition_name' => 'Summer Cup',
                        'type'             => 'tournament',
                        'age_group'        => 'junior',
                    ),
                )
            );
        $redirector = $this->createStub( Admin_Redirector_Interface::class );
        $renderer   = new Php_View_Renderer( dirname( __DIR__, 4 ) . '/templates/' );
        $messages   = new Admin_Message_Service( $flash_store );
        $screen     = $this->create_screen(
            $competition_service,
            new Admin_Screen_Presenter( $renderer, $messages ),
            new Admin_Action_Adapter( $messages, $redirector ),
            $this->createStub( Index_Action_Dispatcher_Interface::class )
        );

        ob_start();
        $screen->display_index_page();
        $output = (string) ob_get_clean();
        $add_form_start = strpos( $output, '<h3>Add Competition</h3>' );
        self::assertNotFalse( $add_form_start );
        $add_form = substr( $output, $add_form_start );

        self::assertStringContainsString( 'Competition already exists', $output );
        self::assertStringContainsString( 'Existing Junior League', $output );
        self::assertStringContainsString( '/wp-admin/admin.php?page=racketmanager-leagues&amp;view=seasons&amp;competition_id=17', $output );
        $this->assert_competition_row( $output, 17, 'Existing Junior League', 'Junior', 'League', 2, 3 );
        self::assertStringContainsString( 'name="competition_name"', $output );
        self::assertStringContainsString( 'value="Summer Cup"', $output );
        self::assertMatchesRegularExpression( '/<option value="tournament"\s+selected="selected">Tournament<\/option>/', $add_form );
        self::assertMatchesRegularExpression( '/<option value="junior"\s+selected="selected">Junior<\/option>/', $add_form );
        self::assertStringContainsString( 'invalid-feedback">Competition already exists</div>', $add_form );
        self::assertStringContainsString( 'name="racketmanager_nonce"', $output );
        self::assertStringContainsString( 'name="doCompDel"', $output );
    }

    public function test_get_renders_competitions_table_with_various_summary_dto_types_and_nullable_age_groups(): void {
        $_GET = array( 'page' => 'racketmanager' );

        $tournament = new Competition_Summary_DTO(
            array(
                'id'           => 101,
                'name'         => 'Annual Open Tournament',
                'age_group'    => null,
                'type'         => 'tournament',
                'season_count' => 1,
                'event_count'  => 5,
            )
        );
        $legacy_std_class = (object) array(
            'id'           => 102,
            'name'         => 'Legacy Cup Competition',
            'age_group'    => 'senior',
            'type'         => 'cup',
            'season_count' => 4,
            'event_count'  => 2,
        );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'find_competitions_with_summary' )
            ->with( '', null )
            ->willReturn( array( $tournament, $legacy_std_class ) );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::once() )->method( 'pop' )->willReturn( array() );
        $redirector = $this->createStub( Admin_Redirector_Interface::class );
        $renderer   = new Php_View_Renderer( dirname( __DIR__, 4 ) . '/templates/' );
        $messages   = new Admin_Message_Service( $flash_store );
        $screen     = $this->create_screen(
            $competition_service,
            new Admin_Screen_Presenter( $renderer, $messages ),
            new Admin_Action_Adapter( $messages, $redirector ),
            $this->createStub( Index_Action_Dispatcher_Interface::class )
        );

        $render_errors = array();
        $error_levels  = E_DEPRECATED | E_USER_DEPRECATED | E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE;
        $buffer_level  = ob_get_level();
        set_error_handler(
            static function ( int $severity, string $message, string $file, int $line ) use ( &$render_errors, $error_levels ): bool {
                if ( 0 !== ( $severity & $error_levels ) ) {
                    $render_errors[] = compact( 'severity', 'message', 'file', 'line' );
                    return true;
                }

                return false;
            }
        );
        try {
            ob_start();
            $screen->display_index_page();
            $output = (string) ob_get_clean();
        } finally {
            while ( ob_get_level() > $buffer_level ) {
                ob_end_clean();
            }
            restore_error_handler();
        }

        self::assertSame( array(), $render_errors, 'Template rendering must not emit PHP notices, warnings, or deprecations.' );
        $this->assert_competition_row( $output, 101, 'Annual Open Tournament', '', 'Tournament', 1, 5 );
        self::assertStringContainsString( '/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=config&amp;competition_id=101', $output );

        $this->assert_competition_row( $output, 102, 'Legacy Cup Competition', 'Senior', 'Cup', 4, 2 );
        self::assertStringContainsString( '/wp-admin/admin.php?page=racketmanager-cups&amp;view=seasons&amp;competition_id=102', $output );
    }

    private function assert_competition_row(
        string $output,
        int $id,
        string $name,
        string $age_group,
        string $type,
        int $season_count,
        int $event_count,
    ): void {
        $row = $this->get_competition_row( $output, $id );
        self::assertSame( 7, preg_match_all( '/<td\b[^>]*>(.*?)<\/td>/s', $row, $matches ) );

        $cells = array_map(
            static fn( string $cell ): string => trim( html_entity_decode( strip_tags( $cell ), ENT_QUOTES | ENT_HTML5 ) ),
            $matches[1]
        );

        self::assertCount( 7, $cells );
        self::assertStringContainsString( 'value="' . $id . '"', $row );
        self::assertStringContainsString( 'name="competition[' . $id . ']"', $row );
        self::assertStringContainsString( 'id="competition-' . $id . '"', $row );
        self::assertSame( (string) $id, $cells[1] );
        self::assertSame( $name, $cells[2] );
        self::assertSame( $age_group, $cells[3] );
        self::assertSame( $type, $cells[4] );
        self::assertSame( (string) $season_count, $cells[5] );
        self::assertSame( (string) $event_count, $cells[6] );
    }

    private function get_competition_row( string $output, int $id ): string {
        preg_match_all( '/<tr\b[^>]*>.*?<\/tr>/s', $output, $matches );

        foreach ( $matches[0] as $row ) {
            if ( str_contains( $row, 'id="competition-' . $id . '"' ) ) {
                return $row;
            }
        }

        self::fail( 'No competition table row found for ID ' . $id . '.' );
    }

    private function create_screen(
        Competition_Service $competition_service,
        Admin_Screen_Presenter $screens,
        Admin_Action_Adapter $actions,
        Index_Action_Dispatcher_Interface $dispatcher,
    ): Admin_Index {
        $screen = ( new \ReflectionClass( Admin_Index::class ) )->newInstanceWithoutConstructor();

        ( new \ReflectionProperty( Admin_Display::class, 'competition_service' ) )->setValue( $screen, $competition_service );
        ( new \ReflectionProperty( Admin_Display::class, 'admin_action_adapter' ) )->setValue( $screen, $actions );
        ( new \ReflectionProperty( Admin_Display::class, 'admin_message_service' ) )->setValue( $screen, new Admin_Message_Service( $this->createStub( Admin_Flash_Message_Store::class ) ) );
        ( new \ReflectionProperty( Admin_Index::class, 'actions' ) )->setValue( $screen, $actions );
        ( new \ReflectionProperty( Admin_Index::class, 'screens' ) )->setValue( $screen, $screens );
        ( new \ReflectionProperty( Admin_Index::class, 'index_action_dispatcher' ) )->setValue( $screen, $dispatcher );

        return $screen;
    }

    public function test_post_fails_when_user_lacks_capability(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array( 'addCompetition' => 'competition', 'competition_name' => 'Summer Cup' );

        $flash_store = $this->createMock( Admin_Flash_Message_Store::class );
        $flash_store->expects( self::never() )->method( 'set' );
        $flash_store->expects( self::never() )->method( 'pop' );
        $redirector = $this->createMock( Admin_Redirector_Interface::class );
        $redirector->expects( self::never() )->method( 'redirect' );
        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( self::never() )->method( 'render' );
        $dispatcher = $this->createMock( Index_Action_Dispatcher_Interface::class );
        $dispatcher->expects( self::never() )->method( 'handle' );
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'find_competitions_with_summary' );

        $screen = $this->create_screen(
            $competition_service,
            new Admin_Screen_Presenter( $renderer, new Admin_Message_Service( $flash_store ) ),
            new Admin_Action_Adapter( new Admin_Message_Service( $flash_store ), $redirector ),
            $dispatcher
        );

        ob_start();
        $screen->display_index_page();
        $output = ob_get_clean();

        self::assertNotSame( '', $output );
        self::assertStringContainsString( 'alert', $output );
    }
}
