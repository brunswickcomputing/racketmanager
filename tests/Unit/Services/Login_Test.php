<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Application\Ports\Template_Renderer_Interface;
use Racketmanager\Services\Login;

class Login_Test extends TestCase {
    private Login $login;
    private $option_service;
    private $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        
        $this->option_service = $this->createStub( Option_Service::class );
        $this->template_renderer = $this->createStub( Template_Renderer_Interface::class );
        
        // This will call add_action in constructor
        $this->login = new Login(
            $this->option_service,
            $this->template_renderer
        );
    }

    public function test_constructor_initializes(): void {
        $this->assertNotNull( $this->login );
    }
}
