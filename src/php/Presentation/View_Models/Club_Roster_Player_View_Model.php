<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Strict immutable view model for club roster player rows.
 */
final readonly class Club_Roster_Player_View_Model {
    /**
     * @param int|null $registration_id Club player registration ID.
     * @param int|null $user_id         Player user/player ID.
     * @param string   $display_name    Player full or display name.
     * @param string   $gender          Player gender ('M' or 'F').
     * @param string   $player_url      URL to the player's profile.
     * @param string   $btm             LTA Tennis number / BTM.
     * @param string   $rating_display  Formatted rating / WTN string (e.g. "[S:9.1] - [D:8.4]").
     */
    public function __construct(
        public ?int $registration_id = null,
        public ?int $user_id = null,
        public string $display_name = '',
        public string $gender = '',
        public string $player_url = '',
        public string $btm = '',
        public string $rating_display = ''
    ) {}
}
