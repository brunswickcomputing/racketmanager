<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Response;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory_Interface;
use Racketmanager\Infrastructure\Wordpress\Response\Logging_Json_Response_Factory;

class Logging_Json_Response_Factory_Test extends TestCase {
    private MockObject|Json_Response_Factory_Interface $factory;
    private Logging_Json_Response_Factory $logging_factory;

    protected function setUp(): void {
        $this->factory = $this->createMock( Json_Response_Factory_Interface::class );
        $this->logging_factory = new Logging_Json_Response_Factory( $this->factory );
    }

    public function test_send_success_delegates(): void {
        $data = ['test' => 'data'];
        $this->factory->expects( $this->once() )
            ->method( 'send_success' )
            ->with( $data, 200 );

        $this->logging_factory->send_success( $data, 200 );
    }

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

    public function test_log_and_send_error_delegates(): void {
        $data = ['error' => 'msg'];
        $this->factory->expects( $this->once() )
            ->method( 'send_error' )
            ->with( $data, 500 );

        $this->logging_factory->log_and_send_error( $data, 500 );
    }

    public function test_send_raw_delegates(): void {
        $content = '<html lang="">test</html>';
        $this->factory->expects( $this->once() )
            ->method( 'send_raw' )
            ->with( $content, 200 );

        $this->logging_factory->send_raw( $content, 200 );
    }
}
