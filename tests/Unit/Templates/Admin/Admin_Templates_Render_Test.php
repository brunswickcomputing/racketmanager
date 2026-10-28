<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Finances_Charge_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Charges_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoice_Page_View_Model;
use Racketmanager\Admin\View_Models\Finances_Invoices_Page_View_Model;
use Racketmanager\Admin\View_Models\Options_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Errors_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Page_View_Model;
use Racketmanager\Admin\View_Models\Player_Requests_Page_View_Model;
use Racketmanager\Admin\View_Models\Players_Page_View_Model;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Templates_Render_Test extends TestCase {
    use Creates_View_Renderer;
    use Manages_Wordpress_Globals;

    private View_Renderer_Interface $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->renderer = $this->create_view_renderer();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
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
            public function get_events( array $args = array(), ?\Racketmanager\Repositories\Interfaces\Competition_Repository_Interface $competition_repository = null ): array {
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

        $_POST = array( 'competition_title' => 'HACKED-TITLE', 'max_teams' => '99', 'grade' => 'ZZ' );
        $html = $this->renderer->render_to_string( 'admin/includes/competition-config', $vm );

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
        $invoice = new \Racketmanager\Domain\Finance\Invoice();
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

        $presenter = new Invoice_Presenter();
        $vm = $presenter->present_admin_invoice_page(
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

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_render_tournament_entries_and_player_list_with_view_model_and_player_entry_urls(): void {
        $tournament = (object) array(
            'id'   => 10,
            'name' => 'Spring Championship',
        );

        $confirmed_entries = array(
            'D' => array(
                (object) array(
                    'id'           => 101,
                    'display_name' => 'John Doe',
                ),
            ),
        );

        $pending_entries = array(
            'S' => array(
                (object) array(
                    'id'           => 102,
                    'display_name' => 'Jane Smith',
                ),
            ),
        );

        $player_entry_urls = array(
            'John Doe'   => '/sentinel/custom-player-john-url/',
            'Jane Smith' => '/sentinel/custom-player-jane-url/',
        );

        $vm = new Tournament_Overview_Page_View_Model(
            tournament: $tournament,
            overview: (object) array(),
            events: array(),
            tab: 'overview',
            confirmed_entries: $confirmed_entries,
            unpaid_entries: array(),
            pending_entries: $pending_entries,
            withdrawn_entries: array(),
            player_entry_urls: $player_entry_urls,
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/tournament/entries', $vm, array( 'renderer' => $this->renderer ) ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'John Doe', $html );
        self::assertStringContainsString( '/sentinel/custom-player-john-url/', $html );
        self::assertStringContainsString( 'Jane Smith', $html );
        self::assertStringContainsString( '/sentinel/custom-player-jane-url/', $html );
    }

    public function test_render_tournament_entries_and_player_list_without_entry_urls_renders_plain_text(): void {
        $tournament = (object) array(
            'id'   => 10,
            'name' => 'Spring Championship',
        );

        $confirmed_entries = array(
            'D' => array(
                (object) array(
                    'id'           => 101,
                    'display_name' => 'Unlinked Player',
                ),
            ),
        );

        $vm = new Tournament_Overview_Page_View_Model(
            tournament: $tournament,
            overview: (object) array(),
            events: array(),
            tab: 'overview',
            confirmed_entries: $confirmed_entries,
            unpaid_entries: array(),
            pending_entries: array(),
            withdrawn_entries: array(),
            player_entry_urls: array(),
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/tournament/entries', $vm, array( 'renderer' => $this->renderer ) ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Unlinked Player', $html );
        self::assertStringNotContainsString( '<a href="', $html );
    }

    public function test_render_cup_entries_template_with_entry_urls(): void {
        $competition = (object) array(
            'id'   => 5,
            'name' => 'Summer Cup',
        );
        $cup_season = (object) array(
            'entries' => array(
                (object) array(
                    'id'        => 1,
                    'shortcode' => 'WTC',
                ),
                (object) array(
                    'id'        => 2,
                    'shortcode' => 'LTC',
                ),
            ),
        );
        $entry_urls = array(
            'WTC' => '/sentinel/custom-cup-wtc-url/',
            'LTC' => '/sentinel/custom-cup-ltc-url/',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/cup/entries',
                array(
                    'competition' => $competition,
                    'season'      => '2026',
                    'cup_season'  => $cup_season,
                    'entry_urls'  => $entry_urls,
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'WTC', $html );
        self::assertStringContainsString( '/sentinel/custom-cup-wtc-url/', $html );
        self::assertStringContainsString( 'LTC', $html );
        self::assertStringContainsString( '/sentinel/custom-cup-ltc-url/', $html );
    }

    public function test_render_cup_entries_template_without_entry_urls_renders_plain_text(): void {
        $competition = (object) array(
            'id'   => 5,
            'name' => 'Summer Cup',
        );
        $cup_season = (object) array(
            'entries' => array(
                (object) array(
                    'id'        => 1,
                    'shortcode' => 'WTC',
                ),
            ),
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/cup/entries',
                array(
                    'competition' => $competition,
                    'season'      => '2026',
                    'cup_season'  => $cup_season,
                    'entry_urls'  => array(),
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'WTC', $html );
        self::assertStringNotContainsString( '<a href="', $html );
    }

    public function test_render_shared_admin_entries_template_with_entry_urls(): void {
        $competition = (object) array(
            'id'   => 8,
            'name' => 'Mixed League',
        );
        $current_season = (object) array(
            'entries' => array(
                (object) array(
                    'id'         => 1,
                    'shortcode'  => 'WTC',
                    'team_count' => 3,
                ),
            ),
        );
        $entry_urls = array(
            'WTC' => '/sentinel/custom-league-wtc-url/',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/includes/entries',
                array(
                    'competition'    => $competition,
                    'season'         => '2026',
                    'current_season' => $current_season,
                    'entry_urls'     => $entry_urls,
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'WTC', $html );
        self::assertStringContainsString( '/sentinel/custom-league-wtc-url/', $html );
        self::assertStringContainsString( '3', $html );
    }

    public function test_render_shared_admin_entries_template_without_entry_urls_renders_plain_text(): void {
        $competition = (object) array(
            'id'   => 8,
            'name' => 'Mixed League',
        );
        $current_season = (object) array(
            'entries' => array(
                (object) array(
                    'id'         => 1,
                    'shortcode'  => 'WTC',
                    'team_count' => 3,
                ),
            ),
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/includes/entries',
                array(
                    'competition'    => $competition,
                    'season'         => '2026',
                    'current_season' => $current_season,
                    'entry_urls'     => array(),
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'WTC', $html );
        self::assertStringNotContainsString( '<a href="', $html );
    }

    public function test_render_players_nav_tabs_template_with_tab_title(): void {
        $vm = new Player_Errors_Page_View_Model(
            player_errors: array(),
            status: 'unconfirmed',
            racketmanager_tab: 'errors',
            tab_title: 'Errors',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/players/nav-tabs', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( '<h1>Errors</h1>', $html );
        self::assertStringContainsString( 'RacketManager Players', $html );
        self::assertStringContainsString( '&raquo; Errors', $html );
    }

    public function test_render_finances_nav_tabs_template_with_tab_title(): void {
        $vm = new Finances_Charges_Page_View_Model(
            charges: array(),
            competitions: array(),
            seasons: array(),
            total_amount_formatted: '£0.00',
            racketmanager_tab: 'charges',
            tab_title: 'Charges',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/finances/nav-tabs', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( '<h1>Charges</h1>', $html );
        self::assertStringContainsString( 'RacketManager Finances', $html );
        self::assertStringContainsString( '&raquo; Charges', $html );

        $invoices_vm = new Finances_Invoices_Page_View_Model(
            invoices: array(),
            charges: array(),
            billable_type: 'Player',
            total_amount_formatted: '£0.00',
            racketmanager_tab: 'player-invoices',
            tab_title: 'Player Invoices',
        );

        $invoices_html = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/finances/nav-tabs', $invoices_vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( '<h1>Player Invoices</h1>', $invoices_html );
        self::assertStringContainsString( '&raquo; Player Invoices', $invoices_html );
    }

    public function test_render_admin_show_settings_without_globals(): void {
        global $racketmanager;
        $racketmanager = (object) array(
            'options' => array(
                'checks' => array(
                    'rosterLeadTime' => '999',
                    'playedRounds'   => '888',
                ),
            ),
        );

        $vm = new Options_Page_View_Model(
            options: array(
                'checks' => array(
                    'rosterLeadTime' => '48',
                    'playedRounds'   => '5',
                ),
            ),
            tab: 'players',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/show-settings', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Racketmanager Global Settings', $html );
        self::assertStringContainsString( "value='48'", $html );
        self::assertStringContainsString( "value='5'", $html );
        self::assertStringNotContainsString( "value='999'", $html );
        self::assertStringNotContainsString( "value='888'", $html );
    }

    public function test_render_admin_cup_events_template_with_leagues_and_zero_warnings(): void {
        $competition = (object) array(
            'id' => 12,
        );

        $league = (object) array(
            'id'               => 101,
            'title'            => 'Men Championship Draw',
            'num_teams_total'  => 16,
        );

        $event = (object) array(
            'leagues' => array( $league ),
        );

        $competition_events = array( $event );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/cup/events',
                array(
                    'competition'        => $competition,
                    'season'             => '2026',
                    'competition_events' => $competition_events,
                    'draw_sizes'         => array( 101 => '8' ),
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'href="/wp-admin/admin.php?page=racketmanager-cups&view=draw&competition_id=12&league=101&season=2026"', $html );
        self::assertStringContainsString( 'Men Championship Draw', $html );
        self::assertMatchesRegularExpression( '#<td>\s*16\s*</td>#', $html );
        self::assertMatchesRegularExpression( '#<td>\s*8\s*</td>#', $html );
    }

    public function test_render_admin_show_clubs_template_with_clubs_and_zero_warnings(): void {
        $club1 = (object) array(
            'id'              => 42,
            'name'            => 'Leigh LTC',
            'match_secretary' => (object) array(
                'display_name' => 'John Doe',
            ),
        );

        $club2 = (object) array(
            'id'              => 43,
            'name'            => 'Westcliff LTC',
            'match_secretary' => null,
        );

        $clubs = array( $club1, $club2 );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/show-clubs',
                new \Racketmanager\Admin\View_Models\Clubs_Page_View_Model( $clubs )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'value="42"', $html );
        self::assertStringContainsString( 'href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=club&amp;club_id=42 "', $html );
        self::assertStringContainsString( 'Leigh LTC', $html );
        self::assertStringContainsString( 'John Doe', $html );
        self::assertStringContainsString( 'Westcliff LTC', $html );
    }

    public function test_render_admin_event_leagues_template_with_precomputed_matches(): void {
        $event = (object) array(
            'id'              => 10,
            'is_championship' => false,
            'competition'     => (object) array(
                'type'      => 'league',
                'is_league' => true,
            ),
        );

        $league1 = (object) array(
            'id'                 => 101,
            'title'              => 'Division 1',
            'is_championship'    => false,
            'num_teams_total'    => 8,
            'num_matches_total'  => 28,
        );

        $league2 = (object) array(
            'id'                 => 102,
            'title'              => 'Championship Draw',
            'is_championship'    => true,
            'num_teams_total'    => 16,
            'championship'       => (object) array(
                'num_teams_first_round' => 16,
            ),
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/event/leagues',
                array(
                    'event'              => $event,
                    'season'             => '2026',
                    'league_id'          => 0,
                    'event_id'           => 10,
                    'league_edit'        => null,
                    'league_title'       => '',
                    'leagues'            => array( $league1, $league2 ),
                    'league_matches_map' => array( 101 => 28 ),
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Division 1', $html );
        self::assertStringContainsString( 'Championship Draw', $html );
        self::assertStringContainsString( '28', $html );
        self::assertStringContainsString( '16', $html );
    }

    public function test_render_admin_league_show_constitution_template_with_precomputed_data(): void {
        $event = (object) array(
            'id'              => 10,
            'name'            => 'Mixed League Event',
            'is_box'          => false,
            'leagues'         => array( (object) array( 'id' => 101, 'title' => 'Division 1' ) ),
            'current_season'  => array( 'name' => '2026' ),
            'competition'     => (object) array(
                'id'          => 1,
                'name'        => 'Mixed League',
                'type'        => 'league',
            ),
        );

        $league = (object) array(
            'id'    => 101,
            'title' => 'Division 1',
        );

        $team = (object) array(
            'table_id'         => 1,
            'old_league_title' => 'Division 1',
            'old_league_id'    => 101,
            'league_id'        => 101,
            'title'            => 'Leigh 1',
            'team_id'          => 501,
            'status'           => 'champion',
            'old_rank'         => 1,
            'rank'             => 1,
            'points_plus'      => 20,
            'add_points'       => 0,
            'rating'           => '10.5',
            'profile'          => '1',
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string(
                'admin/league/show-constitution',
                new \Racketmanager\Admin\View_Models\League_Constitution_Page_View_Model(
                    event: $event,
                    season: '2026',
                    seasons: array( '2026' ),
                    leagues: array( $league ),
                    event_seasons: array( array( 'name' => '2026' ) ),
                    num_teams: 1,
                    league_active_teams: array( 101 => 6 ),
                    teams: array( $team ),
                    constitution_action: 'update',
                    constitution_exists: true,
                    updates_allowed: true,
                    latest_season: '2026',
                    latest_event_season: '2026',
                    standing_status: array( 'champion' => 'Champion' ),
                )
            ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Constitution - Mixed League Event 2026', $html );
        self::assertStringContainsString( 'Division 1', $html );
        self::assertStringContainsString( 'Leigh 1', $html );
    }

    public function test_render_admin_event_config_template_with_precomputed_leagues(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id              = 1;
                $this->name            = 'Mixed League';
                $this->type            = Competition_Type::LEAGUE;
                $this->is_league       = true;
                $this->is_tournament   = false;
                $this->is_championship = true;
                $this->age_group       = 'open';
                $this->settings        = new Competition_Settings( array( 'mode' => 'league' ) );
            }
        };

        $event = (object) array(
            'id'       => 10,
            'name'     => 'Mixed Doubles',
            'settings' => array(),
            'config'   => (object) array( 'primary_league' => 101 ),
        );

        $league = (object) array(
            'id'    => 101,
            'title' => 'Division 1',
        );

        $vm = new Tournament_Event_Config_Page_View_Model(
            competition: $competition,
            event: $event,
            tournament: null,
            new_event: false,
            errors: new Error_Bag(),
            tab: 'championship',
            leagues: array( $league ),
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/event-config', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Division 1', $html );
        self::assertStringContainsString( 'value="101"', $html );
    }

    public function test_render_teams_list_template_with_view_model(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id   = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
            }
            public function get_type(): string {
                return 'league';
            }
        };

        $event = (object) array(
            'id'          => 10,
            'name'        => 'Men Singles',
            'competition' => $competition,
        );

        $league = (object) array(
            'id'       => 101,
            'title'    => 'Division 1',
            'event_id' => 10,
            'event'    => $event,
        );

        $team = (object) array(
            'id'      => 501,
            'title'   => 'Leigh 1',
            'stadium' => 'Court 1',
            'club'    => (object) array( 'shortcode' => 'LTC' ),
        );

        $vm = new Teams_List_Page_View_Model(
            league: $league,
            league_id: 101,
            season: '2026',
            tournament_id: null,
            tournament: null,
            teams: array( $team ),
            type: null,
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/teams-list', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Division 1 - Add Teams to League', $html );
        self::assertStringContainsString( 'Leigh 1', $html );
        self::assertStringContainsString( 'LTC', $html );
        self::assertStringContainsString( 'Court 1', $html );
        self::assertStringContainsString( 'name="team[501]"', $html );
    }

    public function test_render_setup_template_with_view_model(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id   = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
            }
            public function get_type(): string {
                return 'league';
            }
        };

        $vm = new Setup_Page_View_Model(
            competition: $competition,
            season: '2026',
            current_season: array(
                'match_dates'    => array( '2026-05-01', '2026-05-08' ),
                'home_away'      => false,
                'num_match_days' => 2,
            ),
            event: null,
            league: null,
            match_count: 0,
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/setup', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Setup - Premier League - 2026', $html );
        self::assertStringContainsString( 'Set round dates', $html );
        self::assertStringContainsString( 'value="2026-05-01"', $html );
        self::assertStringContainsString( 'value="2026-05-08"', $html );
        self::assertStringContainsString( 'name="competition_id" value="1"', $html );
    }

    public function test_render_event_setup_template_sets_round_dates_without_fixture_action(): void {
        $competition = new class() extends Competition {
            public function __construct() {
                $this->id   = 1;
                $this->name = 'Premier League';
                $this->type = Competition_Type::LEAGUE;
            }
            public function get_type(): string {
                return 'league';
            }
        };
        $event = (object) array( 'id' => 12, 'name' => 'Men Singles', 'competition' => $competition );
        $vm    = new Setup_Page_View_Model(
            competition: $competition,
            season: '2026',
            current_season: array(
                'match_dates'    => array( '2026-05-01' ),
                'home_away'      => false,
                'num_match_days' => 1,
            ),
            event: $event,
            league: null,
            match_count: 0,
        );

        $warnings = array();
        $html     = $this->render_and_capture_warnings(
            fn() => $this->renderer->render_to_string( 'admin/includes/setup', $vm ),
            $warnings
        );

        self::assertEmpty( $warnings );
        self::assertStringContainsString( 'Set round dates', $html );
        self::assertStringContainsString( 'name="event_id" value="12"', $html );
        self::assertStringContainsString( 'name="action" value=""', $html );
        self::assertStringNotContainsString( 'Add fixtures', $html );
        self::assertStringNotContainsString( 'Generate ratings', $html );
        self::assertStringNotContainsString( 'name="rank"', $html );
    }

    public function test_no_inline_event_handlers_in_admin_templates(): void {
        $admin_templates_dir = dirname( __DIR__, 4 ) . '/templates/admin';
        $directory_iterator  = new \RecursiveDirectoryIterator( $admin_templates_dir, \FilesystemIterator::SKIP_DOTS );
        $iterator            = new \RecursiveIteratorIterator( $directory_iterator );
        $regex               = '/\son(click|dblclick|change|input|submit|reset|load|unload|beforeunload|blur|focus|focusin|focusout|keyup|keydown|keypress|mouseover|mouseout|mousedown|mouseup|mousemove|mouseenter|mouseleave|select|scroll|resize|error|abort|toggle|dragstart|dragend|dragover|dragenter|dragleave|drag|drop|touchstart|touchend|touchmove|touchcancel|paste|copy|cut|wheel|contextmenu|invalid|search|hashchange|popstate)\s*=/i';

        $violations = array();
        /** @var \SplFileInfo $file */
        foreach ( $iterator as $file ) {
            if ( 'php' !== $file->getExtension() ) {
                continue;
            }
            $content = (string) file_get_contents( $file->getPathname() );
            if ( preg_match_all( $regex, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
                $relative_path = str_replace( dirname( __DIR__, 4 ) . '/', '', $file->getPathname() );
                foreach ( $matches[0] as $match ) {
                    $violations[] = sprintf( '%s: %s', $relative_path, trim( $match[0] ) );
                }
            }
        }

        self::assertSame( array(), $violations, 'Admin templates must not contain inline event handlers.' );
    }
}
