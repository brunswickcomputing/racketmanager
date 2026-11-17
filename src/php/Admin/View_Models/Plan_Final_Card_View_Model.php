<?php
declare( strict_types=1 );

/**
 * Plan Final Card View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Logic-less, pre-computed draggable card for a final match/fixture in the cup and tournament planners.
 */
final class Plan_Final_Card_View_Model {

    public readonly int $id;
    public readonly string $league_name;

    /**
     * Backward-compatible property hook for legacy callers reading $card->league_title.
     */
    public string $league_title {
        final get => $this->league_name;
    }

    public readonly string $btn_type;
    public readonly string $home_title;
    public readonly string $away_title;
    public readonly bool $is_home_pending;
    public readonly bool $is_away_pending;
    public readonly ?string $home_club;
    public readonly ?string $away_club;

    /**
     * @param int          $id
     * @param string       $league_name
     * @param string       $btn_type
     * @param string       $home_title
     * @param string       $away_title
     * @param bool         $is_home_pending
     * @param bool         $is_away_pending
     * @param string|null  $home_club
     * @param string|null  $away_club
     */
    public function __construct(
        int $id,
        string $league_name,
        string $btn_type,
        string $home_title,
        string $away_title,
        bool $is_home_pending,
        bool $is_away_pending,
        ?string $home_club = null,
        ?string $away_club = null
    ) {
        $this->id              = $id;
        $this->league_name     = $league_name;
        $this->btn_type        = $btn_type;
        $this->home_title      = $home_title;
        $this->away_title      = $away_title;
        $this->is_home_pending = $is_home_pending;
        $this->is_away_pending = $is_away_pending;
        $this->home_club       = $home_club;
        $this->away_club       = $away_club;
    }
}
