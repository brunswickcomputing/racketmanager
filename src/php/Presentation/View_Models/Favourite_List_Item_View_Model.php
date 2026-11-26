<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single item in a favourites list.
 *
 * Encapsulates the data required to render or transfer a favourite item,
 * including its identifier, category, display label, target URL, and icon URL.
 */
final readonly class Favourite_List_Item_View_Model {
    /**
     * Creates a new instance with the provided identifier, type, name, URL, and icon URL.
     *
     * @param int    $id The unique identifier.
     * @param string $type The type value.
     * @param string $name The display name.
     * @param string $url The associated URL.
     * @param string $icon_url The associated icon URL.
     *
     * @return void
     */
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public string $url,
        public string $icon_url,
    ) {}
}
