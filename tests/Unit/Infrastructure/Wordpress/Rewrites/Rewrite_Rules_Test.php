<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Rewrites;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Rewrites\Rewrite_Rules;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Rewrite_Rules_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$GLOBALS['wp_rewrite_rules_added'] = array();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_construct_registers_expected_rewrite_rules(): void {
		$rewrites = new Rewrite_Rules();
		$this->assertInstanceOf( Rewrite_Rules::class, $rewrites );

		$added_rules = $GLOBALS['wp_rewrite_rules_added'] ?? array();
		$this->assertCount( 152, $added_rules );

		$rules_by_regex = array();
		foreach ( $added_rules as $rule ) {
			$this->assertSame( 'top', $rule['after'], 'All rewrite rules are expected to be registered at top priority' );
			$rules_by_regex[ $rule['regex'] ] = $rule['query'];
		}

		$this->assertSame(
			'index.php?pagename=competitions&type=league',
			$rules_by_regex['leagues/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=competitions&type=cup',
			$rules_by_regex['cups/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=competitions&type=tournament',
			$rules_by_regex['tournaments/?$'] ?? null
		);

		$this->assertSame(
			'index.php?pagename=leagues',
			$rules_by_regex['league-news/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=leagues%2F$matches[1]',
			$rules_by_regex['leagues/(.+?)-news/?$'] ?? null
		);

		$this->assertSame(
			'index.php?pagename=competition%2Fdaily-matches&match_date=$matches[1]-$matches[2]-$matches[3]&competition_type=league',
			$rules_by_regex['leagues/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$'] ?? null
		);

		$this->assertSame(
			'index.php?pagename=players%2Fplayer&player_id=$matches[1]&btm=$matches[2]',
			$rules_by_regex['player/(.+?)/([0-9]+)/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=players%2Fplayer&player_id=$matches[1]',
			$rules_by_regex['player/(.+?)/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=players',
			$rules_by_regex['players/?$'] ?? null
		);

		$this->assertSame(
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			$rules_by_regex['leagues/(.+?)/([0-9]{4})/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			$rules_by_regex['leagues/(.+?)/([0-9]{4})/team/(.+?)/?$'] ?? null
		);
		$this->assertSame(
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=teams',
			$rules_by_regex['leagues/(.+?)/([0-9]{4})/teams/?$'] ?? null
		);
	}
}
