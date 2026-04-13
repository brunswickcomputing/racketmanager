<?php

namespace Racketmanager\Tests\Unit\Services\Export\Formatters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Export\Formatters\Csv_Formatter;
use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;

class Csv_Formatter_Test extends TestCase {
	private Csv_Formatter $formatter;

	protected function setUp(): void {
		$this->formatter = new Csv_Formatter();
	}

	public function test_format_returns_csv() {
		$data = array(
			array( 'John', '10' ),
			array( 'Jane', '20' ),
		);
		$options = array(
			'headers' => array( 'Name', 'Score' ),
		);

		$result = $this->formatter->format( $data, $options );

		$expected = "Name,Score\r\nJohn,10\r\nJane,20\r\n";
		$this->assertEquals( $expected, $result );
	}

	public function test_format_without_headers() {
		$data = array(
			array( 'John', '10' ),
		);

		$result = $this->formatter->format( $data );

		$expected = "John,10\r\n";
		$this->assertEquals( $expected, $result );
	}

	public function test_get_content_type() {
  $this->assertEquals( Export_Formatter_Interface::CONTENT_TYPE_CSV, $this->formatter->get_content_type() );
	}

	public function test_get_file_extension() {
		$this->assertEquals( 'csv', $this->formatter->get_file_extension() );
	}
}
