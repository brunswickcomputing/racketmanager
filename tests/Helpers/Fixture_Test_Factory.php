<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;

class Fixture_Test_Factory {
    public static function create(
        int $id = 201,
        ?int $league_id = null,
        string|int $home_team = '1',
        string|int $away_team = '2',
        string $date = '2026-10-12',
        string $start_time = '18:00',
        string $group = 'Group 1',
        string $location = 'Court 1',
        ?string $fixture_title = null,
        ?string $link = null,
        ?int $winner_id = null,
        ?int $loser_id = null,
        ?string $home_points = null,
        ?string $away_points = null,
        string $season = '2026',
        ?int $status = null,
        int $match_day = 1,
        ?string $host = null,
        array $custom = [],
        ?string $confirmed = null,
        ?string $final = null,
        ?int $home_captain = null,
        ?int $away_captain = null,
        ?int $leg = null,
        ?int $linked_fixture = null,
        ?string $date_result_entered = null,
        ?array $comments = null,
    ): Fixture {
        return new Fixture( new Fixture_Hydration_DTO(
            id: $id,
            fixture_title: $fixture_title ?? sprintf( 'Team %s vs Team %s', $home_team, $away_team ),
            link: $link ?? sprintf( 'https://example.test/match/%d/', $id ),
            group: $group,
            date: $date,
            date_original: $date,
            home_team: (string) $home_team,
            away_team: (string) $away_team,
            match_day: $match_day,
            location: $location,
            host: $host,
            league_id: $league_id,
            season: $season,
            status: $status,
            start_time: $start_time,
            winner_id: $winner_id,
            loser_id: $loser_id,
            home_points: $home_points,
            away_points: $away_points,
            custom: $custom,
            confirmed: $confirmed,
            final: $final,
            home_captain: $home_captain,
            away_captain: $away_captain,
            leg: $leg,
            linked_fixture: $linked_fixture,
            date_result_entered: $date_result_entered,
            comments: $comments,
        ) );
    }
}
