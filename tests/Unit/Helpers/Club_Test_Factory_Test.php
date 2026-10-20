<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

final class Club_Test_Factory_Test extends TestCase {
    public function test_defaults_create_club_with_semantic_defaults(): void {
        $club = Club_Test_Factory::create();

        $this->assertInstanceOf( Club::class, $club );
        $this->assertSame( 1, $club->id );
        $this->assertSame( 'Test Club', $club->name );
        $this->assertSame( 'test-club', $club->shortcode );
        $this->assertNull( $club->address );
        $this->assertNull( $club->website );
    }

    public function test_create_with_custom_attributes(): void {
        $club = Club_Test_Factory::create(
            id: 12,
            name: 'Leigh Tennis Club',
            shortcode: 'leigh-tc',
            type: 'tennis',
            website: 'https://example.test',
            contactno: '01234567890',
            founded: 1920,
            facilities: '6 hard courts, clubhouse',
            address: '123 Tennis Lane, SS0 9SB'
        );

        $this->assertSame( 12, $club->id );
        $this->assertSame( 'Leigh Tennis Club', $club->name );
        $this->assertSame( 'leigh-tc', $club->shortcode );
        $this->assertSame( 'tennis', $club->type );
        $this->assertSame( 'https://example.test', $club->website );
        $this->assertSame( '01234567890', $club->contactno );
        $this->assertSame( 1920, $club->founded );
        $this->assertSame( '6 hard courts, clubhouse', $club->facilities );
        $this->assertSame( '123 Tennis Lane, SS0 9SB', $club->address );
    }

    public function test_create_with_null_id(): void {
        $club = Club_Test_Factory::create( id: null, name: 'Unsaved Club' );

        $this->assertNull( $club->id );
        $this->assertSame( 'Unsaved Club', $club->name );
    }

    public function test_create_with_overrides(): void {
        $club = Club_Test_Factory::create(
            overrides: [
                'team_count'   => 5,
                'player_count' => 20,
            ]
        );

        $this->assertSame( 5, $club->team_count );
        $this->assertSame( 20, $club->player_count );
    }

    public function test_rejects_unknown_property_overrides(): void {
        $this->expectException( \InvalidArgumentException::class );

        Club_Test_Factory::create( overrides: [ 'unknown_property' => true ] );
    }
}
