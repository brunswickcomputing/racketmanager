<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Player\Player_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;

class Email_Shortcode_Presenter_Test extends TestCase {
    private Tournament_Service $tournament_service;
    private Competition_Service $competition_service;
    private Team_Service $team_service;
    private Fixture_Link_Service $fixture_link_service;
    private Fixture_Permission_Service $permission_service;
    private Email_Shortcode_Presenter $presenter;
    private string $site_url = 'https://example.com';
    private string $site_name = 'Test Site';

    private array $test_leagues = [];
    private array $test_events = [];
    private array $test_competitions = [];
    private array $test_teams = [];

    protected function setUp(): void {
        parent::setUp();

        $this->test_leagues      = [];
        $this->test_events       = [];
        $this->test_competitions = [];
        $this->test_teams        = [];

        $this->tournament_service   = $this->createStub( Tournament_Service::class );
        $this->competition_service  = $this->createStub( Competition_Service::class );
        $this->team_service         = $this->createStub( Team_Service::class );
        $this->fixture_link_service = $this->createStub( Fixture_Link_Service::class );
        $this->permission_service   = $this->createStub( Fixture_Permission_Service::class );

        $this->team_service->method( 'get_team_details' )->willReturnCallback( fn( int $id ) => $this->test_teams[ $id ] ?? null );

        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturnCallback( fn( int $id ) => $this->test_leagues[ $id ] ?? null );
        $this->competition_service->method( 'get_league_repository' )->willReturn( $league_repo );
        $this->competition_service->method( 'get_event_by_id' )->willReturnCallback( fn( int $id ) => $this->test_events[ $id ] ?? null );
        $this->competition_service->method( 'get_by_id' )->willReturnCallback( fn( int $id ) => $this->test_competitions[ $id ] ?? null );

        $this->presenter = new Email_Shortcode_Presenter(
            $this->tournament_service,
            $this->competition_service,
            $this->team_service,
            $this->fixture_link_service,
            $this->permission_service,
            $this->site_url,
            $this->site_name
        );
    }

    private function create_test_fixture( array $fixture_data = [] ): Fixture {
        $league_id    = $fixture_data['league_id'] ?? 10;
        $event_id     = $fixture_data['event_id'] ?? 5;
        $comp_id      = $fixture_data['comp_id'] ?? 3;
        $home_team_id = (int) ( $fixture_data['home_team'] ?? ( 1000 + count( $this->test_teams ) + 1 ) );
        $away_team_id = (int) ( $fixture_data['away_team'] ?? ( 1000 + count( $this->test_teams ) + 2 ) );

        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $fixture_data['id'] ?? 100 );
        $fixture->method( 'get_league_id' )->willReturn( $league_id );
        $fixture->method( 'get_home_team' )->willReturn( (string) $home_team_id );
        $fixture->method( 'get_away_team' )->willReturn( (string) $away_team_id );
        $fixture->method( 'get_season' )->willReturn( $fixture_data['season'] ?? '2026' );
        $fixture->method( 'get_date' )->willReturn( $fixture_data['date'] ?? '2026-10-15 19:00:00' );
        $fixture->method( 'get_host' )->willReturn( $fixture_data['host'] ?? 'home' );
        $fixture->method( 'get_leg' )->willReturn( isset( $fixture_data['leg'] ) ? (int) $fixture_data['leg'] : null );
        $fixture->method( 'get_final' )->willReturn( $fixture_data['final'] ?? null );
        $fixture->method( 'get_match_day' )->willReturn( $fixture_data['match_day'] ?? 1 );

        $league = $this->createStub( League::class );
        $league->id             = $league_id;
        $league->title          = $fixture_data['league_title'] ?? 'Division 1';
        $league->name           = $fixture_data['league_title'] ?? 'Division 1';
        $league->type           = $fixture_data['league_type'] ?? 'MS';
        $league->mode           = $fixture_data['league_type'] ?? 'MS';
        $league->event_id       = $event_id;
        $league->current_season = [ 'name' => $fixture_data['season'] ?? '2026' ];
        $league->method( 'get_id' )->willReturn( $league_id );
        $league->method( 'get_name' )->willReturn( $fixture_data['league_title'] ?? 'Division 1' );
        $league->method( 'get_event_id' )->willReturn( $event_id );

        $event = $this->createStub( Event::class );
        $event->id             = $event_id;
        $event->name           = $fixture_data['event_name'] ?? 'Men Singles';
        $event->type           = $fixture_data['league_type'] ?? 'MS';
        $event->mode           = $fixture_data['league_type'] ?? 'MS';
        $event->competition_id = $comp_id;
        $event->method( 'get_id' )->willReturn( $event_id );
        $event->method( 'get_name' )->willReturn( $fixture_data['event_name'] ?? 'Men Singles' );
        $event->method( 'get_competition_id' )->willReturn( $comp_id );

        $competition = $this->createStub( Competition::class );
        $competition->id              = $comp_id;
        $competition->name            = $fixture_data['comp_name'] ?? 'Summer Tournament';
        $comp_type                    = $fixture_data['comp_type'] ?? 'tournament';
        $competition->type            = Competition_Type::tryFrom( $comp_type ) ?? Competition_Type::TOURNAMENT;
        $competition->is_tournament   = ( 'tournament' === $comp_type );
        $competition->is_cup          = ( 'cup' === $comp_type );
        $competition->is_championship = ( ! empty( $fixture_data['is_championship'] ) );
        $competition->method( 'get_id' )->willReturn( $comp_id );
        $competition->method( 'get_name' )->willReturn( $fixture_data['comp_name'] ?? 'Summer Tournament' );
        $competition->method( 'get_type' )->willReturn( $comp_type );

        $event->competition = $competition;
        $league->event      = $event;

        $this->test_leagues[ $league_id ]     = $league;
        $this->test_events[ $event_id ]       = $event;
        $this->test_competitions[ $comp_id ] = $competition;

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( $fixture_data['home_name'] ?? 'Home Team' );
        $home_club = $this->createStub( Club::class );
        $home_club->shortcode = 'CA';

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( $fixture_data['away_name'] ?? 'Away Team' );
        $away_club = $this->createStub( Club::class );
        $away_club->shortcode = 'CB';

        $home_dto = new Team_Details_DTO( $home_team, $home_club, null );
        $home_dto->captain      = $fixture_data['home_captain'] ?? 'John Doe';
        $home_dto->contactemail = $fixture_data['home_email'] ?? 'john@example.com';
        $home_dto->contactno    = $fixture_data['home_tel'] ?? '0123456789';
        $home_dto->match_day    = 'Tuesday';
        $home_dto->match_time   = '19:00';

        $away_dto = new Team_Details_DTO( $away_team, $away_club, null );
        $away_dto->captain      = $fixture_data['away_captain'] ?? 'Jane Smith';
        $away_dto->contactemail = $fixture_data['away_email'] ?? 'jane@example.com';
        $away_dto->contactno    = $fixture_data['away_tel'] ?? '0987654321';
        $away_dto->match_day    = 'Thursday';
        $away_dto->match_time   = '19:30';

        $this->test_teams[ $home_team_id ] = $home_dto;
        $this->test_teams[ $away_team_id ] = $away_dto;

        return $fixture;
    }

    public function test_present_match_notification_tournament_singles(): void {
        $fixture = $this->create_test_fixture( [
            'comp_type'    => 'tournament',
            'league_type'  => 'MS',
            'event_name'   => 'Men Singles',
            'league_title' => 'Main Draw',
            'home_name'    => 'Roger Federer',
            'away_name'    => 'Rafael Nadal',
        ] );

        $tournament       = $this->createStub( Tournament::class );
        $tournament->name = 'Wimbledon 2026';

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $args = [
            'competition_type' => 'tournament',
            'tournament'       => 'wimbledon-2026',
            'round'            => 'Final',
            'emailfrom'        => 'admin@example.com',
        ];

        $vars = $this->presenter->present_match_notification( $fixture, $args );

        $this->assertSame( 'Home Player', $vars['teams']['home']->title );
        $this->assertSame( 'Away Player', $vars['teams']['away']->title );
        $this->assertSame( 'Home Player', $vars['home_dtls']['title'] );
        $this->assertSame( 'Away Player', $vars['away_dtls']['title'] );
        $this->assertSame( '<a href="https://example.com/tournament/wimbledon-2026/">Wimbledon 2026</a>', $vars['tournament_link'] );
        $this->assertStringContainsString( 'draw/men-singles', $vars['draw_link'] );
        $this->assertStringContainsString( 'match/main-draw/roger-federer-vs-rafael-nadal/100/', $vars['action_url'] );
        $this->assertStringNotContainsString( '//match/', $vars['action_url'] );
        $this->assertSame( '', $vars['template'] );
    }

    public function test_present_match_notification_tournament_doubles(): void {
        $fixture = $this->create_test_fixture( [
            'comp_type'   => 'tournament',
            'league_type' => 'MD',
        ] );

        $tournament       = $this->createStub( Tournament::class );
        $tournament->name = 'Doubles Championship';

        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $vars = $this->presenter->present_match_notification( $fixture, [
            'competition_type' => 'tournament',
            'tournament'       => 'doubles',
        ] );

        $this->assertSame( 'Home Players', $vars['teams']['home']->title );
        $this->assertSame( 'Away Players', $vars['teams']['away']->title );
        $this->assertSame( 'Home Players', $vars['home_dtls']['title'] );
        $this->assertSame( 'Away Players', $vars['away_dtls']['title'] );
    }

    public function test_present_match_notification_tournament_not_found_throws(): void {
        $fixture = $this->create_test_fixture( [
            'comp_type' => 'tournament',
        ] );

        $this->tournament_service->method( 'get_tournament' )->willThrowException( new Tournament_Not_Found_Exception( 'Tournament not found' ) );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $this->presenter->present_match_notification( $fixture, [
            'competition_type' => 'tournament',
            'tournament'       => 'nonexistent',
        ] );
    }

    public function test_present_match_notification_cup_and_leg(): void {
        $fixture = $this->create_test_fixture( [
            'comp_type'    => 'cup',
            'league_title' => 'Senior Cup',
            'leg'          => '1',
            'season'       => '2026',
        ] );

        $this->fixture_link_service->method( 'get_fixture_link' )->willReturn( '/cups/senior-cup/match/100/' );

        $vars = $this->presenter->present_match_notification( $fixture, [
            'competition_type' => 'cup',
            'round'            => 'Semi-Final',
        ] );

        $this->assertSame( 'Home Team', $vars['teams']['home']->title );
        $this->assertSame( 'Away Team', $vars['teams']['away']->title );
        $this->assertSame( 'cup', $vars['template'] );
        $this->assertStringContainsString( '<a href="https://example.com/cups/senior-cup/2026/">Senior Cup</a>', $vars['cup_link'] );
        $this->assertStringEndsWith( 'leg-1/', $vars['action_url'] );
    }

    public function test_present_match_notification_host_away_mapping(): void {
        $fixture = $this->create_test_fixture( [
            'comp_type'     => 'cup',
            'host'          => 'away',
            'home_name'     => 'Home Club 1',
            'away_name'     => 'Away Club 2',
            'home_captain'  => 'Captain Home',
            'home_email'    => 'home@example.com',
            'away_captain'  => 'Captain Away',
            'away_email'    => 'away@example.com',
        ] );

        $vars = $this->presenter->present_match_notification( $fixture, [
            'competition_type' => 'cup',
        ] );

        // When host is 'away':
        // $teams['away'] receives home team (host) info
        $this->assertSame( 'Home Club 1', $vars['teams']['away']->name );
        $this->assertSame( 'Captain Home', $vars['teams']['away']->captain );
        $this->assertSame( 'home@example.com', $vars['teams']['away']->captain_email );

        // $teams['home'] receives away team (visitor) info
        $this->assertSame( 'Away Club 2', $vars['teams']['home']->name );
        $this->assertSame( 'Captain Away', $vars['teams']['home']->captain );
        $this->assertSame( 'away@example.com', $vars['teams']['home']->captain_email );
    }

    public function test_present_result_notification_admin_url(): void {
        $fixture = $this->create_test_fixture();

        $vars = $this->presenter->present_result_notification( $fixture, [
            'league'    => 12,
            'match_day' => '3',
            'round'     => 'Quarter-Final',
            'complete'  => true,
            'errors'    => false,
        ] );

        $this->assertStringContainsString( 'view=results', $vars['action_url'] );
        $this->assertStringContainsString( '&subpage=show-league&league_id=12', $vars['action_url'] );
        $this->assertStringContainsString( '&match_day=3', $vars['action_url'] );
        $this->assertStringContainsString( '&final=Quarter-Final&tab=matches', $vars['action_url'] );
        $this->assertTrue( $vars['complete'] );
    }

    public function test_present_captain_result_notification_championship_and_standard(): void {
        $fixture_champ = $this->create_test_fixture( [
            'is_championship' => true,
            'league_title'    => 'Premier League',
            'season'          => '2026',
            'final'           => 'final',
            'leg'             => '2',
            'home_name'       => 'Team Red',
            'away_name'       => 'Team Blue',
        ] );

        $vars_champ = $this->presenter->present_captain_result_notification( $fixture_champ, [
            'outstanding' => true,
            'time_period' => '24',
        ] );

        $this->assertStringContainsString( '/match/premier-league/2026/final/team-red-vs-team-blue/leg-2/result/', $vars_champ['action_url'] );
        $this->assertTrue( $vars_champ['outstanding'] );

        $fixture_std = $this->create_test_fixture( [
            'is_championship' => false,
            'league_title'    => 'Division 2',
            'season'          => '2026',
            'match_day'       => 5,
            'home_name'       => 'Team A',
            'away_name'       => 'Team B',
        ] );

        $vars_std = $this->presenter->present_captain_result_notification( $fixture_std, [] );
        $this->assertStringContainsString( '/match/division-2/2026/day5/team-a-vs-team-b/result/', $vars_std['action_url'] );
    }

    public function test_present_result_outstanding_notification_branches(): void {
        // Cup branch
        $fixture_cup = $this->create_test_fixture( [
            'comp_type'    => 'cup',
            'league_title' => 'Challenger Cup',
            'season'       => '2026',
            'final'        => 'semi-final',
            'home_name'    => 'Club X',
            'away_name'    => 'Club Y',
        ] );

        $vars_cup = $this->presenter->present_result_outstanding_notification( $fixture_cup, [
            'time_period' => 48,
        ] );

        $this->assertStringContainsString( '/match/challenger-cup/2026/semi-final/club-x-vs-club-y', $vars_cup['action_url'] );
        $this->assertStringNotContainsString( 'result/', $vars_cup['action_url'] );

        // Tournament branch
        $fixture_tourn = $this->create_test_fixture( [
            'comp_type' => 'tournament',
            'season'    => '2026',
            'id'        => 555,
        ] );

        $tournament       = $this->createStub( Tournament::class );
        $tournament->name = 'Autumn Open';
        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $vars_tourn = $this->presenter->present_result_outstanding_notification( $fixture_tourn, [] );
        $this->assertStringContainsString( '/tournament/autumn-open/match/555/', $vars_tourn['action_url'] );
        $this->assertSame( 'tournament', $vars_tourn['template'] );

        // League branch
        $fixture_league = $this->create_test_fixture( [
            'comp_type'    => 'league',
            'league_title' => 'Division 1',
            'season'       => '2026',
            'match_day'    => 4,
            'home_name'    => 'Alpha',
            'away_name'    => 'Beta',
        ] );

        $vars_league = $this->presenter->present_result_outstanding_notification( $fixture_league, [] );
        $this->assertStringContainsString( '/match/division-1/2026/day4/alpha-vs-beta', $vars_league['action_url'] );
    }

    public function test_present_club_player_notification(): void {
        $vars = $this->presenter->present_club_player_notification( [
            'club'      => 'Leigh Tennis Club',
            'player'    => 'John Smith',
            'action'    => 'registered',
            'requestor' => 'Secretary',
            'btm'       => '12345678',
        ] );

        $this->assertSame( 'https://example.com/clubs/leigh-tennis-club/john-smith/', $vars['action_url'] );
        $this->assertStringContainsString( 'Club Player Request - Leigh Tennis Club', $vars['email_subject'] );
    }

    public function test_present_match_date_change_notification(): void {
        $fixture_tourn = $this->create_test_fixture( [
            'comp_type'    => 'tournament',
            'event_name'   => 'Men Doubles',
            'league_title' => 'MD Draw',
            'home_name'    => 'Pair 1',
            'away_name'    => 'Pair 2',
            'id'           => 88,
        ] );

        $tournament       = $this->createStub( Tournament::class );
        $tournament->name = 'County Championship';
        $this->tournament_service->method( 'get_tournament' )->willReturn( $tournament );

        $vars_tourn = $this->presenter->present_match_date_change_notification( $fixture_tourn, [
            'competition_type' => 'tournament',
            'tournament'       => 'county',
            'original_date'    => '2026-10-10',
            'new_date'         => '2026-10-17',
            'delay'            => true,
        ] );

        $this->assertSame( '<a href="https://example.com/tournament/county-championship/">County Championship</a>', $vars_tourn['tournament_link'] );
        $this->assertStringContainsString( 'match/md-draw/pair-1-vs-pair-2/88/', $vars_tourn['action_url'] );
        $this->assertStringNotContainsString( '//match/', $vars_tourn['action_url'] );
        $this->assertStringContainsString( 'draw/men-doubles', $vars_tourn['draw_link'] );
        $this->assertTrue( $vars_tourn['delay'] );

        // Cup branch
        $fixture_cup = $this->create_test_fixture( [
            'comp_type'    => 'cup',
            'league_title' => 'Challenge Cup',
            'season'       => '2026',
            'leg'          => '2',
        ] );
        $this->fixture_link_service->method( 'get_fixture_link' )->willReturn( '/cups/challenge-cup/match/100/' );

        $vars_cup = $this->presenter->present_match_date_change_notification( $fixture_cup, [
            'competition_type' => 'cup',
        ] );
        $this->assertSame( '', $vars_cup['tournament_link'] );
        $this->assertStringContainsString( '/cups/challenge-cup/2026/', $vars_cup['competition_link'] );
        $this->assertStringEndsWith( 'leg-2/', $vars_cup['action_url'] );

        // League branch
        $fixture_league = $this->create_test_fixture( [
            'comp_type'    => 'league',
            'event_name'   => 'Premier Event',
            'league_title' => 'Premier Division',
            'season'       => '2026',
        ] );
        $this->fixture_link_service->method( 'get_fixture_link' )->willReturn( '/leagues/premier-division/match/101/' );

        $vars_league = $this->presenter->present_match_date_change_notification( $fixture_league, [
            'competition_type' => 'league',
        ] );
        $this->assertSame( '', $vars_league['tournament_link'] );
        $this->assertStringContainsString( '/leagues/premier-event/2026/', $vars_league['competition_link'] );

        // Mixed timed / untimed dates formatting
        $vars_mixed_to_untimed = $this->presenter->present_match_date_change_notification( $fixture_league, [
            'competition_type' => 'league',
            'new_date'         => '2026-10-24 00:00:00',
            'original_date'    => '2026-10-17 19:30:00',
        ] );
        $this->assertSame( '24/10/2026', $vars_mixed_to_untimed['match_date'] );
        $this->assertSame( '17 October 2026 19:30', $vars_mixed_to_untimed['original_date'] );

        $vars_mixed_to_timed = $this->presenter->present_match_date_change_notification( $fixture_league, [
            'competition_type' => 'league',
            'new_date'         => '2026-10-24 18:00:00',
            'original_date'    => '2026-10-17 00:00:00',
        ] );
        $this->assertSame( '24 October 2026 18:00', $vars_mixed_to_timed['match_date'] );
        $this->assertSame( '17/10/2026', $vars_mixed_to_timed['original_date'] );
    }

    public function test_present_team_withdrawn_and_withdrawn_team_match_and_constitution(): void {
        $team         = (object) [ 'title' => 'Withdrawn Team' ];
        $league       = (object) [ 'title' => 'Division 1' ];
        $vars_withdr  = $this->presenter->present_team_withdrawn_email( $team, $league, '2026', [
            'subject' => 'Team Withdrawal Notice',
            'from'    => 'admin@example.com',
        ] );

        $this->assertSame( $team, $vars_withdr['team'] );
        $this->assertSame( $league, $vars_withdr['league'] );
        $this->assertSame( '2026', $vars_withdr['season'] );
        $this->assertSame( 'Team Withdrawal Notice', $vars_withdr['email_subject'] );

        $vars_match = $this->presenter->present_withdrawn_team_match( [
            'is_tournament' => true,
            'event'         => 'Men Singles',
            'round'         => 'Quarter-Final',
            'emailfrom'     => 'admin@example.com',
            'subject'       => 'Opponent Withdrawn',
        ] );
        $this->assertTrue( $vars_match['is_tournament'] );
        $this->assertSame( 'Men Singles', $vars_match['event'] );

        $event = (object) [ 'name' => 'Annual Tournament' ];
        $vars_const = $this->presenter->present_event_constitution( $event );
        $this->assertSame( $event, $vars_const['event'] );
    }
}
