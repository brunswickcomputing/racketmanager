<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Confirmation_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Update_Request;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Result;

final class Result_Test_Factory {
    public static function create_team_update_request(
        int $match_id = 123,
        ?string $match_status = 'completed',
        ?array $rubber_statuses = [ '1' => 'completed' ],
        ?array $match_comments = [],
        ?array $rubber_ids = [ 1 => 10 ],
        ?array $rubber_types = [ 1 => 'S' ],
        ?array $players = [ 1 => [ 'home' => [ 1 => 1001 ], 'away' => [ 1 => 2001 ] ] ],
        ?array $sets = [ 1 => [] ]
    ): Team_Result_Update_Request {
        return new Team_Result_Update_Request(
            match_id: $match_id,
            match_status: $match_status,
            rubber_statuses: $rubber_statuses,
            match_comments: $match_comments,
            rubber_ids: $rubber_ids,
            rubber_types: $rubber_types,
            players: $players,
            sets: $sets
        );
    }

    public static function create_fixture_update_request(
        int $fixture_id = 123,
        ?array $sets = [],
        ?string $match_status = 'share',
        ?string $confirmed = 'Y'
    ): Fixture_Result_Update_Request {
        return new Fixture_Result_Update_Request(
            fixture_id: $fixture_id,
            sets: $sets,
            match_status: $match_status,
            confirmed: $confirmed
        );
    }

    public static function create_team_confirmation_request(
        int $match_id = 123,
        ?string $result_confirm = null,
        ?string $confirm_comments = null,
        ?bool $result_home = null,
        ?bool $result_away = null
    ): Team_Result_Confirmation_Request {
        return new Team_Result_Confirmation_Request(
            match_id: $match_id,
            result_confirm: $result_confirm,
            confirm_comments: $confirm_comments,
            result_home: $result_home,
            result_away: $result_away
        );
    }

    public static function create_rubber_update_result(
        int $rubber_id = 10,
        float $home_points = 2.0,
        float $away_points = 0.0,
        ?int $winner_id = 100,
        array $players = [],
        array $sets = [],
        ?int $status = 0,
        array $custom = [],
        array $stats = [ 'sets' => [ 'home' => 2, 'away' => 0 ], 'games' => [ 'home' => 12, 'away' => 0 ] ]
    ): Rubber_Update_Result {
        return new Rubber_Update_Result(
            rubber_id: $rubber_id,
            home_points: $home_points,
            away_points: $away_points,
            winner_id: $winner_id,
            players: $players,
            sets: $sets,
            status: $status,
            custom: $custom,
            stats: $stats
        );
    }
}
