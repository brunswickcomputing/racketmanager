<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\View_Models\League_Archive_View_Model;

class League_Archive_Presenter_Test extends TestCase {
    public function test_present_archive_standard_league(): void {
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'j M Y' : $default
        );

        $presenter = new League_Archive_Presenter( $option_gateway );

        $competition = new Competition(
            name: 'Summer League 2026',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 10
        );
        $competition->is_team_entry   = true;
        $competition->is_championship = false;
        $competition->set_seasons( [
            '2026' => [
                'name'       => '2026',
                'date_start' => '2026-09-21',
                'date_end'   => '2027-03-21',
            ],
        ] );

        $event = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 20;
                $this->name        = 'Men\'s Doubles';
                $this->is_box      = false;
                $this->competition = $competition;
            }
        };

        $league = new class( $event ) extends League {
            public function __construct( Event $event ) {
                $this->id             = 100;
                $this->title          = 'Division 1';
                $this->current_season = [
                    'name'           => '2026',
                    'home_away'      => 'true',
                    'num_match_days' => 14,
                    'match_dates'    => [],
                ];
                $this->event          = $event;
            }
        };

        $seasons = [
            [ 'name' => '2025' ],
            [ 'name' => '2026' ],
        ];

        $league2 = new class extends League {
            public function __construct() {
                $this->id    = 101;
                $this->title = 'Division 2';
            }
        };

        $leagues = [ $league, $league2 ];

        $vm = $presenter->present_archive(
            league: $league,
            event: $event,
            seasons: $seasons,
            leagues: $leagues,
            tab: 'standings',
            post_id: 42
        );

        $this->assertInstanceOf( League_Archive_View_Model::class, $vm );
        $this->assertSame( 100, $vm->league_id );
        $this->assertSame( 'Division 1', $vm->league_title );
        $this->assertSame( 'division-1', $vm->league_seo_name );
        $this->assertSame( 'league', $vm->competition_type );
        $this->assertSame( 'assets/icons/bootstrap-icons.svg#table', $vm->icon_path );
        $this->assertStringContainsString( 'assets/icons/bootstrap-icons.svg#table', $vm->icon_url );
        $this->assertStringContainsString( 'assets/icons/bootstrap-icons.svg#calendar-range-fill', $vm->calendar_icon_url );
        $this->assertSame( 'Men\'s Doubles', $vm->event_name );
        $this->assertSame( '/leagues/mens-doubles/2026/', $vm->event_url );
        $this->assertSame( 'Summer League 2026', $vm->competition_name );
        $this->assertSame( '/summer-league-2026/2026/', $vm->competition_url );
        $this->assertSame( '2026', $vm->current_season_name );
        $this->assertTrue( $vm->has_date_range );
        $this->assertSame( '21 Sep 2026', $vm->date_start_display );
        $this->assertSame( '21 Mar 2027', $vm->date_end_display );
        $this->assertSame( 42, $vm->post_id );
        $this->assertSame( '/league/division-1/', $vm->pagename );
        $this->assertSame( 'Season', $vm->season_title );
        $this->assertSame( 'Seasons', $vm->season_selection );
        $this->assertSame( 'standings', $vm->tab );

        $this->assertCount( 2, $vm->season_options );
        $this->assertSame( '2026', $vm->season_options[0]['name'] );
        $this->assertSame( '2026', $vm->season_options[0]['label'] );
        $this->assertTrue( $vm->season_options[0]['selected'] );
        $this->assertSame( '2025', $vm->season_options[1]['name'] );
        $this->assertFalse( $vm->season_options[1]['selected'] );

        $this->assertCount( 2, $vm->league_options );
        $this->assertSame( 'division-1', $vm->league_options[0]['slug'] );
        $this->assertTrue( $vm->league_options[0]['selected'] );
        $this->assertSame( 'division-2', $vm->league_options[1]['slug'] );
        $this->assertFalse( $vm->league_options[1]['selected'] );

        $this->assertArrayHasKey( 'standings', $vm->menu_options );
        $this->assertArrayHasKey( 'crosstable', $vm->menu_options );
        $this->assertArrayHasKey( 'matches', $vm->menu_options );
        $this->assertArrayHasKey( 'teams', $vm->menu_options );
        $this->assertArrayHasKey( 'players', $vm->menu_options );
        $this->assertTrue( $vm->menu_options['standings']['selected'] );
        $this->assertFalse( $vm->menu_options['matches']['selected'] );
        $this->assertFalse( $vm->is_championship );
    }

    public function test_present_archive_box_league(): void {
        $presenter = new League_Archive_Presenter();

        $competition = new Competition(
            name: 'Singles Box League',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 11
        );
        $competition->is_team_entry   = false;
        $competition->is_championship = false;

        $event = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 21;
                $this->name        = 'Men\'s Box';
                $this->is_box      = true;
                $this->competition = $competition;
            }
        };

        $league = new class( $event ) extends League {
            public function __construct( Event $event ) {
                $this->id             = 105;
                $this->title          = 'Box 1';
                $this->current_season = [ 'name' => 'Round 1' ];
                $this->event          = $event;
            }
        };

        $vm = $presenter->present_archive(
            league: $league,
            event: $event,
            seasons: [ [ 'name' => 'Round 1' ] ],
            leagues: [ $league ],
            tab: 'matches'
        );

        $this->assertSame( 'Round', $vm->season_title );
        $this->assertSame( 'Rounds', $vm->season_selection );
        $this->assertSame( 'Round - Round 1', $vm->season_options[0]['label'] );
        $this->assertArrayHasKey( 'teams', $vm->menu_options );
        $this->assertArrayNotHasKey( 'players', $vm->menu_options );
        $this->assertTrue( $vm->menu_options['matches']['selected'] );
        $this->assertFalse( $vm->menu_options['standings']['selected'] );
    }

    public function test_present_archive_championship_mode(): void {
        $presenter = new League_Archive_Presenter();

        $competition = new Competition(
            name: 'Cup Championship',
            type: Competition_Type::CUP,
            age_group: 'Open',
            id: 12
        );
        $competition->is_team_entry   = true;
        $competition->is_championship = true;

        $event = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 22;
                $this->name        = 'Championship Finals';
                $this->is_box      = false;
                $this->competition = $competition;
            }
        };

        $league = new class( $event ) extends League {
            public function __construct( Event $event ) {
                $this->id             = 110;
                $this->title          = 'Finals League';
                $this->season         = '2026';
                $this->current_season = [ 'name' => '2026' ];
                $this->event          = $event;
            }
        };

        $vm = $presenter->present_archive(
            league: $league,
            event: $event,
            seasons: [ [ 'name' => '2026' ] ],
            leagues: [ $league ],
            tab: 'standings'
        );

        $this->assertTrue( $vm->is_championship );
        $this->assertSame( '2026', $vm->championship_season );
        $this->assertSame( 'cup', $vm->competition_type );
        $this->assertSame( 'assets/icons/bootstrap-icons.svg#trophy-fill', $vm->icon_path );
    }

    public function test_format_date_with_null_and_zero_dates(): void {
        $presenter = new League_Archive_Presenter();

        $this->assertSame( '', $presenter->format_date( null ) );
        $this->assertSame( '', $presenter->format_date( '' ) );
        $this->assertSame( '', $presenter->format_date( '0000-00-00' ) );
        $this->assertSame( '', $presenter->format_date( '0000-00-00 00:00:00' ) );
        $this->assertSame( '15 Jun 2026', $presenter->format_date( '2026-06-15' ) );
    }

    public function test_present_archive_without_competition_season_dates_has_no_date_range(): void {
        $presenter = new League_Archive_Presenter();

        $competition = new Competition(
            name: 'Winter League',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 13
        );
        $competition->set_seasons( [
            '2026' => [
                'name' => '2026',
            ],
        ] );

        $event = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 23;
                $this->name        = 'Mixed Doubles';
                $this->is_box      = false;
                $this->competition = $competition;
            }
        };

        $league = new class( $event ) extends League {
            public function __construct( Event $event ) {
                $this->id             = 120;
                $this->title          = 'Division 3';
                $this->current_season = [
                    'name'           => '2026',
                    'home_away'      => 'true',
                    'num_match_days' => 10,
                    'match_dates'    => [],
                ];
                $this->event          = $event;
            }
        };

        $vm = $presenter->present_archive(
            league: $league,
            event: $event,
            seasons: [ [ 'name' => '2026' ] ],
            leagues: [ $league ],
            tab: 'standings'
        );

        $this->assertFalse( $vm->has_date_range );
        $this->assertNull( $vm->date_start_display );
        $this->assertNull( $vm->date_end_display );
    }

    public function test_present_archive_maps_templates_into_view_model(): void {
        $presenter = new League_Archive_Presenter();

        $competition = new Competition( name: 'League Comp', type: Competition_Type::LEAGUE, age_group: 'Open', id: 1 );
        $event       = new class( $competition ) extends Event {
            public function __construct( Competition $competition ) {
                $this->id          = 1;
                $this->competition = $competition;
            }
        };

        $league = new class( $event ) extends League {
            public function __construct( Event $event ) {
                $this->id        = 10;
                $this->title     = 'Division 1';
                $this->event     = $event;
                $this->templates = [
                    'standings' => 'standings-custom.php',
                    'matches'   => 'matches-grid.php',
                ];
            }
        };

        $vm = $presenter->present_archive(
            league: $league,
            event: $event,
            seasons: [],
            leagues: [ $league ],
            tab: 'standings'
        );

        $this->assertSame( [
            'standings' => 'standings-custom.php',
            'matches'   => 'matches-grid.php',
        ], $vm->templates );
        $this->assertSame( 'standings-custom.php', $vm->get_template_filename( 'standings' ) );
        $this->assertSame( 'matches-grid.php', $vm->get_template_filename( 'matches' ) );
        $this->assertSame( '', $vm->get_template_filename( 'unknown' ) );
    }
}
