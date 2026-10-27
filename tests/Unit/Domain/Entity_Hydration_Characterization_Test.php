<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Factory;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Club\Club_Player_Factory;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Communication\Message_Factory;
use Racketmanager\Domain\Communication\Results_Report;
use Racketmanager\Domain\Communication\Results_Report_Factory;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Competition\League_Team_Factory;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Finance\Charge_Factory;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Finance\Invoice_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Fixture\Rubber_Factory;
use Racketmanager\Domain\Player\Player_Error;
use Racketmanager\Domain\Player\Player_Error_Factory;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Player\User_Factory;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Domain\Result\Results_Checker_Factory;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Domain\Season\Season_Factory;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Domain\Tournament\Tournament_Entry_Factory;
use Racketmanager\Domain\Tournament\Tournament_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

/**
 * Characterization test guarding all 18 entities migrated under P2-8:
 * - Empty constructor new X() yields a clean/empty instance.
 * - Raw object hydration via factory and/or lenient entity helper produces expected values.
 * - Key derived fields are preserved across hydration.
 */
final class Entity_Hydration_Characterization_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wpdb'] = new \wpdb();
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_events'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_club_characterization(): void {
        $empty = new Club();
        $this->assertNull( $empty->id );
        $this->assertSame( '', $empty->name );

        $raw = (object) [
            'id'        => '12',
            'name'      => 'Thorpe Bay Tennis Club',
            'shortcode' => 'TBTC',
            'website'   => 'https://example.org',
            'postcode'  => 'SS1 3EX',
        ];
        $from_factory = Club_Factory::from_object( $raw );
        $from_lenient = Club::from_object( $raw );

        $this->assertSame( 12, $from_factory->id );
        $this->assertSame( 'Thorpe Bay Tennis Club', $from_factory->name );
        $this->assertSame( 'TBTC', $from_factory->shortcode );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_team_characterization(): void {
        $empty = new Team();
        $this->assertNull( $empty->id );
        $this->assertSame( '', $empty->title );

        $raw = (object) [
            'id'       => '34',
            'title'    => 'Men 1',
            'club_id'  => '12',
            'division' => '1',
        ];
        $from_factory = Team_Factory::from_object( $raw );
        $from_lenient = Team::from_object( $raw );

        $this->assertSame( 34, $from_factory->id );
        $this->assertSame( 'Men 1', $from_factory->title );
        $this->assertSame( 12, $from_factory->club_id );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_tournament_characterization(): void {
        $empty = new Tournament();
        $this->assertNull( $empty->id );
        $this->assertSame( '', $empty->name );

        $raw = (object) [
            'id'        => '5',
            'name'      => 'Annual Closed Tournament',
            'type'      => 'tournament',
            'status'    => 'active',
            'shortcode' => 'ACT',
        ];
        $from_factory = Tournament_Factory::from_object( $raw );
        $from_lenient = Tournament::from_object( $raw );

        $this->assertSame( 5, $from_factory->id );
        $this->assertSame( 'Annual Closed Tournament', $from_factory->name );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_season_characterization(): void {
        $empty = new Season();
        $this->assertNull( $empty->get_id() );
        $this->assertFalse( isset( $empty->name ) );

        $raw = (object) [
            'id'   => '2026',
            'name' => '2026/2027',
        ];
        $from_factory = Season_Factory::from_object( $raw );
        $from_lenient = Season::from_object( $raw );

        $this->assertSame( 2026, $from_factory->get_id() );
        $this->assertSame( '2026/2027', $from_factory->get_name() );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_event_characterization_and_seasons(): void {
        $empty = new Event();
        $this->assertSame( 0, $empty->id );
        $this->assertSame( '', $empty->name );

        $comp_raw = (object) [
            'id'       => '1',
            'name'     => 'Summer League',
            'type'     => 'league',
            'settings' => json_encode( [ 'mode' => 'championship' ] ),
        ];
        $comp = Competition_Factory::from_object( $comp_raw );

        $raw = (object) [
            'id'             => '10',
            'competition_id' => '1',
            'name'           => 'Men Premier',
            'type'           => 'league',
            'num_sets'       => '3',
            'settings'       => serialize( [ 'num_matches_per_page' => '10' ] ),
            'seasons'        => serialize( [ '2026' => [ 'name' => '2026' ] ] ),
        ];
        $from_factory = Event_Factory::from_object( $raw, $comp );
        $from_lenient = Event::from_object( $raw, $comp );

        $this->assertSame( 10, $from_factory->id );
        $this->assertSame( 'Men Premier', $from_factory->name );
        $this->assertTrue( $from_factory->is_championship );
        $this->assertSame( 3, $from_factory->num_sets );
        $this->assertSame( [ '2026' => [ 'name' => '2026' ] ], $from_factory->get_seasons() );
        $this->assertSame( 1, $from_factory->num_seasons );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_league_characterization_and_event(): void {
        $empty = new League();
        $this->assertNull( $empty->id );
        $this->assertSame( '', $empty->title );

        $comp_raw = (object) [
            'id'       => '1',
            'name'     => 'Summer League',
            'type'     => 'league',
            'settings' => json_encode( [ 'mode' => 'championship' ] ),
        ];
        $comp = Competition_Factory::from_object( $comp_raw );

        $event_raw = (object) [
            'id'             => '10',
            'name'           => 'Men Premier',
            'type'           => 'league',
            'competition_id' => 1,
            'num_sets'       => '3',
            'seasons'        => serialize( [ '2026' => [ 'name' => '2026' ] ] ),
        ];
        $event = Event_Factory::from_object( $event_raw, $comp );

        $raw = (object) [
            'id'       => '100',
            'event_id' => '10',
            'title'    => 'Division 1',
            'settings' => serialize( [ 'point_rule' => 'standard' ] ),
            'seasons'  => serialize( [ '2026' => [ 'name' => '2026', 'rounds' => [] ] ] ),
        ];
        $from_factory = League_Factory::from_object( $raw, $event );
        $from_lenient = League::from_object( $raw, $event );

        $this->assertSame( 100, $from_factory->id );
        $this->assertSame( 'Division 1', $from_factory->title );
        $this->assertSame( $event, $from_factory->event );
        $this->assertTrue( $from_factory->is_championship );
        $this->assertSame( 3, $from_factory->num_sets );
        $this->assertSame( 2, $from_factory->num_sets_to_win );
        $this->assertSame( [ '2026' => [ 'name' => '2026', 'rounds' => [] ] ], $from_factory->seasons );
        $this->assertSame( 1, $from_factory->num_seasons );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_league_team_characterization_and_derived_fields(): void {
        $empty = new League_Team();
        $this->assertNull( $empty->id );
        $this->assertNull( $empty->title );

        $raw = (object) [
            'id'            => '77',
            'team_id'       => '34',
            'league_id'     => '100',
            'title'         => '1_semi-final_1',
            'points_plus'   => '18.0',
            'points_minus'  => '6.0',
            'diff'          => '12.0',
            'status'        => '+',
            'custom'        => serialize( [ 'club_id' => '12', 'extra_note' => 'promoted' ] ),
        ];
        $from_factory = League_Team_Factory::from_object( $raw );
        $from_lenient = League_Team::from_object( $raw );

        $this->assertSame( 77, $from_factory->id );
        $this->assertSame( 34, $from_factory->get_team_id() );
        $this->assertSame( 100, $from_factory->league_id );
        $this->assertSame( 12, $from_factory->club_id );
        $this->assertSame( 18.0, $from_factory->points_plus );
        $this->assertSame( 6.0, $from_factory->points_minus );
        $this->assertSame( '+12.0', $from_factory->diff );
        $this->assertSame( 'arrow-up', $from_factory->status_icon );
        $this->assertSame( \Racketmanager\Util\Util_Lookup::get_standing_status( '+' ), $from_factory->status_text );
        $this->assertSame( '1_semi-final_1', $from_factory->team_ref );
        $this->assertSame( 'Winner of', $from_factory->title );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_rubber_characterization_and_score_derivation(): void {
        $empty = new Rubber();
        $this->assertSame( 0, $empty->get_id() );
        $this->assertSame( '-:-', $empty->score );

        $raw = (object) [
            'id'            => '201',
            'match_id'      => '50',
            'rubber_number' => '1',
            'type'          => 'MD',
            'date'          => '2026-06-15 18:30:00',
            'status'        => '1',
            'home_points'   => '2.0',
            'away_points'   => '1.0',
            'custom'        => serialize( [
                'sets' => [
                    1 => [ 'player1' => '6', 'player2' => '4' ],
                    2 => [ 'player1' => '4', 'player2' => '6' ],
                    3 => [ 'player1' => '10', 'player2' => '8' ],
                ],
            ] ),
        ];
        $from_factory = Rubber_Factory::from_object( $raw );
        $from_lenient = Rubber::from_object( $raw );

        $this->assertSame( 201, $from_factory->id );
        $this->assertSame( 15, $from_factory->day );
        $this->assertSame( 6, $from_factory->month );
        $this->assertSame( 2026, $from_factory->year );
        $this->assertSame( '18:30', $from_factory->start_time );
        $this->assertSame( '2 - 1', $from_factory->score );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_charge_characterization(): void {
        $empty = new Charge();
        $this->assertNull( $empty->get_id() );
        $this->assertSame( 'draft', $empty->get_status() );

        $raw = (object) [
            'id'              => '401',
            'competition_id' => '1',
            'season'          => '2026',
            'status'          => 'pending',
            'date'            => '2026-05-01',
            'fee_competition' => '50.00',
            'fee_event'       => '20.00',
            'total'           => '70.00',
        ];
        $from_factory = Charge_Factory::from_object( $raw );
        $from_lenient = Charge::from_object( $raw );

        $this->assertSame( 401, $from_factory->get_id() );
        $this->assertSame( 1, $from_factory->get_competition_id() );
        $this->assertSame( '2026', $from_factory->get_season() );
        $this->assertSame( 70.0, $from_factory->total );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_invoice_characterization_and_json_details(): void {
        $empty = new Invoice();
        $this->assertNull( $empty->get_id() );
        $this->assertSame( 'draft', $empty->get_status() );

        $raw = (object) [
            'id'             => '501',
            'invoice_number' => '1001',
            'billable_id'    => '12',
            'charge_id'      => '401',
            'amount'         => '70.00',
            'status'         => 'paid',
            'date'           => '2026-05-02',
            'date_due'       => '2026-06-01',
            'details'        => json_encode( [ 'item' => 'Affiliation fee', 'vat' => '0.00' ] ),
        ];
        $from_factory = Invoice_Factory::from_object( $raw );
        $from_lenient = Invoice::from_object( $raw );

        $this->assertSame( 501, $from_factory->get_id() );
        $this->assertSame( 1001, $from_factory->get_invoice_number() );
        $this->assertSame( '70.00', $from_factory->get_amount() );
        $this->assertSame( '{"item":"Affiliation fee","vat":"0.00"}', $from_factory->get_details() );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_message_characterization_and_sender_derivation(): void {
        $empty = new Message();
        $this->assertFalse( isset( $empty->id ) );
        $this->assertSame( '', $empty->from_name );
        $this->assertSame( '', $empty->from_email );

        $raw = (object) [
            'id'             => '601',
            'sender'         => 'Match Secretary <secretary@example.org>',
            'recipient'      => 'captain@example.org',
            'subject'        => 'Fixture confirmation',
            'message_object' => 'Please confirm date.',
            'date'           => '2026-04-10 09:00:00',
            'status'         => 'sent',
        ];
        $from_factory = Message_Factory::from_object( $raw );
        $from_lenient = Message::from_object( $raw );

        $this->assertSame( 601, $from_factory->id );
        $this->assertSame( 'Match Secretary', $from_factory->from_name );
        $this->assertSame( 'secretary@example.org', $from_factory->from_email );
        $this->assertSame( 'Fixture confirmation', $from_factory->subject );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_results_report_characterization(): void {
        $empty = new Results_Report();
        $this->assertSame( 0, $empty->id );
        $this->assertFalse( isset( $empty->match_id ) );

        $raw = (object) [
            'id'            => '701',
            'match_id'      => '50',
            'result_object' => json_encode( [ 'issue' => 'Wrong score recorded' ] ),
        ];
        $from_factory = Results_Report_Factory::from_object( $raw );
        $from_lenient = Results_Report::from_object( $raw );

        $this->assertSame( 701, $from_factory->id );
        $this->assertSame( 50, $from_factory->match_id );
        $this->assertIsObject( $from_factory->data );
        $this->assertSame( 'Wrong score recorded', $from_factory->data->issue );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_tournament_entry_characterization(): void {
        $empty = new Tournament_Entry();
        $this->assertNull( $empty->id );

        $raw = (object) [
            'id'            => '801',
            'tournament_id' => '5',
            'player_id'     => '99',
            'status'        => 'confirmed',
            'fee'           => '15.00',
            'club_id'       => '12',
        ];
        $from_factory = Tournament_Entry_Factory::from_object( $raw );
        $from_lenient = Tournament_Entry::from_object( $raw );

        $this->assertSame( 801, $from_factory->id );
        $this->assertSame( 5, $from_factory->tournament_id );
        $this->assertSame( 99, $from_factory->player_id );
        $this->assertSame( 12, $from_factory->club_id );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_player_error_characterization(): void {
        $empty = new Player_Error();
        $this->assertNull( $empty->get_id() );
        $this->assertFalse( isset( $empty->message ) );

        $raw = (object) [
            'id'         => '901',
            'player_id'  => '99',
            'error_type' => 'ineligible',
            'message'    => 'Player unregistered',
        ];
        $from_factory = Player_Error_Factory::from_object( $raw );
        $from_lenient = Player_Error::from_object( $raw );

        $this->assertSame( 901, $from_factory->get_id() );
        $this->assertSame( 99, $from_factory->get_player_id() );
        $this->assertSame( 'Player unregistered', $from_factory->get_message() );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_results_checker_characterization(): void {
        $empty = new Results_Checker();
        $this->assertSame( 0, $empty->id );
        $this->assertSame( 0, $empty->match_id );

        $raw = (object) [
            'id'          => '201',
            'match_id'    => '50',
            'team_id'     => '34',
            'league_id'   => '100',
            'player_id'   => '99',
            'description' => 'Unregistered player',
        ];
        $from_factory = Results_Checker_Factory::from_object( $raw );
        $from_lenient = Results_Checker::from_object( $raw );

        $this->assertSame( 201, $from_factory->id );
        $this->assertSame( 50, $from_factory->match_id );
        $this->assertSame( 34, $from_factory->team_id );
        $this->assertSame( 100, $from_factory->league_id );
        $this->assertSame( 99, $from_factory->player_id );
        $this->assertSame( 'Unregistered player', $from_factory->description );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_club_player_characterization(): void {
        $empty = new Club_Player();
        $this->assertNull( $empty->get_id() );
        $this->assertNull( $empty->get_status() );

        $raw = (object) [
            'id'          => '1101',
            'club_id'     => '12',
            'player_id'   => '99',
            'status'      => 'active',
            'registered'  => '2026-01-10',
            'team_id'     => '34',
            'player_name' => 'Jane Doe',
        ];
        $from_factory = Club_Player_Factory::from_object( $raw );
        $from_lenient = Club_Player::from_object( $raw );

        $this->assertSame( 1101, $from_factory->get_id() );
        $this->assertSame( 12, $from_factory->get_club_id() );
        $this->assertSame( 99, $from_factory->get_player_id() );
        $this->assertSame( 'active', $from_factory->get_status() );
        $this->assertEquals( $from_factory, $from_lenient );
    }

    public function test_club_role_characterization_and_lookup(): void {
        $empty = new Club_Role();
        $this->assertNull( $empty->get_id() );
        $this->assertNull( $empty->role );

        // 1. Role lookup via role_id
        $raw_lookup = (object) [
            'id'           => '1201',
            'club_id'      => '12',
            'user_id'      => '42',
            'role_id'      => 1,
            'created_date' => '2026-02-01',
        ];
        $from_factory = Club_Role_Factory::from_object( $raw_lookup );
        $from_lenient = Club_Role::from_object( $raw_lookup );

        $this->assertSame( 1201, $from_factory->get_id() );
        $this->assertSame( 12, $from_factory->get_club_id() );
        $this->assertSame( 42, $from_factory->get_user_id() );
        $this->assertSame( 1, $from_factory->role_id );
        $this->assertEquals( \Racketmanager\Util\Util_Lookup::get_club_role( '1' ), $from_factory->role );
        $this->assertIsObject( $from_factory->role );
        $this->assertEquals( $from_factory, $from_lenient );

        // 2. Reverse lookup: role object -> role_id resolution
        $role_obj = \Racketmanager\Util\Util_Lookup::get_club_role( '1' );
        $raw_reverse = (object) [
            'id'      => '1202',
            'club_id' => '12',
            'user_id' => '43',
            'role'    => $role_obj,
        ];
        $from_reverse = Club_Role::from_object( $raw_reverse );
        $this->assertSame( 1, $from_reverse->role_id );
        $this->assertSame( $role_obj, $from_reverse->role );
    }

    public function test_user_characterization_and_aliasing(): void {
        $empty = new User();
        $this->assertSame( 0, $empty->ID );
        $this->assertSame( '', $empty->email );
        $this->assertFalse( isset( $empty->id ) );

        // 1. WordPress shape (ID, user_email, display_name)
        $wp_shape = (object) [
            'ID'           => '42',
            'user_email'   => 'jane@example.org',
            'display_name' => 'Jane Doe',
            'first_name'   => 'Jane',
            'last_name'    => 'Doe',
        ];
        $from_factory = User_Factory::from_object( $wp_shape );
        $from_lenient = User::from_object( $wp_shape );

        $this->assertSame( 42, $from_factory->id );
        $this->assertSame( 42, $from_factory->ID );
        $this->assertSame( 'jane@example.org', $from_factory->email );
        $this->assertSame( 'jane@example.org', $from_factory->user_email );
        $this->assertSame( 'Jane Doe', $from_factory->fullname );
        $this->assertSame( 'Jane Doe', $from_factory->display_name );
        $this->assertEquals( $from_factory, $from_lenient );

        // 2. Plugin shape (id, email, fullname)
        $plugin_shape = (object) [
            'id'       => '50',
            'email'    => 'plugin@example.org',
            'fullname' => 'Plugin User',
        ];
        $from_plugin = User_Factory::from_object( $plugin_shape );
        $this->assertSame( 50, $from_plugin->id );
        $this->assertSame( 50, $from_plugin->ID );
        $this->assertSame( 'plugin@example.org', $from_plugin->email );
        $this->assertSame( 'plugin@example.org', $from_plugin->user_email );
        $this->assertSame( 'Plugin User', $from_plugin->fullname );
        $this->assertSame( 'Plugin User', $from_plugin->display_name );

        // 3. Nested WP_User data shape (data->ID, data->user_email, data->display_name)
        $nested_wp = (object) [
            'ID'   => '70',
            'data' => (object) [
                'ID'           => '70',
                'user_email'   => 'wp_nested@example.org',
                'display_name' => 'Nested WP User',
            ],
            'caps' => [],
        ];
        $from_nested = User_Factory::from_object( $nested_wp );
        $this->assertSame( 70, $from_nested->id );
        $this->assertSame( 70, $from_nested->ID );
        $this->assertSame( 'wp_nested@example.org', $from_nested->email );
        $this->assertSame( 'wp_nested@example.org', $from_nested->user_email );
        $this->assertSame( 'Nested WP User', $from_nested->fullname );
        $this->assertSame( 'Nested WP User', $from_nested->display_name );
        $this->assertFalse( property_exists( $from_nested, 'caps' ) );
    }
}
