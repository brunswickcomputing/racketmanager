<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Ports;

interface Template_Renderer_Interface {
    public function render( string $template, array $vars = [], false|string $template_type = false ): string;
}
