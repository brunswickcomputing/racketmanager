<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Domain\DTO\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Request;

class Fixture_Reset_Request_Test extends TestCase {
    public function test_constructor_sets_properties(): void {
        $fixture_id = 123;
        $modal = 'some-modal';
        
        $request = new Fixture_Reset_Request($fixture_id, $modal);
        
        $this->assertEquals($fixture_id, $request->fixture_id);
        $this->assertEquals($modal, $request->modal);
    }

    public function test_constructor_allows_null_modal(): void {
        $fixture_id = 123;
        
        $request = new Fixture_Reset_Request($fixture_id);
        
        $this->assertEquals($fixture_id, $request->fixture_id);
        $this->assertNull($request->modal);
    }

    public function test_from_post_handles_missing_data(): void {
        $post = [];
        
        $request = Fixture_Reset_Request::from_post($post);
        
        $this->assertEquals(0, $request->fixture_id);
        $this->assertNull($request->modal);
    }

    public function test_from_post_maps_match_id_to_fixture_id(): void {
        $post = [
            'match_id' => '456',
            'modal' => 'test-modal'
        ];
        
        $request = Fixture_Reset_Request::from_post($post);
        
        $this->assertEquals(456, $request->fixture_id);
        $this->assertEquals('test-modal', $request->modal);
    }

    public function test_from_post_sanitizes_modal_input(): void {
        $post = [
            'match_id' => 123,
            'modal' => '<script>alert("xss")</script>test-modal'
        ];
        
        // We need to mock/stub WP functions if they are used in from_post
        // sanitize_text_field and wp_unslash are used in Fixture_Reset_Request::from_post
        
        $request = Fixture_Reset_Request::from_post($post);
        
        $this->assertEquals(123, $request->fixture_id);
        // Expecting sanitization to have occurred (tags removed)
        $this->assertEquals('alert("xss")test-modal', $request->modal);
    }
}
