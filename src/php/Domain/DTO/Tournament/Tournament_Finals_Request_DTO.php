<?php
/**
 * Tournament_Finals_Request_DTO API: Tournament_Finals_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

/**
 * Class to implement the Tournament Finals Request Data Transfer Object
 */
readonly class Tournament_Finals_Request_DTO {

    public int $tournament_id;
    public string $start_time;
    /** @var array<int, Court_Schedule_DTO> Keyed by Court Index */
    public array $court_schedules;

    public function __construct( array $post_data ) {
        $this->tournament_id = (int) ( $post_data['tournamentId'] ?? 0 );
        $this->start_time    = sanitize_text_field( $post_data['courtStartTime'] ?? '10:00:00' );
        $this->court_schedules = self::map_courts($post_data);
    }

    private static function map_courts(array $data ): array {
        $schedules = [];
        $courts    = $data['court'] ?? [];

        foreach ($courts as $idx => $name) {
            $court_idx = (int) $idx;
            $schedules[$court_idx] = new Court_Schedule_DTO(
                sanitize_text_field($name),
                sanitize_text_field($data['courtStartTime'][$court_idx] ?? null),
                self::map_slots($data, $court_idx)
            );
        }
        return $schedules;
    }

    private static function map_slots(array $data, int $court_idx): array {
        $slots  = [];
        $fixtures = $data['match'][$court_idx] ?? [];

        foreach ($fixtures as $slot_idx => $fixture_id) {
            if ((int) $fixture_id > 0) {
                $slots[(int) $slot_idx] = new Scheduled_Fixture_DTO(
                    (int) $fixture_id,
                    (int) ($data['matchtime'][$court_idx][$slot_idx] ?? 0)
                );
            }
        }
        return $slots;
    }

}
