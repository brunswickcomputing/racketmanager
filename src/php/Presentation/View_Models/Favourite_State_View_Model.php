<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Represents the immutable display state for a favourite action control.
 *
 * Encapsulates the text, status, and styling identifiers needed to render
 * the control consistently in a view layer.
 */
final readonly class Favourite_State_View_Model {
    /**
     * Initialises the instance with display metadata and styling classes.
     *
     * @param string $title The title text.
     * @param string $status The status value.
     * @param string $button_class The CSS class for the button.
     * @param string $icon_class The CSS class for the icon.
     *
     * @return void
     */
    public function __construct(
        public string $title,
        public string $status,
        public string $button_class,
        public string $icon_class,
    ) {}
}
