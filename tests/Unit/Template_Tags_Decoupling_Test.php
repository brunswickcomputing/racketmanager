<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Template_Tags_Decoupling_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_deprecated_files'] = [];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_wp_get_referer'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_orphan_templates_render_expected_output_and_trigger_deprecations(): void {
        $templates_dir = dirname( __DIR__, 2 ) . '/templates';

        $home_club = (object) [
            'id'              => 1,
            'name'            => 'Home Club',
            'address'         => '10 Tennis Court Lane',
            'match_secretary' => (object) [
                'display_name' => 'Jane Doe',
                'contactno'    => '0123456789',
            ],
        ];

        $away_club = (object) [
            'id'              => 2,
            'name'            => 'Away Club',
            'address'         => '20 Racket Road',
            'match_secretary' => (object) [
                'display_name' => 'John Smith',
                'contactno'    => '0987654321',
            ],
        ];

        $match_obj = (object) [
            'id'                 => 100,
            'league_id'          => 50,
            'season'             => '2026',
            'home_team'          => 'Home Team',
            'away_team'          => 'Away Team',
            'type'               => 'league',
            'round'              => 1,
            'match_day'          => 2,
            'date'               => '2026-10-25 10:00:00',
            'start_time'         => '10:00',
            'location'           => 'Court 1',
            'title'              => 'Home Team vs Away Team',
            'score'              => '4-2',
            'winner_id'          => null,
            'host'               => 'home',
            'is_walkover'        => false,
            'is_shared'          => false,
            'is_retired'         => false,
            'confirmed'          => 'P',
            'comments'           => [ 'result' => 'Good match' ],
            'get_rubbers'        => fn() => [],
            'league'             => (object) [
                'id'              => 50,
                'title'           => 'Division 1',
                'is_championship' => false,
                'num_sets'        => 3,
                'event'           => (object) [
                    'competition' => (object) [
                        'type' => 'league',
                    ],
                ],
            ],
            'teams'              => [
                'home' => (object) [ 'title' => 'Home Team', 'club' => $home_club ],
                'away' => (object) [ 'title' => 'Away Team', 'club' => $away_club ],
            ],
            'sets'               => [],
        ];

        $is_update_allowed = (object) [
            'user_can_update'     => true,
            'user_type'           => 'admin',
            'user_team'           => null,
            'match_approval_mode' => false,
            'match_update'        => true,
            'message'             => 'allowed',
        ];

        // 1. match.php
        $match = $match_obj;
        ob_start();
        include $templates_dir . '/match.php';
        $match_out = ob_get_clean();
        $this->assertStringContainsString( 'class="match" id="match-100"', $match_out );
        $this->assertStringContainsString( 'Match</h3>', $match_out );
        $this->assertStringContainsString( 'Home Team vs Away Team</h4>', $match_out );
        $this->assertStringContainsString( '4-2', $match_out );

        // 2. match-list.php
        $season    = '2026';
        $home_team = 'Home Team';
        $away_team = 'Away Team';
        $league    = (object) [
            'title' => 'Division 1',
            'event' => (object) [
                'name'        => 'Men Doubles',
                'competition' => (object) [ 'type' => 'league' ],
            ],
        ];
        $matches   = [
            (object) [
                'leg'  => 1,
                'link' => '/match/100/',
                'date' => '2026-10-25 10:00:00',
            ],
        ];
        ob_start();
        include $templates_dir . '/match-list.php';
        $list_out = ob_get_clean();
        $this->assertStringContainsString( 'team-match-header', $list_out );
        $this->assertStringContainsString( 'Men Doubles', $list_out );
        $this->assertStringContainsString( 'Matches', $list_out );
        $this->assertStringContainsString( 'Leg 1', $list_out );
        // Event link slug is computed in the template via Util::seo_url() (no legacy seo_url() helper).
        $this->assertStringContainsString( 'href="/leagues/men-doubles/2026/"', $list_out );

        // 3. match-teams.php
        $match = $match_obj;
        ob_start();
        include $templates_dir . '/match-teams.php';
        $teams_out = ob_get_clean();
        $this->assertStringContainsString( 'Rubbers', $teams_out );
        $this->assertStringContainsString( 'Location', $teams_out );
        $this->assertStringContainsString( 'Home Club', $teams_out );
        $this->assertStringContainsString( '10 Tennis Court Lane', $teams_out );
        $this->assertStringContainsString( 'Jane Doe', $teams_out );

        // 4. match-teams-result.php
        $match = new class( $match_obj ) {
            public int $id = 100;
            public bool $is_walkover = false;
            public bool $is_shared = false;
            public bool $is_retired = false;
            public function __construct( private object $orig ) {}
            public function get_rubbers(): array { return []; }
            public function __get( $name ) { return $this->orig->$name ?? null; }
        };
        $home_club_player = [ 'm' => [], 'f' => [] ];
        $away_club_player = [ 'm' => [], 'f' => [] ];
        ob_start();
        include $templates_dir . '/match-teams-result.php';
        $result_out = ob_get_clean();
        $this->assertStringContainsString( 'How does it work?', $result_out );
        $this->assertStringContainsString( 'Results', $result_out );
        $this->assertStringContainsString( 'Players', $result_out );
        // No referrer: the cancel link falls back to <type>/<league slug>/<season>/ built with Util::seo_url().
        $this->assertStringContainsString( 'id="cancelResults" href="league/division-1/2026/"', $result_out );

        // 5. forms/match-input.php
        $match = $match_obj;
        ob_start();
        include $templates_dir . '/forms/match-input.php';
        $input_out = ob_get_clean();
        $this->assertStringContainsString( '<form id="match-view"', $input_out );
        $this->assertStringContainsString( 'name="current_match_id" id="current_match_id" value="100"', $input_out );
        $this->assertStringContainsString( 'id="updateMatchResults"', $input_out );

        // Verify deprecation notices recorded for all 5 orphan templates
        $this->assertCount( 5, $GLOBALS['wp_stubs_deprecated_files'] );
        foreach ( $GLOBALS['wp_stubs_deprecated_files'] as $entry ) {
            $this->assertSame( '10.0.0', $entry['version'] );
            $this->assertStringContainsString( 'Orphan template', $entry['message'] );
        }
    }

    public function test_match_teams_result_view_mode_links_players_and_honours_referrer(): void {
        $templates_dir = dirname( __DIR__, 2 ) . '/templates';

        $competition = new class() {
            public string $type = 'league';
            public function get_type(): string {
                return 'league';
            }
        };
        $league      = (object) [
            'id'              => 50,
            'title'           => 'Division 1',
            'is_championship' => false,
            'num_sets'        => 3,
            'scoring'         => 'S',
            'event'           => (object) [
                'name'        => 'Men Doubles',
                'competition' => $competition,
            ],
        ];
        $rubber      = (object) [
            'id'            => 900,
            'rubber_number' => 1,
            'type'          => 'MS',
            'winner_id'     => null,
            'is_walkover'   => false,
            'is_retired'    => false,
            'is_abandoned'  => false,
            'is_invalid'    => false,
            'is_shared'     => false,
            'walkover'      => null,
            'retired'       => null,
            'invalid'       => null,
            'sets'          => [],
            'players'       => [
                'home' => [ 1 => (object) [ 'display_name' => "Chris O'Brien", 'class' => '', 'description' => '' ] ],
                'away' => [ 1 => (object) [ 'display_name' => 'Pat Lee', 'class' => '', 'description' => '', 'profile_url' => '/players/pat-lee/' ] ],
            ],
        ];
        $match_obj   = (object) [
            'id'          => 100,
            'season'      => '2026',
            'home_team'   => '1',
            'away_team'   => '2',
            'num_rubbers' => 1,
            'leg'         => null,
            'final_round' => null,
            'comments'    => [],
            'league'      => $league,
            'teams'       => [
                'home' => (object) [ 'title' => 'Home Team' ],
                'away' => (object) [ 'title' => 'Away Team' ],
            ],
        ];
        $match       = new class( $match_obj, $rubber ) {
            public int $id = 100;
            public bool $is_walkover = false;
            public bool $is_shared = false;
            public bool $is_retired = false;
            public function __construct( private object $orig, private object $rubber ) {}
            public function get_rubbers(): array { return [ $this->rubber ]; }
            public function __get( $name ) { return $this->orig->$name ?? null; }
        };

        // View mode (no update rights): player names are rendered as profile links.
        $is_update_allowed = (object) [
            'user_can_update'     => false,
            'user_type'           => 'player',
            'user_team'           => null,
            'match_approval_mode' => false,
            'match_update'        => false,
            'message'             => 'not allowed',
        ];
        $home_club_player  = [ 'm' => [], 'f' => [] ];
        $away_club_player  = [ 'm' => [], 'f' => [] ];
        ob_start();
        include $templates_dir . '/match-teams-result.php';
        $view_out = ob_get_clean();

        $this->assertStringContainsString( 'href="/leagues/men-doubles/2026/player/chris-obrien/"', $view_out );
        $this->assertStringContainsString( "Chris O'Brien", $view_out );
        $this->assertStringContainsString( 'href="/players/pat-lee/"', $view_out, 'A pre-supplied profile_url must be used as-is.' );
        $this->assertStringNotContainsString( 'player/pat-lee/', $view_out );
        $this->assertStringNotContainsString( 'id="cancelResults"', $view_out );

        // Edit mode with a referrer: the cancel link uses wp_get_referer() rather than the computed league URL.
        $GLOBALS['wp_stubs_wp_get_referer'] = 'https://example.test/leagues/division-1/2026/';
        $is_update_allowed                  = (object) [
            'user_can_update'     => true,
            'user_type'           => 'admin',
            'user_team'           => null,
            'match_approval_mode' => false,
            'match_update'        => true,
            'message'             => 'allowed',
        ];
        ob_start();
        include $templates_dir . '/match-teams-result.php';
        $edit_out = ob_get_clean();

        $this->assertStringContainsString( 'id="cancelResults" href="https://example.test/leagues/division-1/2026/"', $edit_out );
        $this->assertStringNotContainsString( 'href="league/division-1/2026/"', $edit_out );
        $this->assertStringNotContainsString( 'player/chris-obrien/', $edit_out, 'Edit mode renders selects, not profile links.' );
    }

    public function test_match_teams_result_renders_unique_comments_ids(): void {
        $templates_dir = dirname( __DIR__, 2 ) . '/templates';

        $competition = new class() {
            public string $type = 'league';
            public function get_type(): string {
                return 'league';
            }
        };
        $league      = (object) [
            'id'              => 50,
            'title'           => 'Division 1',
            'is_championship' => false,
            'num_sets'        => 3,
            'scoring'         => 'S',
            'event'           => (object) [
                'name'        => 'Men Doubles',
                'competition' => $competition,
            ],
        ];
        $match_obj   = (object) [
            'id'            => 100,
            'season'        => '2026',
            'home_team'     => '1',
            'away_team'     => '2',
            'home_captain'  => 10,
            'away_captain'  => 20,
            'captain_names' => [ 'home' => 'Alice Home', 'away' => 'Bob Away' ],
            'num_rubbers'   => 0,
            'leg'           => null,
            'final_round'   => null,
            'comments'      => [
                'result' => 'Match overall comment',
                'home'   => 'Home team comment',
                'away'   => 'Away team comment',
            ],
            'league'        => $league,
            'teams'         => [
                'home' => (object) [ 'title' => 'Home Team' ],
                'away' => (object) [ 'title' => 'Away Team' ],
            ],
        ];
        $match       = new class( $match_obj ) {
            public int $id = 100;
            public bool $is_walkover = false;
            public bool $is_shared = false;
            public bool $is_retired = false;
            public function __construct( private object $orig ) {}
            public function get_rubbers(): array { return []; }
            public function __get( $name ) { return $this->orig->$name ?? null; }
            public function __isset( $name ) { return isset( $this->orig->$name ); }
        };

        $is_update_allowed = (object) [
            'user_can_update'     => true,
            'user_type'           => 'captain',
            'user_team'           => 'home',
            'match_approval_mode' => false,
            'match_update'        => true,
            'message'             => 'allowed',
        ];
        $home_club_player  = [ 'm' => [], 'f' => [] ];
        $away_club_player  = [ 'm' => [], 'f' => [] ];

        ob_start();
        include $templates_dir . '/match-teams-result.php';
        $output = ob_get_clean();

        // Overall match comments
        $this->assertStringContainsString( 'name="matchComments[result]" id="matchComments"', $output );
        $this->assertStringContainsString( '<label for="matchComments">', $output );

        // Opponent specific comments
        $this->assertStringContainsString( 'name="matchComments[home]" id="matchComments-home"', $output );
        $this->assertStringContainsString( '<label for="matchComments-home">', $output );
        $this->assertStringContainsString( 'name="matchComments[away]" id="matchComments-away"', $output );
        $this->assertStringContainsString( '<label for="matchComments-away">', $output );

        // Ensure id="matchComments" is unique and not reused for opponent fields
        $this->assertSame( 1, substr_count( $output, 'id="matchComments"' ) );
        $this->assertSame( 1, substr_count( $output, 'id="matchComments-home"' ) );
        $this->assertSame( 1, substr_count( $output, 'id="matchComments-away"' ) );
    }
}
