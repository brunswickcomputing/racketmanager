<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Services\Competition\Knockout_Progression_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Result\Result_Reporting_Service;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Services\Validator\Score_Validation_Service;

/**
 * Provides access to various services in the system.
 */
class Service_Provider {
    private ?Result_Service $result_service;
    private ?Knockout_Progression_Service $progression_service;
    private ?League_Service $league_service;
    private ?Score_Validation_Service $score_validator;
    private ?Player_Validation_Service $player_validator;
    private ?Rubber_Result_Manager $rubber_manager = null;
    private ?Notification_Service $notification_service;
    private ?Registration_Service $registration_service;
    private ?Settings_Service $settings_service = null;
    private ?Team_Service $team_service = null;
    private ?Competition_Service $competition_service = null;
    private ?Result_Reporting_Service $result_reporting_service = null;
    private ?Fixture_Permission_Service $fixture_permission_service = null;
    private ?Fixture_Detail_Service $fixture_detail_service = null;
    private ?Fixture_Maintenance_Service $fixture_maintenance_service = null;
    private ?Fixture_Lifecycle_Service $fixture_lifecycle_service = null;

    public function __construct(
        ?Result_Service $result_service = null, ?Knockout_Progression_Service $progression_service = null, ?League_Service $league_service = null, ?Score_Validation_Service $score_validator = null, ?Player_Validation_Service $player_validator = null, ?Notification_Service $notification_service = null, ?Registration_Service $registration_service = null
    ) {
        $this->result_service       = $result_service;
        $this->progression_service  = $progression_service;
        $this->league_service       = $league_service;
        $this->score_validator      = $score_validator;
        $this->player_validator     = $player_validator;
        $this->notification_service = $notification_service;
        $this->registration_service = $registration_service;
    }

    public function get_result_reporting_service(): ?Result_Reporting_Service {
        return $this->result_reporting_service;
    }

    public function set_result_reporting_service( ?Result_Reporting_Service $result_reporting_service ): void {
        $this->result_reporting_service = $result_reporting_service;
    }

    public function get_result_service(): ?Result_Service {
        return $this->result_service;
    }

    public function get_progression_service(): ?Knockout_Progression_Service {
        return $this->progression_service;
    }

    public function get_league_service(): ?League_Service {
        return $this->league_service;
    }

    public function get_score_validator(): ?Score_Validation_Service {
        return $this->score_validator;
    }

    public function get_player_validator(): ?Player_Validation_Service {
        return $this->player_validator;
    }

    public function set_player_validator( ?Player_Validation_Service $player_validator ): void {
        $this->player_validator = $player_validator;
    }

    public function get_rubber_manager(): ?Rubber_Result_Manager {
        return $this->rubber_manager;
    }

    public function set_rubber_manager( ?Rubber_Result_Manager $rubber_manager ): void {
        $this->rubber_manager = $rubber_manager;
    }

    public function get_notification_service(): ?Notification_Service {
        return $this->notification_service;
    }

    public function get_registration_service(): ?Registration_Service {
        return $this->registration_service;
    }

    public function get_settings_service(): ?Settings_Service {
        return $this->settings_service;
    }

    public function set_settings_service( ?Settings_Service $settings_service ): void {
        $this->settings_service = $settings_service;
    }

    public function get_team_service(): ?Team_Service {
        return $this->team_service;
    }

    public function set_team_service( ?Team_Service $team_service ): void {
        $this->team_service = $team_service;
    }

    public function get_competition_service(): ?Competition_Service {
        return $this->competition_service;
    }

    public function set_competition_service( ?Competition_Service $competition_service ): void {
        $this->competition_service = $competition_service;
    }

    public function get_fixture_permission_service(): ?Fixture_Permission_Service {
        return $this->fixture_permission_service;
    }

    public function set_fixture_permission_service( ?Fixture_Permission_Service $fixture_permission_service ): void {
        $this->fixture_permission_service = $fixture_permission_service;
    }

    public function get_fixture_detail_service(): ?Fixture_Detail_Service {
        return $this->fixture_detail_service;
    }

    public function set_fixture_detail_service( ?Fixture_Detail_Service $fixture_detail_service ): void {
        $this->fixture_detail_service = $fixture_detail_service;
    }

    public function get_fixture_maintenance_service(): ?Fixture_Maintenance_Service {
        return $this->fixture_maintenance_service;
    }

    public function set_fixture_maintenance_service( ?Fixture_Maintenance_Service $fixture_maintenance_service ): void {
        $this->fixture_maintenance_service = $fixture_maintenance_service;
    }

    public function get_fixture_lifecycle_service(): ?Fixture_Lifecycle_Service {
        return $this->fixture_lifecycle_service;
    }

    public function set_fixture_lifecycle_service( ?Fixture_Lifecycle_Service $fixture_lifecycle_service ): void {
        $this->fixture_lifecycle_service = $fixture_lifecycle_service;
    }
}
