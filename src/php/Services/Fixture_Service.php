<?php
/**
 * Fixture_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Class to implement the Fixture Management Service
 */
class Fixture_Service {

    private Fixture_Repository $fixture_repository;
    private Rubber_Repository $rubber_repository;
    private ?Notification_Service $notification_service;
    private Fixture_Permission_Service $permission_service;
    private Fixture_Detail_Service $detail_service;

    public function __construct( Repository_Provider $repository_provider, Service_Provider $service_provider ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->rubber_repository    = $repository_provider->get_rubber_repository();
        $this->notification_service = $service_provider->get_notification_service();

        $this->permission_service = $service_provider->get_fixture_permission_service() ?? new Fixture_Permission_Service( $repository_provider, $service_provider );
        $this->detail_service     = $service_provider->get_fixture_detail_service() ?? new Fixture_Detail_Service( $repository_provider, $service_provider );
    }

    /**
     * Create a new fixture and associated rubbers
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return Fixture
     */
    public function create_fixture( Fixture $fixture, object $league ): Fixture {
        $this->fixture_repository->save( $fixture );

        if ( ! empty( $league->num_rubbers ) ) {
            $this->create_associated_rubbers( $fixture, $league );
        }

        // Handle championship leg logic
        if ( ! empty( $league->is_championship ) && ! empty( $league->event->current_season['home_away'] ) && 'final' !== $fixture->get_final() ) {
            $this->handle_championship_legs( $fixture, $league );
        }

        return $fixture;
    }

    /**
     * Create associated rubbers for a fixture
     *
     * @param Fixture $fixture
     * @param object $league
     */
    private function create_associated_rubbers( Fixture $fixture, object $league ): void {
        $max_rubbers = $this->calculate_max_rubbers( $fixture, $league );
        for ( $ix = 1; $ix <= $max_rubbers; $ix ++ ) {
            $rubber_data = new stdClass();
            $type        = $this->determine_rubber_type( $league->type, $ix );

            $rubber_data->type          = $type;
            $rubber_data->rubber_number = $ix;
            $rubber_data->date          = $fixture->get_date();
            $rubber_data->match_id      = $fixture->get_id();
            new Rubber( $rubber_data );
        }
    }

    /**
     * Determine the rubber type based on league type and index
     *
     * @param string $league_type
     * @param int $index
     * @return string
     */
    private function determine_rubber_type( string $league_type, int $index ): string {
        if ( 'LD' === $league_type ) {
            return match ( $index ) {
                1 => 'WD',
                2 => 'MD',
                3 => 'XD',
                default => 'LD',
            };
        }

        return $league_type;
    }

    /**
     * Handle championship leg logic for a fixture
     *
     * @param Fixture $fixture
     * @param object $league
     */
    private function handle_championship_legs( Fixture $fixture, object $league ): void {
        if ( ! empty( $fixture->get_leg() ) ) {
            // Leg is already set, don't create more legs recursively
            return;
        }

        $competition_season = $league->event->competition->get_season_by_name( $fixture->get_season() );
        $fixture->set_leg( 1 );
        $this->fixture_repository->save( $fixture );

        $new_fixture = clone $fixture;
        $weeks_diff  = empty( $competition_season['home_away_diff'] ) ? 2 : $competition_season['home_away_diff'];
        $new_fixture->set_date( Util::amend_date( $fixture->get_date(), $weeks_diff, '+', 'weeks' ) );
        $new_fixture->set_linked_match( $fixture->get_id() );
        $new_fixture->set_leg( 2 ); // Explicitly setting it to 2

        if ( ! empty( $fixture->get_host() ) ) {
            $new_fixture->set_host( 'home' === $fixture->get_host() ? 'away' : 'home' );
        }

        $new_fixture->set_id( null );
        $this->create_fixture( $new_fixture, $league );

        $fixture->set_linked_match( $new_fixture->get_id() );
        $this->fixture_repository->save( $fixture );
    }

    /**
     * Calculate maximum rubbers for a fixture
     *
     * @param Fixture $fixture
     * @param object $league
     *
     * @return int
     */
    private function calculate_max_rubbers( Fixture $fixture, object $league ): int {
        $max_rubbers = (int) $league->num_rubbers;
        if ( ! empty( $league->is_championship ) && ! empty( $league->current_season['home_away'] ) && ! empty( $fixture->get_leg() ) && 2 === $fixture->get_leg() && 'MPL' === $league->event->scoring ) {
            ++ $max_rubbers;
        } elseif ( '1' === $league->event->reverse_rubbers ) {
            $max_rubbers *= 2;
        }

        return $max_rubbers;
    }

    /**
     * Update an existing fixture
     *
     * @param Fixture $fixture
     *
     * @return void
     */
    public function update_fixture( Fixture $fixture ): void {
        $this->fixture_repository->save( $fixture );
    }

    /**
     * Update fixture location
     *
     * @param Fixture $fixture
     * @param string $location
     */
    public function update_fixture_location( Fixture $fixture, string $location ): void {
        $fixture->set_location( $location );
        $this->fixture_repository->save( $fixture );
    }

    /**
     * Set the fixture date based on start date, day, and time
     *
     * @param Fixture $fixture
     * @param string $start_date
     * @param string|null $match_day
     * @param string|null $match_time
     */
    public function set_fixture_date( Fixture $fixture, string $start_date, ?string $match_day, ?string $match_time ): void {
        if ( strlen( $start_date ) > 10 ) {
            $start_date = substr( $start_date, 0, 10 );
        }
        if ( ! empty( $match_day ) ) {
            $day        = Util_Lookup::get_match_day_number( $match_day );
            $match_date = Util::amend_date( $start_date, $day );
        } else {
            $match_date = $start_date;
        }
        if ( empty( $match_time ) ) {
            $match_time = '00:00';
        }
        $match_date = $match_date . ' ' . $match_time;
        $this->update_fixture_date( $fixture, $match_date );
    }

    /**
     * Update fixture date and synchronize associated rubbers
     *
     * @param Fixture $fixture
     * @param string $new_date
     * @param string|null $original_date
     */
    public function update_fixture_date( Fixture $fixture, string $new_date, ?string $original_date = null ): void {
        if ( empty( $new_date ) ) {
            return;
        }

        $fixture->set_date( $new_date );

        if ( ! empty( $original_date ) && empty( $fixture->get_date_original() ) ) {
            $fixture->set_date_original( $original_date );
        }

        $this->fixture_repository->save( $fixture );

        // Synchronize rubbers
        $rubbers = $this->rubber_repository->find_by_fixture_id( $fixture->get_id() );
        foreach ( $rubbers as $rubber ) {
            $rubber->set_date( $new_date );
            $this->rubber_repository->save( $rubber );
        }

        // Notify if date was changed from an original date
        if ( ! empty( $fixture->get_date_original() ) && $this->notification_service ) {
            $this->notification_service->send_date_change_notification( $fixture );
        }
    }

    /**
     * Reset the final's fixtures for a tournament
     *
     * @param int $tournament_id
     * @param string $fixture_date
     *
     * @return bool
     */
    public function reset_finals_fixtures_for_tournament( int $tournament_id, string $fixture_date ): bool {
        $updates = false;
        $finals  = $this->fixture_repository->find_finals_fixtures_for_tournament( $tournament_id );
        foreach ( $finals as $fixture ) {
            $fixture_changed = $fixture->reset_finals_data( $fixture_date );
            if ( $fixture_changed ) {
                $saved = $this->fixture_repository->save( $fixture );
                if ( $saved ) {
                    $updates = true;
                }
            }
        }

        return $updates;
    }

    /**
     * Update fixture finals data
     *
     * @param int $fixture_id
     * @param string $new_date
     * @param string $court_name
     *
     * @return bool
     */
    public function update_fixture_finals_data( int $fixture_id, string $new_date, string $court_name ): bool {
        $fixture = $this->get_fixture( $fixture_id );
        if ( ! $fixture ) {
            return false;
        }

        $fixture_changed = $fixture->set_finals_data( $new_date, $court_name );
        if ( $fixture_changed ) {
            return $this->fixture_repository->save( $fixture );
        }

        return false;
    }

    /**
     * Get a fixture by ID
     *
     * @param int $fixture_id
     *
     * @return Fixture|null
     */
    public function get_fixture( int $fixture_id ): ?Fixture {
        return $this->fixture_repository->find_by_id( $fixture_id );
    }

    /**
     * Find a final's fixtures for a tournament
     *
     * @param int $tournament_id
     *
     * @return Fixture[]
     */
    public function get_finals_fixtures_for_tournament( int $tournament_id ): array {
        return $this->fixture_repository->find_finals_fixtures_for_tournament( $tournament_id );
    }

    public function delete_fixtures_for_season( int $league_id, string $season ): void {
        $this->fixture_repository->delete_by_league_and_season( $league_id, $season );
    }

    /**
     * @param int|null $player_id
     * @param int|null $tournament_id
     *
     * @return Fixture_Details_DTO[]
     */
    public function get_fixtures_for_player_for_tournament( ?int $player_id, ?int $tournament_id ): array {
        return $this->detail_service->get_fixtures_for_player_for_tournament( $player_id, $tournament_id );
    }

    public function get_tournament_fixture_with_details( int|Fixture|null $fixture_id ): ?Fixture_Details_DTO {
        return $this->detail_service->get_tournament_fixture_with_details( $fixture_id );
    }

    /**
     * Check whether match update allowed
     *
     * @param int|Fixture $fixture_id
     *
     * @return object
     */
    public function is_update_allowed( int|Fixture $fixture_id ): object {
        return $this->permission_service->is_update_allowed( $fixture_id );
    }
}
