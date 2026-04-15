<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Security;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Security\Security_Service;

class Security_Service_Test extends TestCase {
    private Security_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new Security_Service();
    }

    protected function tearDown(): void {
        unset($GLOBALS['wp_stubs_wp_verify_nonce_return']);
        unset($GLOBALS['wp_stubs_current_user_can']);
        parent::tearDown();
    }

    public function test_verify_nonce_returns_true_when_wp_verify_nonce_succeeds(): void {
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;
        $this->assertTrue($this->service->verify_nonce('valid_nonce', 'some_action'));
    }

    public function test_verify_nonce_returns_false_when_wp_verify_nonce_fails(): void {
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;
        $this->assertFalse($this->service->verify_nonce('invalid_nonce', 'some_action'));
    }

    public function test_current_user_can_returns_true_when_user_has_capability(): void {
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $this->assertTrue($this->service->current_user_can('edit_posts'));
    }

    public function test_current_user_can_returns_false_when_user_lacks_capability(): void {
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $this->assertFalse($this->service->current_user_can('edit_posts'));
    }
}
