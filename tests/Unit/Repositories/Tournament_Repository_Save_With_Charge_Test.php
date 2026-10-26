<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use RuntimeException;
use wpdb;

require_once __DIR__ . '/../../wp-stubs.php';

final class Tournament_Repository_Save_With_Charge_Test extends TestCase {
	use Manages_Wordpress_Globals;

	/** @var array<int, string> transaction / raw statements issued through `query()` */
	private array $statements = [];
	/** @var array<int, string> ordered log of every wpdb write (`query:<sql>`, `insert`, `update`) */
	private array $log = [];

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		$GLOBALS['wp_stubs_cache']         = array();
		$this->statements                  = [];
		$this->log                         = [];
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function make_db( bool $insert_ok = true, bool|int $update_result = 1 ): wpdb|Stub {
		$db            = $this->createStub( wpdb::class );
		$db->prefix    = 'wp_';
		$db->insert_id = 0;
		$db->method( 'query' )->willReturnCallback( function ( string $sql ) {
			$this->statements[] = $sql;
			$this->log[]        = 'query:' . $sql;
			return 1;
		} );
		$db->method( 'insert' )->willReturnCallback( function () use ( $db, $insert_ok ) {
			$this->log[] = 'insert';
			if ( $insert_ok ) {
				$db->insert_id = 77;
				return 1;
			}
			return false;
		} );
		$db->method( 'update' )->willReturnCallback( function () use ( $update_result ) {
			$this->log[] = 'update';
			return $update_result;
		} );

		return $db;
	}

	private function make_repository( wpdb $db ): Tournament_Repository {
		return new Tournament_Repository( db: $db );
	}

	private function make_tournament( ?int $id = null ): Tournament {
		return new Tournament( (object) [ 'id' => $id, 'name' => 'Spring Open', 'competition_id' => 10, 'season' => '2026' ] );
	}

	public function test_commits_and_returns_both_ids_when_tournament_insert_and_charge_save_succeed(): void {
		$repository = $this->make_repository( $this->make_db() );
		$tournament = $this->make_tournament();
		$charge     = new Charge();

		$charge_repository = $this->createMock( Charge_Repository_Interface::class );
		$charge_repository->expects( self::once() )
			->method( 'save' )
			->with( self::identicalTo( $charge ) )
			->willReturnCallback( function () {
				$this->log[] = 'charge';
				return 501;
			} );

		$result = $repository->save_with_charge( $tournament, $charge, $charge_repository );

		self::assertSame( [ 'tournament' => 77, 'charge' => 501 ], $result );
		self::assertSame( 77, $tournament->get_id() );
		self::assertSame( [ 'START TRANSACTION', 'COMMIT' ], $this->statements );
		self::assertSame( [ 'query:START TRANSACTION', 'insert', 'charge', 'query:COMMIT' ], $this->log, 'charge must be saved inside the transaction' );
	}

	public function test_commits_when_existing_tournament_update_returns_true_and_charge_reports_no_change(): void {
		$repository = $this->make_repository( $this->make_db() );
		$tournament = $this->make_tournament( 42 );

		$charge_repository = $this->createStub( Charge_Repository_Interface::class );
		$charge_repository->method( 'save' )->willReturn( 0 );

		$result = $repository->save_with_charge( $tournament, new Charge(), $charge_repository );

		self::assertSame( [ 'tournament' => true, 'charge' => 0 ], $result );
		self::assertSame( [ 'query:START TRANSACTION', 'update', 'query:COMMIT' ], $this->log );
	}

	public function test_rolls_back_and_skips_charge_when_tournament_save_fails(): void {
		$repository = $this->make_repository( $this->make_db( insert_ok: false ) );

		$charge_repository = $this->createMock( Charge_Repository_Interface::class );
		$charge_repository->expects( self::never() )->method( 'save' );

		$result = $repository->save_with_charge( $this->make_tournament(), new Charge(), $charge_repository );

		self::assertSame( [ 'tournament' => false, 'charge' => null ], $result );
		self::assertSame( [ 'START TRANSACTION', 'ROLLBACK' ], $this->statements );
	}

	public function test_rolls_back_when_existing_tournament_update_fails(): void {
		$repository = $this->make_repository( $this->make_db( update_result: false ) );

		$charge_repository = $this->createMock( Charge_Repository_Interface::class );
		$charge_repository->expects( self::never() )->method( 'save' );

		$result = $repository->save_with_charge( $this->make_tournament( 42 ), new Charge(), $charge_repository );

		self::assertSame( [ 'tournament' => false, 'charge' => null ], $result );
		self::assertSame( [ 'query:START TRANSACTION', 'update', 'query:ROLLBACK' ], $this->log );
	}

	public function test_rolls_back_when_charge_save_fails_after_tournament_saved(): void {
		$repository = $this->make_repository( $this->make_db() );

		$charge_repository = $this->createStub( Charge_Repository_Interface::class );
		$charge_repository->method( 'save' )->willReturn( false );

		$result = $repository->save_with_charge( $this->make_tournament(), new Charge(), $charge_repository );

		self::assertSame( [ 'tournament' => 77, 'charge' => false ], $result );
		self::assertSame( [ 'START TRANSACTION', 'ROLLBACK' ], $this->statements );
	}

	public function test_rolls_back_and_rethrows_when_charge_save_throws(): void {
		$repository = $this->make_repository( $this->make_db() );

		$charge_repository = $this->createStub( Charge_Repository_Interface::class );
		$charge_repository->method( 'save' )->willThrowException( new RuntimeException( 'boom' ) );

		try {
			$repository->save_with_charge( $this->make_tournament(), new Charge(), $charge_repository );
			self::fail( 'Expected the exception to propagate.' );
		} catch ( RuntimeException $exception ) {
			self::assertSame( 'boom', $exception->getMessage() );
		}

		self::assertSame( [ 'START TRANSACTION', 'ROLLBACK' ], $this->statements );
	}
}
