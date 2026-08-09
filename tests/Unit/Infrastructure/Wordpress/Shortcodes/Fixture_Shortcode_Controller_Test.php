<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Controller;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;

class Fixture_Shortcode_Controller_Test extends TestCase {
    public function test_handle_delegates_to_adapter_from_container(): void {
        $container = $this->createMock(Simple_Container::class);
        $adapter = $this->createMock(Fixture_Shortcode_Adapter::class);
        
        $racketmanager = $this->createStub(RacketManager::class);
        $racketmanager->container = $container;
        
        $container->method('get')
            ->with('fixture_shortcode_adapter')
            ->willReturn($adapter);
            
        $atts = ['match_id' => 123];
        $adapter->method('handle')
            ->with($atts)
            ->willReturn('html output');
            
        $controller = new Fixture_Shortcode_Controller($racketmanager);
        $result = $controller->handle($atts);
        
        $this->assertEquals('html output', $result);
    }
}
