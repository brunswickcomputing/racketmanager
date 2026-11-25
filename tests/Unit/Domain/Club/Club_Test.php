<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Club;

use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;
use TypeError;

final class Club_Test extends TestCase {

    public function test_constructor_hydrates_from_dto(): void {
        $dto  = new Club_Hydration_DTO( id: 5, name: 'Direct DTO Club', shortcode: 'DDC' );
        $club = new Club( $dto );

        $this->assertSame( 5, $club->id );
        $this->assertSame( 'Direct DTO Club', $club->name );
        $this->assertSame( 'DDC', $club->shortcode );
    }

    public function test_constructor_without_dto_yields_default_values(): void {
        $club = new Club();

        $this->assertNull( $club->id );
        $this->assertSame( '', $club->name );
        $this->assertNull( $club->shortcode );
        $this->assertNull( $club->founded );
        $this->assertSame( 0, $club->team_count );
        $this->assertSame( 0, $club->player_count );
        $this->assertSame( [], $club->players );
        $this->assertSame( [], $club->teams );
        $this->assertSame( [], $club->competitions );
        $this->assertFalse( $club->single );
        $this->assertNull( $club->match_secretary );
    }

    public function test_constructor_rejects_raw_object(): void {
        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Club::class,
            new Club( (object) [ 'id' => 1, 'name' => 'Raw' ] )
        );
    }

    #[DataProvider( 'provide_all_club_properties' )]
    public function test_cannot_modify_private_set_properties_directly( string $property, mixed $value ): void {
        $club = new Club();

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property Racketmanager\\\\Domain\\\\Club\\\\Club::\$' . $property . '(?: from scope [^\r\n]+)?$/' );

        $club->{$property} = $value;
    }

    /**
     * Data provider of all 21 properties for asymmetric visibility tests.
     *
     * @return array<string, array{string, mixed}> Property name and sample value.
     */
    public static function provide_all_club_properties(): array {
        return [
            'id'              => [ 'id', 123 ],
            'name'            => [ 'name', 'Direct Mutation Attempt' ],
            'shortcode'       => [ 'shortcode', 'MUT' ],
            'type'            => [ 'type', 'tennis' ],
            'website'         => [ 'website', 'https://example.com' ],
            'contactno'       => [ 'contactno', '0123456789' ],
            'founded'         => [ 'founded', 1980 ],
            'facilities'      => [ 'facilities', '4 courts' ],
            'address'         => [ 'address', 'Street' ],
            'team_count'      => [ 'team_count', 4 ],
            'player_count'    => [ 'player_count', 40 ],
            'players'         => [ 'players', [ (object) [ 'id' => 1 ] ] ],
            'teams'           => [ 'teams', [ (object) [ 'id' => 2 ] ] ],
            'matches'         => [ 'matches', [ (object) [ 'id' => 3 ] ] ],
            'results'         => [ 'results', [ (object) [ 'id' => 4 ] ] ],
            'created_date'    => [ 'created_date', '2024-01-01' ],
            'removed_date'    => [ 'removed_date', '2026-11-24' ],
            'single'          => [ 'single', true ],
            'entry'           => [ 'entry', [ 10 => true ] ],
            'competitions'    => [ 'competitions', [ (object) [ 'id' => 5 ] ] ],
            'match_secretary' => [ 'match_secretary', (object) [ 'id' => 6 ] ],
        ];
    }

    public function test_mutators_update_fields(): void {
        $club = new Club();
        $sec  = (object) [ 'id' => 42, 'name' => 'Secretary' ];
        $team = (object) [ 'id' => 1, 'name' => 'Team A' ];
        $mat  = (object) [ 'id' => 10 ];
        $res  = (object) [ 'id' => 20 ];
        $ply  = (object) [ 'id' => 30 ];
        $cmp  = (object) [ 'id' => 40 ];
        $ent  = (object) [ 'id' => 50 ];

        $club->set_id( 12 );
        $club->set_name( 'Westcliff Lawn Tennis Club' );
        $club->set_shortcode( 'WLTC' );
        $club->set_type( 'tennis' );
        $club->set_website( 'https://wltc.example.com' );
        $club->set_contact_no( '0123456789' );
        $club->set_founded( 1920 );
        $club->set_facilities( '6 grass courts' );
        $club->set_address( '123 Tennis Lane' );
        $club->set_team_count( 8 );
        $club->set_player_count( 150 );
        $club->set_created_date( '2020-01-01' );
        $club->set_removed_date( '2026-11-24' );
        $club->set_single( true );
        $club->set_match_secretary( $sec );
        $club->set_teams( [ $team ] );
        $club->set_matches( [ $mat ] );
        $club->set_results( [ $res ] );
        $club->set_players( [ $ply ] );
        $club->set_competitions( [ $cmp ] );
        $club->set_entry( [ $ent ] );

        $this->assertSame( 12, $club->id );
        $this->assertSame( 12, $club->get_id() );
        $this->assertSame( 'Westcliff Lawn Tennis Club', $club->name );
        $this->assertSame( 'Westcliff Lawn Tennis Club', $club->get_name() );
        $this->assertSame( 'WLTC', $club->shortcode );
        $this->assertSame( 'WLTC', $club->get_shortcode() );
        $this->assertSame( 'tennis', $club->type );
        $this->assertSame( 'tennis', $club->get_type() );
        $this->assertSame( 'https://wltc.example.com', $club->website );
        $this->assertSame( 'https://wltc.example.com', $club->get_website() );
        $this->assertSame( '0123456789', $club->contactno );
        $this->assertSame( '0123456789', $club->get_contact_no() );
        $this->assertSame( 1920, $club->founded );
        $this->assertSame( '1920', $club->get_founded() );
        $this->assertSame( '6 grass courts', $club->facilities );
        $this->assertSame( '6 grass courts', $club->get_facilities() );
        $this->assertSame( '123 Tennis Lane', $club->address );
        $this->assertSame( '123 Tennis Lane', $club->get_address() );
        $this->assertSame( 8, $club->team_count );
        $this->assertSame( 150, $club->player_count );
        $this->assertSame( '2020-01-01', $club->created_date );
        $this->assertSame( '2026-11-24', $club->removed_date );
        $this->assertTrue( $club->single );
        $this->assertSame( $sec, $club->match_secretary );
        $this->assertSame( [ $team ], $club->teams );
        $this->assertSame( [ $mat ], $club->matches );
        $this->assertSame( [ $res ], $club->results );
        $this->assertSame( [ $ply ], $club->players );
        $this->assertSame( [ $cmp ], $club->competitions );
        $this->assertSame( [ $ent ], $club->entry );

        // Test set_contactno alias
        $club->set_contactno( '0987654321' );
        $this->assertSame( '0987654321', $club->contactno );
    }

    public function test_setters_accept_nullable_types(): void {
        $club = new Club();
        $club->set_website( null );
        $club->set_type( null );
        $club->set_address( null );
        $club->set_contact_no( null );
        $club->set_contactno( null );
        $club->set_facilities( null );
        $club->set_shortcode( null );
        $club->set_founded( null );
        $club->set_match_secretary( null );
        $club->set_created_date( null );
        $club->set_removed_date( null );

        $this->assertNull( $club->website );
        $this->assertNull( $club->type );
        $this->assertNull( $club->address );
        $this->assertNull( $club->contactno );
        $this->assertNull( $club->facilities );
        $this->assertNull( $club->shortcode );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->get_founded() );
        $this->assertNull( $club->match_secretary );
        $this->assertNull( $club->created_date );
        $this->assertNull( $club->removed_date );
    }

    public function test_set_founded_handles_numeric_strings_and_non_numeric_values(): void {
        $club = new Club();

        $club->set_founded( 1980 );
        $this->assertSame( 1980, $club->founded );
        $this->assertSame( '1980', $club->get_founded() );

        $club->set_founded( '1984' );
        $this->assertSame( 1984, $club->founded );
        $this->assertSame( '1984', $club->get_founded() );

        $club->set_founded( '0' );
        $this->assertSame( 0, $club->founded );
        $this->assertSame( '0', $club->get_founded() );

        $club->set_founded( 'circa 1920' );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->get_founded() );

        $club->set_founded( 'not-a-year' );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->get_founded() );

        $club->set_founded( '' );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->get_founded() );

        $club->set_founded( null );
        $this->assertNull( $club->founded );
        $this->assertNull( $club->get_founded() );
    }
}
