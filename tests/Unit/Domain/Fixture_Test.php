<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Result\Result;

final class Fixture_Test extends TestCase {
    public function test_set_result_updates_fixture_properties(): void {
        $fixture = Fixture_Factory::from_object((object)[
            'id' => 1,
            'status' => 0
        ]);

        $result = new Result(
            home_points: 3.0,
            away_points: 1.0,
            winner_id: 10,
            loser_id: 20,
            status: 1, // Walkover
            sets: [['p1' => 6, 'p2' => 4]],
            custom: ['some' => 'data']
        );

        $fixture->set_result($result);

        $this->assertSame('3', $fixture->get_home_points());
        $this->assertSame('1', $fixture->get_away_points());
        $this->assertSame(10, $fixture->get_winner_id());
        $this->assertSame(20, $fixture->get_loser_id());
        $this->assertSame(1, $fixture->get_status());
        $this->assertTrue($fixture->is_walkover());
        
        $custom = $fixture->get_custom();
        $this->assertSame('data', $custom['some']);
        $this->assertSame([['p1' => 6, 'p2' => 4]], $custom['sets']);
    }

    public function test_set_result_updates_status_flags(): void {
        $fixture = Fixture_Factory::from_object((object)['id' => 1]);
        
        // Test Shared status
        $result = new Result(home_points: 2, away_points: 2, status: 3);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_shared());
        $this->assertFalse($fixture->is_walkover());

        // Test Cancelled status
        $result = new Result(home_points: 0, away_points: 0, status: 8);
        $fixture->set_result($result);
        $this->assertTrue($fixture->is_cancelled());
        $this->assertFalse($fixture->is_shared());
    }

    public function test_is_pending_reflects_winner_id(): void {
        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => null ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => 0 ]);
        $this->assertTrue($fixture->is_pending());

        $fixture = Fixture_Factory::from_object((object)[ 'winner_id' => 123 ]);
        $this->assertFalse($fixture->is_pending());

        $result = new Result(home_points: 3, away_points: 0, winner_id: 456);
        $fixture->set_result($result);
        $this->assertFalse($fixture->is_pending());

        $fixture->reset_result();
        $this->assertTrue($fixture->is_pending());
    }

    public function test_start_time_is_set_from_constructor(): void {
        $fixture = Fixture_Factory::from_object((object)[ 'start_time' => '10:00' ]);
        $this->assertEquals('10:00', $fixture->get_start_time());

        $fixture->set_start_time('11:30');
        $this->assertEquals('11:30', $fixture->get_start_time());
    }

    public function test_constructor_handles_string_comments_gracefully(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => 'This is a plain string comment'
        ];

        // This should NOT throw a TypeError
        $fixture = Fixture_Factory::from_object($fixture_data);

        $this->assertIsArray($fixture->get_comments(), 'Comments should be converted to an array');
        $this->assertSame('This is a plain string comment', $fixture->get_comments()['legacy']);
    }

    public function test_constructor_handles_null_comments_gracefully(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => null
        ];

        $fixture = Fixture_Factory::from_object($fixture_data);
        $this->assertNull($fixture->get_comments());
    }

    public function test_create_from_dto_directly(): void {
        $dto = new Fixture_Hydration_DTO(
            id: 99,
            fixture_title: 'Title',
            link: 'http://test',
            date: '2026-05-01',
            start_time: '14:00',
            season: '2026',
            league_id: 10,
            home_team: 'Home Team',
            away_team: 'Away Team',
            home_points: '4',
            away_points: '2',
            winner_id: 1,
            loser_id: 2,
            status: 0,
            confirmed: 'Y',
            custom: array( 'note' => 'test' ),
            comments: array( 'admin' => 'ok' ),
            rubbers: array()
        );

        $fixture = new Fixture( $dto );
        $this->assertSame( 99, $fixture->get_id() );
        $this->assertSame( 'Title', $fixture->fixture_title );
        $this->assertSame( '2026', $fixture->get_season() );
        $this->assertSame( 10, $fixture->get_league_id() );
        $this->assertSame( 'Home Team', $fixture->get_home_team() );
        $this->assertSame( 'Away Team', $fixture->get_away_team() );
        $this->assertSame( '4', $fixture->get_home_points() );
        $this->assertSame( '2', $fixture->get_away_points() );
        $this->assertSame( 1, $fixture->get_winner_id() );
        $this->assertSame( 2, $fixture->get_loser_id() );
        $this->assertSame( 'Y', $fixture->get_confirmed() );
        $this->assertSame( array( 'note' => 'test' ), $fixture->get_custom() );
        $this->assertSame( array( 'admin' => 'ok' ), $fixture->get_comments() );
    }

    public function test_fixture_final_property_and_finals_data(): void {
        $fixture = Fixture_Factory::from_object( (object) [
            'id'    => 10,
            'final' => 'final',
            'date'  => '2026-06-01 10:00:00',
        ] );

        $this->assertSame( 'final', $fixture->get_final() );

        $fixture->set_final( 'semi' );
        $this->assertSame( 'semi', $fixture->get_final() );

        // Test set_finals_data
        $changed = $fixture->set_finals_data( '2026-06-15 14:00:00', 'Court 1' );
        $this->assertTrue( $changed );
        $this->assertSame( '2026-06-15 14:00:00', $fixture->get_date() );
        $this->assertSame( 'Court 1', $fixture->get_location() );

        // Test reset_finals_data
        $reset_changed = $fixture->reset_finals_data( '2026-06-20 10:00:00' );
        $this->assertTrue( $reset_changed );
        $this->assertSame( '2026-06-20 10:00:00', $fixture->get_date() );
    }
}
