<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\DTOs\Competition_Page_Result;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Presentation\View_Models\Draw_Fixture_View_Model;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use RuntimeException;

final class Competition_Shortcode_Presenter_Test extends TestCase {
    private function create_presenter(
        ?View_Renderer_Interface $renderer = null,
        ?Option_Gateway_Interface $option_gateway = null
    ): Competition_Shortcode_Presenter {
        $renderer ??= $this->createStub( View_Renderer_Interface::class );
        return new Competition_Shortcode_Presenter(
            $renderer,
            new Club_Presenter(),
            new Player_Presenter(),
            $option_gateway
        );
    }

    public function test_competition_teams_links_prefers_team_id_and_team_name(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'type'           => 'league',
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];

        // Object payload with conflicting team_id vs id and team_name vs name
        $team_obj = (object) [
            'team_id'     => 101,
            'id'          => 202,
            'team_name'   => 'First Team',
            'name'        => 'Second Team',
            'league_name' => 'Division 1',
            'club'        => (object) [ 'shortcode' => 'WTC' ],
        ];

        // Array payload with conflicting team_id vs id and team_name vs name
        $team_arr = [
            'team_id'        => 303,
            'id'             => 404,
            'team_name'      => 'Third Team',
            'name'           => 'Fourth Team',
            'league_name'    => 'Division 2',
            'club_shortcode' => 'LTC',
        ];

        $links = $presenter->present_competition_teams_links( $competition, [ $team_obj, $team_arr ] );

        // Should be keyed by team_id (101 and 303), not id (202 and 404)
        $this->assertArrayHasKey( 101, $links );
        $this->assertArrayNotHasKey( 202, $links );
        $this->assertArrayHasKey( 303, $links );
        $this->assertArrayNotHasKey( 404, $links );

        // URLs should contain team_name slug (first-team and third-team), not name slug
        $this->assertSame( '/league/division-1/2026/team/first-team/', $links[101]['team_link'] );
        $this->assertSame( '/summer-league/2026/club/wtc/', $links[101]['club_link'] );
        $this->assertSame( '/league/division-1/2026/', $links[101]['league_link'] );

        $this->assertSame( '/league/division-2/2026/team/third-team/', $links[303]['team_link'] );
        $this->assertSame( '/summer-league/2026/club/ltc/', $links[303]['club_link'] );
        $this->assertSame( '/league/division-2/2026/', $links[303]['league_link'] );
    }

    public function test_competition_club_detail_team_links_uses_id_and_name_only(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'type'            => 'league',
            'name'            => 'Summer League',
            'current_season'  => [ 'name' => '2026' ],
            'is_championship' => false,
        ];

        $team_obj = (object) [
            'team_id'      => 999,
            'id'           => 12,
            'team_name'    => 'Wrong Team Name',
            'name'         => 'Men A',
            'league_title' => 'Division One',
        ];

        $team_arr = [
            'team_id'      => 888,
            'id'           => 34,
            'team_name'    => 'Ignored Name',
            'name'         => 'Ladies B',
            'league_title' => 'Division Two',
        ];

        $club = (object) [
            'shortcode' => 'WTC',
            'teams'     => [ $team_obj, $team_arr ],
            'players'   => [],
        ];

        $result = $presenter->present_competition_club_detail_links( $competition, $club );

        $this->assertSame( '/clubs/wtc/', $result['club_url'] );
        $this->assertSame( [], $result['player_links'] );
        $this->assertArrayHasKey( 12, $result['team_links'] );
        $this->assertArrayNotHasKey( 999, $result['team_links'] );
        $this->assertArrayHasKey( 34, $result['team_links'] );
        $this->assertArrayNotHasKey( 888, $result['team_links'] );

        $this->assertSame( '/league/division-one/2026/team/men-a/', $result['team_links'][12]['team_link'] );
        $this->assertSame( '/league/division-one/2026/', $result['team_links'][12]['league_link'] );

        $this->assertSame( '/league/division-two/2026/team/ladies-b/', $result['team_links'][34]['team_link'] );
        $this->assertSame( '/league/division-two/2026/', $result['team_links'][34]['league_link'] );
    }

    public function test_competition_club_detail_team_links_championship_routing(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'type'            => 'tournament',
            'name'            => 'Club Championship',
            'current_season'  => [ 'name' => '2026' ],
            'is_championship' => true,
        ];

        $team = (object) [
            'id'         => 55,
            'name'       => 'Pair Alpha',
            'event_name' => 'Men Singles',
        ];

        $club = (object) [
            'shortcode' => 'WTC',
            'teams'     => [ $team ],
            'players'   => [],
        ];

        $result = $presenter->present_competition_club_detail_links( $competition, $club );

        $this->assertSame( '/tournament/men-singles/2026/team/pair-alpha/', $result['team_links'][55]['team_link'] );
        $this->assertSame( '/tournaments/men-singles/2026/', $result['team_links'][55]['league_link'] );
    }

    public function test_event_club_detail_team_links_uses_id_and_name_only(): void {
        $presenter = $this->create_presenter();
        $event     = (object) [
            'name'           => 'Men 1st Division',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => 'league' ],
        ];

        $team_obj = (object) [
            'team_id'      => 777,
            'id'           => 10,
            'team_name'    => 'Wrong Team',
            'name'         => 'Team 1',
            'league_title' => 'Division 1',
        ];

        $team_arr = [
            'team_id'      => 666,
            'id'           => 20,
            'team_name'    => 'Wrong Team Arr',
            'name'         => 'Team 2',
            'league_title' => 'Division 2',
        ];

        $club = (object) [
            'shortcode' => 'ETC',
            'teams'     => [ $team_obj, $team_arr ],
            'players'   => [],
        ];

        $result = $presenter->present_event_club_detail_links( $event, $club );

        $this->assertSame( '/clubs/etc/', $result['club_url'] );
        $this->assertSame( [], $result['player_links'] );
        $this->assertArrayHasKey( 10, $result['team_links'] );
        $this->assertArrayNotHasKey( 777, $result['team_links'] );
        $this->assertArrayHasKey( 20, $result['team_links'] );
        $this->assertArrayNotHasKey( 666, $result['team_links'] );

        $this->assertSame( '/league/division-1/2026/team/team-1/', $result['team_links'][10]['team_link'] );
        $this->assertSame( '/league/division-1/2026/', $result['team_links'][10]['league_link'] );

        $this->assertSame( '/league/division-2/2026/team/team-2/', $result['team_links'][20]['team_link'] );
        $this->assertSame( '/league/division-2/2026/', $result['team_links'][20]['league_link'] );
    }

    public function test_event_teams_links_uses_id_and_name_only_and_handles_box_leagues(): void {
        $presenter = $this->create_presenter();
        $event     = (object) [
            'name'           => 'Box League Group A',
            'current_season' => [ 'name' => '2026' ],
            'is_box'         => true,
            'competition'    => (object) [ 'type' => 'league' ],
        ];

        $team_obj = (object) [
            'team_id'      => 501,
            'id'           => 1,
            'team_name'    => 'Ignored Box Team',
            'name'         => 'Box Player 1',
            'league_title' => 'Box Group 1',
            'club'         => (object) [ 'shortcode' => 'BOXC' ],
        ];

        $team_arr = [
            'team_id'        => 502,
            'id'             => 2,
            'team_name'      => 'Ignored Box Team 2',
            'name'           => 'Box Player 2',
            'league_title'   => 'Box Group 2',
            'club_shortcode' => 'BOXD',
        ];

        $links = $presenter->present_event_teams_links( $event, [ $team_obj, $team_arr ] );

        $this->assertArrayHasKey( 1, $links );
        $this->assertArrayNotHasKey( 501, $links );
        $this->assertArrayHasKey( 2, $links );
        $this->assertArrayNotHasKey( 502, $links );

        $this->assertSame( '/league/box-group-1/2026/team/box-player-1/', $links[1]['team_link'] );
        $this->assertSame( '/leagues/box-league-group-a/2026/club/boxc/', $links[1]['club_link'] );
        $this->assertSame( '/league/box-group-1/round-2026/', $links[1]['league_link'] );

        $this->assertSame( '/league/box-group-2/2026/team/box-player-2/', $links[2]['team_link'] );
        $this->assertSame( '/leagues/box-league-group-a/2026/club/boxd/', $links[2]['club_link'] );
        $this->assertSame( '/league/box-group-2/round-2026/', $links[2]['league_link'] );
    }

    public function test_club_detail_player_links_prefers_fullname_over_display_name(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'type'           => 'league',
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];

        $player_obj = new class() {
            public int $id = 7;
            public string $display_name = 'Johnny';
            public function get_fullname(): string {
                return 'John Doe';
            }
        };

        $player_arr = [
            'id'           => 8,
            'display_name' => 'JaneD',
            'fullname'     => 'Jane Doe',
        ];

        $club = (object) [
            'shortcode' => 'WTC',
            'teams'     => [],
            'players'   => [ $player_obj, $player_arr ],
        ];

        // Competition club detail player links
        $comp_res = $presenter->present_competition_club_detail_links( $competition, $club );
        $this->assertSame( '/summer-league/2026/player/john-doe/', $comp_res['player_links'][7] );
        $this->assertSame( '/summer-league/2026/player/jane-doe/', $comp_res['player_links'][8] );

        // Event club detail player links
        $event = (object) [
            'name'           => 'Men Singles',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => 'tournament' ],
        ];
        $event_res = $presenter->present_event_club_detail_links( $event, $club );
        $this->assertSame( '/tournaments/men-singles/2026/player/john-doe/', $event_res['player_links'][7] );
        $this->assertSame( '/tournaments/men-singles/2026/player/jane-doe/', $event_res['player_links'][8] );
    }

    public function test_grouped_player_links_prefers_display_name_over_fullname(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];

        $player_obj = new class() {
            public int $id = 15;
            public string $display_name = 'Johnny';
            public function get_fullname(): string {
                return 'John Doe';
            }
        };

        $player_arr = [
            'id'           => 16,
            'display_name' => 'JaneD',
            'fullname'     => 'Jane Doe',
        ];

        // Competition grouped players
        $comp_links = $presenter->present_competition_players_links( $competition, [ [ $player_obj, $player_arr ] ] );
        $this->assertSame( '/summer-league/2026/player/johnny/', $comp_links[15] );
        $this->assertSame( '/summer-league/2026/player/janed/', $comp_links[16] );

        // Event grouped players
        $event = (object) [
            'name'           => 'Premier Event',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => 'league' ],
        ];
        $event_links = $presenter->present_event_players_links( $event, [ [ $player_obj, $player_arr ] ] );
        $this->assertSame( '/leagues/premier-event/2026/player/johnny/', $event_links[15] );
        $this->assertSame( '/leagues/premier-event/2026/player/janed/', $event_links[16] );
    }

    public function test_player_id_extraction_handles_id_and_fallback_to_uppercase_id(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];

        $player_with_both = (object) [
            'id'           => 100,
            'ID'           => 200,
            'display_name' => 'Alice',
        ];

        $player_with_uppercase_only = (object) [
            'ID'           => 300,
            'display_name' => 'Bob',
        ];

        $links = $presenter->present_competition_players_links(
            $competition,
            [ [ $player_with_both, $player_with_uppercase_only ] ]
        );

        $this->assertArrayHasKey( 100, $links );
        $this->assertArrayNotHasKey( 200, $links );
        $this->assertArrayHasKey( 300, $links );
        $this->assertSame( '/summer-league/2026/player/alice/', $links[100] );
        $this->assertSame( '/summer-league/2026/player/bob/', $links[300] );
    }

    public function test_present_event_draw_preserves_clone_semantics_and_handles_empty_leagues(): void {
        $presenter = $this->create_presenter();
        $event     = (object) [
            'name'    => 'Summer Tournament',
            'leagues' => [],
        ];

        $presented = $presenter->present_event_draw( $event );
        $this->assertNotSame( $event, $presented );
        $this->assertSame( 'Summer Tournament', $presented->name );
        $this->assertSame( [], $presented->leagues );

        $league_obj = (object) [
            'id'     => 1,
            'title'  => 'Round Robin',
            'finals' => null,
        ];
        $league_arr = [
            'id'     => 2,
            'title'  => 'Consolation',
            'finals' => null,
        ];

        $event->leagues = [ $league_obj, $league_arr ];
        $presented      = $presenter->present_event_draw( $event );

        $this->assertNotSame( $event, $presented );
        $this->assertCount( 2, $presented->leagues );
        $this->assertNotSame( $league_obj, $presented->leagues[0] );
        $this->assertSame( 1, $presented->leagues[0]->id );
        $this->assertIsObject( $presented->leagues[1] );
        $this->assertSame( 2, $presented->leagues[1]->id );
    }

    public function test_present_event_draw_transforms_finals_via_draw_presenter(): void {
        $presenter = $this->create_presenter();
        $event     = (object) [
            'name'        => 'Summer Tournament',
            'competition' => (object) [
                'type' => 'tournament',
                'name' => 'Tournament Competition',
            ],
            'leagues'     => [
                (object) [
                    'id'     => 10,
                    'title'  => 'Main Draw',
                    'finals' => [
                        (object) [
                            'name'     => 'Final',
                            'fixtures' => [
                                (object) [
                                    'fixture'   => (object) [
                                        'id'        => 901,
                                        'home_team' => 11,
                                        'away_team' => 12,
                                        'winner_id' => 11,
                                        'leg'       => null,
                                    ],
                                    'home_team' => (object) [
                                        'team' => (object) [
                                            'id'    => 11,
                                            'title' => 'Home Pair',
                                        ],
                                    ],
                                    'away_team' => (object) [
                                        'team' => (object) [
                                            'id'    => 12,
                                            'title' => 'Away Pair',
                                        ],
                                    ],
                                    'link'      => '/match/901/',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $presented = $presenter->present_event_draw( $event );

        $this->assertNotSame( $event, $presented );
        $this->assertCount( 1, $presented->leagues );
        $league = $presented->leagues[0];
        $this->assertCount( 1, $league->finals );

        $final = $league->finals[0];
        $this->assertCount( 1, $final->fixtures );
        $fixture = $final->fixtures[0];

        $this->assertInstanceOf( Draw_Fixture_View_Model::class, $fixture );
        $this->assertSame( '/match/901/', $fixture->match_link );
        $this->assertSame( 'Home Pair', $fixture->home->team_name );
        $this->assertSame( 'Away Pair', $fixture->away->team_name );
        $this->assertSame( 'winner', $fixture->home->winner_class );
        $this->assertNull( $fixture->away->winner_class );
    }

    public function test_event_teams_links_non_box_league_and_championship_routing(): void {
        $presenter = $this->create_presenter();
        $event     = (object) [
            'name'           => 'Division One Men',
            'current_season' => [ 'name' => '2026' ],
            'is_box'         => false,
            'competition'    => (object) [ 'type' => 'league' ],
        ];

        $team_obj = (object) [
            'id'           => 10,
            'name'         => 'Team A',
            'league_title' => 'Division 1',
            'club'         => (object) [ 'shortcode' => 'WTC' ],
        ];

        $team_arr = [
            'id'             => 20,
            'name'           => 'Team B',
            'league_title'   => 'Division 1',
            'club_shortcode' => 'LTC',
        ];

        $links = $presenter->present_event_teams_links( $event, [ $team_obj, $team_arr ] );

        $this->assertArrayHasKey( 10, $links );
        $this->assertArrayHasKey( 20, $links );

        $this->assertSame( '/league/division-1/2026/team/team-a/', $links[10]['team_link'] );
        $this->assertSame( '/leagues/division-one-men/2026/club/wtc/', $links[10]['club_link'] );
        $this->assertSame( '/league/division-1/2026/', $links[10]['league_link'] );

        $this->assertSame( '/league/division-1/2026/team/team-b/', $links[20]['team_link'] );
        $this->assertSame( '/leagues/division-one-men/2026/club/ltc/', $links[20]['club_link'] );
        $this->assertSame( '/league/division-1/2026/', $links[20]['league_link'] );
    }

    public function test_present_header_icons_backed_enum_and_date_boundaries(): void {
        $presenter = $this->create_presenter();

        // 1. Icons for various competition types
        $league_comp = (object) [ 'name' => 'League 1', 'type' => 'league' ];
        $header_l    = $presenter->present_header( $league_comp );
        $this->assertSame( 'assets/icons/bootstrap-icons.svg#table', $header_l->icon_path );

        $cup_comp = (object) [ 'name' => 'Cup 1', 'type' => Competition_Type::CUP ];
        $header_c = $presenter->present_header( $cup_comp );
        $this->assertSame( 'assets/icons/bootstrap-icons.svg#trophy-fill', $header_c->icon_path );

        $tourn_comp = (object) [ 'name' => 'Tournament 1', 'type' => Competition_Type::TOURNAMENT ];
        $header_t   = $presenter->present_header( $tourn_comp );
        $this->assertSame( 'assets/icons/lta-icons.svg#icon-bracket', $header_t->icon_path );

        $unknown_comp = (object) [ 'name' => 'Special 1', 'type' => 'other' ];
        $header_u     = $presenter->present_header( $unknown_comp );
        $this->assertNull( $header_u->icon_path );

        $empty_type_comp = (object) [ 'name' => 'Empty Type', 'type' => '' ];
        $header_empty    = $presenter->present_header( $empty_type_comp );
        $this->assertNull( $header_empty->icon_path );

        // 2. Date ranges and format
        $season_data = [
            'name'       => '2026',
            'venue_name' => 'Central Courts',
            'date_start' => '2026-05-01',
            'date_end'   => '2026-08-31',
        ];
        $header_custom = $presenter->present_header(
            competition: $league_comp,
            competition_season: $season_data,
            season: '2026',
            seasons: [ '2025', '2026' ],
            post_id: 15,
            pagename: 'league-1',
            date_format: 'd/m/Y'
        );
        $this->assertTrue( $header_custom->has_date_range );
        $this->assertSame( '01/05/2026', $header_custom->date_start_display );
        $this->assertSame( '31/08/2026', $header_custom->date_end_display );
        $this->assertSame( 'Central Courts', $header_custom->venue_name );
        $this->assertSame( '2026', $header_custom->season_title );
        $this->assertFalse( $header_custom->show_season_selector );

        // 3. Empty season shows season selector
        $header_empty_season = $presenter->present_header(
            competition: $league_comp,
            competition_season: $season_data,
            season: null
        );
        $this->assertNull( $header_empty_season->season_title );
        $this->assertTrue( $header_empty_season->show_season_selector );
    }

    public function test_present_event_header_routes_templates_and_header_levels(): void {
        $presenter = $this->create_presenter();

        // 1. Club event route
        $club_event = (object) [
            'name'        => 'Men Singles',
            'competition' => (object) [
                'type'           => 'league',
                'current_season' => [
                    'name'       => '2026',
                    'date_start' => '2026-06-01',
                    'date_end'   => '2026-06-30',
                ],
            ],
        ];
        $club = (object) [ 'shortcode' => 'WTC' ];

        $vm_club = $presenter->present_event_header(
            event: $club_event,
            club: $club,
            curr_season: '2026',
            pagename_query: 'club/event'
        );
        $this->assertSame( '/clubs/wtc/event/men-singles/', $vm_club->pagename );

        // 2. Tournament competition type preserves query pagename
        $tourn_event = (object) [
            'name'        => 'Men Open',
            'competition' => (object) [
                'type'           => 'tournament',
                'current_season' => [ 'name' => '2026' ],
            ],
        ];
        $vm_tourn = $presenter->present_event_header(
            event: $tourn_event,
            curr_season: '2026',
            pagename_query: 'custom-tournament-page'
        );
        $this->assertSame( 'custom-tournament-page', $vm_tourn->pagename );

        // 3. Standard league route
        $standard_event = (object) [
            'name'        => 'Division One',
            'is_box'      => true,
            'competition' => (object) [
                'type'           => Competition_Type::LEAGUE,
                'name'           => 'Summer League',
                'current_season' => [ 'name' => '2026' ],
            ],
        ];
        $vm_standard = $presenter->present_event_header(
            event: $standard_event,
            curr_season: '2026',
            header_level: 0,
            standings_template: 'constitution'
        );
        $this->assertSame( '/leagues/division-one/', $vm_standard->pagename );
        $this->assertSame( 1, $vm_standard->header_level );
        $this->assertFalse( $vm_standard->show_season_selector );
        $this->assertSame( 'Round', $vm_standard->season_label );
        $this->assertSame( '/summer-league/2026/', $vm_standard->competition_link );
    }

    public function test_present_competition_list_formatting_player_suffix_and_entry_form(): void {
        $presenter = $this->create_presenter();

        // 1. Regular league competition
        $league_comp = (object) [
            'name'           => 'Summer League',
            'season'         => '2026',
            'current_season' => [ 'name' => '2026' ],
            'date_start'     => '2026-05-01',
            'date_end'       => '2026-08-31',
            'is_open'        => true,
        ];

        // 2. Tournament competition
        $tourn_comp = (object) [
            'name'           => 'Championship Tournament',
            'season'         => '2026',
            'current_season' => [ 'name' => '2026' ],
            'date_start'     => '2026-07-01',
            'date_end'       => '2026-07-15',
            'is_open'        => true,
        ];

        $player = (object) [ 'display_name' => 'Jane Player' ];

        $items = $presenter->present_competition_list(
            [ $league_comp, $tourn_comp ],
            'league',
            $player,
            'd/m/Y'
        );

        $this->assertCount( 2, $items );

        // League list item
        $item1 = $items[0];
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $item1 );
        $this->assertSame( 'Summer League 2026', $item1->name );
        $this->assertSame( '/summer-league/2026/player/jane-player/', $item1->link );
        $this->assertTrue( $item1->is_open );
        $this->assertTrue( $item1->has_date_range );
        $this->assertSame( '01/05/2026', $item1->date_start_display );
        $this->assertSame( '31/08/2026', $item1->date_end_display );
        $this->assertSame( '/league/entry-form/summer-league-2026/2026/', $item1->entry_url );

        // Tournament list item (presented with tournament type)
        $tourn_items = $presenter->present_competition_list(
            [ $tourn_comp ],
            'tournament',
            null,
            'd/m/Y'
        );
        $item2 = $tourn_items[0];
        $this->assertSame( 'Championship Tournament', $item2->name );
        $this->assertSame( '/tournament/championship-tournament/', $item2->link );
        $this->assertTrue( $item2->has_date_range );
        $this->assertSame( '01/07/2026', $item2->date_start_display );
        $this->assertSame( '15/07/2026', $item2->date_end_display );
        $this->assertSame( '/tournament/entry-form/championship-tournament/', $item2->entry_url );

        // 3. Pre-existing Competition_List_Item_View_Model is passed through
        $existing_vm = new Competition_List_Item_View_Model(
            name: 'Already Formatted',
            link: '/already/',
            icon_path: null,
            venue_name: null,
            date_start_display: null,
            date_end_display: null,
            has_date_range: false,
            is_open: false,
            entry_url: null
        );
        $passthrough = $presenter->present_competition_list( [ $existing_vm ], 'league' );
        $this->assertSame( [ $existing_vm ], $passthrough );
    }

    public function test_present_overview_timeline_states_and_classes(): void {
        $presenter = $this->create_presenter();

        $comp = (object) [
            'is_open'     => true,
            'is_closed'   => false,
            'is_started'  => false,
            'is_complete' => false,
        ];
        $overview = (object) [
            'num_events'  => 5,
            'num_teams'   => 20,
            'num_players' => 50,
            'grade'       => 'Grade 3',
        ];
        $season = [
            'venue_name'   => 'Leigh Tennis Club',
            'date_open'    => '2026-03-01',
            'date_closing' => '2026-04-15',
            'date_start'   => '2026-05-01',
            'date_end'     => '2026-08-31',
        ];

        $vm = $presenter->present_overview( $comp, $overview, $season, 'd/m/Y' );

        $this->assertSame( 'Leigh Tennis Club', $vm->venue_name );
        $this->assertSame( 5, $vm->num_events );
        $this->assertSame( 20, $vm->num_teams );
        $this->assertSame( 50, $vm->num_players );
        $this->assertSame( 'Grade 3', $vm->grade );

        $this->assertCount( 4, $vm->timeline_items );
        $items = $vm->timeline_items;

        $this->assertSame( 'entry_open', $items[0]['key'] );
        $this->assertSame( '01/03/2026', $items[0]['date'] );
        $this->assertSame( 'is-current is-success', $items[0]['current_class'] );

        $this->assertSame( 'closing_deadline', $items[1]['key'] );
        $this->assertSame( '15/04/2026', $items[1]['date'] );
        $this->assertSame( '', $items[1]['current_class'] );

        // Test closed, started, complete states
        $comp->is_open     = false;
        $comp->is_closed   = true;
        $comp->is_started  = true;
        $comp->is_complete = true;

        $vm2 = $presenter->present_overview( $comp, $overview, $season, 'd/m/Y' );
        $this->assertSame( 'is-current is-warning', $vm2->timeline_items[1]['current_class'] );
        $this->assertSame( 'is-current is-success', $vm2->timeline_items[2]['current_class'] );
        $this->assertSame( 'is-current is-danger', $vm2->timeline_items[3]['current_class'] );

        // Empty date_end returns empty timeline items
        $season_empty_end = $season;
        unset( $season_empty_end['date_end'] );
        $vm_empty = $presenter->present_overview( $comp, $overview, $season_empty_end );
        $this->assertSame( [], $vm_empty->timeline_items );

        // Partial timeline with missing open and closing dates
        $season_partial = [
            'date_start' => '2026-05-01',
            'date_end'   => '2026-08-31',
        ];
        $vm_partial     = $presenter->present_overview( $comp, $overview, $season_partial, 'd/m/Y' );
        $this->assertCount( 2, $vm_partial->timeline_items );
        $this->assertSame( 'start_competition', $vm_partial->timeline_items[0]['key'] );
        $this->assertSame( '01/05/2026', $vm_partial->timeline_items[0]['date'] );
        $this->assertSame( 'end_competition', $vm_partial->timeline_items[1]['key'] );
        $this->assertSame( '31/08/2026', $vm_partial->timeline_items[1]['date'] );

        // Integer overview dates handling
        $season_int = [
            'date_open'    => 20260301,
            'date_closing' => 20260415,
            'date_start'   => 20260501,
            'date_end'     => 20260831,
        ];
        $vm_int     = $presenter->present_overview( $comp, $overview, $season_int, 'Y-m-d' );
        $this->assertCount( 4, $vm_int->timeline_items );
        $this->assertSame( '2026-03-01', $vm_int->timeline_items[0]['date'] );
        $this->assertSame( '2026-04-15', $vm_int->timeline_items[1]['date'] );
        $this->assertSame( '2026-05-01', $vm_int->timeline_items[2]['date'] );
        $this->assertSame( '2026-08-31', $vm_int->timeline_items[3]['date'] );
    }

    public function test_render_competition_view_model_and_page_result(): void {
        $renderer  = $this->createMock( View_Renderer_Interface::class );
        $presenter = $this->create_presenter( renderer: $renderer );

        $competition = Competition_Test_Factory::create(
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            current_season: [ 'name' => '2026' ]
        );

        // 1. Rendering Competition_Page_Result
        $page_result = new Competition_Page_Result(
            competition: $competition,
            competition_season: [ 'name' => '2026' ],
            pagename: 'summer-league',
            template: 'competition/overview'
        );

        $renderer->expects( $this->once() )
            ->method( 'render_to_string' )
            ->with(
                'competition/overview',
                $this->isInstanceOf( Competition_View_Model::class ),
                $this->callback(
                    function ( array $vars ): bool {
                        return isset( $vars['competition_header'] )
                        && isset( $vars['club_presenter'] )
                        && isset( $vars['player_presenter'] );
                    }
                )
            )
            ->willReturn( '<div class="overview">Rendered</div>' );

        $output = $presenter->render( $page_result );
        $this->assertSame( '<div class="overview">Rendered</div>', $output );

        // 2. Rendering error message renders alert template
        $renderer_alert  = $this->createMock( View_Renderer_Interface::class );
        $presenter_alert = $this->create_presenter( renderer: $renderer_alert );

        $vm_error = new Competition_View_Model(
            template: 'competition/overview',
            error_message: 'Something went wrong'
        );

        $renderer_alert->expects( $this->once() )
            ->method( 'render_to_string' )
            ->with(
                'alert',
                [],
                $this->callback(
                    function ( array $vars ): bool {
                        return 'Something went wrong' === $vars['msg'] && 'danger' === $vars['class'];
                    }
                )
            )
            ->willReturn( '<div class="alert danger">Something went wrong</div>' );

        $alert_output = $presenter_alert->render( $vm_error );
        $this->assertSame( '<div class="alert danger">Something went wrong</div>', $alert_output );

        // 3. Template_Not_Found_Exception handling and output buffer restoration
        $renderer_tnf  = $this->createMock( View_Renderer_Interface::class );
        $presenter_tnf = $this->create_presenter( renderer: $renderer_tnf );

        $vm_tnf = new Competition_View_Model(
            template: 'non_existent_template'
        );

        $initial_level_tnf = ob_get_level();
        $renderer_tnf->expects( $this->exactly( 2 ) )
            ->method( 'render_to_string' )
            ->willReturnCallback(
                function ( string $template ): string {
                    if ( 'non_existent_template' === $template ) {
                        ob_start();
                        echo 'buffer content in missing template';
                        throw new Template_Not_Found_Exception( 'Template not found' );
                    }
                    return '<div class="alert danger">Missing template</div>';
                }
            );

        $tnf_output = $presenter_tnf->render( $vm_tnf );
        $this->assertSame( '<div class="alert danger">Missing template</div>', $tnf_output );
        $this->assertSame( $initial_level_tnf, ob_get_level() );

        // 4. RuntimeException is rethrown and output buffer is restored
        $renderer_err  = $this->createMock( View_Renderer_Interface::class );
        $presenter_err = $this->create_presenter( renderer: $renderer_err );

        $initial_level_err = ob_get_level();
        $renderer_err->expects( $this->once() )
            ->method( 'render_to_string' )
            ->willReturnCallback(
                function (): string {
                    ob_start();
                    echo 'buffer content before failure';
                    throw new RuntimeException( 'Fatal renderer issue' );
                }
            );

        try {
            $presenter_err->render( new Competition_View_Model( template: 'failing_template' ) );
            $this->fail( 'Expected RuntimeException was not thrown' );
        } catch ( RuntimeException $e ) {
            $this->assertSame( 'Fatal renderer issue', $e->getMessage() );
            $this->assertSame( $initial_level_err, ob_get_level() );
        }
    }

    public function test_rules_url_and_club_url_and_closing_date(): void {
        $option_gw = $this->createStub( Option_Gateway_Interface::class );
        $option_gw->method( 'get_option' )->willReturn( 'Y-m-d' );
        $presenter = $this->create_presenter( option_gateway: $option_gw );

        // 1. get_rules_url
        $comp_obj = (object) [ 'name' => 'Winter Cup 2026' ];
        $this->assertSame( '/rules/winter-cup-2026-rules/', $presenter->get_rules_url( $comp_obj ) );

        $comp_method = new class() {
            public function get_name(): string {
                return 'Championship League';
            }
        };
        $this->assertSame( '/rules/championship-league-rules/', $presenter->get_rules_url( $comp_method ) );

        // 2. get_club_url
        $club = (object) [ 'shortcode' => 'WTC' ];
        $this->assertSame( '/clubs/wtc/', $presenter->get_club_url( $club ) );

        // 3. present_closing_date
        $this->assertSame( '', $presenter->present_closing_date( null ) );
        $this->assertSame( '', $presenter->present_closing_date( '' ) );
        $this->assertSame( '2026-05-31', $presenter->present_closing_date( '2026-05-31' ) );
    }

    public function test_competition_and_event_relational_links_and_winners(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
            'type'           => 'league',
        ];

        // 1. present_competition_club_links
        $club_obj = (object) [ 'id' => 1, 'shortcode' => 'WTC' ];
        $club_arr = [ 'id' => 2, 'shortcode' => 'LTC' ];
        $c_links  = $presenter->present_competition_club_links( $competition, [ $club_obj, $club_arr ] );
        $this->assertSame( '/summer-league/2026/club/wtc/', $c_links[1] );
        $this->assertSame( '/summer-league/2026/club/ltc/', $c_links[2] );

        // 2. present_competition_events_links
        $event_obj = (object) [ 'event_id' => 10, 'event_name' => 'Men 1st' ];
        $event_arr = [ 'id' => 20, 'name' => 'Ladies 1st' ];
        $e_links   = $presenter->present_competition_events_links( $competition, [ $event_obj, $event_arr ] );
        $this->assertSame( '/leagues/men-1st/2026/', $e_links[10] );
        $this->assertSame( '/leagues/ladies-1st/2026/', $e_links[20] );

        // 3. present_event_club_links
        $event = (object) [
            'name'           => 'Premier Division',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => 'league' ],
        ];
        $ev_club_links = $presenter->present_event_club_links( $event, [ $club_obj, $club_arr ] );
        $this->assertSame( '/leagues/premier-division/2026/club/wtc/', $ev_club_links[1] );
        $this->assertSame( '/leagues/premier-division/2026/club/ltc/', $ev_club_links[2] );

        // 4. present_competition_winners
        $winner_obj = (object) [
            'competition_type' => 'cup',
            'event_name'       => 'Open Singles',
            'season'           => '2026',
            'winner_name'      => 'Champion Player',
            'data_season'      => 'old',
        ];
        $winner_arr = [
            'competition_type' => 'league',
            'event_name'       => 'Division One',
            'season'           => '2026',
            'winner_name'      => 'Champion Team',
        ];

        $presented_winners = $presenter->present_competition_winners(
            [
                'singles' => [ $winner_obj ],
                'teams'   => [ $winner_arr ],
            ]
        );

        $w1 = $presented_winners['singles'][0];
        $this->assertSame( '/cups/open-singles/2026/', $w1->link_url );
        $this->assertNull( $w1->data_season );

        $w2 = $presented_winners['teams'][0];
        $this->assertSame( '/leagues/division-one/2026/', $w2->link_url );
    }

    public function test_club_event_player_links_and_entry_form_club_urls(): void {
        $presenter = $this->create_presenter();

        // 1. present_club_event_player_links
        $event = (object) [
            'name'           => 'Club Championship',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => Competition_Type::TOURNAMENT ],
        ];
        $stat_obj = (object) [ 'fullname' => 'Alice Smith' ];
        $stat_arr = [ 'fullname' => 'Bob Jones' ];

        $player_links = $presenter->present_club_event_player_links( $event, [ $stat_obj, $stat_arr ] );
        $this->assertSame( '/tournaments/club-championship/2026/player/alice-smith/', $player_links['Alice Smith'] );
        $this->assertSame( '/tournaments/club-championship/2026/player/bob-jones/', $player_links['Bob Jones'] );

        // 2. present_entry_form_club_urls
        $clubs = [
            (object) [ 'id' => 10, 'shortcode' => 'WTC' ],
            [ 'id' => 20, 'shortcode' => 'LTC' ],
        ];
        $urls = $presenter->present_entry_form_club_urls( $clubs );

        $this->assertSame( 'wtc/', $urls['10'] );
        $this->assertSame( 'wtc/', $urls[10] );
        $this->assertSame( 'wtc/', $urls['WTC'] );
        $this->assertSame( 'ltc/', $urls['20'] );
        $this->assertSame( 'ltc/', $urls[20] );
        $this->assertSame( 'ltc/', $urls['LTC'] );
    }

    public function test_present_event_league_urls_and_standings_links_and_match_groups(): void {
        $presenter = $this->create_presenter();

        $event_box = (object) [
            'name'           => 'Box League Event',
            'is_box'         => true,
            'current_season' => [ 'name' => '2026' ],
        ];
        $event_standard = (object) [
            'name'           => 'Standard League Event',
            'is_box'         => false,
            'current_season' => [ 'name' => '2026' ],
        ];

        $leagues = [
            (object) [ 'id' => 1, 'title' => 'Division One' ],
            [ 'id' => 2, 'title' => 'Division Two' ],
        ];

        // Box league URLs
        $box_urls = Competition_Shortcode_Presenter::present_event_league_urls( $event_box, $leagues, base_url: '/tennis' );
        $this->assertSame( '/tennis/league/division-one/round-2026/', $box_urls[1] );
        $this->assertSame( '/tennis/league/division-two/round-2026/', $box_urls[2] );

        // Standings links (non-box)
        $standings = $presenter->present_event_standings_links( $event_standard, $leagues );
        $this->assertSame( '/league/division-one/2026/', $standings[1] );
        $this->assertSame( '/league/division-two/2026/', $standings[2] );

        // Match groups
        $match_groups = [
            'group_1' => (object) [
                'league' => (object) [
                    'title'  => 'Division 1',
                    'season' => '2026',
                    'event'  => (object) [
                        'name'        => 'Men Singles',
                        'competition' => (object) [ 'type' => 'cup' ],
                    ],
                ],
            ],
            'group_2' => [
                'league' => (object) [
                    'title'  => 'Division 2',
                    'season' => '2026',
                    'event'  => (object) [
                        'name'        => 'Ladies Singles',
                        'competition' => (object) [ 'type' => 'league' ],
                    ],
                ],
            ],
        ];

        $group_links = $presenter->present_player_match_group_links( $match_groups );
        $this->assertArrayHasKey( 'group_1', $group_links );
        $this->assertSame( '/cups/men-singles/2026/', $group_links['group_1']['event_link'] );
        $this->assertSame( '/cup/division-1/2026/', $group_links['group_1']['league_link'] );

        $this->assertArrayHasKey( 'group_2', $group_links );
        $this->assertSame( '/leagues/ladies-singles/2026/', $group_links['group_2']['event_link'] );
        $this->assertSame( '/league/division-2/2026/', $group_links['group_2']['league_link'] );
    }

    public function test_empty_player_collections_and_scalar_null_iterable_fallbacks(): void {
        $presenter   = $this->create_presenter();
        $competition = (object) [
            'type'           => 'league',
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];
        $event       = (object) [
            'name'           => 'Premier Division',
            'current_season' => [ 'name' => '2026' ],
            'competition'    => (object) [ 'type' => 'league' ],
        ];

        // Empty player collections
        $club_empty_players = (object) [
            'shortcode' => 'WTC',
            'teams'     => [],
            'players'   => [],
        ];
        $club_res           = $presenter->present_competition_club_detail_links( $competition, $club_empty_players );
        $this->assertSame( [], $club_res['player_links'] );

        $event_club_res = $presenter->present_event_club_detail_links( $event, $club_empty_players );
        $this->assertSame( [], $event_club_res['player_links'] );

        $this->assertSame( [], $presenter->present_competition_players_links( $competition, [] ) );
        $this->assertSame( [], $presenter->present_event_players_links( $event, [] ) );
        $this->assertSame( [], $presenter->present_club_event_player_links( $event, [] ) );

        // Collections containing null and scalar values gracefully handled without TypeError
        $mixed_events = [ null, 'invalid_event', (object) [ 'event_id' => 99, 'name' => 'Valid Event' ] ];
        $event_links  = $presenter->present_competition_events_links( $competition, $mixed_events );
        $this->assertSame( '/leagues//2026/', $event_links[0] );
        $this->assertSame( '/leagues/valid-event/2026/', $event_links[99] );

        $mixed_clubs     = [ null, 42, (object) [ 'id' => 10, 'shortcode' => 'WTC' ] ];
        $comp_club_links = $presenter->present_competition_club_links( $competition, $mixed_clubs );
        $this->assertSame( '/summer-league/2026/club//', $comp_club_links[0] );
        $this->assertSame( '/summer-league/2026/club/wtc/', $comp_club_links[10] );

        $mixed_leagues = [ null, 'scalar_league', (object) [ 'id' => 77, 'title' => 'Division 1' ] ];
        $league_links  = Competition_Shortcode_Presenter::present_event_league_urls( $event, $mixed_leagues );
        $this->assertSame( '/league//2026/', $league_links[0] );
        $this->assertSame( '/league/division-1/2026/', $league_links[77] );

        $mixed_teams = [
            null,
            'scalar_team',
            (object) [
                'team_id'     => 123,
                'team_name'   => 'First Team',
                'league_name' => 'Division 1',
                'club'        => (object) [ 'shortcode' => 'WTC' ],
            ],
        ];
        $team_links  = $presenter->present_competition_teams_links( $competition, $mixed_teams );
        $this->assertSame(
            [
                'team_link'   => '/league//2026/team//',
                'club_link'   => '/summer-league/2026/club//',
                'league_link' => '/league//2026/',
            ],
            $team_links[0]
        );
        $this->assertSame(
            [
                'team_link'   => '/league/division-1/2026/team/first-team/',
                'club_link'   => '/summer-league/2026/club/wtc/',
                'league_link' => '/league/division-1/2026/',
            ],
            $team_links[123]
        );

        $mixed_event_teams = [
            null,
            'scalar_team',
            (object) [
                'id'           => 55,
                'name'         => 'Event Team',
                'league_title' => 'Division 1',
                'club'         => (object) [ 'shortcode' => 'WTC' ],
            ],
        ];
        $event_teams_links = $presenter->present_event_teams_links( $event, $mixed_event_teams );
        $this->assertSame(
            [
                'team_link'   => '/league//2026/team//',
                'club_link'   => '/leagues/premier-division/2026/club//',
                'league_link' => '/league//2026/',
            ],
            $event_teams_links[0]
        );
        $this->assertSame(
            [
                'team_link'   => '/league/division-1/2026/team/event-team/',
                'club_link'   => '/leagues/premier-division/2026/club/wtc/',
                'league_link' => '/league/division-1/2026/',
            ],
            $event_teams_links[55]
        );

        // Club detail links with null and scalar teams/players (non-championship and championship)
        $club_mixed_entries = (object) [
            'shortcode' => 'WTC',
            'teams'     => [ null, 42 ],
            'players'   => [ null, 'bad_player' ],
        ];
        $comp_club_detail   = $presenter->present_competition_club_detail_links( $competition, $club_mixed_entries );
        $this->assertSame( '/clubs/wtc/', $comp_club_detail['club_url'] );
        $this->assertSame(
            [
                'team_link'   => '/league//2026/team//',
                'league_link' => '/league//2026/',
            ],
            $comp_club_detail['team_links'][0]
        );
        $this->assertSame( '/summer-league/2026/player//', $comp_club_detail['player_links'][0] );

        $champ_comp        = (object) [
            'type'            => 'cup',
            'name'            => 'Summer Cup',
            'current_season'  => [ 'name' => '2026' ],
            'is_championship' => true,
        ];
        $champ_club_detail = $presenter->present_competition_club_detail_links( $champ_comp, $club_mixed_entries );
        $this->assertSame(
            [
                'team_link'   => '/cup//2026/team//',
                'league_link' => '/cups//2026/',
            ],
            $champ_club_detail['team_links'][0]
        );
        $this->assertSame( '/summer-cup/2026/player//', $champ_club_detail['player_links'][0] );

        $event_club_detail = $presenter->present_event_club_detail_links( $event, $club_mixed_entries );
        $this->assertSame( '/clubs/wtc/', $event_club_detail['club_url'] );
        $this->assertSame(
            [
                'team_link'   => '/league//2026/team//',
                'league_link' => '/league//2026/',
            ],
            $event_club_detail['team_links'][0]
        );
        $this->assertSame( '/leagues/premier-division/2026/player//', $event_club_detail['player_links'][0] );

        // Grouped player links and stats containing null or scalar items
        $mixed_comp_players = $presenter->present_competition_players_links(
            $competition,
            [ [ null, 42, (object) [ 'id' => 5, 'display_name' => 'Alice' ] ] ]
        );
        $this->assertSame( '/summer-league/2026/player//', $mixed_comp_players[0] );
        $this->assertSame( '/summer-league/2026/player/alice/', $mixed_comp_players[5] );

        $mixed_event_players = $presenter->present_event_players_links(
            $event,
            [ [ null, 'scalar', (object) [ 'id' => 6, 'display_name' => 'Bob' ] ] ]
        );
        $this->assertSame( '/leagues/premier-division/2026/player//', $mixed_event_players[0] );
        $this->assertSame( '/leagues/premier-division/2026/player/bob/', $mixed_event_players[6] );

        $mixed_player_stats = $presenter->present_club_event_player_links(
            $event,
            [ null, 'bad_stat', (object) [ 'fullname' => 'Charlie Smith' ] ]
        );
        $this->assertSame(
            [ 'Charlie Smith' => '/leagues/premier-division/2026/player/charlie-smith/' ],
            $mixed_player_stats
        );
    }
}
