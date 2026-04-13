<?php

namespace {
	if ( ! function_exists( 'mysql2date' ) ) {
		function mysql2date( $format, $date ): string {
			return date( $format, strtotime( $date ) );
		}
	}
}

namespace Racketmanager\Tests\Unit\Services\Export\Formatters {

	use PHPUnit\Framework\TestCase;
	use Racketmanager\Services\Export\Formatters\Ics_Formatter;

	class Ics_Formatter_Test extends TestCase {
		private Ics_Formatter $formatter;

		protected function setUp(): void {
			$this->formatter = new Ics_Formatter();
		}

		public function test_format_returns_ics_structure() {
			$data = array(
				array(
					'id'       => 1,
					'date'     => '2023-05-20 10:00:00',
					'title'    => 'Test Match',
					'location' => 'Court 1',
				),
			);

			$result = $this->formatter->format( $data );

			$this->assertStringContainsString( 'BEGIN:VCALENDAR', $result );
			$this->assertStringContainsString( 'BEGIN:VEVENT', $result );
			$this->assertStringContainsString( 'SUMMARY:Test Match', $result );
			$this->assertStringContainsString( 'LOCATION:Court 1', $result );
			$this->assertStringContainsString( 'UID:1', $result );
			$this->assertStringContainsString( 'END:VEVENT', $result );
			$this->assertStringContainsString( 'END:VCALENDAR', $result );
		}

		public function test_get_content_type() {
			$this->assertEquals( 'text/calendar', $this->formatter->get_content_type() );
		}

		public function test_get_file_extension() {
			$this->assertEquals( 'ics', $this->formatter->get_file_extension() );
		}
	}
}
