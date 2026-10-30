<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Winners_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Query;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Draw_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

class Draw_And_Winners_Templates_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;

    private Php_View_Renderer $view_renderer;
    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $template_path           = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
        $this->view_renderer     = new Php_View_Renderer( $template_path );
        $this->template_renderer = new Shortcode_Template_Renderer( $this->view_renderer );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_present_competition_winners_generates_correct_urls(): void {
        $presenter = new Competition_Shortcode_Presenter();
        $winner_item = (object) [
            'competition_type' => 'cup',
            'event_name'       => 'Mens Singles',
            'season'           => '2026',
            'league'           => 'Main Draw',
            'winner'           => 'John Doe',
            'winner_club'      => 'WTC',
            'loser'            => 'Jane Smith',
            'loser_club'       => 'LTC',
            'event_id'         => 12,
            'is_team_entry'    => false,
        ];

        $presented = $presenter->present_competition_winners( [ 'MENS' => [ $winner_item ] ] );

        $this->assertArrayHasKey( 'MENS', $presented );
        $this->assertCount( 1, $presented['MENS'] );
        $this->assertSame( '/cups/mens-singles/2026/', $presented['MENS'][0]->link_url );
        $this->assertNull( $presented['MENS'][0]->link_class );
    }

    public function test_present_tournament_winners_generates_correct_urls(): void {
        $presenter  = new Tournament_Presenter();
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 42 );
        $tournament->method( 'get_name' )->willReturn( 'Club Finals 2026' );

        $winner_item = (object) [
            'event_name'    => 'Open Singles',
            'league'        => 'Championship',
            'winner'        => 'Roger Federer',
            'winner_club'   => 'WTC',
            'loser'         => 'Rafael Nadal',
            'loser_club'    => 'ETC',
            'event_id'      => 99,
            'is_team_entry' => false,
        ];

        $presented = $presenter->present_tournament_winners( [ 'OPEN' => [ $winner_item ] ], $tournament );

        $this->assertArrayHasKey( 'OPEN', $presented );
        $this->assertSame( '/tournament/club-finals-2026/draw/open-singles/', $presented['OPEN'][0]->link_url );
        $this->assertSame( 'tabDataLink', $presented['OPEN'][0]->link_class );
        $this->assertSame( 42, $presented['OPEN'][0]->data_type_id );
        $this->assertSame( 'draws', $presented['OPEN'][0]->data_type_type );
    }

    public function test_render_competition_winners_template_with_producer_links_and_zero_warnings(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_id' )->willReturn( 10 );
        $competition->current_season = [ 'name' => '2026' ];

        $winner_item = (object) [
            'competition_type' => 'cup',
            'event_name'       => 'Ladies Doubles',
            'season'           => '2026',
            'league'           => 'Final Stage',
            'winner'           => 'Alice & Bob',
            'winner_club'      => 'Club A',
            'loser'            => 'Charlie & Dave',
            'loser_club'       => 'Club B',
            'event_id'         => 15,
            'is_team_entry'    => false,
        ];

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_winners_for_competition' )->willReturn( [ 'LADIES' => [ $winner_item ] ] );

        $presenter = new Competition_Shortcode_Presenter();
        $handler   = new Get_Competition_Winners_Handler( $comp_service, $this->competition_season_service() );
        $result    = $handler->handle( new Get_Competition_Winners_Query( competition_id: 10, season: 2026 ) );

        $this->assertFalse( $result->is_error() );
        $winners_vm = $presenter->present_competition_winners( $result->raw_winners );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $result->template, [
                'competition' => $result->competition,
                'winners'     => $winners_vm,
            ], 'competition' ),
            $warnings
        );

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'href="/cups/ladies-doubles/2026/"', $output );
        $this->assertStringContainsString( 'Alice', $output );
        $this->assertStringContainsString( 'Bob', $output );
        $this->assertStringContainsString( 'Charlie', $output );
        $this->assertStringContainsString( 'Dave', $output );
    }

    public function test_render_tournament_winners_template_with_producer_links_and_zero_warnings(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 55;
        $tournament->name = 'Summer 2026';
        $tournament->method( 'get_id' )->willReturn( 55 );
        $tournament->method( 'get_name' )->willReturn( 'Summer 2026' );

        $winner_item = (object) [
            'event_name'    => 'Mens Doubles',
            'league'        => 'Main Draw',
            'winner'        => 'Champion Player',
            'winner_club'   => 'Leigh TC',
            'loser'         => 'Runner Up',
            'loser_club'    => 'Westcliff Hard',
            'event_id'      => 20,
            'is_team_entry' => false,
        ];

        $tourn_service = $this->createStub( Tournament_Service::class );
        $tourn_service->method( 'get_tournament' )->willReturn( $tournament );
        $tourn_service->method( 'get_winners_for_tournament' )->willReturn( [ 'MAIN' => [ $winner_item ] ] );

        $handler = new Get_Tournament_Winners_Handler( $tourn_service );
        $result  = $handler->handle( new Get_Tournament_Winners_Query( tournament_id: 55 ) );
        $adapter = new Tournament_Shortcode_Adapter( tournament_presenter: new Tournament_Presenter() );
        $vm      = $adapter->present_winners( $result );

        $this->assertFalse( $vm->is_error() );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'href="/tournament/summer-2026/draw/mens-doubles/"', $output );
        $this->assertStringContainsString( 'class="tabDataLink"', $output );
        $this->assertStringContainsString( 'data-type="tournament"', $output );
        $this->assertStringContainsString( 'data-type-id="55"', $output );
        $this->assertStringContainsString( 'Champion Player', $output );
        $this->assertStringContainsString( 'Runner Up', $output );
    }

    /**
     * Build a championship cup (team entry) event whose league returns raw Fixture entities from get_matches(),
     * matching the production Get_Event_Draw_Handler::get_draw() input shape.
     *
     * @param array<string, Fixture[]> $matches_by_final
     * @return array{0: Event, 1: League, 2: Competition_Service, 3: League_Service, 4: Competition}
     */
    private League_Query_Service $stub_league_query;

    private function build_cup_event_context( array $matches_by_final ): array {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_type' )->willReturn( 'cup' );
        $competition->method( 'get_name' )->willReturn( 'Summer Cups' );
        $competition->method( 'is_event_allowed' )->willReturn( true );
        $competition->is_tournament   = false;
        $competition->is_team_entry   = true;
        $competition->is_championship = true;

        $event              = $this->createStub( Event::class );
        $event->id          = 8;
        $event->name        = 'Mens Jubilee Cup';
        $event->type        = 'MD';
        $event->competition = $competition;
        $event->current_season = [ 'name' => '2025' ];

        $championship = $this->createStub( Championship::class );
        $championship->method( 'get_finals' )->willReturn( [
            [ 'key' => 'final', 'name' => 'Final' ],
            [ 'key' => 'semi', 'name' => 'Semi Final' ],
        ] );

        $league                 = $this->createStub( League::class );
        $league->id             = 81;
        $league->title          = 'Mens Jubilee Cup';
        $league->event          = $event;
        $league->championship   = $championship;
        $league->current_season = [ 'name' => '2025' ];
        $league->seasons        = [ '2025' => [ 'rounds' => [] ] ];
        $this->stub_league_query = $this->createStub( League_Query_Service::class );
        $this->stub_league_query->method( 'get_matches' )->willReturnCallback(
            fn( object $league, array $args = [] ) => $matches_by_final[ $args['final'] ] ?? []
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        return [ $event, $league, $comp_service, $league_service, $competition ];
    }

    private function make_team( int $id, string $title, array $players = [] ): Team {
        $team          = $this->createStub( Team::class );
        $team->id      = $id;
        $team->title   = $title;
        $team->players = $players;
        $team->method( 'get_id' )->willReturn( $id );

        return $team;
    }

    private function make_fixture( array $props ): Fixture {
        $fixture = $this->createStub( Fixture::class );
        foreach ( $props as $key => $value ) {
            $fixture->$key = $value;
        }

        return $fixture;
    }

    public function test_event_draw_handler_resolves_raw_fixtures_and_renders_team_entry_points_legs_and_byes(): void {
        // Semi: single-leg team-entry fixture won by home (points shown, persisted sets ignored for team entry).
        $semi = $this->make_fixture( [
            'id'          => 701,
            'home_team'   => '11',
            'away_team'   => '12',
            'winner_id'   => 11,
            'home_points' => '3',
            'away_points' => '1',
            'custom'      => [ 'sets' => [ [ 'player1' => '6', 'player2' => '4', 'tiebreak' => '', 'completed' => '1', 'settype' => '' ] ] ],
        ] );
        // Semi: bye fixture (away -1), withdrawn home team.
        $bye = $this->make_fixture( [
            'id'        => 702,
            'home_team' => '13',
            'away_team' => '-1',
        ] );
        // Final: two-legged tie; leg 1 is skipped, leg 2 renders aggregate tie points.
        $leg1 = $this->make_fixture( [ 'id' => 703, 'home_team' => '11', 'away_team' => '13', 'leg' => 1 ] );
        $leg2 = $this->make_fixture( [
            'id'              => 704,
            'home_team'       => '13',
            'away_team'       => '11',
            'leg'             => 2,
            'winner_id_tie'   => 11,
            'home_points_tie' => 2.5,
            'away_points_tie' => 5.5,
        ] );

        [ $event, $league, $comp_service, $league_service, $competition ] = $this->build_cup_event_context( [
            'semi'  => [ $semi, $bye ],
            'final' => [ $leg1, $leg2 ],
        ] );

        $teams = [
            '11' => $this->make_team( 11, 'Rayleigh Mens 1' ),
            '12' => $this->make_team( 12, 'Southend Mens 1' ),
            '13' => $this->make_team( 13, 'Hadleigh Mens 1' ),
            '-1' => $this->make_team( -1, 'Bye' ),
        ];
        $resolved_fixture_ids = [];
        $detail_service       = $this->createMock( Fixture_Detail_Service::class );
        $detail_service->expects( $this->exactly( 3 ) )
            ->method( 'get_fixture_with_details' )
            ->willReturnCallback(
                function ( Fixture $fixture ) use ( &$resolved_fixture_ids, $teams, $league, $event, $competition ) {
                    $resolved_fixture_ids[] = $fixture->id;
                    return new Fixture_Details_DTO(
                        fixture: $fixture,
                        league: $league,
                        event: $event,
                        competition: $competition,
                        home_team: new Team_Details_DTO( $teams[ $fixture->home_team ], is_withdrawn: '13' === $fixture->home_team && 702 === $fixture->id ),
                        away_team: new Team_Details_DTO( $teams[ $fixture->away_team ] ),
                        link: '/match/mens-jubilee-cup/2025/' . $fixture->id . '/'
                    );
                }
            );

        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: new Competition_Shortcode_Presenter()
        );
        $handler = new Get_Event_Draw_Handler( $comp_service, $league_service, $detail_service, $this->event_query_service( $this->event_repository_with_results( [ 'query_leagues' => [ $league ] ] ) ), $this->stub_league_query );
        $result  = $handler->handle( new Get_Event_Draw_Query( event_id: 8, season: '2025' ) );
        $this->assertFalse( $result->is_error() );
        $vm = $adapter->present_draw( $result );
        // Assert detail service is only called for visible leg 2 and non-leg fixtures (703 leg 1 was skipped).
        $this->assertSame( [ 701, 702, 704 ], $resolved_fixture_ids );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );
        $html = (string) preg_replace( '/\s+/', ' ', $output );

        $this->assertSame( [], $warnings );
        // Teams resolved from raw fixtures (no blank placeholders), team links and match links precomputed.
        $this->assertStringNotContainsString( '<p>&nbsp;</p>', $html );
        $this->assertStringContainsString( 'href="/cups/summer-cups/mens-jubilee-cup/rayleigh-mens-1"', $html );
        $this->assertStringContainsString( 'href="/match/mens-jubilee-cup/2025/701/" class="score-row__anchor"', $html );
        $this->assertStringContainsString( 'href="/match/mens-jubilee-cup/2025/704/" class="score-row__anchor"', $html );
        $this->assertStringNotContainsString( '/702/', $html, 'Bye fixtures have no match link.' );
        $this->assertStringNotContainsString( '/703/', $html, 'First legs are not rendered.' );
        // Team-entry points (winner only) and two-legged tie points.
        $this->assertStringContainsString( 'player-row__score-game winner"> 3 </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game "> 1 </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game winner"> 5.5 </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game "> 2.5 </div>', $html );
        $this->assertStringNotContainsString( '> 6 </div>', $html, 'Team-entry draws show points, not set games.' );
        // Withdrawn team, responsive score wrapper and status ids preserved.
        $this->assertStringContainsString( 'Hadleigh Mens 1 has withdrawn', $html );
        $this->assertStringContainsString( 'class="d-none d-lg-flex player-row__score-game-wrapper"', $html );
        $this->assertStringContainsString( 'class="match__status winner" id="match-status-11"', $html );
        // Desktop + mobile carousels each render 3 rows (semi x2, final leg 2).
        $this->assertSame( 6, substr_count( $html, 'score-row draws-score-row' ) );
    }

    public function test_draw_presenter_renders_rubber_sets_with_legacy_tiebreak_markers(): void {
        $rubber = $this->createStub( Rubber::class );
        $rubber->method( 'get_custom' )->willReturn( [
            'sets' => [
                [ 'player1' => '7', 'player2' => '6', 'tiebreak' => '3' ],
                [ 'player1' => '2', 'player2' => '6', 'tiebreak' => null ],
                [ 'player1' => '', 'player2' => '', 'tiebreak' => '' ],
            ],
        ] );
        $fixture = $this->make_fixture( [ 'id' => 901, 'home_team' => '31', 'away_team' => '32', 'winner_id' => 32, 'rubbers' => [ $rubber ], 'is_retired' => true ] );
        $details = (object) [
            'fixture'   => $fixture,
            'home_team' => new Team_Details_DTO( $this->make_team( 31, 'Home Pair' ) ),
            'away_team' => new Team_Details_DTO( $this->make_team( 32, 'Away Pair' ) ),
            'link'      => '/match/901/',
        ];

        $row = Draw_Presenter::present_fixture( $details );

        $this->assertSame( '/match/901/', $row->match_link );
        $this->assertSame( [ '7', '2' ], array_map( fn( $s ) => $s->games, $row->home->scores ) );
        $this->assertSame( [ 'winner', null ], array_map( fn( $s ) => $s->winner_class, $row->home->scores ) );
        // Rubber sets: empty tiebreak marker whenever a tiebreak is recorded, regardless of set winner.
        $this->assertSame( [ true, false ], array_map( fn( $s ) => $s->has_tiebreak, $row->home->scores ) );
        $this->assertSame( [ true, false ], array_map( fn( $s ) => $s->has_tiebreak, $row->away->scores ) );
        $this->assertSame( '', $row->home->scores[0]->tiebreak );
        $this->assertSame( 'Retired', $row->home->match_message_text );
        $this->assertSame( 'W', $row->away->match_status_text );
    }

    public function test_draw_presenter_marks_walkover_and_not_played_ties(): void {
        $home = new Team_Details_DTO( $this->make_team( 41, 'Alpha' ) );
        $away = new Team_Details_DTO( $this->make_team( 42, 'Beta' ) );

        $walkover = Draw_Presenter::present_fixture( (object) [
            'fixture'   => $this->make_fixture( [ 'id' => 1, 'home_team' => '41', 'away_team' => '42', 'winner_id' => 41, 'is_walkover' => true ] ),
            'home_team' => $home,
            'away_team' => $away,
        ] );
        $this->assertSame( 'Walkover', $walkover->away->match_message_text );
        $this->assertSame( 'match-warning', $walkover->away->match_message_class );
        $this->assertSame( 'winner', $walkover->home->winner_class );

        $tie = Draw_Presenter::present_fixture( (object) [
            'fixture'   => $this->make_fixture( [ 'id' => 2, 'home_team' => '41', 'away_team' => '42', 'winner_id' => -1 ] ),
            'home_team' => $home,
            'away_team' => $away,
        ] );
        $this->assertSame( 'T', $tie->home->match_status_text );
        $this->assertSame( 'Not played', $tie->away->match_message_text );
    }

    public function test_get_tournament_draws_handler_renders_tournament_match_url_and_zero_warnings(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 20;
        $tournament->name = 'Summer 2026';
        $tournament->method( 'get_id' )->willReturn( 20 );
        $tournament->method( 'get_name' )->willReturn( 'Summer 2026' );
        $tournament->method( 'is_event_allowed' )->willReturn( true );

        $competition = $this->createStub( Competition::class );
        $competition->is_player_entry = true;
        $competition->is_tournament = true;
        $competition->method( 'get_name' )->willReturn( 'Tournament Competition' );

        $event = $this->createStub( Event::class );
        $event->id = 20;
        $event->name = 'Mens Doubles';
        $event->type = 'MD';
        $event->competition = $competition;

        $championship = $this->createStub( Championship::class );

        $league = $this->createStub( League::class );
        $league->id = 201;
        $league->title = 'Mens Doubles';
        $league->event = $event;
        $league->championship = $championship;
        $league->current_season = [ 'name' => '2026' ];
        $league->seasons = [
            '2026' => [
                'rounds' => [
                    'quarter' => (object) [ 'date' => '2026-07-25' ],
                ],
            ],
        ];

        // Doubles Pair 1
        $p1 = $this->createStub( Player::class );
        $p1->method( 'get_fullname' )->willReturn( 'Scott Bowman' );
        $p2 = $this->createStub( Player::class );
        $p2->method( 'get_fullname' )->willReturn( 'Lloyd Watts' );

        $team1 = $this->createStub( Team::class );
        $team1->id = 101;
        $team1->title = 'Scott Bowman & Lloyd Watts';
        $team1->method( 'get_id' )->willReturn( 101 );
        $team1->players = [ $p1, $p2 ];

        // Doubles Pair 2
        $p3 = $this->createStub( Player::class );
        $p3->method( 'get_fullname' )->willReturn( 'Archie Taylor' );
        $p4 = $this->createStub( Player::class );
        $p4->method( 'get_fullname' )->willReturn( 'Liam Maskell' );

        $team2 = $this->createStub( Team::class );
        $team2->id = 102;
        $team2->title = 'Archie Taylor & Liam Maskell';
        $team2->method( 'get_id' )->willReturn( 102 );
        $team2->players = [ $p3, $p4 ];

        $fixture = $this->createStub( Fixture::class );
        $fixture->id = 159507;
        $fixture->home_team = '101';
        $fixture->away_team = '102';
        $fixture->winner_id = 101;
        // Persisted set shape (custom['sets'] arrays keyed player1/player2/tiebreak), as stored for fixture 159507.
        $fixture->custom = [
            'sets' => [
                1 => [ 'player1' => '6', 'player2' => '7', 'tiebreak' => '6', 'completed' => '1', 'settype' => 'MTB' ],
                2 => [ 'player1' => '6', 'player2' => '3', 'tiebreak' => '', 'completed' => '1', 'settype' => 'MTB' ],
                3 => [ 'player1' => '7', 'player2' => '6', 'tiebreak' => '4', 'completed' => '1', 'settype' => 'MTB' ],
                4 => [ 'player1' => '', 'player2' => '', 'tiebreak' => '', 'completed' => '', 'settype' => 'null' ],
            ],
        ];

        // Intentionally generic/fallback link in DTO
        $generic_link = '/league/mens-doubles/match/159507/';
        $fixture_details = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $competition,
            home_team: new Team_Details_DTO( $team1 ),
            away_team: new Team_Details_DTO( $team2 ),
            link: $generic_link
        );

        $final = (object) [
            'key'      => 'quarter',
            'name'     => 'Quarter Final',
            'fixtures' => [ $fixture_details ],
        ];

        $league->finals = [ $final ];
        $event->leagues = [ $league ];

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_draw_details_for_tournament' )->willReturn( $event );

        $presenter = new Tournament_Presenter();
        $handler   = new Get_Tournament_Draws_Handler( $tournament_service );

        $result  = $handler->handle( new Get_Tournament_Draws_Query( tournament_id: 20, draw_id: 'mens-doubles' ) );
        $adapter = new Tournament_Shortcode_Adapter( tournament_presenter: $presenter );
        $vm      = $adapter->present_draws( $result );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'draw', $vm->template );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type ),
            $warnings
        );

        $this->assertSame( [], $warnings );

        // Crucial regression check: exact tournament match URL is present, generic link is NOT used
        $expected_match_slug = Util::seo_url( 'Scott Bowman & Lloyd Watts' ) . '-vs-' . Util::seo_url( 'Archie Taylor & Liam Maskell' );
        $expected_match_url  = '/tournament/summer-2026/match/mens-doubles/' . $expected_match_slug . '/159507/';
        $this->assertStringContainsString( 'href="' . $expected_match_url . '"', $output );
        $this->assertStringNotContainsString( 'href="' . $generic_link . '"', $output );

        // Player URLs
        $this->assertStringContainsString( 'href="/tournament/summer-2026/player/scott-bowman/"', $output );
        $this->assertStringContainsString( 'href="/tournament/summer-2026/player/lloyd-watts/"', $output );
        $this->assertStringContainsString( 'href="/tournament/summer-2026/player/archie-taylor/"', $output );
        $this->assertStringContainsString( 'href="/tournament/summer-2026/player/liam-maskell/"', $output );

        // Persisted array set scores and tiebreaks (tiebreak shown on the set winner only), with legacy markup hooks.
        $html = (string) preg_replace( '/\s+/', ' ', $output );
        $this->assertStringContainsString( 'class="d-none d-lg-flex player-row__score-game-wrapper"', $html );
        $this->assertStringContainsString( 'class="match__status winner" id="match-status-101"', $html );
        $this->assertStringContainsString( 'class="match__status blank" id="match-status-102"', $html );
        $this->assertStringContainsString( 'player-row__score-game winner"> 7 <span class="player-row__tie-break">6</span> </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game winner"> 7 <span class="player-row__tie-break">4</span> </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game winner"> 6 <span class="player-row__tie-break"></span> </div>', $html );
        $this->assertStringContainsString( 'player-row__score-game "> 6 </div>', $html );
        // Desktop + mobile: 3 played sets x 2 sides x 2 layouts; unplayed 4th set omitted.
        $this->assertSame( 12, substr_count( $html, 'class="player-row__score-game ' ) );
        $this->assertSame( 6, substr_count( $html, 'player-row__tie-break' ) );
    }

    public function test_render_championship_draw_team_template_with_zero_warnings(): void {
        $tournament = $this->createStub( Tournament::class );
        $tournament->name = 'Championship 2026';
        $tournament->method( 'get_name' )->willReturn( 'Championship 2026' );

        $competition = (object) [
            'is_player_entry' => true,
        ];
        $event = (object) [
            'type'        => 'MD',
            'competition' => $competition,
        ];
        $league = (object) [
            'event' => $event,
        ];
        $match = (object) [
            'league' => $league,
        ];

        // 1. Single team with string title and precomputed team_url
        $team_single = (object) [
            'id'       => 10,
            'title'    => 'Mens Single Winner',
            'team_url' => '/tournament/championship-2026/player/mens-single-winner/',
        ];

        $warnings = [];
        $output_single = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'includes/championship-draw-team', [
                'tournament' => $tournament,
                'team'       => $team_single,
                'match'      => $match,
            ], '' ),
            $warnings
        );
        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'href="/tournament/championship-2026/player/mens-single-winner/"', $output_single );
        $this->assertStringContainsString( 'Single Winner', $output_single );

        // 2. Doubles team with player array and precomputed player_urls
        $team_doubles = (object) [
            'id'     => 20,
            'title'  => 'Doubles Pair',
            'player' => [ 'Player A', 'Player B' ],
        ];
        $player_urls = [
            'Player A' => '/tournament/championship-2026/players/player-a/',
            'Player B' => '/tournament/championship-2026/players/player-b/',
        ];

        $output_doubles = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'includes/championship-draw-team', [
                'tournament'  => $tournament,
                'team'        => $team_doubles,
                'match'       => $match,
                'player_urls' => $player_urls,
            ], '' ),
            $warnings
        );
        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'href="/tournament/championship-2026/players/player-a/"', $output_doubles );
        $this->assertStringContainsString( 'href="/tournament/championship-2026/players/player-b/"', $output_doubles );
    }
}
