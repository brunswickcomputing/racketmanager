<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

final readonly class Favourite_Controls_View_Model {
    /** @param array<string, array<int|string, Favourite_State_View_Model>> $selected */
    public function __construct(
        public bool $can_toggle,
        public array $selected,
        public Favourite_State_View_Model $unselected,
    ) {}
}