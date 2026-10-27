<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Factory;
use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;
use stdClass;

final class Club_Factory_Test extends TestCase {

    public function test_create_from_dto_instantiates_club_with_matching_properties(): void {
        $dto = new Club_Hydration_DTO(
            id: 42,
            name: 'Leigh Lawn Tennis Club',
            shortcode: 'LLTC',
            type: 'tennis',
            website: 'https://leighlawn.example',
            contactno: '01702 123456',
            founded: 1930,
            facilities: '6 grass courts, 4 hard courts',
            address: 'The Ridgeway, Chalkwell, SS0 8NP',
            team_count: 5,
            player_count: 120,
            players: [ 1, 2, 3 ],
            teams: [ 10, 20 ],
            matches: [ 100 ],
            results: [ 200 ],
            created_date: '2020-01-01',
            removed_date: null,
            single: true,
            entry: [ 'comp-1' ],
            competitions: [ (object) [ 'id' => 1 ] ],
            match_secretary: (object) [ 'name' => 'Jane Secretary' ]
        );

        $club = Club_Factory::create_from_dto( $dto );

        $this->assertInstanceOf( Club::class, $club );
        $this->assertSame( 42, $club->id );
        $this->assertSame( 'Leigh Lawn Tennis Club', $club->name );
        $this->assertSame( 'LLTC', $club->shortcode );
        $this->assertSame( 'tennis', $club->type );
        $this->assertSame( 'https://leighlawn.example', $club->website );
        $this->assertSame( '01702 123456', $club->contactno );
        $this->assertSame( 1930, $club->founded );
        $this->assertSame( '6 grass courts, 4 hard courts', $club->facilities );
        $this->assertSame( 'The Ridgeway, Chalkwell, SS0 8NP', $club->address );
        $this->assertSame( 5, $club->team_count );
        $this->assertSame( 120, $club->player_count );
        $this->assertSame( [ 1, 2, 3 ], $club->players );
        $this->assertSame( [ 10, 20 ], $club->teams );
        $this->assertSame( [ 100 ], $club->matches );
        $this->assertSame( [ 200 ], $club->results );
        $this->assertSame( '2020-01-01', $club->created_date );
        $this->assertNull( $club->removed_date );
        $this->assertTrue( $club->single );
        $this->assertSame( [ 'comp-1' ], $club->entry );
        $this->assertCount( 1, $club->competitions );
        $this->assertSame( 'Jane Secretary', $club->match_secretary->name );
    }

    public function test_from_object_handles_numeric_strings_for_id_and_founded(): void {
        $row = (object) [
            'id'           => '88',
            'name'         => 'Westcliff Hard',
            'founded'      => '1955',
            'team_count'   => '7',
            'player_count' => '40',
        ];

        $club = Club_Factory::from_object( $row );

        $this->assertSame( 88, $club->id );
        $this->assertSame( 'Westcliff Hard', $club->name );
        $this->assertSame( 1955, $club->founded );
        $this->assertSame( 7, $club->team_count );
        $this->assertSame( 40, $club->player_count );
    }

    public function test_from_object_handles_non_numeric_founded_and_non_object_secretary(): void {
        $row = (object) [
            'id'              => 12,
            'name'            => 'Invalid Field Club',
            'founded'         => 'circa 1920s',
            'match_secretary' => 'Jane (string instead of object)',
        ];

        $club = Club_Factory::from_object( $row );

        $this->assertSame( 12, $club->id );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->match_secretary );
    }

    public function test_from_object_coerces_non_array_collections_to_empty_arrays(): void {
        $row = (object) [
            'id'           => 15,
            'name'         => 'Collection Test Club',
            'players'      => 'not-an-array',
            'teams'        => null,
            'competitions' => 123,
            'entry'        => false,
            'matches'      => 'none',
            'results'      => null,
        ];

        $club = Club_Factory::from_object( $row );

        $this->assertSame( [], $club->players );
        $this->assertSame( [], $club->teams );
        $this->assertSame( [], $club->competitions );
        $this->assertSame( [], $club->entry );
        $this->assertSame( [], $club->matches );
        $this->assertSame( [], $club->results );
    }

    public function test_from_array_hydrates_club_properly(): void {
        $data = [
            'id'        => 99,
            'name'      => 'Array Hydrated Club',
            'shortcode' => 'AHC',
            'founded'   => 2005,
            'players'   => [ 10, 20 ],
        ];

        $club = Club_Factory::from_array( $data );

        $this->assertInstanceOf( Club::class, $club );
        $this->assertSame( 99, $club->id );
        $this->assertSame( 'Array Hydrated Club', $club->name );
        $this->assertSame( 'AHC', $club->shortcode );
        $this->assertSame( 2005, $club->founded );
        $this->assertSame( [ 10, 20 ], $club->players );
    }

    public function test_constructor_accepts_dto_directly_and_rejects_raw_objects(): void {
        $dto  = new Club_Hydration_DTO( id: 5, name: 'Direct DTO Club', shortcode: 'DDC' );
        $club = new Club( $dto );

        $this->assertSame( 5, $club->id );
        $this->assertSame( 'Direct DTO Club', $club->name );
        $this->assertSame( 'DDC', $club->shortcode );

        $empty = new Club();
        $this->assertNull( $empty->id );
        $this->assertSame( '', $empty->name );

        $this->expectException( \TypeError::class );
        new Club( (object) [ 'id' => 1, 'name' => 'Raw' ] );
    }

    public function test_lenient_from_object_matches_factory_output_and_drops_unknown_keys(): void {
        $row = (object) [
            'id'          => '31',
            'name'        => 'Parity Club',
            'founded'     => '1901',
            'unknown_key' => 'ignored',
        ];

        $lenient   = Club::from_object( $row );
        $validated = Club_Factory::from_object( $row );

        $this->assertEquals( $validated, $lenient );
        $this->assertSame( 31, $lenient->id );
        $this->assertSame( 1901, $lenient->founded );
        $this->assertFalse( property_exists( $lenient, 'unknown_key' ) );
        $this->assertEquals( $lenient, Club::from_array( (array) $row ) );
    }
}
