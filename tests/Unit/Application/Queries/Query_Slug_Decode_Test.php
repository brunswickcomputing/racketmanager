<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\League\Queries\Get_League_Players_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Shared\Seo_Slug;

/**
 * The query objects decode rewrite-tag slugs through the production codec
 * (Domain\Shared\Seo_Slug), not through the legacy helper / test stubs.
 */
final class Query_Slug_Decode_Test extends TestCase {
    public function test_event_players_query_decodes_player_slug_with_production_codec(): void {
        $query = Get_Event_Players_Query::from_atts( [ 'id' => '7' ], [ 'player_id' => 'John-O_Brien' ] );

        $this->assertSame( 7, $query->event_id );
        $this->assertSame( 'john o-brien', $query->player_id );
        $this->assertSame( Seo_Slug::decode( 'John-O_Brien' ), $query->player_id );
    }

    public function test_event_players_query_prefers_attribute_over_query_var(): void {
        $query = Get_Event_Players_Query::from_atts( [ 'id' => '7', 'players' => '42' ], [ 'player_id' => 'john-doe' ] );

        $this->assertSame( '42', $query->player_id );
    }

    public function test_event_players_query_leaves_player_null_without_query_var(): void {
        $query = Get_Event_Players_Query::from_atts( [ 'id' => '7' ], [ 'player_id' => '' ] );

        $this->assertNull( $query->player_id );
    }

    public function test_league_players_query_decodes_player_slug_with_production_codec(): void {
        $query = Get_League_Players_Query::from_atts( [ 'league_id' => '3' ], [ 'player_id' => 'Mary-Anne_Smith' ] );

        $this->assertSame( 3, $query->league_id );
        $this->assertSame( 'mary anne-smith', $query->player_id );
    }

    public function test_league_players_query_prefers_attribute_over_query_var(): void {
        $query = Get_League_Players_Query::from_atts( [ 'league_id' => '3', 'players' => '99' ], [ 'player_id' => 'mary-anne' ] );

        $this->assertSame( '99', $query->player_id );
    }

    public function test_league_teams_query_decodes_team_slug_with_production_codec(): void {
        $query = Get_League_Teams_Query::from_atts( [ 'id' => '5' ], [ 'team' => 'Leigh-Westcliff_A' ] );

        $this->assertSame( 5, $query->league_id );
        $this->assertSame( 'leigh westcliff-a', $query->team_id );
        $this->assertSame( Seo_Slug::decode( 'Leigh-Westcliff_A' ), $query->team_id );
    }

    public function test_league_teams_query_prefers_attribute_over_query_var(): void {
        $query = Get_League_Teams_Query::from_atts( [ 'id' => '5', 'teams' => '12' ], [ 'team' => 'leigh-westcliff' ] );

        $this->assertSame( '12', $query->team_id );
    }
}
