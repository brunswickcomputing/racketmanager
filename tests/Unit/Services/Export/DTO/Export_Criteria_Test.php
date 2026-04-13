<?php

namespace {
	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $text ) {
			return $text;
		}
	}
}

namespace Racketmanager\Tests\Unit\Services\Export\DTO {

	use PHPUnit\Framework\TestCase;
	use Racketmanager\Services\Export\DTO\Export_Criteria;

	class Export_Criteria_Test extends TestCase {
	public function test_constructor_populates_properties() {
		$args = array(
			'league_id'      => '123',
			'competition_id' => '456',
			'season'         => '2023-24',
			'club_id'        => '789',
			'team_id'        => '101',
			'date_from'      => '2023-01-01',
			'date_to'        => '2023-12-31',
			'format'         => 'csv',
		);

		$criteria = new Export_Criteria( $args );

		$this->assertEquals( 123, $criteria->league_id );
		$this->assertEquals( 456, $criteria->competition_id );
		$this->assertEquals( '2023-24', $criteria->season );
		$this->assertEquals( 789, $criteria->club_id );
		$this->assertEquals( 101, $criteria->team_id );
		$this->assertEquals( '2023-01-01', $criteria->date_from );
		$this->assertEquals( '2023-12-31', $criteria->date_to );
		$this->assertEquals( 'csv', $criteria->format );
	}

	public function test_constructor_handles_missing_args() {
		$criteria = new Export_Criteria( array() );

		$this->assertNull( $criteria->league_id );
		$this->assertNull( $criteria->competition_id );
		$this->assertNull( $criteria->season );
		$this->assertNull( $criteria->club_id );
		$this->assertNull( $criteria->team_id );
		$this->assertNull( $criteria->date_from );
		$this->assertNull( $criteria->date_to );
		$this->assertNull( $criteria->format );
	}
}
}
