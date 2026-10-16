<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Competition_Season_Action_Dispatcher_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $container                    = new \Racketmanager\Services\Container\Simple_Container();
        $existence_service            = $this->createStub( Competition_Service::class );
        $existence_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );
        $container->set( 'competition_service', fn() => $existence_service );
        $racketmanager            = $this->createStub( \Racketmanager\RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_competition_mock(
        int $id = 1,
        bool $is_league = true,
        bool $is_team_entry = true,
        mixed $season_data = false
    ): Competition {
        $competition                = $this->createMock( Competition::class );
        $competition->id            = $id;
        $competition->is_league     = $is_league;
        $competition->is_team_entry = $is_team_entry;
        if ( false !== $season_data ) {
            $competition->method( 'get_season_by_name' )->willReturn( $season_data );
        }
        return $competition;
    }

    private function create_season_payload( array $overrides = [], string $action = 'addSeason' ): array {
        $payload = array(
            'competition_id'   => '1',
            'season'           => '2025',
            'grade'            => 'A',
            'dateOpen'         => '2025-01-01',
            'dateEnd'          => '2025-06-30',
            'dateClose'        => '2025-01-15',
            'dateStart'        => '2025-02-01',
            'max_teams'        => '10',
            'teams_per_club'   => '2',
            'teams_prom_relg'  => '2',
            'lowest_promotion' => '1',
            'num_match_days'   => '10',
            'round_length'     => '7',
        );
        if ( '' !== $action ) {
            $payload[ $action ] = '1';
        }
        return array_merge( $payload, $overrides );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result     = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
    }

    public function test_add_season_guard_failure(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-season', 'edit_seasons' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addSeason'      => '1',
                'competition_id' => '1',
                'season'         => '2025',
            )
        );

        self::assertSame( 'Unauthorized', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_season_invalid_competition_or_season(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'addSeason' => '1',
                'season'    => '',
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_season_validation_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->create_competition_mock();

        $competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        // Missing required dates and fields
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addSeason'      => '1',
                'competition_id' => '1',
                'season'         => '2025',
                'grade'          => '',
            )
        );

        self::assertSame( 'Errors found', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertIsArray( $result->flash_data['errors'] ?? null );
    }

    public function test_add_season_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );
        $competition         = $this->create_competition_mock( season_data: null );
        $competition->expects( self::once() )->method( 'add_season' );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_season_payload()
        );

        self::assertSame( 'Season added to competition', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'season' => '2025', 'view' => 'modify' ), $result->query_params );
    }

    public function test_add_season_with_fees_but_no_lead_time_returns_field_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $competition = $this->create_competition_mock( season_data: null );
        $competition->expects( self::never() )->method( 'add_season' );
        $competition_service->method( 'get_competition' )->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_season_payload( array(
                'feeClub' => '25.50',
                'feeTeam' => '10',
            ) )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertArrayHasKey( 'feeLeadTime', $result->flash_data['errors'] );
    }

    public function test_add_season_with_fees_and_lead_time_succeeds(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $competition = $this->create_competition_mock( season_data: null );
        $competition->expects( self::once() )->method( 'add_season' );
        $competition_service->method( 'get_competition' )->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result     = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_season_payload( array(
                'feeClub'     => '25.50',
                'feeTeam'     => '10',
                'feeLeadTime' => '14',
            ) )
        );

        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_edit_season_success(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $competition = $this->create_competition_mock( season_data: array(
            'name'           => '2025',
            'grade'          => 'B',
            'date_open'      => '2025-01-01',
            'date_end'       => '2025-06-30',
            'date_closing'   => '2025-01-15',
            'date_start'     => '2025-02-01',
            'max_teams'      => 10,
            'teams_per_club' => 2,
            'teams_prom_relg'=> 2,
            'lowest_promotion'=> 1,
            'num_match_days' => 10,
            'round_length'   => 7,
            'home_away_diff' => 0,
            'filler_weeks'   => 0,
            'match_dates'    => array(),
        ) );
        $competition->expects( self::once() )->method( 'update_season' )->willReturn( true );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_season_payload( array(
                'grade' => 'A', // Changed grade triggers updates
            ), 'editSeason' )
        );

        self::assertSame( 'Season updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'season' => '2025', 'view' => 'modify' ), $result->query_params );
    }

    public function test_edit_season_no_updates(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $competition = $this->create_competition_mock( season_data: array(
            'name'           => '2025',
            'grade'          => 'A',
            'date_open'      => '2025-01-01',
            'date_end'       => '2025-06-30',
            'date_closing'   => '2025-01-15',
            'date_start'     => '2025-02-01',
            'max_teams'      => 10,
            'teams_per_club' => 2,
            'teams_prom_relg'=> 2,
            'lowest_promotion'=> 1,
            'num_match_days' => 10,
            'round_length'   => 7,
            'home_away_diff' => 1,
            'filler_weeks'   => 1,
            'competition_code' => 'C1',
            'fixed_match_dates' => true,
            'home_away'      => true,
            'match_dates'    => array(),
        ) );
        $competition->expects( self::never() )->method( 'update_season' );

        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            $this->create_season_payload( array(
                'home_away_diff'   => '1',
                'filler_weeks'     => '1',
                'competition_code' => 'C1',
                'fixedMatchDates'  => 'true',
                'homeAway'         => 'true',
            ), 'editSeason' )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( array( 'season' => '2025', 'view' => 'modify' ), $result->query_params );
    }

    public function test_add_season_whitelists_form_data_and_excludes_nonces_and_secrets(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $finance_service     = $this->createMock( Finance_Service::class );
        $guard               = $this->createStub( Action_Guard_Interface::class );

        $competition = $this->createStub( Competition::class );
        $competition->id   = 1;
        $competition->type = Competition_Type::LEAGUE;
        $competition->name = 'Summer League';
        $competition->method( 'get_type' )->willReturn( 'league' );

        $competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $dispatcher = new Competition_Season_Action_Dispatcher( $competition_service, $finance_service, $guard );
        // Missing dates to trigger validation error
        $result = $dispatcher->handle(
            array( 'competition_id' => '1' ),
            array(
                'addSeason'             => '1',
                'competition_id'        => '1',
                'racketmanager_nonce'   => 'secret_nonce_value',
                '_wpnonce'              => 'another_nonce',
                'user_password'         => 'supersecret',
                'unknown_param'         => 'malicious',
                'season'                => '2026',
                'grade'                 => 'B',
                'venue'                 => '12',
                'homeAway'              => 'true',
                'fixedMatchDates'       => 'true',
                'feeClub'               => '50.00',
                'feeTeam'               => '20.00',
                'match_dates'           => array(
                    '1' => ' 2026-06-01 ',
                    'nested' => array(
                        'final' => ' 2026-07-01 ',
                    ),
                ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertIsArray( $result->flash_data );
        self::assertArrayHasKey( 'form_data', $result->flash_data );
        $formData = $result->flash_data['form_data'];

        self::assertArrayNotHasKey( 'racketmanager_nonce', $formData );
        self::assertArrayNotHasKey( '_wpnonce', $formData );
        self::assertArrayNotHasKey( 'user_password', $formData );
        self::assertArrayNotHasKey( 'unknown_param', $formData );
        self::assertArrayNotHasKey( 'addSeason', $formData );

        self::assertSame( '2026', $formData['season'] );
        self::assertSame( 'B', $formData['grade'] );
        self::assertSame( '12', $formData['venue'] );
        self::assertSame( 'true', $formData['homeAway'] );
        self::assertSame( 'true', $formData['fixedMatchDates'] );
        self::assertSame( '50.00', $formData['feeClub'] );
        self::assertSame( '20.00', $formData['feeTeam'] );
        self::assertSame(
            array(
                '1'      => '2026-06-01',
                'nested' => array(
                    'final' => '2026-07-01',
                ),
            ),
            $formData['match_dates']
        );
    }
}
