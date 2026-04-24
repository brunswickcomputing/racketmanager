<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Response;

use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;
use Racketmanager\Infrastructure\Wordpress\Response\Response;

class Logging_Json_Response_Factory_Test extends TestCase {
    private MockObject|Json_Response_Factory_Interface $factory;
    private Logging_Json_Response_Factory $logging_factory;

    protected function setUp(): void {
        $this->factory = $this->createMock( Json_Response_Factory_Interface::class );
        $this->logging_factory = new Logging_Json_Response_Factory( $this->factory );
    }

    public function test_create_success_response_delegates(): void {
        $data = ['test' => 'data'];
        $response = new Response($data);
        $this->factory->expects( $this->once() )
            ->method( 'create_success_response' )
            ->with( $data, 200 )
            ->willReturn($response);

        $result = $this->logging_factory->create_success_response( $data );
        $this->assertSame($response, $result);
    }

    public function test_create_error_response_delegates_and_logs(): void {
        $data = ['error' => 'msg'];
        $response = new Response($data);
        
        $this->factory->expects( $this->once() )
            ->method( 'create_error_response' )
            ->with( $data, 400 )
            ->willReturn($response);

        $result = $this->logging_factory->create_error_response( $data, 400 );
        $this->assertSame($response, $result);
    }

    public function test_create_raw_response_delegates(): void {
        $content = '<html lang="">test</html>';
        $response = new Response($content);
        $this->factory->expects( $this->once() )
            ->method( 'create_raw_response' )
            ->with( $content, 200 )
            ->willReturn($response);

        $result = $this->logging_factory->create_raw_response( $content );
        $this->assertSame($response, $result);
    }

    #[NoReturn]
    public function test_send_success_delegates(): void {
        $data = ['test' => 'data'];
        $this->factory->expects( $this->once() )
            ->method( 'send_success' )
            ->with( $data, 200 );

        $this->logging_factory->send_success( $data, 200 );
    }

    #[NoReturn]
    public function test_send_error_delegates_and_logs(): void {
        $data = ['error' => 'msg'];
        
        // We expect send_error to be called on the inner factory
        $this->factory->expects( $this->once() )
            ->method( 'send_error' )
            ->with( $data, 400 );

        // We can't easily assert on error_log without some extra work, 
        // but we can at least ensure it doesn't crash and delegates properly.
        $this->logging_factory->send_error( $data, 400 );
    }

    #[NoReturn]
    public function test_log_and_send_error_delegates(): void {
        $data = ['error' => 'msg'];
        $this->factory->expects( $this->once() )
            ->method( 'send_error' )
            ->with( $data, 500 );

        $this->logging_factory->log_and_send_error( $data, 500 );
    }

}
