<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for the [competition] shortcode.
 */
final readonly class Competition_View_Model {
    /**
     * @param string          $template             Template suffix or name ('competition', 'competition-custom').
     * @param object|null     $competition          The competition domain entity.
     * @param array           $competition_season   Current season configuration array.
     * @param string|null     $tab                  Active tab.
     * @param array           $seasons              Available seasons.
     * @param string|null     $error_message        Error message if competition could not be loaded.
     * @param array           $menu_options         Navigation menu tab options.
     * @param bool            $is_singular          Whether a singular query param is active.
     * @param string|int      $post_id              Page ID.
     * @param string          $pagename             Page SEO slug.
     * @param string          $competition_seo_name Precomputed competition SEO slug.
     */
    public function __construct(
        public string $template = 'competition',
        public ?object $competition = null,
        public array $competition_season = [],
        public ?string $tab = null,
        public array $seasons = [],
        public ?string $error_message = null,
        public array $menu_options = [],
        public bool $is_singular = false,
        public string|int $post_id = '',
        public string $pagename = '',
        public string $competition_seo_name = ''
    ) {}

    /**
     * Variables exposed to template.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        return [
            'competition'          => $this->competition,
            'competition_season'   => $this->competition_season,
            'tab'                  => $this->tab,
            'seasons'              => $this->seasons,
            'menu_options'         => $this->menu_options,
            'is_singular'          => $this->is_singular,
            'post_id'              => $this->post_id,
            'pagename'             => $this->pagename,
            'competition_seo_name' => $this->competition_seo_name,
        ];
    }
}
