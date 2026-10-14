<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Rest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Rest\Rest_Match_Response;
use Racketmanager\Domain\DTO\Rest\Rest_Standing_Response;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\Util\Util_Lookup;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

/**
 * Verifies the typed REST response DTOs serialise to exactly the legacy JSON payload.
 */
final class Rest_Response_Dto_Parity_Test extends TestCase {

    private Rest_Controller_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->adapter = new Rest_Controller_Adapter();
    }

    /**
     * Legacy (pre-DTO) match payload construction, kept verbatim as a parity oracle.
     */
    private static function legacy_match( object $match ): stdClass {
        $json_result             = new stdClass();
        $league_title            = is_object( $match->league ?? null ) ? ( $match->league->title ?? '' ) : ( $match->league ?? '' );
        $json_result->league     = str_replace( '"', '', (string) $league_title );
        $home_title              = is_object( $match->teams['home'] ?? null ) ? ( $match->teams['home']->title ?? '' ) : '';
        $json_result->home_team  = str_replace( '"', '', (string) $home_title );
        $away_title              = is_object( $match->teams['away'] ?? null ) ? ( $match->teams['away']->title ?? '' ) : '';
        $json_result->away_team  = str_replace( '"', '', (string) $away_title );
        $json_result->match_date = substr( (string) ( $match->date ?? '' ), 0, 10 );
        $json_result->match_time = $match->start_time ?? '';
        if ( ! empty( $match->winner_id ) ) {
            $json_result->score = str_replace( '"', '', (string) ( $match->score ?? '' ) );
            if ( ! is_null( $match->status ?? null ) ) {
                $json_result->status = Util_Lookup::get_match_status( (int) $match->status );
            } else {
                $json_result->status = $match->status ?? null;
            }
        }
        return $json_result;
    }

    /**
     * Legacy (pre-DTO) standings row construction, kept verbatim as a parity oracle.
     */
    private static function legacy_standing( object $team, ?object $club_obj ): stdClass {
        $json_result = new stdClass();
        if ( ! empty( $club_obj ) ) {
            $json_result->club = str_replace( '"', '', (string) $club_obj->shortcode );
        }
        $json_result->league = $team->league;
        $json_result->season = $team->season;
        $json_result->team   = $team->title;
        $json_result->rank   = $team->rank;
        $json_result->status = $team->status;
        $json_result->played = $team->done_matches;
        $json_result->won    = $team->won_matches;
        $json_result->drawn  = $team->draw_matches;
        $json_result->lost   = $team->lost_matches;
        $json_result->points = $team->points['plus'] ?? 0;
        return $json_result;
    }

    public static function match_provider(): array {
        $full                 = new stdClass();
        $full->league         = (object) array( 'title' => 'Division "1"' );
        $full->teams          = array(
            'home' => (object) array( 'title' => 'Club "A"' ),
            'away' => (object) array( 'title' => 'Club B' ),
        );
        $full->date           = '2026-05-01 19:30:00';
        $full->start_time     = '19:30';
        $full->winner_id      = 3;
        $full->score          = '6-4 "6-3"';
        $full->status         = 0;

        $no_result             = clone $full;
        $no_result->winner_id  = 0;

        $null_status           = clone $full;
        $null_status->status   = null;

        $string_league         = new stdClass();
        $string_league->league = 'Plain League';
        $string_league->teams  = array();
        $string_league->date   = '2026-06-01';

        $empty                 = new stdClass();

        return array(
            'full result'           => array( $full ),
            'no result'             => array( $no_result ),
            'winner with null status' => array( $null_status ),
            'string league no teams' => array( $string_league ),
            'empty match'           => array( $empty ),
        );
    }

    #[DataProvider( 'match_provider' )]
    public function test_match_response_json_matches_legacy_payload( object $match ): void {
        $dto = $this->adapter->build_match_response( $match );

        self::assertInstanceOf( Rest_Match_Response::class, $dto );
        self::assertSame( json_encode( self::legacy_match( $match ) ), json_encode( $dto->to_array() ) );
        self::assertEquals( self::legacy_match( $match ), $this->adapter->prepare_match_for_response( $match ) );
    }

    public function test_match_response_omits_score_and_status_without_result(): void {
        $match             = new stdClass();
        $match->league     = 'L';
        $match->date       = '2026-01-01';
        $match->start_time = '10:00';

        $payload = $this->adapter->build_match_response( $match )->to_array();

        self::assertSame( array( 'league', 'home_team', 'away_team', 'match_date', 'match_time' ), array_keys( $payload ) );
        self::assertSame( '', $payload['home_team'] );
        self::assertSame( '', $payload['away_team'] );
    }

    public function test_match_response_includes_null_status_key_when_result_without_status(): void {
        $match            = new stdClass();
        $match->winner_id = 1;
        $match->score     = '6-0';

        $payload = $this->adapter->build_match_response( $match )->to_array();

        self::assertSame( array( 'league', 'home_team', 'away_team', 'match_date', 'match_time', 'score', 'status' ), array_keys( $payload ) );
        self::assertNull( $payload['status'] );
        self::assertSame( '6-0', $payload['score'] );
    }

    public function test_fixture_entity_serialises_derived_score_and_status(): void {
        $fixture              = new Fixture();
        $fixture->winner_id   = 10;
        $fixture->home_points = '6';
        $fixture->away_points = '3';
        $fixture->status      = 0;
        $fixture->date        = '2026-05-01 19:30:00';
        $fixture->start_time  = '19:30';

        $payload = $this->adapter->build_match_response( $fixture )->to_array();

        self::assertSame( array( 'league', 'home_team', 'away_team', 'match_date', 'match_time', 'score', 'status' ), array_keys( $payload ) );
        self::assertSame( '6 - 3', $payload['score'] );
        self::assertSame( 'Complete', $payload['status'] );
        self::assertSame( '2026-05-01', $payload['match_date'] );
        self::assertSame( '19:30', $payload['match_time'] );
    }

    public static function standing_provider(): array {
        $team               = new stdClass();
        $team->league       = 'Premier League';
        $team->season       = '2026';
        $team->title        = 'Team A';
        $team->rank         = 1;
        $team->status       = 'C';
        $team->done_matches = 10;
        $team->won_matches  = '7';
        $team->draw_matches = 2;
        $team->lost_matches = 1;
        $team->points       = array( 'plus' => 23.5 );

        $no_points          = clone $team;
        $no_points->points  = array();

        $nulls               = clone $team;
        $nulls->rank         = null;
        $nulls->status       = null;
        $nulls->points       = null;

        $club = (object) array( 'shortcode' => 'Club "X"' );

        return array(
            'without club'      => array( $team, null ),
            'with club'         => array( $team, $club ),
            'missing points'    => array( $no_points, null ),
            'null fields'       => array( $nulls, $club ),
            'empty club string' => array( $team, (object) array( 'shortcode' => '' ) ),
        );
    }

    #[DataProvider( 'standing_provider' )]
    public function test_standing_response_json_matches_legacy_payload( object $team, ?object $club ): void {
        $dto = $this->adapter->build_standing_response( $team, $club );

        self::assertInstanceOf( Rest_Standing_Response::class, $dto );
        self::assertSame( json_encode( self::legacy_standing( $team, $club ) ), json_encode( $dto->to_array() ) );
    }

    public function test_standing_response_key_order_and_optional_club(): void {
        $team = (object) array(
            'league'       => 'L',
            'season'       => 2026,
            'title'        => 'T',
            'rank'         => 2,
            'status'       => null,
            'done_matches' => 0,
            'won_matches'  => 0,
            'draw_matches' => 0,
            'lost_matches' => 0,
            'points'       => array( 'plus' => 0 ),
        );
        $expected_keys = array( 'league', 'season', 'team', 'rank', 'status', 'played', 'won', 'drawn', 'lost', 'points' );

        self::assertSame( $expected_keys, array_keys( $this->adapter->build_standing_response( $team )->to_array() ) );
        self::assertSame(
            array_merge( array( 'club' ), $expected_keys ),
            array_keys( $this->adapter->build_standing_response( $team, (object) array( 'shortcode' => 'abc' ) )->to_array() )
        );
    }

    public function test_collection_responses_serialise_dtos_to_legacy_objects(): void {
        $match = new stdClass();
        $match->league = 'L';
        $team = (object) array(
            'league' => 'L', 'season' => '2026', 'title' => 'T', 'rank' => 1, 'status' => 'C',
            'done_matches' => 1, 'won_matches' => 1, 'draw_matches' => 0, 'lost_matches' => 0,
            'points' => array( 'plus' => 3 ),
        );

        $matches_response   = $this->adapter->prepare_matches_response( array( $this->adapter->build_match_response( $match ) ) );
        $standings_response = $this->adapter->prepare_standings_response( array( $this->adapter->build_standing_response( $team ) ) );

        self::assertEquals( array( self::legacy_match( $match ) ), $matches_response->get_data() );
        self::assertEquals( array( self::legacy_standing( $team, null ) ), $standings_response->get_data() );
        self::assertSame( json_encode( array( self::legacy_match( $match ) ) ), json_encode( $matches_response->get_data() ) );
        self::assertSame( json_encode( array( self::legacy_standing( $team, null ) ) ), json_encode( $standings_response->get_data() ) );
    }

    public function test_empty_collections_serialise_to_empty_json_array(): void {
        self::assertSame( '[]', json_encode( $this->adapter->prepare_matches_response( array() )->get_data() ) );
        self::assertSame( '[]', json_encode( $this->adapter->prepare_standings_response( array() )->get_data() ) );
    }

    public function test_dtos_are_immutable(): void {
        $dto = new Rest_Match_Response( 'L', 'H', 'A', '2026-01-01', '10:00' );

        $this->expectException( \Error::class );
        /** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
        $dto->league = 'changed';
    }
}
