<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\DTO\Competition\League_Hydration_DTO;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Standings\Standings_Service;

/**
 * Factory for creating League entities.
 */
final class League_Factory {
	/**
	 * Create a League entity from a hydration DTO.
	 *
	 * Missing events return null so callers (including League::get_instance) can
	 * preserve the original false/null behaviour instead of fatalling.
	 *
	 * @param League_Hydration_DTO $dto
	 * @param mixed                $event Optional event; when omitted, resolved from $dto->event_id.
	 * @param League_Repository_Interface|null $league_repository
	 * @param Fixture_Repository_Interface|null $fixture_repository
	 * @param Standings_Service|null $standings_service
	 * @param League_Schedule_Service|null $league_schedule_service
	 * @param Competition_Notification_Service|null $competition_notification_service
	 * @param Fixture_Service|null $fixture_service
	 * @param Fixture_Result_Manager|null $fixture_result_manager
	 * @param Notification_Service|null $notification_service
	 * @return League|null
	 */
	public static function create_from_dto(
		League_Hydration_DTO $dto,
		mixed $event = null,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Repository_Interface $fixture_repository = null,
		?Standings_Service $standings_service = null,
		?League_Schedule_Service $league_schedule_service = null,
		?Competition_Notification_Service $competition_notification_service = null,
		?Fixture_Service $fixture_service = null,
		?Fixture_Result_Manager $fixture_result_manager = null,
		?Notification_Service $notification_service = null
	): ?League {
		$std_object = $dto->to_std_class();

		if ( null === $event ) {
			if ( empty( $dto->event_id ) ) {
				return null;
			}
			$event = \Racketmanager\get_event( $dto->event_id );
		}
		if ( empty( $event ) ) {
			return null;
		}

		if ( ! empty( $event->competition->sport ) ) {
			$sport_class = 'Racketmanager\\sports\\League_' . ucfirst( $event->competition->sport );
			if ( class_exists( $sport_class ) ) {
				return new $sport_class(
					$std_object,
					$league_repository,
					$fixture_repository,
					$standings_service,
					$league_schedule_service,
					$competition_notification_service,
					$fixture_service,
					$fixture_result_manager,
					$notification_service
				);
			}
		}

		return new League(
			$std_object,
			$league_repository,
			$fixture_repository,
			$standings_service,
			$league_schedule_service,
			$competition_notification_service,
			$fixture_service,
			$fixture_result_manager,
			$notification_service
		);
	}

	/**
	 * Create a League entity from a generic object (e.g. database row).
	 *
	 * @param object $row
	 * @param mixed  $event Optional event; when omitted, resolved from the row.
	 * @param League_Repository_Interface|null $league_repository
	 * @param Fixture_Repository_Interface|null $fixture_repository
	 * @param Standings_Service|null $standings_service
	 * @param League_Schedule_Service|null $league_schedule_service
	 * @param Competition_Notification_Service|null $competition_notification_service
	 * @param Fixture_Service|null $fixture_service
	 * @param Fixture_Result_Manager|null $fixture_result_manager
	 * @param Notification_Service|null $notification_service
	 * @return League|null
	 */
	public static function from_object(
		object $row,
		mixed $event = null,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Repository_Interface $fixture_repository = null,
		?Standings_Service $standings_service = null,
		?League_Schedule_Service $league_schedule_service = null,
		?Competition_Notification_Service $competition_notification_service = null,
		?Fixture_Service $fixture_service = null,
		?Fixture_Result_Manager $fixture_result_manager = null,
		?Notification_Service $notification_service = null
	): ?League {
		$dto = League_Hydration_DTO::from_object( $row );
		return self::create_from_dto(
			$dto,
			$event,
			$league_repository,
			$fixture_repository,
			$standings_service,
			$league_schedule_service,
			$competition_notification_service,
			$fixture_service,
			$fixture_result_manager,
			$notification_service
		);
	}

	/**
	 * Create a League entity from an associative array.
	 *
	 * @param array $data
	 * @param mixed $event Optional event; when omitted, resolved from the data.
	 * @param League_Repository_Interface|null $league_repository
	 * @param Fixture_Repository_Interface|null $fixture_repository
	 * @param Standings_Service|null $standings_service
	 * @param League_Schedule_Service|null $league_schedule_service
	 * @param Competition_Notification_Service|null $competition_notification_service
	 * @param Fixture_Service|null $fixture_service
	 * @param Fixture_Result_Manager|null $fixture_result_manager
	 * @param Notification_Service|null $notification_service
	 * @return League|null
	 */
	public static function from_array(
		array $data,
		mixed $event = null,
		?League_Repository_Interface $league_repository = null,
		?Fixture_Repository_Interface $fixture_repository = null,
		?Standings_Service $standings_service = null,
		?League_Schedule_Service $league_schedule_service = null,
		?Competition_Notification_Service $competition_notification_service = null,
		?Fixture_Service $fixture_service = null,
		?Fixture_Result_Manager $fixture_result_manager = null,
		?Notification_Service $notification_service = null
	): ?League {
		$dto = League_Hydration_DTO::from_array( $data );
		return self::create_from_dto(
			$dto,
			$event,
			$league_repository,
			$fixture_repository,
			$standings_service,
			$league_schedule_service,
			$competition_notification_service,
			$fixture_service,
			$fixture_result_manager,
			$notification_service
		);
	}
}
