<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Domain\Message;
use Racketmanager\Domain\User;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes_Message;
use Racketmanager\RacketManager;
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
        $handler = new Get_Messages_Handler();
        $query   = new Get_Messages_Query(
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_error() );
        $this->assertStringContainsString( 'You must be logged in to view a message', $vm->error_message );
    }

    public function test_messages_handler_when_logged_in(): void {
        $handler = new Get_Messages_Handler();
        $query   = new Get_Messages_Query(
            template: 'custom',
            is_logged_in: true,
            user_id: 101
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'messages-custom', $vm->template );
        $this->assertSame( 'account', $vm->template_type );
    }

    public function test_message_handler_when_logged_out(): void {
        $handler = new Get_Message_Handler();
        $query   = new Get_Message_Query(
            message_id: 42,
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_error() );
        $this->assertStringContainsString( 'You must be logged in to view a message', $vm->error_message );
    }

    public function test_message_handler_missing_id(): void {
        $handler = new Get_Message_Handler();
        $query   = new Get_Message_Query(
            message_id: null,
            is_logged_in: true,
            user_id: 101
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_error() );
        $this->assertStringContainsString( 'Message not found', $vm->error_message );
    }

    public function test_message_handler_not_found(): void {
        $GLOBALS['message'] = null;

        $handler = new Get_Message_Handler();
        $query   = new Get_Message_Query(
            message_id: 99999,
            is_logged_in: true,
            user_id: 101
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_error() );
        $this->assertStringContainsString( 'Message not found', $vm->error_message );
    }

    public function test_message_handler_unauthorized(): void {
        $message = $this->getMockBuilder( Message::class )
            ->disableOriginalConstructor()
            ->getMock();
        $message->id     = 42;
        $message->userid = 999;
        $message->status = '1';
        $message->expects( $this->never() )->method( 'set_status' );
        $GLOBALS['message'] = $message;
        $GLOBALS['wp_stubs_messages'][42] = $message;

        $handler = new Get_Message_Handler();
        $query   = new Get_Message_Query(
            message_id: 42,
            is_logged_in: true,
            user_id: 101
        );

        $vm = $handler->handle( $query );
        $this->assertTrue( $vm->is_error() );
        $this->assertStringContainsString( 'You are not authorised to view this message', $vm->error_message );
    }

    public function test_message_handler_success(): void {
        $message = $this->getMockBuilder( Message::class )
            ->disableOriginalConstructor()
            ->getMock();
        $message->userid = 101;
        $message->status = '1';
        $message->expects( $this->once() )
            ->method( 'set_status' )
            ->with( '0' );

        $GLOBALS['message'] = $message;
        $GLOBALS['wp_stubs_messages'][42] = $message;

        $handler = new Get_Message_Handler();
        $query   = new Get_Message_Query(
            message_id: 42,
            template: 'custom',
            is_logged_in: true,
            user_id: 101
        );

        $vm = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'message-custom', $vm->template );
        $this->assertSame( 'account', $vm->template_type );
        $this->assertSame( $message, $vm->vars['message_dtl'] );
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
        $GLOBALS['wp_stubs_messages'][42] = $message;

        $vm = ( new Get_Message_Handler() )->handle(
            new Get_Message_Query(
                message_id: 42,
                is_logged_in: true,
                user_id: 101
            )
        );
        $presenter = new Shortcode_View_Presenter(
            new Shortcode_Template_Renderer(
                new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' )
            )
        );

        $output = $presenter->render( $vm );

        $this->assertStringContainsString( 'Court booking update', $output );
        $this->assertStringContainsString( 'data-msg-id="42"', $output );
    }

    public function test_adapter_and_facade_delegation(): void {
        $messages_handler = new Get_Messages_Handler();
        $message_handler  = new Get_Message_Handler();

        $adapter = new Message_Shortcode_Adapter(
            $this->racketmanager,
            $this->view_presenter,
            $messages_handler,
            $message_handler
        );

        $this->racketmanager->container = $this->create_container_with_services( [ 'message_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes_Message( $this->racketmanager );

        $this->assertNotEmpty( $facade->show_messages( [] ) );
        $this->assertNotEmpty( $facade->show_message( [ 'id' => 1 ] ) );
    }
}
