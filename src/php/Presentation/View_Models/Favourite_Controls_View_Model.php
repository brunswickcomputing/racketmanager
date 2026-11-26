<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model describing the available favourite control states and whether the
 * control can be toggled.
 */
final readonly class Favourite_Controls_View_Model {
    /**
     * Initialises the favourite state view model.
     *
     * @param bool                       $can_toggle Whether the favourite state can be toggled.
     * @param array                      $selected The selected favourite state data.
     * @param Favourite_State_View_Model $unselected The unselected favourite state view model.
     *
     * @return void
     */
    public function __construct(
        public bool $can_toggle,
        public array $selected,
        public Favourite_State_View_Model $unselected,
    ) {}
}
