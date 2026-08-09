<?php
declare( strict_types=1 );
namespace Racketmanager\Tests\Unit\Domain\Competition;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Season_Collection;
class Season_Collection_Test extends TestCase {
    private array $seasons_data = [
        '2023' => ['id' => 1, 'name' => '2023'],
        '2024' => ['id' => 2, 'name' => '2024'],
        '2025' => ['id' => 3, 'name' => '2025'],
    ];
    public function test_all() {
        $collection = new Season_Collection( $this->seasons_data );
        $this->assertEquals( $this->seasons_data, $collection->all() );
    }
    public function test_get() {
        $collection = new Season_Collection( $this->seasons_data );
        $this->assertEquals( $this->seasons_data['2024'], $collection->get('2024') );
        $this->assertNull( $collection->get('2026') );
    }
    public function test_latest() {
        $collection = new Season_Collection( $this->seasons_data );
        $this->assertEquals( $this->seasons_data['2025'], $collection->latest() );
    }
    public function test_latest_empty() {
        $collection = new Season_Collection( [] );
        $this->assertNull( $collection->latest() );
    }
    public function test_reverse() {
        $collection = new Season_Collection( $this->seasons_data );
        $expected = array_reverse( $this->seasons_data );
        $this->assertEquals( $expected, $collection->reverse() );
    }
    public function test_has() {
        $collection = new Season_Collection( $this->seasons_data );
        $this->assertTrue( $collection->has('2024') );
        $this->assertFalse( $collection->has('2026') );
    }
    public function test_count() {
        $collection = new Season_Collection( $this->seasons_data );
        $this->assertEquals( 3, $collection->count() );
    }
}
