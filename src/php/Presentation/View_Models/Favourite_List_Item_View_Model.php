<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Favourite_List_Item_View_Model {
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public string $url,
        public string $icon_url,
    ) {}
}