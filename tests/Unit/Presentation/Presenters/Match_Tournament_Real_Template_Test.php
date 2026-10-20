<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Handler;
use Racketmanager\Application\Matches\Queries\Get_Match_Header_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Match_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;

class Match_Tournament_Real_Template_Test extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['racketmanager'] = new class {
            public string $date_format = 'Y-m-d';
            public function is_user_favourite( mixed $type = null, mixed $id = null ): bool {
                return false;
            }
            public function is_player_in_club( array $args = [] ): bool {
                return false;
            }
        };
    }

    private function view_presenter(): Shortcode_View_Presenter {
        return new Shortcode_View_Presenter( new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ) );
    }

    private function render_header( ?Fixture $fixture, string $type = 'league', ?string $home_placeholder = null, ?object $permission = null, bool $edit = false ): string {
        $details = null;
        if ( $fixture ) {
            $league = $this->createStub( League::class );
            $league->title = 'First Division';
            $event = $this->createStub( Event::class );
            $event->name = 'Summer Event';
            $competition = $this->createStub( Competition::class );
            $competition->method( 'get_type' )->willReturn( $type );
            $details = new Fixture_Details_DTO(
                fixture: $fixture,
                league: $league,
                event: $event,
                competition: $competition,
                prev_home_fixture_title: $home_placeholder,
                is_update_allowed: $permission,
                link: 'https://example.test/match/201/'
            );
        }

        $service = $this->createStub( Fixture_Detail_Service::class );
        $service->method( 'get_fixture_with_details' )->willReturn( $details );
        $options = $this->createStub( Option_Gateway_Interface::class );
        $options->method( 'get_option' )->willReturnCallback( static fn( string $name, mixed $default = null ) => match ( $name ) {
            'date_format' => 'd/m/Y',
            'time_format' => 'g:i a',
            default => $default,
        } );
        $presenter = new Match_Shortcode_Presenter(
            $this->view_presenter(),
            new Fixture_Presenter( new Fixture_Link_Service(), $options ),
            $this->createStub( \Racketmanager\Services\Player_Service::class )
        );

        return $presenter->render_header( ( new Get_Match_Header_Handler( $service ) )->handle( new Get_Match_Header_Query( 201, $edit ) ) );
    }

    public function test_header_pending_and_played_keep_score_markup_time_and_localized_date(): void {
        $pending = Fixture_Test_Factory::create( date: '2026-10-12 19:30', start_time: '19:30' );
        $pending->set_date_original( null );
        $html = $this->render_header( $pending );
        $this->assertStringContainsString( 'class="score score--large is-not-played"', $html );
        $this->assertStringContainsString( '<time datetime="2026-10-12 19:30">19:30</time>', $html );
        $this->assertStringContainsString( '>12/10/2026</time>', $html );
        $this->assertStringContainsString( '/leagues/summer-event/2026/', $html );
        $this->assertStringNotContainsString( 'Start:', $html );

        $played = Fixture_Test_Factory::create( date: '2026-10-12 19:30', start_time: '00:00', winner_id: 1, loser_id: 2, home_points: '3', away_points: '2' );
        $played->set_date_original( null );
        $html = $this->render_header( $played );
        $this->assertStringContainsString( 'class="score score--large "', $html );
        $this->assertStringContainsString( '<span class="score-separator">-</span>', $html );
        $this->assertStringContainsString( 'Start: <time datetime="2026-10-12 19:30"></time>', $html );
    }

    public function test_header_status_cup_placeholder_host_and_missing_details(): void {
        $fixture = Fixture_Test_Factory::create( home_team: '0', away_team: '2', status: 1, custom: [ 'walkover' => 'away' ], host: 'away' );
        $fixture->set_date_original( null );
        $html = $this->render_header( $fixture, 'cup', 'Winner of semi-final' );
        $this->assertStringContainsString( 'Winner of semi-final', $html );
        $this->assertStringContainsString( '/cups/summer-event/2026/', $html );
        $this->assertStringNotContainsString( '>Home</', $html );
        $this->assertStringContainsString( 'match__message match-warning', $html );

        $fixture->set_status( 5 );
        $fixture->set_date_original( '2026-10-10 18:30' );
        $rescheduled = $this->render_header( $fixture );
        $this->assertStringContainsString( 'Match rescheduled from 10 October 2026 18:30', $rescheduled );
        $this->assertStringContainsString( 'Original scheduled time', $rescheduled );

        $this->assertStringContainsString( 'Match not found', $this->render_header( null ) );
    }

    public function test_header_actions_are_only_visible_to_logged_in_authorized_users(): void {
        $fixture = Fixture_Test_Factory::create();
        $permission = (object) [
            'user_can_update' => true,
            'user_type' => 'admin',
            'user_team' => 'both',
            'match_approval_mode' => false,
        ];
        $previous = $GLOBALS['wp_stubs_is_user_logged_in'] ?? null;
        try {
            $GLOBALS['wp_stubs_is_user_logged_in'] = false;
            $this->assertStringNotContainsString( 'match__change', $this->render_header( $fixture, permission: $permission ) );
            $GLOBALS['wp_stubs_is_user_logged_in'] = true;
            $this->assertStringContainsString( 'match__change', $this->render_header( $fixture, permission: $permission ) );
            $this->assertStringContainsString( 'data-match-option="schedule_match"', $this->render_header( $fixture, permission: $permission ) );
            $played = Fixture_Test_Factory::create( winner_id: 1, loser_id: 2, home_points: '3', away_points: '2' );
            $this->assertStringContainsString( 'Reset', $this->render_header( $played, permission: $permission, edit: true ) );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
            } else {
                $GLOBALS['wp_stubs_is_user_logged_in'] = $previous;
            }
        }
    }

    private function render_matches( array $dates, array $groups = [], ?string $selected = null ): string {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_id' )->willReturn( 9 );
        $tournament->name = 'Autumn Open';
        $service = $this->createStub( Tournament_Service::class );
        $service->method( 'get_tournament' )->willReturn( $tournament );
        $service->method( 'get_fixture_dates_for_tournament' )->willReturn( $dates );
        $service->method( 'get_fixtures_by_date_for_tournament' )->willReturn( $groups );
        $handler = new Get_Tournament_Matches_Handler( $service, new Tournament_Presenter() );

        return $this->view_presenter()->render( $handler->handle( new Get_Tournament_Matches_Query( 9, $selected ) ) );
    }

    public function test_tournament_empty_dates_and_matches_render_no_matches(): void {
        $html = $this->render_matches( [] );
        $this->assertStringContainsString( 'No matches', $html );
        $this->assertStringContainsString( 'id="tournament-match-date-form"', $html );
    }

    public function test_tournament_tabs_keep_selected_links_and_unscheduled_group(): void {
        $dates = [ '2026-10-12', '2026-10-13', '2026-10-14' ];
        $html = $this->render_matches( $dates, [ '99:99' => [] ], $dates[1] );
        $this->assertSame( 3, substr_count( $html, 'class="nav-link__value tabDataLink"' ) );
        $this->assertStringContainsString( '/tournament/autumn-open/matches/2026-10-13/', $html );
        $this->assertSame( 1, substr_count( $html, 'class="nav-item nav-link is_selected"' ) );
        $this->assertStringContainsString( 'Unscheduled', $html );
        $this->assertStringContainsString( 'id="tournament-match-date-form"', $html );
    }

    public function test_tournament_group_with_fixture_retains_child_template_context(): void {
        $fixture = Fixture_Test_Factory::create( final: 'final' );
        $league = $this->createStub( League::class );
        $league->title = 'Open Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Summer Event';
        $league->event = $event;
        $detail = new Fixture_Details_DTO( $fixture, $league, $event, $this->createStub( Competition::class ) );

        $html = $this->render_matches( [ '2026-10-12' ], [ '99:99' => [ $detail ] ] );
        $this->assertStringContainsString( 'Unscheduled', $html );
        $this->assertStringContainsString( '/tournament/autumn-open/draw/summer-event/', $html );
    }

    public function test_tournament_more_than_eight_dates_renders_localized_select_options(): void {
        $dates = array_map( static fn( int $day ) => sprintf( '2026-10-%02d', $day ), range( 12, 21 ) );
        $html = $this->render_matches( $dates );
        $this->assertSame( 10, substr_count( $html, '<option value=' ) );
        $this->assertStringContainsString( 'id="tournament-match-date-form"', $html );
        $this->assertMatchesRegularExpression( '/<option value="2026-10-14"\s+selected="selected">/', $this->render_matches( $dates, [], '2026-10-14' ) );
        $this->assertStringContainsString( 'Wed 14 Oct', $html );
        $this->assertStringNotContainsString( 'class="nav-link__value tabDataLink"', $html );
    }

    private function create_test_team( int $id, string $name, array $players = [] ): Team_Details_DTO {
        $team = $this->createStub( Team::class );
        $team->method( 'get_id' )->willReturn( $id );
        $team->method( 'get_name' )->willReturn( $name );
        $team->method( 'has_player' )->willReturnCallback( static fn( int $player_id ) => in_array( $player_id, array_map( static fn( $p ) => $p->get_id(), $players ), true ) );
        $team->players = $players;
        return new Team_Details_DTO( $team );
    }

    private function create_test_player( int $id, string $fullname ): Player {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( $id );
        $player->method( 'get_fullname' )->willReturn( $fullname );
        $player->id = $id;
        $player->display_name = $fullname;
        return $player;
    }

    private function render_match_card(
        mixed $fixture_detail,
        ?Tournament $tournament = null,
        ?object $tournament_player = null,
        string|bool $match_display = '',
        bool $location_in_header = false
    ): string {
        $presenter = new Tournament_Presenter( 'd/m/Y' );
        $card = $presenter->present_match_card(
            $fixture_detail,
            $tournament,
            $tournament_player,
            $match_display,
            $location_in_header
        );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        return (string) ob_get_clean();
    }

    public function test_match_card_played_with_sets_and_winner_markup(): void {
        $player1 = $this->create_test_player( 101, 'Roger Federer' );
        $player2 = $this->create_test_player( 102, 'Rafael Nadal' );

        $home_team = $this->create_test_team( 10, 'Team Federer', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Nadal', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 501,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            custom: [
                'sets' => [
                    new Set_Score( 6, 4 ),
                    new Set_Score( 7, 6, 7, 5 ),
                ],
            ]
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Singles Event';
        $league->event = $event;

        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 5;
        $tournament->name = 'Summer Championships';

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $presenter = new Tournament_Presenter( 'd/m/Y' );
        $card      = $presenter->present_match_card( $detail, $tournament );

        // Direct view-model assertions for Set 1 (6-4 home win) and Set 2 (7-6 home win with 7-5 tiebreak)
        $this->assertSame( '6', $card->sets[0]->player1_games );
        $this->assertSame( '4', $card->sets[0]->player2_games );
        $this->assertSame( ' winner', $card->sets[0]->player1_winner_class );
        $this->assertSame( '', $card->sets[0]->player2_winner_class );
        $this->assertFalse( $card->sets[0]->show_p1_tiebreak );
        $this->assertFalse( $card->sets[0]->show_p2_tiebreak );

        $this->assertSame( '7', $card->sets[1]->player1_games );
        $this->assertSame( '6', $card->sets[1]->player2_games );
        $this->assertSame( ' winner', $card->sets[1]->player1_winner_class );
        $this->assertSame( '', $card->sets[1]->player2_winner_class );
        $this->assertTrue( $card->sets[1]->show_p1_tiebreak );
        $this->assertFalse( $card->sets[1]->show_p2_tiebreak );
        $this->assertSame( '7', $card->sets[1]->tiebreak );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString( 'class="match__row  winner"', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '/tournament/summer-championships/player/roger-federer/', $html );
        $this->assertMatchesRegularExpression( '/<li class="match-points__cell\s+winner">\s*6\s*<\/li>\s*<li class="match-points__cell\s*">\s*4\s*<\/li>/', $html );
        $this->assertMatchesRegularExpression( '/<li class="match-points__cell\s+winner">\s*7\s*<span class="player-row__tie-break">7<\/span>\s*<\/li>\s*<li class="match-points__cell\s*">\s*6\s*<\/li>/', $html );
    }

    public function test_match_card_played_with_away_winner_sets_and_tiebreak_markup(): void {
        $player1 = $this->create_test_player( 101, 'Roger Federer' );
        $player2 = $this->create_test_player( 102, 'Rafael Nadal' );

        $home_team = $this->create_test_team( 10, 'Team Federer', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Nadal', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 501,
            home_team: '10',
            away_team: '20',
            winner_id: 20,
            loser_id: 10,
            custom: [
                'sets' => [
                    new Set_Score( 4, 6 ),
                    new Set_Score( 6, 7, 5, 7 ),
                ],
            ]
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Singles Event';
        $league->event = $event;

        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 5;
        $tournament->name = 'Summer Championships';

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $presenter = new Tournament_Presenter( 'd/m/Y' );
        $card      = $presenter->present_match_card( $detail, $tournament );

        // Direct view-model assertions for Set 1 (4-6 away win) and Set 2 (6-7 away win with 5-7 tiebreak)
        $this->assertSame( '4', $card->sets[0]->player1_games );
        $this->assertSame( '6', $card->sets[0]->player2_games );
        $this->assertSame( '', $card->sets[0]->player1_winner_class );
        $this->assertSame( ' winner', $card->sets[0]->player2_winner_class );
        $this->assertFalse( $card->sets[0]->show_p1_tiebreak );
        $this->assertFalse( $card->sets[0]->show_p2_tiebreak );

        $this->assertSame( '6', $card->sets[1]->player1_games );
        $this->assertSame( '7', $card->sets[1]->player2_games );
        $this->assertSame( '', $card->sets[1]->player1_winner_class );
        $this->assertSame( ' winner', $card->sets[1]->player2_winner_class );
        $this->assertFalse( $card->sets[1]->show_p1_tiebreak );
        $this->assertTrue( $card->sets[1]->show_p2_tiebreak );
        $this->assertSame( '5', $card->sets[1]->tiebreak );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString( 'class="match__row  winner"', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '/tournament/summer-championships/player/rafael-nadal/', $html );
        $this->assertMatchesRegularExpression( '/<li class="match-points__cell\s*">\s*4\s*<\/li>\s*<li class="match-points__cell\s+winner">\s*6\s*<\/li>/', $html );
        $this->assertMatchesRegularExpression( '/<li class="match-points__cell\s*">\s*6\s*<\/li>\s*<li class="match-points__cell\s+winner">\s*7\s*<span class="player-row__tie-break">5<\/span>\s*<\/li>/', $html );
    }

    public function test_match_card_walkover_retired_and_tie_status(): void {
        $home_team = $this->create_test_team( 10, 'Team A' );
        $away_team = $this->create_test_team( 20, 'Team B' );

        $walkover_fixture = Fixture_Test_Factory::create(
            id: 502,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            status: 1
        );
        $walkover_fixture->is_walkover = true;

        $league = $this->createStub( League::class );
        $league->title = 'Open';
        $event = $this->createStub( Event::class );
        $event->name = 'Event';
        $league->event = $event;

        $detail_walkover = new Fixture_Details_DTO(
            fixture: $walkover_fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $html_wo = $this->render_match_card( $detail_walkover );
        $this->assertStringContainsString( 'Walkover', $html_wo );

        $retired_fixture = Fixture_Test_Factory::create(
            id: 503,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            status: 2
        );
        $retired_fixture->is_retired = true;

        $detail_retired = new Fixture_Details_DTO(
            fixture: $retired_fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $html_ret = $this->render_match_card( $detail_retired );
        $this->assertStringContainsString( 'Retired', $html_ret );

        $tie_fixture = Fixture_Test_Factory::create(
            id: 504,
            home_team: '10',
            away_team: '20',
            winner_id: -1,
            loser_id: -1
        );

        $detail_tie = new Fixture_Details_DTO(
            fixture: $tie_fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $html_tie = $this->render_match_card( $detail_tie );
        $this->assertStringContainsString( 'Not played', $html_tie );
    }

    public function test_match_card_tournament_player_and_user_selection(): void {
        $player1 = $this->create_test_player( 55, 'Home Player' );
        $player2 = $this->create_test_player( 77, 'Away Player' );

        $home_team = $this->create_test_team( 10, 'Team Home', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Away', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 505,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20
        );

        $league = $this->createStub( League::class );
        $league->title = 'Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Event';
        $league->event = $event;

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $tournament_player = (object) [ 'id' => 77 ];
        $html_loser = $this->render_match_card( $detail, null, $tournament_player );
        $this->assertStringContainsString( '<span class="match__status loser">L</span>', $html_loser );

        $prev_logged_in = $GLOBALS['wp_stubs_is_user_logged_in'] ?? null;
        $prev_user_id = $GLOBALS['wp_stubs_current_user_id'] ?? null;
        try {
            $GLOBALS['wp_stubs_is_user_logged_in'] = true;
            $GLOBALS['wp_stubs_current_user_id'] = 55;
            $html_selected = $this->render_match_card( $detail );
            $this->assertStringContainsString( 'is-selected', $html_selected );
        } finally {
            if ( null === $prev_logged_in ) {
                unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
            } else {
                $GLOBALS['wp_stubs_is_user_logged_in'] = $prev_logged_in;
            }
            if ( null === $prev_user_id ) {
                unset( $GLOBALS['wp_stubs_current_user_id'] );
            } else {
                $GLOBALS['wp_stubs_current_user_id'] = $prev_user_id;
            }
        }
    }

    public function test_match_card_permissions_and_update_button(): void {
        $home_team = $this->create_test_team( 10, 'Team A' );
        $away_team = $this->create_test_team( 20, 'Team B' );

        $fixture = Fixture_Test_Factory::create( id: 506, home_team: '10', away_team: '20' );

        $league = $this->createStub( League::class );
        $league->title = 'Open Division';
        $event = $this->createStub( Event::class );
        $event->name = 'Event';
        $league->event = $event;

        $tournament = $this->createStub( Tournament::class );
        $tournament->name = 'Spring Open';

        $permission = (object) [ 'user_can_update' => true ];

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team,
            is_update_allowed: $permission
        );

        $html_unconfirmed = $this->render_match_card( $detail, $tournament );
        $this->assertStringContainsString( 'class="btn match__btn"', $html_unconfirmed );
        $this->assertStringContainsString( '/tournament/spring-open/match/open-division/team-a-vs-team-b/506/', $html_unconfirmed );

        $fixture->set_confirmed( 'P' );
        $html_confirmed = $this->render_match_card( $detail, $tournament );
        $this->assertStringNotContainsString( 'class="btn match__btn"', $html_confirmed );
    }

    public function test_match_card_direct_include_without_card_vm_returns_empty(): void {
        $home_team = $this->create_test_team( 10, 'Alpha' );
        $away_team = $this->create_test_team( 20, 'Beta' );
        $fixture = Fixture_Test_Factory::create( id: 507, home_team: '10', away_team: '20', date: '2026-10-12 10:00:00', start_time: '10:00' );

        $fixture_detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $this->createStub( League::class ),
            event: $this->createStub( Event::class ),
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        // Without passing $card or $card_vm, the template should return early without rendering.
        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();

        $this->assertSame( '', trim( $html ) );
    }

    public function test_get_tournament_draws_handler_renders_real_template_with_match_cards(): void {
        $player1 = $this->create_test_player( 101, 'Roger Federer' );
        $player2 = $this->create_test_player( 102, 'Rafael Nadal' );
        $home_team = $this->create_test_team( 10, 'Team Federer', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Nadal', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 501,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            custom: [
                'sets' => [
                    new Set_Score( 6, 4 ),
                    new Set_Score( 7, 6, 7, 5 ),
                ],
            ]
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Singles';
        $league->finals = [];
        $event = $this->createStub( Event::class );
        $event->id = 12;
        $event->name = 'Draw Event';
        $event->leagues = [ $league ];

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        // Backed meta store so the handler's set_meta( 'fixtures', $cards ) is observable by the template.
        $meta = [ 'fixtures' => [ $detail ] ];
        $event->method( 'get_meta' )->willReturnCallback( function ( $key, $default = null ) use ( &$meta ) {
            return $meta[ $key ] ?? $default;
        } );
        $event->method( 'set_meta' )->willReturnCallback( function ( $key, $value ) use ( &$meta ): void {
            $meta[ $key ] = $value;
        } );

        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 5;
        $tournament->name = 'Summer Championships';

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_draw_details_for_tournament' )->willReturn( $event );

        $presenter = new Tournament_Presenter( 'd/m/Y' );
        $handler = new Get_Tournament_Draws_Handler( $tournament_service, $presenter );

        $vm = $handler->handle( new Get_Tournament_Draws_Query( 5, 12 ) );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $event->get_meta( 'fixtures' ) );
        $html = $this->view_presenter()->render( $vm );

        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        // Parity with legacy template: the tie-break value shown in the winning cell is the home tie-break points.
        $this->assertStringContainsString( '<span class="player-row__tie-break">7</span>', $html );
        $this->assertStringContainsString( '/tournament/summer-championships/draw/draw-event/', $html );
    }

    public function test_get_tournament_players_handler_renders_real_template_with_match_cards(): void {
        $player1 = $this->create_test_player( 101, 'Roger Federer' );
        $player2 = $this->create_test_player( 102, 'Rafael Nadal' );
        $home_team = $this->create_test_team( 10, 'Team Federer', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Nadal', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 501,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            custom: [
                'sets' => [
                    new Set_Score( 6, 4 ),
                ],
            ]
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Draw Event';

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $tournament_player = $player1;
        $tournament_player->matches = [ $detail ];
        $tournament_player->teams = [];
        $tournament_player->statistics = [];

        $tournament = $this->createStub( Tournament::class );
        $tournament->id = 5;
        $tournament->name = 'Summer Championships';

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament' )->willReturn( $tournament );
        $tournament_service->method( 'get_player_details_for_tournament' )->willReturn( $tournament_player );

        $presenter = new Tournament_Presenter( 'd/m/Y' );
        $handler = new Get_Tournament_Players_Handler( $tournament_service, $presenter, new Player_Presenter() );

        $vm = $handler->handle( new Get_Tournament_Players_Query( 5, 101 ) );
        $html = $this->view_presenter()->render( $vm );

        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringContainsString( 'Court 1', $html );
    }

    public function test_match_card_enrichment_from_fixture_entity_with_fixture_detail_service(): void {
        $player1 = $this->create_test_player( 101, 'Roger Federer' );
        $player2 = $this->create_test_player( 102, 'Rafael Nadal' );
        $home_team = $this->create_test_team( 10, 'Team Federer', [ $player1 ] );
        $away_team = $this->create_test_team( 20, 'Team Nadal', [ $player2 ] );

        $fixture = Fixture_Test_Factory::create(
            id: 501,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Singles';
        $event = $this->createStub( Event::class );
        $event->name = 'Draw Event';

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            home_team: $home_team,
            away_team: $away_team
        );

        $detail_service = $this->createStub( Fixture_Detail_Service::class );
        $detail_service->method( 'get_fixture_with_details' )->willReturn( $detail );

        $presenter = new Tournament_Presenter( 'd/m/Y', $detail_service );
        $card = $presenter->present_match_card( $fixture );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
    }

    public function test_match_card_unresolvable_fixture_entity_returns_safe_missing_card(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 999,
            home_team: '10',
            away_team: '20'
        );

        $presenter = new Tournament_Presenter( 'd/m/Y', null );
        $card = $presenter->present_match_card( $fixture );

        $this->assertSame( 999, $card->id );
        $this->assertTrue( $card->is_missing );
        $this->assertEmpty( $card->opponents );
        $this->assertEmpty( $card->sets );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();
        // Explicit unavailable state rather than a nameless card or silent drop.
        $this->assertStringContainsString( 'match--unavailable', $html );
        $this->assertStringContainsString( 'Match details unavailable', $html );
        $this->assertStringNotContainsString( 'player-row', $html );
    }

    public function test_match_card_location_in_header_and_final_placeholder(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 508,
            home_team: '0',
            away_team: '0',
            final: 'final',
            location: 'Center Court'
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men Doubles';
        $event = $this->createStub( Event::class );
        $event->name = 'Championships';
        $league->event = $event;

        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $this->createStub( Competition::class ),
            prev_home_fixture_title: 'Winner Semi 1',
            prev_away_fixture_title: 'Winner Semi 2'
        );

        $html = $this->render_match_card( $detail, null, null, '', true );

        $this->assertStringContainsString( 'Winner Semi 1', $html );
        $this->assertStringContainsString( 'Winner Semi 2', $html );
        $this->assertStringContainsString( 'class="match__header-title-item match__location"', $html );
        $this->assertStringContainsString( 'Center Court', $html );
    }

    public function test_match_card_handles_null_or_empty_details_gracefully(): void {
        $html = $this->render_match_card( null );
        $this->assertStringContainsString( 'Match details unavailable', $html );
        $this->assertStringNotContainsString( 'match__header', $html );
    }
}