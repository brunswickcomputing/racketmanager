<?php

namespace {
	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $data, $options = 0, $depth = 512 ): false|string {
			return json_encode( $data, $options, $depth );
		}
	}
}

namespace Racketmanager\Tests\Unit\Services\Export\Formatters {

	use PHPUnit\Framework\TestCase;
	use Racketmanager\Services\Export\Formatters\Json_Formatter;

	class Json_Formatter_Test extends TestCase {
	private Json_Formatter $formatter;

	protected function setUp(): void {
		$this->formatter = new Json_Formatter();
	}

	public function test_format_returns_json() {
		$data = array(
			array( 'name' => 'John', 'score' => 10 ),
			array( 'name' => 'Jane', 'score' => 20 ),
		);

		$result = $this->formatter->format( $data );

		$this->assertJson( $result );
		$this->assertEquals( wp_json_encode( $data ), $result );
	}

	public function test_get_content_type() {
		$this->assertEquals( 'application/json', $this->formatter->get_content_type() );
	}

	public function test_get_file_extension() {
		$this->assertEquals( 'json', $this->formatter->get_file_extension() );
	}
}
}
