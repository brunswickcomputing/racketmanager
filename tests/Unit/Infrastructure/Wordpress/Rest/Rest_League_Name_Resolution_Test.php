<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Rest;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Rest_Controller_Adapter_Exposed extends Rest_Controller_Adapter {
	public function expose_league_name( object $match ): string {
		return $this->resolve_league_name( $match );
	}

	public function expose_league_title( object $match ): string {
		return $this->resolve_league_title( $match );
	}
}

final class Rest_League_Name_Resolution_Test extends TestCase {

	private Rest_Controller_Adapter_Exposed $adapter;

	protected function setUp(): void {
		parent::setUp();
		$this->adapter = new Rest_Controller_Adapter_Exposed();
	}

	public function test_resolve_league_name_prefers_name_over_legacy_title(): void {
		$league        = new \stdClass();
		$league->name  = 'Division 1';
		$league->title = 'Legacy Division';
		$match         = (object) array( 'league' => $league );

		$this->assertSame( 'Division 1', $this->adapter->expose_league_name( $match ) );
	}

	public function test_resolve_league_name_falls_back_to_legacy_title_when_name_is_empty(): void {
		$league        = new \stdClass();
		$league->name  = '';
		$league->title = 'Division 2';
		$match         = (object) array( 'league' => $league );

		$this->assertSame( 'Division 2', $this->adapter->expose_league_name( $match ) );
	}

	public function test_resolve_league_name_strips_double_quotes(): void {
		$league       = new \stdClass();
		$league->name = 'Division "3"';
		$match        = (object) array( 'league' => $league );

		$this->assertSame( 'Division 3', $this->adapter->expose_league_name( $match ) );
	}

	public function test_resolve_league_name_returns_empty_string_for_missing_league(): void {
		$match = (object) array( 'league' => null );

		$this->assertSame( '', $this->adapter->expose_league_name( $match ) );
	}

	public function test_resolve_league_title_forwarder_matches_resolve_league_name(): void {
		$league        = new \stdClass();
		$league->name  = 'Division 4';
		$league->title = 'Legacy Division 4';
		$match         = (object) array( 'league' => $league );

		$this->assertSame(
			$this->adapter->expose_league_name( $match ),
			$this->adapter->expose_league_title( $match )
		);
		$this->assertSame( 'Division 4', $this->adapter->expose_league_title( $match ) );
	}

	public function test_resolve_league_title_forwarder_returns_empty_string_for_missing_league(): void {
		$match = (object) array( 'league' => null );

		$this->assertSame( '', $this->adapter->expose_league_title( $match ) );
	}
}
