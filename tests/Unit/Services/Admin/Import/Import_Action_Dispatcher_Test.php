<?php
/**
 * Import action dispatcher unit tests
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Services/Admin/Import
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Import;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Import\Import_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Import_Action_Dispatcher_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Action_Guard_Interface $action_guard;
    private Fixture_Service $fixture_service;
    private Player_Service $player_service;
    private Registration_Service $registration_service;
    private object $racketmanager;
    private Import_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->action_guard         = $this->createMock( Action_Guard_Interface::class );
        $this->fixture_service      = $this->createMock( Fixture_Service::class );
        $this->player_service       = $this->createMock( Player_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );

        $this->racketmanager = $this->createMock( RacketManager::class );
        $this->racketmanager->method( 'get_team_id' )->willReturn( 10 );

        $this->dispatcher = new Import_Action_Dispatcher(
            $this->action_guard,
            $this->fixture_service,
            $this->player_service,
            $this->registration_service,
            $this->racketmanager,
        );

        $GLOBALS['wp_stubs_filesystem_method']     = 'direct';
        $GLOBALS['wp_stubs_wp_filesystem_return']  = true;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );

        $this->assertNull( $result->message );
        $this->assertNull( $result->message_type );
    }

    public function test_guard_denial_returns_error_dto(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_import-datasets', 'import_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle( array(), array( 'import' => '1' ) );

        $this->assertSame( 'Forbidden', $result->message );
        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_missing_file_returns_error(): void {
        $this->action_guard->expects( self::once() )->method( 'assert_allowed' );

        $result = $this->dispatcher->handle( array(), array( 'import' => '1' ), array() );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'No file specified for upload', $result->message );
    }

    public function test_empty_file_returns_error(): void {
        $this->action_guard->expects( self::once() )->method( 'assert_allowed' );

        $files = array(
            'racketmanager_import' => array(
                'name' => 'test.csv',
                'size' => 0,
            ),
        );

        $result = $this->dispatcher->handle( array(), array( 'import' => '1' ), $files );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Upload file is empty', $result->message );
    }

    public function test_non_direct_filesystem_returns_error(): void {
        $this->action_guard->expects( self::once() )->method( 'assert_allowed' );
        $GLOBALS['wp_stubs_filesystem_method'] = 'ftp';

        $files = array(
            'racketmanager_import' => array(
                'name' => 'test.csv',
                'size' => 100,
            ),
        );

        $result = $this->dispatcher->handle( array(), array( 'import' => '1' ), $files );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Unable to access file', $result->message );
    }

    public function test_filesystem_copy_fails(): void {
        $this->action_guard->expects( self::once() )->method( 'assert_allowed' );
        
        $wp_filesystem_mock = new class {
            public function copy( string $source, string $destination, bool $overwrite = true ): bool {
                return false;
            }
        };
        $GLOBALS['wp_filesystem'] = $wp_filesystem_mock;

        $files = array(
            'racketmanager_import' => array(
                'name'     => 'test.csv',
                'tmp_name' => '/tmp/test.csv',
                'size'     => 100,
            ),
        );

        $result = $this->dispatcher->handle( array(), array( 'import' => '1' ), $files );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertStringContainsString( 'could not be moved', $result->message );
    }

    public function test_import_players_does_not_mutate_global_post_and_calls_player_service(): void {
        $original_post = array( 'unrelated' => 'preserve_me' );
        $_POST         = $original_post;

        $contents = array(
            'First,Surname,Gender,BTM,Email,Contact,YOB',
            'Roger,Federer,M,12345,roger@tennis.test,07123456789,1981',
            'Rafael,Nadal,M,67890,rafa@tennis.test,07987654321,1986',
        );

        $player_mock = (new \ReflectionClass( Player::class ))->newInstanceWithoutConstructor();

        $this->player_service->expects( self::exactly( 2 ) )
            ->method( 'add_new_player' )
            ->willReturnCallback( function ( array $data ) use ( $player_mock ) {
                $this->assertNotEmpty( $data['firstname'] );
                $this->assertNotEmpty( $data['surname'] );
                $this->assertNotEmpty( $data['email'] );
                return $player_mock;
            } );

        $result = $this->dispatcher->import_players( $contents, ',' );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertStringContainsString( '2 Players imported', $result->message );
        $this->assertSame( $original_post, $_POST, 'Global $_POST must not be mutated by player import' );
    }

    public function test_import_players_captures_validation_errors_and_exceptions(): void {
        $contents = array(
            'First,Surname,Gender,BTM,Email,Contact,YOB',
            'Bad,Player,M,111,bad@test,07000,1990',
            'Duplicate,Player,M,222,dup@test,07001,1990',
        );

        $wp_error = new WP_Error( 'invalid_email', 'Invalid email address' );

        $this->player_service->expects( self::exactly( 2 ) )
            ->method( 'add_new_player' )
            ->willReturnOnConsecutiveCalls(
                $wp_error,
                self::throwException( new Player_Exists_Exception( 'Player already exists' ) )
            );

        $result = $this->dispatcher->import_players( $contents, ',' );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertStringContainsString( '0 Players imported', $result->message );
        $this->assertStringContainsString( 'Error with player 1 details', $result->message );
        $this->assertStringContainsString( 'Invalid email address', $result->message );
        $this->assertStringContainsString( 'Player already exists', $result->message );
    }

    public function test_import_club_players_does_not_mutate_global_post_and_calls_registration_service(): void {
        $original_post = array( 'unrelated' => 'preserve_me' );
        $_POST         = $original_post;

        $contents = array(
            'First,Surname,Gender,BTM,Email,Contact,YOB',
            'Novak,Djokovic,M,99999,novak@tennis.test,07000000000,1987',
        );

        $this->registration_service->expects( self::once() )
            ->method( 'register_player_to_club' )
            ->with(
                5,
                self::anything(),
                self::callback( function ( array $data ) {
                    return 'Novak' === $data['firstname']
                        && 'Djokovic' === $data['surname']
                        && 'novak@tennis.test' === $data['email'];
                } )
            )
            ->willReturn( 'Player added to club' );

        $result = $this->dispatcher->import_club_players( $contents, ',', 5 );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertStringContainsString( '1 Club Players imported', $result->message );
        $this->assertSame( $original_post, $_POST, 'Global $_POST must not be mutated by club player import' );
    }

    public function test_import_table_updates_league_team_records(): void {
        $mock_league = $this->createMock( League::class );
        $mock_league->id = 10;
        $mock_league->expects( self::once() )
            ->method( 'add_team' )
            ->with( 10, '2026' )
            ->willReturn( 101 );
        $mock_league->expects( self::once() )
            ->method( 'set_teams_rank' )
            ->with( '2026' );

        $GLOBALS['wp_stubs_leagues'][10] = $mock_league;

        $league_team_mock = new class {
            public int $done_matches = 0;
            public int $won_matches = 0;
            public int $draw_matches = 0;
            public int $lost_matches = 0;
            public int $points_2_plus = 0;
            public int $points_2_minus = 0;
            public float $points_plus = 0;
            public float $points_minus = 0;
            public int $add_points = 0;
            public array $custom = array();
            public bool $updated = false;

            public function update(): void {
                $this->updated = true;
            }
        };

        $GLOBALS['wp_stubs_league_teams'][101] = $league_team_mock;

        global $racketmanager;
        $racketmanager = $this->createMock( RacketManager::class );
        $racketmanager->method( 'get_team_id' )->willReturn( 10 );

        $contents = array(
            'Team,Done,Won,Draw,Lost,Points2,Points,Add,SetsWon,SetsAllowed,GamesWon,GamesAllowed',
            'Team Alpha,5,4,0,1,8:2,12:3,0,16,4,120,40',
        );

        $result = $this->dispatcher->import_table( $contents, ',', 10, '2026' );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertStringContainsString( '1 Table Entries imported', $result->message );
        $this->assertTrue( $league_team_mock->updated );
        $this->assertSame( 8, $league_team_mock->points_2_plus );
        $this->assertSame( 2, $league_team_mock->points_2_minus );
    }

    public function test_full_filesystem_pipeline_with_mocked_wp_filesystem(): void {
        $this->action_guard->expects( self::once() )->method( 'assert_allowed' );

        $wp_filesystem_mock = new class {
            public array $copied = array();
            public array $deleted = array();

            public function copy( string $source, string $destination, bool $overwrite = true ): bool {
                $this->copied[] = array( $source, $destination );
                return true;
            }

            public function get_contents_array( string $file ): array {
                return array(
                    "First\tSurname\tGender\tBTM\tEmail\tContact\tYOB",
                    "Stan\tWawrinka\tM\t44444\tstan@tennis.test\t07111\t1985",
                );
            }

            public function delete( string $file ): bool {
                $this->deleted[] = $file;
                return true;
            }
        };

        $GLOBALS['wp_filesystem'] = $wp_filesystem_mock;

        $player_mock = (new \ReflectionClass( Player::class ))->newInstanceWithoutConstructor();
        $this->player_service->expects( self::once() )
            ->method( 'add_new_player' )
            ->willReturn( $player_mock );

        $files = array(
            'racketmanager_import' => array(
                'name'     => 'players.tab',
                'tmp_name' => '/tmp/php12345',
                'size'     => 120,
            ),
        );

        $post = array(
            'import'    => '1',
            'mode'      => 'players',
            'delimiter' => 'TAB',
        );

        $result = $this->dispatcher->handle( array(), $post, $files );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertStringContainsString( '1 Players imported', $result->message );
        $this->assertCount( 1, $wp_filesystem_mock->copied );
        $this->assertCount( 1, $wp_filesystem_mock->deleted );
    }
}
