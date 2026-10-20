<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model for grouped match listings ([dailymatches], [latest_results]).
 */
final readonly class Matches_List_View_Model {
    public bool $has_matches;

    /**
     * @param string                $template     Template name (without extension), e.g. "matches-daily".
     * @param array<string, array>  $matches_list Presentation fixtures grouped by league name.
     * @param string|null           $match_date   Selected match date (daily listing only).
     * @param int                   $header_level Heading level for the module title.
     * @param int                   $post_id      Page hosting the daily matches form.
     * @param string                $form_action  Prepared daily matches form URL.
     */
    public function __construct(
        public string $template,
        public array $matches_list = [],
        public ?string $match_date = null,
        public int $header_level = 1,
        public int $post_id = 0,
        public string $form_action = ''
    ) {
        $this->has_matches = ! empty( $matches_list );
    }

    /**
     * Variables exposed to templates (and theme overrides) under their legacy names.
     *
     * @return array<string, mixed>
     */
    public function template_vars(): array {
        $vars = [
            'matches_list' => $this->matches_list,
            'post_id'      => $this->post_id,
            'form_action'  => $this->form_action,
        ];
        if ( null !== $this->match_date ) {
            $vars['match_date'] = $this->match_date;
        } else {
            $vars['header_level'] = $this->header_level;
        }

        return $vars;
    }
}
