<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;

trait Creates_View_Renderer {

    protected function create_view_renderer( ?string $template_path = null ): View_Renderer_Interface {
        return new Php_View_Renderer( $template_path ?? RACKETMANAGER_PATH . 'templates/' );
    }
}
