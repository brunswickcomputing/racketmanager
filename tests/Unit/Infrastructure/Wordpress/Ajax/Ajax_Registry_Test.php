<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Ajax;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Ajax\Ajax_Registry;

class Ajax_Registry_Test extends TestCase {

    public function test_register_authenticated_only(): void {
        $registry = new Ajax_Registry();
        $callback = function() { return 'test'; };
        
        $GLOBALS['wp_action_hooks'] = [];

        $registry->register('test_action', $callback);

        $this->assertArrayHasKey('wp_ajax_racketmanager_test_action', $GLOBALS['wp_action_hooks']);
        $this->assertSame($callback, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_test_action'][0]);
        $this->assertArrayNotHasKey('wp_ajax_nopriv_racketmanager_test_action', $GLOBALS['wp_action_hooks']);
    }

    public function test_register_public(): void {
        $registry = new Ajax_Registry();
        $callback = function() { return 'test'; };
        
        $GLOBALS['wp_action_hooks'] = [];

        $registry->register('test_public', $callback, true);

        $this->assertArrayHasKey('wp_ajax_racketmanager_test_public', $GLOBALS['wp_action_hooks']);
        $this->assertArrayHasKey('wp_ajax_nopriv_racketmanager_test_public', $GLOBALS['wp_action_hooks']);
        $this->assertSame($callback, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_test_public'][0]);
        $this->assertSame($callback, $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_test_public'][0]);
    }

    public function test_register_with_unauthenticated(): void {
        $registry = new Ajax_Registry();
        $callback = function() { return 'auth'; };
        $unauth_callback = function() { return 'unauth'; };
        
        $GLOBALS['wp_action_hooks'] = [];

        $registry->register_with_unauthenticated('test_split', $callback, $unauth_callback);

        $this->assertArrayHasKey('wp_ajax_racketmanager_test_split', $GLOBALS['wp_action_hooks']);
        $this->assertArrayHasKey('wp_ajax_nopriv_racketmanager_test_split', $GLOBALS['wp_action_hooks']);
        $this->assertSame($callback, $GLOBALS['wp_action_hooks']['wp_ajax_racketmanager_test_split'][0]);
        $this->assertSame($unauth_callback, $GLOBALS['wp_action_hooks']['wp_ajax_nopriv_racketmanager_test_split'][0]);
    }
}
