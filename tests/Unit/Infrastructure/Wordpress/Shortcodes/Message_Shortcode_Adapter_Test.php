<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Message\Queries\Get_Message_Handler;
use Racketmanager\Application\Message\Queries\Get_Message_Query;
use Racketmanager\Application\Message\Queries\Get_Messages_Handler;
use Racketmanager\Application\Message\Queries\Get_Messages_Query;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class Message_Shortcode_Adapter_Test extends TestCase {
    private Message_Shortcode_Adapter $adapter;
    private Get_Messages_Handler $messages_handler;
    private Get_Message_Handler $message_handler;

    protected function setUp(): void {
        parent::setUp();

        $racketmanager = $this->createStub( RacketManager::class );
        $renderer = new class implements View_Renderer_Interface {
            public function render( string $template, object|array $data, array $additional_vars = [] ): void {}
            public function render_to_string( string $template, object|array $data, array $additional_vars = [] ): string {
                return '<div class="rendered">' . $template . '</div>';
            }
            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $view_presenter = new Shortcode_View_Presenter( new \Racketmanager\Services\View\Shortcode_Template_Renderer( $renderer ) );
        $this->messages_handler = $this->createStub( Get_Messages_Handler::class );
        $this->message_handler  = $this->createStub( Get_Message_Handler::class );

        $this->adapter = new Message_Shortcode_Adapter(
            $racketmanager,
            $view_presenter,
            $this->messages_handler,
            $this->message_handler
        );
    }

    public function test_show_messages_returns_string(): void {
        $this->messages_handler->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_html( '<div class="messages-list">Messages</div>' ) );

        $output = $this->adapter->show_messages( [] );
        $this->assertStringContainsString( 'messages-list', $output );
    }

    public function test_show_message_returns_string(): void {
        $this->message_handler->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_html( '<div class="message-detail">Message Detail</div>' ) );

        $output = $this->adapter->show_message( [ 'id' => 99999 ] );
        $this->assertStringContainsString( 'message-detail', $output );
    }
}
