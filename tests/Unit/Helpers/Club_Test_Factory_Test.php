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
        $secretary   = (object) [ 'id' => 7, 'name' => 'Sec' ];
        $team        = (object) [ 'id' => 10 ];
        $match       = (object) [ 'id' => 20 ];
        $result      = (object) [ 'id' => 30 ];
        $player      = (object) [ 'id' => 40 ];
        $competition = (object) [ 'id' => 50 ];
        $entry       = (object) [ 'id' => 60 ];

        $club = Club_Test_Factory::create(
            overrides: [
                'id'              => 99,
                'name'            => 'Overridden Club',
                'shortcode'       => 'overridden',
                'type'            => 'padel',
                'website'         => 'https://overridden.test',
                'contactno'       => '0123456789',
                'founded'         => 1999,
                'facilities'      => 'Indoor padel courts',
                'address'         => '99 Padel Way',
                'single'          => true,
                'team_count'      => 5,
                'player_count'    => 20,
                'teams'           => [ $team ],
                'matches'         => [ $match ],
                'results'         => [ $result ],
                'players'         => [ $player ],
                'competitions'    => [ $competition ],
                'entry'           => [ 101 => $entry ],
                'match_secretary' => $secretary,
                'created_date'    => '2024-01-01',
                'removed_date'    => '2026-11-24',
            ]
        );

        $this->assertSame( 99, $club->id );
        $this->assertSame( 'Overridden Club', $club->name );
        $this->assertSame( 'overridden', $club->shortcode );
        $this->assertSame( 'padel', $club->type );
        $this->assertSame( 'https://overridden.test', $club->website );
        $this->assertSame( '0123456789', $club->contactno );
        $this->assertSame( 1999, $club->founded );
        $this->assertSame( 'Indoor padel courts', $club->facilities );
        $this->assertSame( '99 Padel Way', $club->address );
        $this->assertTrue( $club->single );
        $this->assertSame( 5, $club->team_count );
        $this->assertSame( 20, $club->player_count );
        $this->assertSame( [ $team ], $club->teams );
        $this->assertSame( [ $match ], $club->matches );
        $this->assertSame( [ $result ], $club->results );
        $this->assertSame( [ $player ], $club->players );
        $this->assertSame( [ $competition ], $club->competitions );
        $this->assertSame( [ 101 => $entry ], $club->entry );
        $this->assertSame( $secretary, $club->match_secretary );
        $this->assertSame( '2024-01-01', $club->created_date );
        $this->assertSame( '2026-11-24', $club->removed_date );
    }

    public function test_rejects_unknown_property_overrides(): void {
        $this->expectException( \InvalidArgumentException::class );

        Club_Test_Factory::create( overrides: [ 'unknown_property' => true ] );
    }
}
