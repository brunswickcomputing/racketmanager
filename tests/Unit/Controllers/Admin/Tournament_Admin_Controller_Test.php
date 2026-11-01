<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Tournament_Action_Result_DTO;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Action_Dispatcher;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Admin_Controller_Test extends TestCase {

    private bool $had_request_method = false;

    private ?string $original_request_method = null;

    protected function setUp(): void {
        parent::setUp();
        $this->had_request_method     = isset( $_SERVER['REQUEST_METHOD'] );
        $this->original_request_method = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function tearDown(): void {
        if ( $this->had_request_method ) {
            $_SERVER['REQUEST_METHOD'] = $this->original_request_method;
        } else {
            unset( $_SERVER['REQUEST_METHOD'] );
        }
        parent::tearDown();
    }

    public function test_get_new_tournament_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertFalse( $result['view_model']->edit );
        self::assertSame( 'Add Tournament', $result['view_model']->form_title );
    }

    public function test_get_existing_tournament_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $tournament_id = 123;
        $tournament_service->expects( self::once() )
            ->method( 'get_tournament_and_fees' )
            ->with( $tournament_id )
            ->willReturn( [
                'tournament' => new stdClass(),
                'fees' => new stdClass(),
            ] );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [ 'tournament' => (string) $tournament_id ], [] );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertTrue( $result['view_model']->edit );
        self::assertSame( 'Edit Tournament', $result['view_model']->form_title );
    }

    public function test_post_success_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $post_data = [ 'some' => 'data' ];
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            tournament_id: 456,
            message: 'Tournament added',
            message_type: Admin_Message_Type::SUCCESS
        );

        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( null, $post_data )
            ->willReturn( $action_result );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [ 'page' => 'racketmanager-tournaments' ], $post_data );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'tournament=456', $result['redirect'] );
        self::assertStringContainsString( 'view=modify', $result['redirect'] );
        self::assertSame( 'Tournament added', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_post_error_re_renders_form_with_errors(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $post_data = [ 'tournamentName' => 'New Name' ];
        $wp_error = new WP_Error( 'invalid_name', 'Name is required' );
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            message: 'Validation failed',
            message_type: Admin_Message_Type::ERROR,
            raw_error: $wp_error
        );

        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( null, $post_data )
            ->willReturn( $action_result );

        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [], $post_data );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Modify_Page_View_Model::class, $result['view_model'] );
        self::assertSame( [ 'tournamentName' => 'New Name' ], $result['view_model']->form_values );
        self::assertTrue( $result['view_model']->validator->error );
        self::assertContains( 'Name is required', $result['view_model']->validator->err_msgs );
        self::assertSame( 'Validation failed', $result['message'] );
        self::assertTrue( $result['message_type'] );
    }

    public function test_post_error_retains_submitted_form_values_including_date_withdraw(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $club_service = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service = $this->createMock( Season_Service::class );
        $dispatcher = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard = $this->createMock( Action_Guard_Interface::class );

        $post_data = [
            'tournamentName' => 'Submitted Name',
            'dateWithdraw'   => '2026-11-15',
            'dateStart'      => '2026-11-20',
        ];
        $wp_error = new WP_Error( 'invalid_name', 'Invalid' );
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            message: 'Validation failed',
            message_type: Admin_Message_Type::ERROR,
            raw_error: $wp_error
        );

        $dispatcher->method( 'handle' )->willReturn( $action_result );
        $club_service->method( 'get_clubs' )->willReturn( [] );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( [] );
        $season_service->method( 'get_all_seasons' )->willReturn( [] );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( [], $post_data );

        self::assertSame( '2026-11-15', $result['view_model']->form_values['dateWithdraw'] );
        self::assertSame( 'Submitted Name', $result['view_model']->form_values['tournamentName'] );
        $vars = $result['view_model']->to_template_vars();
        self::assertArrayHasKey( 'form_values', $vars );
        self::assertEquals( $post_data, $vars['form_values'] );
    }

    public function test_post_error_whitelists_unslashes_and_sanitizes_form_values(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service  = $this->createMock( Tournament_Service::class );
        $club_service        = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service      = $this->createMock( Season_Service::class );
        $dispatcher          = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $post_data = array(
            'tournamentName'   => "O\\'Brien Championship <b>2026</b>",
            'competition_id'   => '42',
            'season'           => '2026',
            'venue'            => '7',
            'competition_code' => ' CODE-123 ',
            'grade'            => '3',
            'feeCompetition'   => '15.50',
            'feeEvent'         => '10',
            'num_entries'      => '32',
            'dateStart'        => '2026-06-01',
            'dateEnd'          => '2026-06-07',
            'dateOpen'         => '2026-05-01',
            'dateClose'        => '2026-05-25',
            'dateWithdraw'     => '2026-05-28',
            'malicious_array'  => array( 'bad' => 'payload' ),
            'untrusted_field'  => 'ignored',
        );

        $wp_error      = new WP_Error( 'invalid_name', 'Invalid' );
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            message: 'Validation failed',
            message_type: Admin_Message_Type::ERROR,
            raw_error: $wp_error
        );

        $dispatcher->method( 'handle' )->willReturn( $action_result );
        $club_service->method( 'get_clubs' )->willReturn( array() );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( array() );
        $season_service->method( 'get_all_seasons' )->willReturn( array() );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result = $controller->modify_page( array(), $post_data );
        $form_values = $result['view_model']->form_values;

        // Magic quotes unslashed and HTML stripped
        self::assertSame( "O'Brien Championship 2026", $form_values['tournamentName'] );
        // Numeric values correctly cast
        self::assertSame( 42, $form_values['competition_id'] );
        self::assertSame( 2026, $form_values['season'] );
        self::assertSame( 7, $form_values['venue'] );
        self::assertSame( 32, $form_values['num_entries'] );
        // Decimal fee preserved as float, integer fee as int
        self::assertSame( 15.50, $form_values['feeCompetition'] );
        self::assertSame( 10, $form_values['feeEvent'] );
        // Dates and codes sanitized
        self::assertSame( 'CODE-123', $form_values['competition_code'] );
        self::assertSame( '3', $form_values['grade'] );
        self::assertSame( '2026-06-01', $form_values['dateStart'] );
        self::assertSame( '2026-06-07', $form_values['dateEnd'] );
        self::assertSame( '2026-05-01', $form_values['dateOpen'] );
        self::assertSame( '2026-05-25', $form_values['dateClose'] );
        self::assertSame( '2026-05-28', $form_values['dateWithdraw'] );
        // Non-scalar and untrusted fields dropped
        self::assertArrayNotHasKey( 'malicious_array', $form_values );
        self::assertArrayNotHasKey( 'untrusted_field', $form_values );
    }

    public function test_post_error_drops_non_scalar_values_for_whitelisted_fields(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service  = $this->createMock( Tournament_Service::class );
        $club_service        = $this->createMock( Club_Service::class );
        $competition_service = $this->createMock( Competition_Service::class );
        $season_service      = $this->createMock( Season_Service::class );
        $dispatcher          = $this->createMock( Tournament_Action_Dispatcher::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );

        $post_data = array(
            'tournamentName'   => array( 'attempt' => 'exploit' ),
            'competition_id'   => array( 1, 2, 3 ),
            'season'           => '2026',
        );

        $wp_error      = new WP_Error( 'invalid_name', 'Invalid' );
        $action_result = new Tournament_Action_Result_DTO(
            intent: Tournament_Action_Result_DTO::INTENT_ADD,
            message: 'Validation failed',
            message_type: Admin_Message_Type::ERROR,
            raw_error: $wp_error
        );

        $dispatcher->method( 'handle' )->willReturn( $action_result );
        $club_service->method( 'get_clubs' )->willReturn( array() );
        $competition_service->method( 'get_tournament_competitions' )->willReturn( array() );
        $season_service->method( 'get_all_seasons' )->willReturn( array() );

        $controller = new Tournament_Admin_Controller(
            $tournament_service,
            $club_service,
            $competition_service,
            $season_service,
            $dispatcher,
            $guard
        );

        $result      = $controller->modify_page( array(), $post_data );
        $form_values = $result['view_model']->form_values;

        self::assertArrayNotHasKey( 'tournamentName', $form_values );
        self::assertArrayNotHasKey( 'competition_id', $form_values );
        self::assertSame( 2026, $form_values['season'] );
    }
}
