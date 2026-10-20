<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Favourite_State_View_Model {
    public function __construct(
        public string $title,
        public string $status,
        public string $button_class,
        public string $icon_class,
    ) {}
}