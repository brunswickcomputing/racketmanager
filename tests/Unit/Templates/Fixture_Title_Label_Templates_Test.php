<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture_Factory;

final class Fixture_Title_Label_Templates_Test extends TestCase {
	private mixed $previous = null;

	protected function setUp(): void {
		parent::setUp();
		$this->previous                         = $GLOBALS['wp_stubs_translate_callback'] ?? null;
		$GLOBALS['wp_stubs_translate_callback'] = static fn( string $text ): string => match ( $text ) {
			'Bye' => 'Freilos',
			'Unknown' => 'Unbekannt',
			default => $text,
		};
	}

	protected function tearDown(): void {
		if ( null === $this->previous ) {
			unset( $GLOBALS['wp_stubs_translate_callback'] );
		} else {
			$GLOBALS['wp_stubs_translate_callback'] = $this->previous;
		}
		parent::tearDown();
	}

	private function bye_fixture(): object {
		return Fixture_Factory::from_array( array( 'id' => 63, 'home_team' => '-1', 'away_team' => '999' ) );
	}

	public function test_match_dropdown_translates_bye_and_unknown_labels(): void {
		$matches  = array( $this->bye_fixture() );
		$match_id = 0;
		ob_start();
		include dirname( __DIR__, 3 ) . '/templates/league/match-dropdown.php';
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'Freilos - Unbekannt', $html );
		$this->assertStringNotContainsString( 'Unknown - Unknown', $html );
	}

	public function test_email_match_heading_translates_bye_and_unknown_labels(): void {
		$match            = $this->bye_fixture();
		$competition_name = 'Cup';
		ob_start();
		include dirname( __DIR__, 3 ) . '/templates/email/components/match-heading.php';
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'Freilos - Unbekannt', $html );
	}
}
