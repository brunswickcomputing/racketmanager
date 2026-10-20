<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\Competition\Queries\Tournament_Entry_Access;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

/**
 * Handler to real template tests: the tournament entry form permissions are decided by the producer, never by the template.
 */
final class Entry_Tournament_Handler_Permissions_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private const int PLAYER_ID = 42;
    private const int OTHER_ID  = 99;

    private const string MSG_CLOSED_WITHDRAW = 'You can still withdraw from events.';
    private const string MSG_OTHER_USER      = 'You can not make changes to a entry form for someone else.';

    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );
        // Deliberately contradict the query: the template must not consult WordPress for permissions.
        $GLOBALS['wp_stubs_current_user_id']  = 12345;
        $GLOBALS['wp_stubs_current_user_can'] = true;
        $GLOBALS['wp_stubs_clubs'][1]         = (object) [ 'id' => 1, 'name' => 'Leigh Tennis Club' ];

        $this->template_renderer = new Shortcode_Template_Renderer(
            new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' )
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * @return array<string, array{int, bool, bool, bool, bool, bool, ?string}>
     */
    public static function role_and_phase_provider(): array {
        // user id, is admin, tournament open, expected changes, expected withdrawal, expected editable form, expected alert text.
        return [
            'own player, tournament open'        => [ self::PLAYER_ID, false, true, true, true, true, null ],
            'own player, entries closed'         => [ self::PLAYER_ID, false, false, false, true, false, self::MSG_CLOSED_WITHDRAW ],
            'administrator, entries closed'      => [ self::OTHER_ID, true, false, true, true, true, null ],
            'another user, tournament open'      => [ self::OTHER_ID, false, true, false, false, false, self::MSG_OTHER_USER ],
            'another user, entries closed'       => [ self::OTHER_ID, false, false, false, false, false, null ],
            'anonymous user, tournament open'    => [ 0, false, true, false, false, false, self::MSG_OTHER_USER ],
        ];
    }

    #[DataProvider( 'role_and_phase_provider' )]
    public function test_handler_supplies_permissions_and_template_renders_them(
        int $user_id,
        bool $is_admin,
        bool $open,
        bool $expected_changes,
        bool $expected_withdrawal,
        bool $expected_editable,
        ?string $expected_alert
    ): void {
        $handler = $this->create_handler( $open );
        $vm      = $handler->handle( new Get_Competition_Entry_Query( tournament_name: 'Summer Open', user_id: $user_id, is_admin: $is_admin ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( $expected_changes, $vm->vars['changes_allowed'] );
        $this->assertSame( $expected_withdrawal, $vm->vars['withdrawal_allowed'] );
        $this->assertSame( $is_admin, $vm->vars['can_manage'] );

        $warnings = [];
        set_error_handler( static function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );
        try {
            $html = $this->template_renderer->render( $vm->template, $vm->vars, $vm->template_type );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertSame( $expected_editable, 0 === preg_match( '/id="contactno"[^>]*\sreadonly/', $html ) );
        $this->assertSame( $expected_changes || $expected_withdrawal, str_contains( $html, 'id="entrySubmit"' ) );
        $this->assertSame( $expected_withdrawal, str_contains( $html, 'id="tournamentWithdraw"' ) );
        if ( null !== $expected_alert ) {
            $this->assertStringContainsString( $expected_alert, $html );
        }
    }

    public function test_template_defaults_to_read_only_when_producer_supplies_no_permissions(): void {
        $handler = $this->create_handler( true );
        $vm      = $handler->handle( new Get_Competition_Entry_Query( tournament_name: 'Summer Open', user_id: self::PLAYER_ID ) );
        $vars    = $vm->vars;
        unset( $vars['changes_allowed'], $vars['withdrawal_allowed'], $vars['can_manage'], $vars['has_permission'], $vars['is_own_player'] );

        $html = $this->template_renderer->render( $vm->template, $vars, $vm->template_type );

        $this->assertMatchesRegularExpression( '/id="contactno"[^>]*\sreadonly/', $html );
        $this->assertStringNotContainsString( 'id="entrySubmit"', $html );
    }

    public function test_access_policy_rules(): void {
        $own_open = Tournament_Entry_Access::resolve( true, false, 7, 7, false );
        $this->assertTrue( $own_open->is_own_player );
        $this->assertTrue( $own_open->changes_allowed );

        $zero_user = Tournament_Entry_Access::resolve( true, false, 0, 0, false );
        $this->assertFalse( $zero_user->is_own_player, 'An anonymous user must never match a missing player' );
        $this->assertFalse( $zero_user->has_permission );

        $admin_between = Tournament_Entry_Access::resolve( false, false, 1, 7, true );
        $this->assertTrue( $admin_between->changes_allowed );
        $this->assertFalse( $admin_between->withdrawal_allowed );
    }

    private function create_handler( bool $open ): Get_Competition_Entry_Handler {
        $club_service         = $this->createStub( Club_Service::class );
        $player_service       = $this->createStub( Player_Service::class );
        $comp_service         = $this->createStub( Competition_Service::class );
        $tournament_service   = $this->createStub( Tournament_Service::class );
        $registration_service = $this->createStub( Registration_Service::class );
        $comp_entry_service   = $this->createStub( Competition_Entry_Service::class );

        $today        = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
        $date_open    = $today->modify( '-20 days' )->format( 'Y-m-d' );
        $date_closing = $open ? $today->modify( '+10 days' )->format( 'Y-m-d' ) : $today->modify( '-5 days' )->format( 'Y-m-d' );
        $date_start   = $today->modify( '+30 days' )->format( 'Y-m-d' );
        $date_end     = $today->modify( '+37 days' )->format( 'Y-m-d' );

        $competition = new class( $date_closing ) extends Competition {
            public function __construct( string $date_closing ) {
                $this->id             = 8;
                $this->name           = 'Summer Open';
                $this->type           = Competition_Type::TOURNAMENT;
                $this->is_tournament  = true;
                $this->is_open        = true;
                $this->seasons        = new Season_Collection( [ '2026' => [ 'name' => '2026', 'date_closing' => $date_closing ] ] );
                $this->current_season = [ 'name' => '2026', 'date_closing' => $date_closing ];
            }
        };

        $tournament = new Tournament( (object) [
            'id'             => 1,
            'competition_id' => 8,
            'name'           => 'Summer Open',
            'season'         => '2026',
            'date_open'      => $date_open,
            'date_start'     => $date_start,
            'date_end'       => $date_end,
            'date_closing'   => $date_closing,
            'fees'           => (object) [ 'competition' => 5, 'event' => 10 ],
        ] );
        $this->assertSame( $open, $tournament->is_open, 'Fixture tournament phase must match the requested state' );
        $this->assertSame( ! $open, $tournament->is_closed );

        $player = Player_Test_Factory::create_sparse( [
            'id'            => self::PLAYER_ID,
            'firstname'     => 'Roger',
            'surname'       => 'Federer',
            'display_name'  => 'Roger Federer',
            'gender'        => 'M',
            'year_of_birth' => 1986,
            'age'           => 40,
            'contactno'     => '07123456789',
            'user_email'    => 'roger@example.com',
            'btm'           => '12345678',
        ] );

        $tournament_service->method( 'get_tournament_by_name' )->willReturn( $tournament );
        $tournament_service->method( 'get_fees' )->willReturn( (object) [ 'competition' => 5, 'event' => 10 ] );
        $tournament_service->method( 'get_events_for_tournament' )->willReturn( [] );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $player_service->method( 'get_player' )->willReturn( $player );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [
            new Club_Player_DTO( $player, Club_Test_Factory::create( id: 1, name: 'Leigh Tennis Club', shortcode: 'LTC' ), new Club_Player() ),
        ] );
        $comp_entry_service->method( 'get_tournament_entry_by_tournament_and_player' )->willReturn( new Tournament_Entry( (object) [
            'id'            => 10,
            'tournament_id' => 1,
            'player_id'     => self::PLAYER_ID,
            'status'        => 1,
            'fee'           => 15,
            'club_id'       => 1,
        ] ) );

        return new Get_Competition_Entry_Handler(
            club_entry_selection: new Club_Entry_Selection_Service( $club_service, $player_service ),
            competition_service: $comp_service,
            tournament_service: $tournament_service,
            player_service: $player_service,
            club_service: $club_service,
            team_service: $this->createStub( Team_Service::class ),
            registration_service: $registration_service,
            competition_entry_service: $comp_entry_service,
            finance_service: $this->createStub( Finance_Service::class ),
            competition_presenter: new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ),
            tournament_presenter: new Tournament_Presenter( 'd/m/Y' )
        );
    }
}
