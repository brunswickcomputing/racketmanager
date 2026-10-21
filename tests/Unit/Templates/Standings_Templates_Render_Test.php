<?php
/**
 * Standings Templates Render Test
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Templates
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Standings_Templates_Render_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $templates_dir  = ( defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH : dirname( __DIR__, 3 ) . '/' ) . 'templates/';
        $this->renderer = new Php_View_Renderer( $templates_dir );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_standings_template_renders_columns_based_on_view_model(): void {
        $competition = new Competition( name: 'League Comp', type: Competition_Type::LEAGUE, age_group: 'Open', id: 1 );
        $event       = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 1;
                $this->competition = $competition;
            }
        };

        $team1 = (object) [
            'id'           => 101,
            'title'        => 'Team Alpha',
            'status'       => 'active',
            'rank'         => 1,
            'prev_rank'    => 1,
            'played'       => 5,
            'won'          => 4,
            'tie'          => 0,
            'lost'         => 1,
            'win_percent'  => '80%',
            'sets'         => '12:3',
            'games'        => '50:20',
            'points'       => 10,
            'point_adjust' => 0,
            'bonus'        => 0,
        ];

        $league = new class( $event, $team1 ) extends League {
            public function __construct( Event $event, object $team1 ) {
                $this->id        = 10;
                $this->title     = 'Division 1';
                $this->event     = $event;
                $this->teams     = [ $team1 ];
                $this->standings = [
                    'pld'        => 1,
                    'won'        => 1,
                    'tie'        => 0,
                    'lost'       => 0,
                    'winPercent' => 0,
                    'sets'       => 1,
                    'games'      => 1,
                    'pts'        => 1,
                ];
            }
        };

        $team_links = [ 101 => '/league/division-1/team/alpha/' ];

        $vm = new League_Standings_View_Model(
            template: 'standings',
            league: $league,
            teams: [ $team1 ],
            team_links: $team_links,
        );

        $html = $this->renderer->render_to_string( 'standings', [
            'vm'         => $vm,
            'league'     => $league,
            'teams'      => [ $team1 ],
            'team_links' => $team_links,
        ] );

        // Should have Pld, W, Sets, Games, Pts
        self::assertStringContainsString( 'Team Alpha', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Pld\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*W\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Sets\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Games\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Pts\s*<\/th>/', $html );

        // Should not have T, L, PCT
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*T\s*<\/th>/', $html );
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*L\s*<\/th>/', $html );
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*PCT\s*<\/th>/', $html );
    }

    public function test_standings_template_renders_alternate_columns_when_configured(): void {
        $competition = new Competition( name: 'League Comp', type: Competition_Type::LEAGUE, age_group: 'Open', id: 1 );
        $event       = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 1;
                $this->competition = $competition;
            }
        };

        $team1 = (object) [
            'id'           => 101,
            'title'        => 'Team Beta',
            'status'       => 'active',
            'rank'         => 1,
            'prev_rank'    => 1,
            'played'       => 5,
            'won'          => 3,
            'tie'          => 1,
            'lost'         => 1,
            'win_percent'  => '60%',
            'sets'         => '10:5',
            'games'        => '40:30',
            'points'       => 7,
            'point_adjust' => 0,
            'bonus'        => 0,
        ];

        $league = new class( $event, $team1 ) extends League {
            public function __construct( Event $event, object $team1 ) {
                $this->id        = 10;
                $this->title     = 'Division 1';
                $this->event     = $event;
                $this->teams     = [ $team1 ];
                $this->standings = [
                    'pld'        => 1,
                    'won'        => 1,
                    'tie'        => 1,
                    'lost'       => 1,
                    'winPercent' => 1,
                    'sets'       => 0,
                    'games'      => 0,
                    'pts'        => 1,
                ];
            }
        };

        $team_links = [ 101 => '/league/division-1/team/beta/' ];

        $vm = new League_Standings_View_Model(
            template: 'standings',
            league: $league,
            teams: [ $team1 ],
            team_links: $team_links,
        );

        $html = $this->renderer->render_to_string( 'standings', [
            'vm'         => $vm,
            'league'     => $league,
            'teams'      => [ $team1 ],
            'team_links' => $team_links,
        ] );

        // Should have T, L, PCT
        self::assertStringContainsString( 'Team Beta', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*T\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*L\s*<\/th>/', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*PCT\s*<\/th>/', $html );

        // Should not have Sets, Games
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*Sets\s*<\/th>/', $html );
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*Games\s*<\/th>/', $html );
    }

    public function test_standings_template_falls_back_to_global_league_when_vm_is_not_provided(): void {
        $competition = new Competition( name: 'League Comp', type: Competition_Type::LEAGUE, age_group: 'Open', id: 1 );
        $event       = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 1;
                $this->competition = $competition;
            }
        };

        $team1 = (object) [
            'id'           => 101,
            'title'        => 'Team Gamma',
            'status'       => 'active',
            'rank'         => 1,
            'prev_rank'    => 1,
            'played'       => 5,
            'won'          => 5,
            'tie'          => 0,
            'lost'         => 0,
            'win_percent'  => '100%',
            'sets'         => '15:0',
            'games'        => '60:10',
            'points'       => 10,
            'point_adjust' => 0,
            'bonus'        => 0,
        ];

        $league = new class( $event, $team1 ) extends League {
            public function __construct( Event $event, object $team1 ) {
                $this->id        = 10;
                $this->title     = 'Division 1';
                $this->event     = $event;
                $this->teams     = [ $team1 ];
                $this->standings = [
                    'pld'        => 1,
                    'won'        => 1,
                    'tie'        => 0,
                    'lost'       => 0,
                    'winPercent' => 0,
                    'sets'       => 1,
                    'games'      => 0,
                    'pts'        => 1,
                ];
            }
        };

        $GLOBALS['league'] = $league;

        $html = $this->renderer->render_to_string( 'standings', [
            'vm'         => null,
            'league'     => $league,
            'teams'      => [ $team1 ],
            'team_links' => [ 101 => '/league/gamma/' ],
        ] );

        self::assertStringContainsString( 'Team Gamma', $html );
        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Sets\s*<\/th>/', $html );
        self::assertDoesNotMatchRegularExpression( '/<th[^>]*>\s*Games\s*<\/th>/', $html );
    }

    public function test_standings_last5_template_renders_last5_column_when_enabled(): void {
        $competition = new Competition( name: 'League Comp', type: Competition_Type::LEAGUE, age_group: 'Open', id: 1 );
        $event       = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 1;
                $this->competition = $competition;
            }
        };

        $team1 = (object) [
            'id'           => 101,
            'title'        => 'Team Delta',
            'status'       => 'active',
            'rank'         => 1,
            'prev_rank'    => 1,
            'played'       => 2,
            'won'          => 2,
            'tie'          => 0,
            'lost'         => 0,
            'win_percent'  => '100%',
            'sets'         => '4:0',
            'games'        => '24:6',
            'points'       => 4,
            'point_adjust' => 0,
            'bonus'        => 0,
            'history'      => [],
        ];

        $league = new class( $event, $team1 ) extends League {
            public function __construct( Event $event, object $team1 ) {
                $this->id        = 10;
                $this->title     = 'Division 1';
                $this->event     = $event;
                $this->teams     = [ $team1 ];
                $this->standings = [
                    'status' => 1,
                    'pld'    => 1,
                    'last5'  => 1,
                    'pts'    => 1,
                ];
            }
        };

        $vm = new League_Standings_View_Model(
            template: 'standings-last5',
            league: $league,
            teams: [ $team1 ],
            team_links: [ 101 => '/league/delta/' ],
        );

        $html = $this->renderer->render_to_string( 'standings-last5', [
            'vm'         => $vm,
            'league'     => $league,
            'teams'      => [ $team1 ],
            'team_links' => [ 101 => '/league/delta/' ],
        ] );

        self::assertMatchesRegularExpression( '/<th[^>]*>\s*Last 5\s*<\/th>/', $html );
    }

    public function test_archive_template_passes_vm_template_override_to_dispatched_tag(): void {
        $vm = new \Racketmanager\Presentation\View_Models\League_Archive_View_Model(
            league_id: 10,
            league_title: 'Division 1',
            league_seo_name: 'division-1',
            competition_type: 'league',
            icon_path: '',
            icon_url: '',
            calendar_icon_url: '',
            event_name: 'Men Singles',
            event_url: '/competition/men-singles/',
            competition_name: 'Summer League',
            competition_url: '/competition/summer-league/',
            current_season_name: '2026',
            menu_options: [
                [
                    'name'      => 'standings',
                    'title'     => 'Standings',
                    'available' => true,
                    'selected'  => true,
                ],
            ],
            templates: [
                'standings' => 'custom-standings.php',
            ],
        );

        $html = $this->renderer->render_to_string( 'archive', [
            'vm' => $vm,
        ] );

        self::assertStringContainsString( "template='custom-standings.php'", $html );
    }

    public function test_archive_template_falls_back_to_global_template_when_vm_template_is_empty(): void {
        $league = new class extends League {
            public function __construct() {
                $this->id = 10;
                $this->templates = ['standings' => 'global-tpl'];
            }
        };
        $GLOBALS['league'] = $league;

        $vm = new \Racketmanager\Presentation\View_Models\League_Archive_View_Model(
            league_id: 10,
            league_title: 'Division 1',
            league_seo_name: 'division-1',
            competition_type: 'league',
            icon_path: '',
            icon_url: '',
            calendar_icon_url: '',
            event_name: 'Men Singles',
            event_url: '/competition/men-singles/',
            competition_name: 'Summer League',
            competition_url: '/competition/summer-league/',
            current_season_name: '2026',
            menu_options: [
                [
                    'name'      => 'standings',
                    'title'     => 'Standings',
                    'available' => true,
                    'selected'  => true,
                ],
            ],
            templates: [],
        );

        $html = $this->renderer->render_to_string( 'archive', [
            'vm' => $vm,
        ] );

        self::assertStringContainsString( "template='global-tpl'", $html );
    }
}
