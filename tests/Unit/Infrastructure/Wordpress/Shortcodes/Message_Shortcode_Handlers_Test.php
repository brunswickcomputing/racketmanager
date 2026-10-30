<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Player\User;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;

class Message_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;

    private RacketManager $racketmanager;
    private View_Renderer_Interface $renderer;
    private Shortcode_View_Presenter $view_presenter;

    protected function setUp(): void {
        parent::setUp();

        $this->racketmanager = $this->createStub( RacketManager::class );

        $this->renderer = new class implements View_Renderer_Interface {
            public array $rendered_templates = [];
            public function render( string $template, object|array $data, array $additional_vars = [] ): void {}
            public function render_to_string( string $template, object|array $data, array $additional_vars = [] ): string {
                $this->rendered_templates[] = [ 'template' => $template, 'data' => $data, 'folder' => 'templates' ];
                return '<div class="rendered-' . $template . '"></div>';
            }
            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $this->view_presenter = new Shortcode_View_Presenter( new \Racketmanager\Services\View\Shortcode_Template_Renderer( $this->renderer ) );
    }

    public function test_messages_handler_when_logged_out(): void {
        $user_repository = $this->createStub( User_Repository_Interface::class );
        $handler         = new Get_Messages_Handler(
            $user_repository,
            $this->createStub( Message_Repository_Interface::class )
        );
        $query           = new Get_Messages_Query(
            is_logged_in: false,
            user_id: null
        );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertStringContainsString( 'You must be logged in to view a message', $result->error_message );
    }

    public function test_messages_handler_when_logged_in(): void {
        $user = $this->createStub( User::class );
        $user->id = 101;
        $details = [ (object) [ 'id' => 15 ] ];
        $message_calls = [];
        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 101 )
            ->willReturn( $user );
        $message_repository = $this->createMock( Message_Repository_Interface::class );
        $message_repository->expects( $this->exactly( 3 ) )
            ->method( 'find_by_user' )
            ->willReturnCallback( static function ( int $user_id, array $args = [] ) use ( &$message_calls, $details ): array|int {
                $message_calls[] = $args;
                return match ( count( $message_calls ) ) {
                    1 => 3,
                    2 => $details,
                    3 => 2,
                };
            } );

        $handler = new Get_Messages_Handler( $user_repository, $message_repository );
        $query   = new Get_Messages_Query(
            template: 'custom',
            is_logged_in: true,
            user_id: 101
        );

        $result = $handler->handle( $query );
        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'custom', $result->template );
        $this->assertSame( 3, $result->messages['total'] );
        $this->assertSame( 2, $result->messages['unread'] );
        $this->assertSame( $details, $result->messages['detail'] );
        $this->assertSame( [
            [ 'count' => true ],
            [],
            [ 'count' => true, 'status' => 'unread' ],
        ], $message_calls );
    }

    public function test_message_handler_when_logged_out(): void {
        $message_repository = $this->createStub( Message_Repository_Interface::class );
        $handler            = new Get_Message_Handler( $message_repository );
        $query              = new Get_Message_Query(
            message_id: 42,
            is_logged_in: false,
            user_id: null
        );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertStringContainsString( 'You must be logged in to view a message', $result->error_message );
    }

    public function test_message_handler_missing_id(): void {
        $message_repository = $this->createStub( Message_Repository_Interface::class );
        $handler            = new Get_Message_Handler( $message_repository );
        $query              = new Get_Message_Query(
            message_id: null,
            is_logged_in: true,
            user_id: 101
        );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertStringContainsString( 'Message not found', $result->error_message );
    }

    public function test_message_handler_not_found(): void {
        $message_repository = $this->createMock( Message_Repository_Interface::class );
        $message_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 99999 )
            ->willReturn( null );

        $handler = new Get_Message_Handler( $message_repository );
        $query   = new Get_Message_Query(
            message_id: 99999,
            is_logged_in: true,
            user_id: 101
        );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertStringContainsString( 'Message not found', $result->error_message );
    }

    public function test_message_handler_unauthorized(): void {
        $message = $this->getMockBuilder( Message::class )
            ->disableOriginalConstructor()
            ->getMock();
        $message->id     = 42;
        $message->userid = 999;
        $message->status = '1';
        $message->expects( $this->never() )->method( 'set_status' );

        $message_repository = $this->createMock( Message_Repository_Interface::class );
        $message_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $message );

        $handler = new Get_Message_Handler( $message_repository );
        $query   = new Get_Message_Query(
            message_id: 42,
            is_logged_in: true,
            user_id: 101
        );

        $result = $handler->handle( $query );
        $this->assertTrue( $result->is_error() );
        $this->assertStringContainsString( 'You are not authorised to view this message', $result->error_message );
    }

    public function test_message_handler_success(): void {
        $message = $this->getMockBuilder( Message::class )
            ->disableOriginalConstructor()
            ->getMock();
        $message->id     = 42;
        $message->userid = 101;
        $message->status = '1';
        $message->expects( $this->once() )
            ->method( 'set_status' )
            ->with( '0' );

        $message_repository = $this->createMock( Message_Repository_Interface::class );
        $message_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $message );
        $message_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $message )
            ->willReturn( 42 );

        $handler = new Get_Message_Handler( $message_repository );
        $query   = new Get_Message_Query(
            message_id: 42,
            template: 'custom',
            is_logged_in: true,
            user_id: 101
        );

        $result = $handler->handle( $query );
        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'custom', $result->template );
        $this->assertSame( $message, $result->message );
    }

    public function test_message_handler_renders_real_template_with_message_details(): void {
        $message = $this->createStub( Message::class );
        $message->id             = 42;
        $message->userid         = 101;
        $message->status         = '0';
        $message->from_name      = 'Tennis Admin';
        $message->from_email     = 'admin@example.com';
        $message->subject        = 'Court booking update';
        $message->date           = '2026-10-12 09:30:00';
        $message->message_object = '<p>New court booking details</p>';

        $message_repository = $this->createStub( Message_Repository_Interface::class );
        $message_repository->method( 'find' )->willReturn( $message );

        $handler = new Get_Message_Handler( $message_repository );
        $adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            new Shortcode_View_Presenter(
                new Shortcode_Template_Renderer(
                    new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' )
                )
            ),
            $this->createStub( Get_Messages_Handler::class ),
            $handler,
            security_service: new class implements \Racketmanager\Infrastructure\Security\Security_Service_Interface {
                public function is_user_logged_in(): bool { return true; }
                public function get_current_user_id(): int { return 101; }
                public function verify_nonce( string $nonce, string $nonce_action ): bool { return true; }
                public function current_user_can( string $capability ): bool { return true; }
            }
        );

        $output = $adapter->show_message( [ 'id' => 42 ] );

        $this->assertStringContainsString( 'Court booking update', $output );
        $this->assertStringContainsString( 'data-msg-id="42"', $output );
        $this->assertStringContainsString( '12-10-2026 9:30:00', $output );
        $this->assertStringContainsString( 'Tennis Admin [<a href="mailto:admin@example.com">admin@example.com</a>]', $output );
        $this->assertStringContainsString( "srcdoc='<p>New court booking details</p>'", $output );
    }

    public function test_messages_handler_renders_real_list_template(): void {
        $make = static function ( int $id, string $status, string $date, string $subject ): object {
            return (object) [
                'id' => $id, 'status' => $status, 'date' => $date, 'subject' => $subject,
                'from_name' => 'Admin', 'from_email' => 'a@example.com', 'message_object' => '',
            ];
        };
        $user = $this->createStub( User::class );
        $user->id = 101;
        $user_repository = $this->createStub( User_Repository_Interface::class );
        $user_repository->method( 'find' )->willReturn( $user );
        $message_repository = $this->createStub( Message_Repository_Interface::class );
        $message_repository->method( 'find_by_user' )->willReturnCallback(
            static function ( int $user_id, array $args = [] ) use ( $make ) {
                if ( ! empty( $args['count'] ) ) {
                    return isset( $args['status'] ) ? 1 : 2;
                }
                return [
                    $make( 1, '1', '2026-03-05 10:00:00', 'Unread one' ),
                    $make( 2, '0', '2026-12-25 11:00:00', 'Read one' ),
                ];
            }
        );
        $handler = new Get_Messages_Handler( $user_repository, $message_repository );
        $adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            new Shortcode_View_Presenter(
                new Shortcode_Template_Renderer( new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' ) )
            ),
            $handler,
            $this->createStub( Get_Message_Handler::class ),
            security_service: new class implements \Racketmanager\Infrastructure\Security\Security_Service_Interface {
                public function is_user_logged_in(): bool { return true; }
                public function get_current_user_id(): int { return 101; }
                public function verify_nonce( string $nonce, string $nonce_action ): bool { return true; }
                public function current_user_can( string $capability ): bool { return true; }
            }
        );
        $output = $adapter->show_messages( [] );

        $this->assertMatchesRegularExpression( '/id="message-summary-1" class="message-summary unread"/', $output );
        $this->assertMatchesRegularExpression( '/id="message-summary-2" class="message-summary read"/', $output );
        $this->assertStringContainsString( '<span id="read-messages">1</span>', $output );
        $this->assertStringContainsString( '<span id="unread-messages">1</span>', $output );
        $this->assertStringContainsString( '<span class="date__month">Mar</span>', $output );
        $this->assertStringContainsString( '<span class="date__day">5</span>', $output );
        $this->assertStringContainsString( '<span class="date__year">2026</span>', $output );
        $this->assertStringContainsString( '<span class="date__month">Dec</span>', $output );
    }

    public function test_messages_handler_without_messages_passes_empty_view_model_and_renders(): void {
        $user = $this->createStub( User::class );
        $user->id = 101;
        $user_repository = $this->createStub( User_Repository_Interface::class );
        $user_repository->method( 'find' )->willReturn( $user );
        $message_repository = $this->createStub( Message_Repository_Interface::class );
        $message_repository->method( 'find_by_user' )->willReturn( 0 );

        $handler = new Get_Messages_Handler( $user_repository, $message_repository );
        $result  = $handler->handle( new Get_Messages_Query( is_logged_in: true, user_id: 101 ) );
        $this->assertSame( [], $result->messages );

        $adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            new Shortcode_View_Presenter(
                new Shortcode_Template_Renderer( new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' ) )
            ),
            $handler,
            $this->createStub( Get_Message_Handler::class ),
            security_service: new class implements \Racketmanager\Infrastructure\Security\Security_Service_Interface {
                public function is_user_logged_in(): bool { return true; }
                public function get_current_user_id(): int { return 101; }
                public function verify_nonce( string $nonce, string $nonce_action ): bool { return true; }
                public function current_user_can( string $capability ): bool { return true; }
            }
        );
        $output = $adapter->show_messages( [] );
        $this->assertStringContainsString( 'My messages', $output );
        $this->assertStringNotContainsString( 'message-summary', $output );
    }

    public function test_adapter_delegation(): void {
        $messages_handler = new Get_Messages_Handler(
            $this->createStub( User_Repository_Interface::class ),
            $this->createStub( Message_Repository_Interface::class )
        );
        $message_handler  = new Get_Message_Handler(
            $this->createStub( Message_Repository_Interface::class )
        );

        $adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            $this->view_presenter,
            $messages_handler,
            $message_handler
        );

        $this->assertNotEmpty( $adapter->show_messages( [] ) );
        $this->assertNotEmpty( $adapter->show_message( [ 'id' => 1 ] ) );
    }
}
