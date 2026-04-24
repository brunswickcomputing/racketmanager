<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Response;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Response\Json_Response_Factory;
use WP_Error;

class Json_Response_Factory_Test extends TestCase {
    private Json_Response_Factory $factory;

    protected function setUp(): void {
        $this->factory = new Json_Response_Factory();
    }

    public function test_create_success_response(): void {
        $data = ['test' => 'data'];
        $response = $this->factory->create_success_response($data, 201);
        
        $this->assertEquals(201, $response->get_status_code());
        $this->assertEquals([
            'success' => true,
            'data' => $data
        ], $response->get_content());
    }

    public function test_create_error_response_defaults_to_400(): void {
        $data = ['error' => 'msg'];
        $response = $this->factory->create_error_response($data);
        
        $this->assertEquals(400, $response->get_status_code());
        $this->assertEquals([
            'success' => false,
            'data' => $data
        ], $response->get_content());
    }

    public function test_create_error_response_with_custom_status(): void {
        $data = ['error' => 'msg'];
        $response = $this->factory->create_error_response($data, 403);
        
        $this->assertEquals(403, $response->get_status_code());
    }

    public function test_create_error_response_unpacks_wp_error(): void {
        $wp_error = new WP_Error('code1', 'message1', 'data1');
        $wp_error->add('code1', 'message2');
        $wp_error->add('code2', 'message3');

        $response = $this->factory->create_error_response($wp_error);
        
        $expected_data = [
            'code1' => ['message1', 'message2'],
            'code2' => ['message3']
        ];

        $this->assertEquals(400, $response->get_status_code());
        $this->assertEquals([
            'success' => false,
            'data' => $expected_data
        ], $response->get_content());
    }

    public function test_create_raw_response(): void {
        $content = 'raw content';
        $response = $this->factory->create_raw_response($content, 202);
        
        $this->assertEquals(202, $response->get_status_code());
        $this->assertEquals($content, $response->get_content());
    }
}
