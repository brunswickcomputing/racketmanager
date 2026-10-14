<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\View\Php_View_Renderer;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Templates_Render_Test extends TestCase {

    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'http://localhost/' );
        }
        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    public function test_render_competition_config_template_with_errors_and_values(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
                $this->is_league = true;
                $this->is_cup = false;
                $this->is_tournament = false;
                $this->is_championship = false;
                $this->settings = new Competition_Settings( array(
                    'mode' => 'league',
                    'sport' => 'tennis',
                    'grade' => 'A',
                    'point_rule' => 'standard',
                    'max_teams' => 12,
                    'scoring' => 'sets',
                    'num_sets' => 3,
                    'num_rubbers' => 4,
                    'start_time' => array(
                        'weekday' => array( 'min' => '18:30', 'max' => '21:00' ),
                        'weekend' => array( 'min' => '10:00', 'max' => '16:00' ),
                    ),
                    'default_match_start_time' => array( 'hour' => 19, 'hours' => 19, 'minutes' => 30 ),
                ) );
                $this->config = (object) $this->get_settings();
                $this->config->type = 'league';
                $this->config->age_group = 'open';
            }
            public function get_events( array $args = array() ): array {
                return array();
            }
        };

        $errors = new Error_Bag( array(
            'name' => 'Competition name is required',
            'grade' => 'Invalid grade selected',
            'scoring' => 'Scoring format required',
        ) );

        $vm = new Tournament_Competition_Config_Page_View_Model(
            competition: $competition,
            tournament: null,
            rules_options: array( 'rule_a' => 1, 'rule_b' => 0 ),
            clubs: array(),
            events: array(),
            tab: 'general',
            errors: $errors,
        );

        $original_post = $_POST;
        $_POST         = array( 'competition_title' => 'HACKED-TITLE', 'max_teams' => '99', 'grade' => 'ZZ' );
        try {
            $html = $this->renderer->render_to_string( 'admin/includes/competition-config', $vm );
        } finally {
            $_POST = $original_post;
        }

        self::assertStringNotContainsString( 'HACKED-TITLE', $html );
        self::assertStringNotContainsString( 'value="99"', $html );
        self::assertStringContainsString( 'Premier League', $html );
        self::assertStringContainsString( 'value="12"', $html );
        self::assertStringContainsString( '18:30', $html );
        self::assertStringContainsString( 'Competition name is required', $html );
        self::assertStringContainsString( 'Invalid grade selected', $html );
        self::assertStringContainsString( 'is-invalid', $html );
        self::assertStringContainsString( 'name="competition_title"', $html );
        self::assertStringContainsString( 'name="updateCompetitionConfig"', $html );
    }

    public function test_render_event_config_template_with_errors_and_values(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
                $this->is_league = true;
                $this->is_cup = false;
                $this->is_tournament = false;
                $this->is_championship = false;
            }
        };

        $event = new stdClass();
        $event->id = 5;
        $event->name = "Men's Singles";
        $event->type = 'MS';
        $event->config = (object) array(
            'age_limit' => 'open',
            'age_offset' => '0',
            'scoring' => 'sets',
            'num_sets' => 3,
            'num_rubbers' => 4,
            'reverse_rubbers' => 0,
            'match_days_allowed' => array( '1' => '1', '3' => '1' ),
            'offset' => 1,
            'primary_league' => null,
        );

        $errors = new Error_Bag( array(
            'name' => 'Event name is required',
            'scoring' => 'Invalid scoring format',
        ) );

        $vm = new Tournament_Event_Config_Page_View_Model(
            competition: $competition,
            event: $event,
            tournament: null,
            new_event: false,
            errors: $errors,
            tab: 'general',
        );

        $html = $this->renderer->render_to_string( 'admin/includes/event-config', $vm );

        self::assertStringContainsString( "Men's Singles", $html );
        self::assertStringContainsString( 'Event name is required', $html );
        self::assertStringContainsString( 'Invalid scoring format', $html );
        self::assertStringContainsString( 'is-invalid', $html );
        self::assertStringContainsString( 'name="event_name"', $html );
        self::assertStringContainsString( 'name="updateEventConfig"', $html );
    }

    public function test_render_season_edit_template_with_errors_and_values(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
                $this->is_league = true;
                $this->is_cup = false;
                $this->is_tournament = false;
                $this->is_championship = false;
            }
            public function get_seasons(): array {
                return array();
            }
        };

        $current_season = (object) array(
            'name' => '2025',
            'date_start' => '2025-05-01',
            'date_end' => '2025-09-30',
            'date_open' => '2025-03-01',
            'date_closing' => '2025-04-15',
            'grade' => 'A',
            'max_teams' => 10,
            'teams_per_club' => 2,
            'teams_prom_relg' => 2,
            'lowest_promotion' => 1,
            'num_match_days' => 10,
            'round_length' => 7,
            'home_away_diff' => 1,
            'filler_weeks' => 1,
            'home_away' => true,
            'fixed_match_dates' => true,
            'fee_competition' => 45.0,
            'fee_event' => 15.0,
            'fee_lead_time' => 14,
        );

        $errors = new Error_Bag( array(
            'date_start' => 'Start date is required',
            'date_end' => 'End date is required',
        ) );

        $vm = new Season_Modify_Page_View_Model(
            competition: $competition,
            season: '2025',
            current_season: $current_season,
            seasons: array(),
            clubs: array(),
            edit_mode: true,
            errors: $errors,
        );

        $html = $this->renderer->render_to_string( 'admin/includes/season-edit', $vm );

        self::assertStringContainsString( 'Modify season for Premier League', $html );
        self::assertStringContainsString( '2025-05-01', $html );
        self::assertStringContainsString( '2025-09-30', $html );
        self::assertStringContainsString( 'Start date is required', $html );
        self::assertStringContainsString( 'End date is required', $html );
        self::assertStringContainsString( 'is-invalid', $html );
    }

    public function test_render_finance_charge_template_with_errors_and_values(): void {
        $charge = (object) array(
            'id'              => 10,
            'competition_id'  => 2,
            'season'          => '2026',
            'status'          => 'draft',
            'date'            => '2026-06-01',
            'fee_competition' => 50,
            'fee_event'       => 20,
        );

        $competition = (object) array( 'id' => 2, 'name' => 'Summer League' );
        $season      = (object) array( 'name' => '2026' );

        $errors = new Error_Bag( array(
            'feeClub' => 'Fee is invalid',
            'date'    => 'Date is required',
        ) );

        $vm = new Finances_Charge_Page_View_Model(
            charge: $charge,
            competitions: array( $competition ),
            seasons: array( $season ),
            club_charges: array(),
            edit: true,
            form_title: 'Edit Charge',
            form_action: 'Update',
            errors: $errors,
        );

        $html = $this->renderer->render_to_string( 'admin/finances/charge', $vm );

        self::assertStringContainsString( 'Edit Charge', $html );
        self::assertStringContainsString( 'value="50"', $html );
        self::assertStringContainsString( 'value="20"', $html );
        self::assertStringContainsString( '2026-06-01', $html );
        self::assertStringContainsString( 'Fee is invalid', $html );
        self::assertStringContainsString( 'Date is required', $html );
        self::assertStringContainsString( 'is-invalid', $html );
    }

    public function test_render_finance_invoice_template_with_errors_and_values(): void {
        $invoice = new \Racketmanager\Domain\Invoice();
        $invoice->id             = 55;
        $invoice->charge_id      = 5;
        $invoice->billable_id    = 12;
        $invoice->billable_type  = 'club';
        $invoice->invoice_number = 5005;
        $invoice->date           = '2026-06-01';
        $invoice->date_due       = '2026-07-01';
        $invoice->status         = 'sent';
        $invoice->amount         = '150';
        $invoice->details        = new stdClass();
        $invoice->set_purchase_order( 'PO-5555' );

        $raw_charge = new stdClass();
        $raw_charge->id = 5;
        $raw_charge->competition_id = 2;
        $raw_charge->season = '2026';
        $raw_charge->status = 'final';
        $raw_charge->date = '2026-05-01';
        $raw_charge->fee_competition = 50;
        $raw_charge->fee_event = 20;
        $raw_charge->competition_name = 'League';
        $raw_charge->competition_type = 'league';
        $charge_dto = new \Racketmanager\Domain\DTO\Finance\Charge_Details_DTO( $raw_charge );

        $invoice_dto = new \Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO( $invoice, 'Test Club', 'Test Address', null, $charge_dto );

        $errors = new Error_Bag( array(
            'status' => 'Status error occurred',
        ) );

        $vm = new Finances_Invoice_Page_View_Model(
            invoice: $invoice_dto,
            invoice_view: '<div class="test-inv-view">INVOICE HTML BODY</div>',
            errors: $errors,
        );

        $html = $this->renderer->render_to_string( 'admin/finances/invoice', $vm );

        self::assertStringContainsString( 'PO-5555', $html );
        self::assertStringContainsString( '5005', $html );
        self::assertStringContainsString( 'Status error occurred', $html );
        self::assertStringContainsString( 'INVOICE HTML BODY', $html );
        self::assertStringContainsString( 'is-invalid', $html );
    }

    public function test_render_player_edit_template_with_errors_and_values(): void {
        $player               = new stdClass();
        $player->id           = 10;
        $player->display_name = 'John Doe';
        $player->firstname    = 'John';
        $player->surname      = 'Doe';
        $player->gender       = 'M';
        $player->btm          = '123456';
        $player->email        = 'john@example.com';
        $player->contactno    = '0123456789';
        $player->year_of_birth= 1990;
        $player->locked       = '';

        $errors = new Error_Bag( array(
            'firstname' => 'First name invalid',
        ) );

        $vm = new Player_Page_View_Model(
            player: $player,
            player_id: 10,
            club: null,
            club_id: null,
            page_referrer: 'admin.php?page=racketmanager-players',
            errors: $errors,
            form_valid: false,
        );

        $html = $this->renderer->render_to_string( 'admin/players/show-player', $vm );

        self::assertStringContainsString( 'Edit Player', $html );
        self::assertStringContainsString( 'John Doe', $html );
        self::assertStringContainsString( 'First name invalid', $html );
        self::assertStringContainsString( 'is-invalid', $html );
        self::assertDoesNotMatchRegularExpression( '/<input[^>]*id="locked"[^>]*checked/', $html );
    }

    public function test_render_players_list_template_with_errors_and_values(): void {
        $player_form            = new stdClass();
        $player_form->firstname = 'Alice';
        $player_form->surname   = '';

        $players = array(
            (object) array(
                'id'              => 1,
                'fullname'        => 'Bob Smith',
                'gender'          => 'M',
                'btm'             => '654321',
                'user_registered' => '2026-01-01 10:00:00',
            ),
        );

        $errors = new Error_Bag( array(
            'surname' => 'Surname required',
        ) );

        $vm = new Players_Page_View_Model(
            players: $players,
            player: $player_form,
            racketmanager_tab: 'players',
            errors: $errors,
            form_valid: false,
        );

        $html = $this->renderer->render_to_string( 'admin/players/show-players', $vm );

        self::assertStringContainsString( 'Bob Smith', $html );
        self::assertStringContainsString( 'Alice', $html );
        self::assertStringContainsString( 'Surname required', $html );
        self::assertStringContainsString( 'is-invalid', $html );
    }

    public function test_render_player_errors_template(): void {
        $player_errors = array(
            (object) array(
                'id'          => 1,
                'player_id'   => 10,
                'player_name' => 'John Doe',
                'btm'         => '123456',
                'message'     => 'No WTN available',
            ),
        );

        $vm = new Player_Errors_Page_View_Model(
            player_errors: $player_errors,
            status: 'no_wtn',
            racketmanager_tab: 'errors',
        );

        $html = $this->renderer->render_to_string( 'admin/players/show-errors', $vm );

        self::assertStringContainsString( 'John Doe', $html );
        self::assertStringContainsString( 'No WTN available', $html );
        self::assertStringContainsString( '123456', $html );
    }

    public function test_render_player_requests_template(): void {
        $player_requests = array(
            (object) array(
                'registration_id'   => 5,
                'user_id'           => 10,
                'club_name'         => 'Westcliff',
                'display_name'      => 'Jane Doe',
                'gender'            => 'F',
                'btm'               => '987654',
                'registered_by_user'=> 'Admin User',
                'registration_date' => '2026-02-01',
                'approved_by_user'  => null,
                'approval_date'     => null,
                'removed_by_user'   => null,
                'removal_date'      => null,
            ),
        );

        $clubs = array(
            (object) array( 'id' => 1, 'shortcode' => 'WTC' ),
        );

        $vm = new Player_Requests_Page_View_Model(
            player_requests: $player_requests,
            clubs: $clubs,
            club_id: 1,
            status: 'outstanding',
            racketmanager_tab: 'requests',
        );

        $html = $this->renderer->render_to_string( 'admin/players/show-requests', $vm );

        self::assertStringContainsString( 'Westcliff', $html );
        self::assertStringContainsString( 'Jane Doe', $html );
        self::assertStringContainsString( '987654', $html );
    }
}
