<?php
declare( strict_types=1 );
/**
 * Overview Option Item View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Class Overview_Option_Item
 *
 * Represents an individual management option card on the portal overview screen.
 */
final readonly class Overview_Option_Item {

    /**
     * @param string        $slug         Section slug identifier.
     * @param string        $title        Display title.
     * @param string        $category     Functional category grouping.
     * @param string        $description  Summary of service capabilities.
     * @param string        $url          Navigation URL.
     * @param string        $badge        Visual tag or status indicator.
     * @param array<string> $capabilities Key operational features provided.
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $category,
        public string $description,
        public string $url,
        public string $badge = '',
        public array $capabilities = [],
    ) {
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'slug'         => $this->slug,
            'title'        => $this->title,
            'category'     => $this->category,
            'description'  => $this->description,
            'url'          => $this->url,
            'badge'        => $this->badge,
            'capabilities' => $this->capabilities,
        ];
    }
}
