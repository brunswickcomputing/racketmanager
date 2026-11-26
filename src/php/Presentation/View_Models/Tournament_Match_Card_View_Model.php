<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Represents the data used when rendering a tournament match card.
 */
final class Tournament_Match_Card_View_Model {

    public readonly string $league_name;

    /**
     * Backward-compatible property hook for legacy callers reading $card->league_title.
     *
     * @var string
     */
    public string $league_title {
        get => $this->league_name;
    }

    /**
     * Initialises the match display data.
     *
     * @param int|string|null $id Match identifier.
     * @param string          $match_display_class CSS class used for match display styling.
     * @param bool            $is_selected Whether the match is currently selected.
     * @param string          $selected_class CSS class applied when the match is selected.
     * @param string          $final_name Final display name for the match.
     * @param string|null     $draw_link Link to the draw, if available.
     * @param string          $league_name League name to display.
     * @param bool            $show_draw_link Whether to show the draw link.
     * @param bool            $show_header_date Whether to show the header date.
     * @param bool            $header_play_by Whether the header date represents a play-by date.
     * @param string|null     $header_formatted_date Formatted header date.
     * @param string|null     $header_time_display Header time display value.
     * @param bool            $show_header_location Whether to show the header location.
     * @param string|null     $location Match location.
     * @param array           $opponents Opponent display data.
     * @param array           $sets Set display data.
     * @param bool            $show_update_button Whether to show the update button.
     * @param string|null     $update_link Link used for updating the match.
     * @param string          $pencil_icon_url URL for the pencil icon.
     * @param string|null     $footer_location Footer location display value.
     * @param bool            $show_footer_date Whether to show the footer date.
     * @param bool            $footer_play_by Whether the footer date represents a play-by date.
     * @param string|null     $footer_formatted_date Formatted footer date.
     * @param string|null     $footer_time_display Footer time display value.
     * @param bool            $is_missing Whether the match is marked as missing.
     * @param string|null     $league_title Fallback league title when no league name is provided.
     *
     * @return void
     */
    public function __construct(
        public readonly int|string|null $id = null,
        public readonly string $match_display_class = '',
        public readonly bool $is_selected = false,
        public readonly string $selected_class = '',
        public readonly string $final_name = '',
        public readonly ?string $draw_link = null,
        string $league_name = '',
        public readonly bool $show_draw_link = false,
        public readonly bool $show_header_date = false,
        public readonly bool $header_play_by = false,
        public readonly ?string $header_formatted_date = null,
        public readonly ?string $header_time_display = null,
        public readonly bool $show_header_location = false,
        public readonly ?string $location = null,
        public readonly array $opponents = [],
        public readonly array $sets = [],
        public readonly bool $show_update_button = false,
        public readonly ?string $update_link = null,
        public readonly string $pencil_icon_url = '',
        public readonly ?string $footer_location = null,
        public readonly bool $show_footer_date = false,
        public readonly bool $footer_play_by = false,
        public readonly ?string $footer_formatted_date = null,
        public readonly ?string $footer_time_display = null,
        public readonly bool $is_missing = false,
        ?string $league_title = null,
    ) {
        $this->league_name = '' !== $league_name ? $league_name : $league_title ?? '';
    }
}
