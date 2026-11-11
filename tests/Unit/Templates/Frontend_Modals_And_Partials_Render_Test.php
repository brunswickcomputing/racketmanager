<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

require_once __DIR__ . '/../../wp-stubs.php';

use ErrorException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\Presentation\View_Models\Order_Of_Play_View_Model;
use Racketmanager\Services\View\Php_View_Renderer;
use RuntimeException;
use stdClass;

final class Frontend_Modals_And_Partials_Render_Test extends TestCase {
    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_userdata'] );
        parent::tearDown();
    }

    /**
     * Trap the legacy plugin locator so a regression to $GLOBALS['racketmanager']->get_player_name() fails loudly.
     */
    private function trap_racketmanager_locator(): object {
        $previous = $GLOBALS['racketmanager'] ?? null;
        $GLOBALS['racketmanager'] = new class() {
            public function get_player_name( int $player_id ): never {
                throw new RuntimeException( 'orphan templates must not use the racketmanager locator' );
            }
        };
        return (object) [ 'previous' => $previous ];
    }

    private function restore_racketmanager_locator( object $trap ): void {
        if ( null === $trap->previous ) {
            unset( $GLOBALS['racketmanager'] );
        } else {
            $GLOBALS['racketmanager'] = $trap->previous;
        }
    }

    private function render_strictly( string $template, object|array $vars ): string {
        set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
            static function ( int $errno, string $errstr, string $errfile, int $errline ): bool {
                throw new ErrorException( $errstr, 0, $errno, $errfile, $errline ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            }
        );

        try {
            return $this->renderer->render_to_string( $template, $vars );
        } finally {
            restore_error_handler();
        }
    }

    public function test_club_role_modal_renders_strictly(): void {
        $club_role          = new stdClass();
        $club_role->id      = 12;
        $club_role->role_id = 1;
        $club_role->club_id = 10;
        $club_role->role    = (object) [ 'desc' => 'Chairman' ];
        $club_role->user = (object) [
            'display_name' => 'John Doe',
            'id'           => 45,
            'contactno'    => '0123456789',
            'email'        => 'john@example.com',
        ];

        $role_opt       = new stdClass();
        $role_opt->id   = 1;
        $role_opt->desc = 'Secretary';

        $vars = [
            'club_role'  => $club_role,
            'modal'      => 'roleModal',
            'club_roles' => [ $role_opt ],
        ];

        $html = $this->render_strictly( 'club/club-role-modal', $vars );

        $this->assertStringContainsString( 'name="clubRoleId" value="12"', $html );
        $this->assertStringContainsString( 'John Doe', $html );
        $this->assertStringContainsString( 'Chairman', $html );
    }

    public function test_purchase_order_modal_renders_strictly(): void {
        $invoice                 = new stdClass();
        $invoice->id             = 99;
        $invoice->purchase_order = 'PO-2026-001';

        $vars = [
            'invoice' => $invoice,
            'modal'   => 'poModal',
        ];

        $html = $this->render_strictly( 'club/purchase-order-modal', $vars );

        $this->assertStringContainsString( 'name="invoiceId" value="99"', $html );
        $this->assertStringContainsString( 'value="PO-2026-001"', $html );
    }

    public function test_team_edit_modal_renders_strictly(): void {
        $team      = (object) [ 'id' => 10, 'club_id' => 3, 'title' => 'Men\'s 1st Team' ];
        $event     = (object) [ 'id' => 5 ];
        $match_days = [
            '5' => 'Saturday',
            '6' => 'Sunday',
        ];

        $event_team = (object) [
            'captain'      => 'Jane Smith',
            'captain_id'   => 55,
            'contactno'    => '07111222333',
            'contactemail' => 'jane@example.com',
            'match_day'    => 'Saturday',
            'match_time'   => '14:00',
        ];

        $vars = [
            'team'       => $team,
            'event'      => $event,
            'modal'      => 'teamModal',
            'match_days' => $match_days,
            'event_team' => $event_team,
        ];

        $html = $this->render_strictly( 'club/team-edit-modal', $vars );

        $this->assertStringContainsString( 'name="team_id" value="10"', $html );
        $this->assertStringContainsString( 'value="Jane Smith"', $html );
        $this->assertStringContainsString( 'value="14:00"', $html );
        $this->assertStringContainsString( 'Saturday', $html );
    }

    public function test_partner_modal_renders_strictly(): void {
        $event = (object) [ 'id' => 7, 'name' => 'Men\'s Doubles' ];

        $vars = [
            'player_id'      => 101,
            'tournament_id'  => 202,
            'event'          => $event,
            'modal'          => 'partnerModal',
            'date_end'       => '2026-08-01',
            'season'         => '2026',
            'partner_name'   => 'Alice Walker',
            'partner_id'     => 102,
            'partner_btm'    => '12345678',
            'partner_gender' => 'F',
        ];

        $html = $this->render_strictly( 'event/partner-modal', $vars );

        $this->assertStringContainsString( 'name="tournamentId" value="202"', $html );
        $this->assertStringContainsString( 'name="partnerId"', $html );
        $this->assertStringContainsString( 'value="102"', $html );
        $this->assertStringContainsString( 'value="Alice Walker"', $html );
        $this->assertStringContainsString( 'Men\'s Doubles', $html );
    }

    public function test_team_matches_partial_renders_strictly(): void {
        $m1 = (object) [ 'id' => 501, 'match_title' => 'Hawks vs Eagles' ];
        $m2 = (object) [ 'id' => 502, 'match_title' => 'Hawks vs Falcons' ];

        $vars = [ 'matches' => [ $m1, $m2 ] ];

        $html = $this->render_strictly( 'event/team-matches', $vars );

        $this->assertStringContainsString( 'value="501"', $html );
        $this->assertStringContainsString( 'Hawks vs Eagles', $html );
    }

    public function test_dropdown_partial_renders_strictly(): void {
        $l1 = (object) [ 'id' => 11, 'title' => 'Division 1' ];

        $vars = [ 'leagues' => [ $l1 ] ];

        $html = $this->render_strictly( 'event/dropdown', $vars );

        $this->assertStringContainsString( 'value=11', $html );
        $this->assertStringContainsString( 'Division 1', $html );
    }

    public function test_team_players_list_renders_strictly(): void {
        $event = (object) [
            'id'          => 3,
            'num_rubbers' => 1,
            'type'        => 'MD',
        ];
        $club  = (object) [ 'id' => 4 ];
        $team  = (object) [ 'team_id' => 20, 'title' => 'First Team' ];

        $p1 = (object) [
            'registration_id' => 301,
            'display_name'    => 'Bob Brown',
            'btm'             => '998877',
            'removed_date'    => null,
        ];

        $vars = [
            'can_update'         => true,
            'teams'              => [ $team ],
            'event'              => $event,
            'club_players'       => [ 'm' => [ $p1 ], 'f' => [] ],
            'club'               => $club,
            'match_message_text' => '',
        ];

        $html = $this->render_strictly( 'event/team-players-list', $vars );

        $this->assertStringContainsString( 'name="clubId" value="4"', $html );
        $this->assertStringContainsString( 'name="eventId" value="3"', $html );
        $this->assertStringContainsString( 'Bob Brown - 998877', $html );
    }

    public function test_team_order_renders_strictly(): void {
        $club  = (object) [ 'id' => 1, 'shortcode' => 'LLTC' ];
        $event = (object) [ 'id' => 2, 'name' => 'Summer Men' ];

        $vars = [
            'clubs'  => [ $club ],
            'events' => [ $event ],
        ];

        $html = $this->render_strictly( 'team-order', $vars );

        $this->assertStringContainsString( 'value="1"', $html );
        $this->assertStringContainsString( 'LLTC', $html );
        $this->assertStringContainsString( 'Summer Men', $html );
    }

    public function test_order_of_play_renders_strictly(): void {
        $match_vm = new Order_Of_Play_Match_View_Model(
            id: 1,
            league_title: 'Championship Final',
            home_match_title: 'Player A',
            away_match_title: 'Player B',
            is_home_pending: false,
            is_away_pending: false
        );

        $oop_vm = new Order_Of_Play_View_Model(
            col_width: 6,
            is_expanded: false,
            times: [ '10:00' ],
            courts: [
                'Court 1' => [
                    '10:00' => [ $match_vm ],
                ],
            ]
        );

        $vars = [ 'order_of_play' => $oop_vm ];

        $html = $this->render_strictly( 'orderofplay', $vars );

        $this->assertStringContainsString( 'Court 1', $html );
        $this->assertStringContainsString( 'Championship Final', $html );
        $this->assertStringContainsString( 'Player A', $html );
        $this->assertStringContainsString( 'Player B', $html );
    }

    public function test_match_teams_renders_strictly(): void {
        $match = (object) [
            'id'        => 77,
            'winner_id' => null,
            'host'      => 'home',
            'teams'     => [
                'home' => (object) [
                    'title'   => 'Home Team',
                    'captain' => 'Home Captain',
                    'club'    => (object) [
                        'name'            => 'Home Club',
                        'address'         => '1 Home Lane',
                        'match_secretary' => (object) [
                            'display_name' => 'Sec Display',
                            'contactno'    => '01234',
                        ],
                    ],
                ],
                'away' => (object) [
                    'title'   => 'Away Team',
                    'captain' => 'Away Captain',
                ],
            ],
        ];

        $is_update_allowed = (object) [
            'user_can_update' => true,
        ];

        $vars = [
            'match'             => $match,
            'is_update_allowed' => $is_update_allowed,
        ];

        $html = $this->render_strictly( 'match-teams', $vars );

        $this->assertStringContainsString( 'Home Club', $html );
        $this->assertStringContainsString( '1 Home Lane', $html );
        $this->assertStringContainsString( 'Sec Display', $html );
        $this->assertStringContainsString( 'Home Captain', $html );
        $this->assertStringContainsString( 'Away Captain', $html );
    }


    public function test_match_teams_result_renders_strictly(): void {
        $match = new class() {
            public int $id = 55;
            public ?string $season = '2026';
            public ?string $home_team = 'home_id';
            public ?string $away_team = 'away_id';
            public bool $is_walkover = false;
            public bool $is_shared = false;
            public bool $is_retired = false;
            public ?string $walkover = null;
            public ?string $retired = null;
            public array $comments = [ 'result' => 'Pending check' ];
            public array $captain_names = [ 'home' => 'Home Cap' ];
            public array $approver_names = [ 'away' => 'Away Approver' ];
            public ?string $home_captain = 'user_h';
            public ?string $away_captain = 'user_a';
            public array $teams = [];
            public object $league;

            public function __construct() {
                $this->teams = [
                    'home' => (object) [ 'title' => 'Team Home' ],
                    'away' => (object) [ 'title' => 'Team Away' ],
                ];
                $this->league = (object) [
                    'title' => 'League 1',
                    'event' => (object) [
                        'name' => 'Event 1',
                        'competition' => (object) [
                            'type' => 'league',
                        ],
                    ],
                ];
            }

            public function get_rubbers(): array {
                return [];
            }
        };

        $is_update_allowed = (object) [
            'user_can_update'     => true,
            'user_type'           => 'captain',
            'user_team'           => 'home',
            'match_approval_mode' => false,
            'match_update'        => true,
        ];

        $vars = [
            'match'             => $match,
            'match_type'        => 'league',
            'is_update_allowed' => $is_update_allowed,
            'tournament'        => null,
            'home_club_player'  => [],
            'away_club_player'  => [],
            'club_players'      => [],
        ];

        $html = $this->render_strictly( 'match-teams-result', $vars );

        $this->assertStringContainsString( 'name="current_match_id" id="current_match_id" value="55"', $html );
        $this->assertStringContainsString( 'Team Home', $html );
        $this->assertStringContainsString( 'Team Away', $html );
        $this->assertStringContainsString( 'Home Cap', $html );
        $this->assertStringContainsString( 'Away Approver', $html );
    }

    public function test_match_teams_result_resolves_captain_ids_to_display_names(): void {
        $GLOBALS['wp_stubs_userdata'][10] = (object) [
            'ID'           => 10,
            'display_name' => 'Home Captain Name',
        ];
        $GLOBALS['wp_stubs_userdata'][20] = (object) [
            'ID'           => 20,
            'display_name' => 'Away Approver Name',
        ];
        $trap = $this->trap_racketmanager_locator();

        $match = new class() {
            public int $id = 55;
            public ?string $season = '2026';
            public ?string $home_team = 'home_id';
            public ?string $away_team = 'away_id';
            public bool $is_walkover = false;
            public bool $is_shared = false;
            public bool $is_retired = false;
            public ?string $walkover = null;
            public ?string $retired = null;
            public array $comments = [ 'result' => 'Pending check' ];
            public int $home_captain = 10;
            public int $away_captain = 20;
            public array $teams = [];
            public object $league;

            public function __construct() {
                $this->teams = [
                    'home' => (object) [ 'title' => 'Team Home' ],
                    'away' => (object) [ 'title' => 'Team Away' ],
                ];
                $this->league = (object) [
                    'title' => 'League 1',
                    'event' => (object) [
                        'name' => 'Event 1',
                        'competition' => (object) [
                            'type' => 'league',
                        ],
                    ],
                ];
            }

            public function get_rubbers(): array {
                return [];
            }
        };

        try {
            $html = $this->render_strictly(
                'match-teams-result',
                [
                    'match'             => $match,
                    'match_type'        => 'league',
                    'is_update_allowed' => (object) [
                        'user_can_update'     => true,
                        'user_type'           => 'captain',
                        'user_team'           => 'home',
                        'match_approval_mode' => false,
                        'match_update'        => true,
                    ],
                    'tournament'        => null,
                    'home_club_player'  => [],
                    'away_club_player'  => [],
                    'club_players'      => [],
                ]
            );
        } finally {
            $this->restore_racketmanager_locator( $trap );
        }

        $this->assertStringContainsString( 'Home Captain Name', $html );
        $this->assertStringContainsString( 'Away Approver Name', $html );
        $this->assertStringNotContainsString( '>10<', $html );
        $this->assertStringNotContainsString( '>20<', $html );
    }

    public function test_player_statistics_and_group_rows_renders_strictly(): void {
        $stats = [
            'totals' => [
                'total'     => [
                    'win'  => 10,
                    'loss' => 2,
                    'tie'  => 0,
                ],
                'breakdown' => [
                    'S' => [
                        'win'  => 6,
                        'loss' => 1,
                        'tie'  => 0,
                    ],
                    'D' => [
                        'win'  => 4,
                        'loss' => 1,
                        'tie'  => 0,
                    ],
                ],
            ],
            'seasons' => [
                '2026' => [
                    'total'     => [
                        'win'  => 5,
                        'loss' => 0,
                        'tie'  => 0,
                    ],
                    'breakdown' => [
                        'S' => [
                            'win'  => 5,
                            'loss' => 0,
                            'tie'  => 0,
                        ],
                    ],
                ],
            ],
        ];

        $vars = [ 'stats' => $stats ];

        $html = $this->render_strictly( 'player/statistics', $vars );

        $this->assertStringContainsString( 'Career', $html );
        $this->assertStringContainsString( 'Singles', $html );
        $this->assertStringContainsString( 'Doubles', $html );
        $this->assertStringContainsString( '10-2', $html );
    }

    public function test_includes_player_statistics_renders_strictly(): void {
        $player_statistics = [
            'detail' => [
                's' => [
                    'played'   => [ 'winner' => 5, 'loser' => 1, 'draw' => 0 ],
                    'sets'     => [ 'winner' => 10, 'loser' => 2 ],
                    'games'    => [ 'winner' => 60, 'loser' => 20 ],
                    'walkover' => 0,
                ],
            ],
            'total' => (object) [
                'matches_won'  => 8,
                'matches_lost' => 2,
                'matches_tie'  => 0,
                'sets_won'     => 16,
                'sets_lost'    => 4,
                'games_won'    => 90,
                'games_lost'   => 30,
                'walkover'     => 1,
            ],
        ];

        $vars = [
            'player_statistics' => $player_statistics,
            'walkover_allowed'  => true,
        ];

        $html = $this->render_strictly( 'includes/player-statistics', $vars );

        $this->assertStringContainsString( 'Summary', $html );
        $this->assertStringContainsString( 'Singles', $html );
        $this->assertStringContainsString( '8-2', $html );
        $this->assertStringContainsString( '16-4', $html );
        $this->assertStringContainsString( '90-30', $html );
    }

    public function test_form_member_account_renders_strictly(): void {
        $user = (object) [
            'firstname'     => 'Sarah',
            'surname'       => 'Connor',
            'gender'        => 'F',
            'btm'           => '11223344',
            'year_of_birth' => '1990',
            'email'         => 'sarah@example.com',
            'contactno'     => '012345678',
            'address'       => 'Tech City',
            'opt_ins'       => [ 'newsletter' ],
            'err_flds'      => [],
            'err_msgs'      => [],
        ];

        $vars = [
            'user'           => $user,
            'user_data'      => [ 'message' => 'Profile up to date' ],
            'opt_in_choices' => [ 'newsletter' => 'Monthly Newsletter' ],
        ];

        $html = $this->render_strictly( 'forms/form-member-account', $vars );

        $this->assertStringContainsString( 'value="Sarah"', $html );
        $this->assertStringContainsString( 'value="Connor"', $html );
        $this->assertStringContainsString( 'value="11223344"', $html );
        $this->assertStringContainsString( 'Profile up to date', $html );
        $this->assertStringContainsString( 'Monthly Newsletter', $html );
        $this->assertStringContainsString( 'checked', $html );
    }

    public function test_match_list_renders_strictly_with_date_format_and_traps_racketmanager_locator(): void {
        $GLOBALS['wp_stubs_options']['date_format'] = 'd/m/Y';
        $trap = $this->trap_racketmanager_locator();

        $league = (object) [
            'title' => 'Division 1',
            'event' => (object) [
                'name'        => 'Men Doubles',
                'competition' => (object) [ 'type' => 'league' ],
            ],
        ];
        $matches = [
            (object) [
                'leg'  => 1,
                'link' => '/match/100/',
                'date' => '2026-10-25 10:00:00',
            ],
        ];

        $vars = [
            'season'    => '2026',
            'home_team' => 'Westcliff 1',
            'away_team' => 'Leigh 1',
            'league'    => $league,
            'matches'   => $matches,
        ];

        try {
            $html = $this->render_strictly( 'match-list', $vars );
        } finally {
            $this->restore_racketmanager_locator( $trap );
            unset( $GLOBALS['wp_stubs_options']['date_format'] );
        }

        $this->assertStringContainsString( 'Westcliff 1', $html );
        $this->assertStringContainsString( 'Leigh 1', $html );
        $this->assertStringContainsString( '25/10/2026', $html );
    }

    public function test_match_single_renders_strictly_with_date_format_and_traps_racketmanager_locator(): void {
        $GLOBALS['wp_stubs_options']['date_format'] = 'd/m/Y';
        $trap = $this->trap_racketmanager_locator();

        $match = (object) [
            'id'         => 100,
            'title'      => 'Westcliff 1 vs Leigh 1',
            'date'       => '2026-10-25 14:00:00',
            'start_time' => '14:00',
            'location'   => 'Court 1',
            'score'      => '4-2',
            'match_day'  => 3,
            'post_id'    => 0,
        ];

        try {
            $html = $this->render_strictly( 'match', [ 'match' => $match ] );
        } finally {
            $this->restore_racketmanager_locator( $trap );
            unset( $GLOBALS['wp_stubs_options']['date_format'] );
        }

        $this->assertStringContainsString( 'Westcliff 1 vs Leigh 1', $html );
        $this->assertStringContainsString( '25/10/2026', $html );
        $this->assertStringContainsString( '4-2', $html );
        $this->assertStringContainsString( 'Match Day 3', $html );
    }
}
