<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

final class Entry_Tournament_Template_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private string $template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->template_path = dirname( __DIR__, 3 ) . '/templates/entry/entry-tournament.php';

        global $racketmanager;
        $racketmanager = (object) [
            'date_format' => 'd/m/Y',
        ];

        $GLOBALS['wp_stubs_clubs'] = [
            1 => (object) [
                'id'   => 1,
                'name' => 'Leigh Tennis Club',
            ],
        ];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_club_player_dto( int $club_id, string $name, string $shortcode = '', int $player_id = 42 ): Club_Player_DTO {
        $player = Player_Test_Factory::create_sparse( [ 'id' => $player_id ] );
        $club   = Club_Test_Factory::create( id: $club_id, name: $name, shortcode: $shortcode ?: strtolower( $name ) );
        $reg    = new Club_Player();
        $reg->id        = 1;
        $reg->player_id = $player_id;
        $reg->club_id   = $club_id;
        return new Club_Player_DTO( $player, $club, $reg );
    }

    private function render_template( array $context = [] ): string {
        $tournament       = $context['tournament'] ?? $this->create_default_tournament();
        $player           = $context['player'] ?? $this->create_default_player();
        $club_memberships = $context['club_memberships'] ?? [ $this->create_club_player_dto( 1, 'Leigh Tennis Club', 'LTC' ) ];
        $events           = $context['events'] ?? [
            (object) [
                'id'   => 101,
                'name' => "Men's Singles",
                'type' => 'MS',
            ],
        ];
        $player_entries   = $context['player_entries'] ?? [];
        $tournament_entry = array_key_exists( 'tournament_entry', $context ) ? $context['tournament_entry'] : (object) [ 'club_id' => 1 ];
        $season           = $context['season'] ?? '2026';
        $type             = $context['type'] ?? 'tournament';
        $malePartners     = $context['malePartners'] ?? null;
        $femalePartners   = $context['femalePartners'] ?? null;

        ob_start();
        include $this->template_path;
        return (string) ob_get_clean();
    }

    private function assert_element_attribute( string $html, string $id, string $attribute, bool $expected ): void {
        $matched = preg_match( '/<[a-z][^>]*\bid="' . preg_quote( $id, '/' ) . '"[^>]*>/i', $html, $matches );
        self::assertSame( 1, $matched, sprintf( 'Expected an element with id "%s".', $id ) );

        $pattern = '/(?:^|\s)' . preg_quote( $attribute, '/' ) . '(?:\s|=|\/?>)/i';
        self::assertSame( $expected, 1 === preg_match( $pattern, $matches[0] ), sprintf( 'Unexpected "%s" attribute on element "%s".', $attribute, $id ) );
    }

    private function create_default_tournament( array $overrides = [] ): object {
        return new class( $overrides ) {
            public int $id = 1;
            public string $name = 'Summer Open 2026';
            public string $date_end = '2026-08-01';
            public string $season = '2026';
            public string $date_closing = '2026-07-20';
            public string $date_start = '2026-07-25';
            public string $date = '2026-08-01';
            public bool $is_open = true;
            public bool $is_closed = false;
            public ?int $num_entries = 2;
            public ?int $payments = 0;
            public object $fees;

            public function __construct( array $overrides = [] ) {
                $this->fees = (object) [
                    'competition' => 5,
                    'event'       => 10,
                ];
                foreach ( $overrides as $k => $v ) {
                    $this->$k = $v;
                }
            }

            public function get_meta( string $key ): string {
                return 'Central Courts';
            }
        };
    }

    private function create_default_player( array $overrides = [] ): object {
        $player = (object) [
            'id'           => 42,
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => 'Roger Federer',
            'gender'       => 'M',
            'age'          => 40,
            'wtn'          => [ 'S' => '1.0', 'D' => '1.5' ],
            'contactno'    => '07123456789',
            'user_email'   => 'roger@example.com',
            'btm'          => 12345678,
        ];
        foreach ( $overrides as $k => $v ) {
            $player->$k = $v;
        }
        return $player;
    }

    public function test_own_player_open_tournament_is_editable_without_alerts(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => true,
            'is_closed' => false,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringNotContainsString( 'alert_rm mt-3 alert--', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', false );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', false );
        self::assertMatchesRegularExpression( '/<span class="tag-pair__value">\s*1\.0\s*<\/span>/', $html );
        self::assertStringContainsString( 'id="entrySubmit"', $html );
        self::assertStringContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_own_player_closed_tournament_allows_withdrawal_with_warning_alert(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => true,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringContainsString( 'alert--warning', $html );
        self::assertStringContainsString( 'Tournament entries are now closed.', $html );
        self::assertStringContainsString( 'You can still withdraw from events.', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', true );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', false );
        self::assertStringContainsString( 'id="tournamentWithdraw"', $html );
        self::assertStringContainsString( 'id="entrySubmit"', $html );
    }

    public function test_own_player_not_open_entered_shows_info_alert_and_disables_actions(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => false,
        ] );

        $player_entries = [
            101 => (object) [
                'partner_id'   => null,
                'partner_name' => null,
            ],
        ];

        $html = $this->render_template( [
            'tournament'     => $tournament,
            'player_entries' => $player_entries,
        ] );

        self::assertStringContainsString( 'alert--info', $html );
        self::assertStringContainsString( 'Tournament entries are now closed.', $html );
        self::assertStringContainsString( 'These are the latest entry details.', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', true );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', true );
        $this->assert_element_attribute( $html, 'event-101', 'checked', true );
        $this->assert_element_attribute( $html, 'event-101', 'disabled', true );
        self::assertStringNotContainsString( 'id="entrySubmit"', $html );
        self::assertStringNotContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_own_player_not_open_not_entered_shows_warning_alert_and_disables_actions(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => false,
        ] );

        $html = $this->render_template( [
            'tournament'     => $tournament,
            'player_entries' => [],
        ] );

        self::assertStringContainsString( 'alert--warning', $html );
        self::assertStringContainsString( 'Tournament not currently open for entries', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', true );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', true );
        self::assertStringNotContainsString( 'id="entrySubmit"', $html );
        self::assertStringNotContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_manager_not_open_tournament_is_editable_without_alerts(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 99; // Different user ID
        $GLOBALS['wp_stubs_current_user_can'] = true; // Manager

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => false,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringNotContainsString( 'alert_rm mt-3 alert--', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', false );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', false );
        self::assertStringContainsString( 'id="entrySubmit"', $html );
    }

    public function test_manager_closed_tournament_is_editable_without_alerts(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 99;
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => true,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringNotContainsString( 'alert_rm mt-3 alert--', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', false );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', false );
        self::assertStringContainsString( 'id="entrySubmit"', $html );
        self::assertStringContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_third_party_open_tournament_shows_info_alert_and_disables_changes(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 99; // Different user ID
        $GLOBALS['wp_stubs_current_user_can'] = false; // Not manager

        $tournament = $this->create_default_tournament( [
            'is_open'   => true,
            'is_closed' => false,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringContainsString( 'alert--info', $html );
        self::assertStringContainsString( 'You can not make changes to a entry form for someone else.', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', true );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', true );
        self::assertStringNotContainsString( 'id="entrySubmit"', $html );
        self::assertStringNotContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_third_party_closed_tournament_keeps_warning_but_disables_actions(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 99;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => true,
        ] );

        $html = $this->render_template( [ 'tournament' => $tournament ] );

        self::assertStringContainsString( 'alert--warning', $html );
        self::assertStringContainsString( 'Tournament entries are now closed.', $html );
        self::assertStringContainsString( 'You can still withdraw from events.', $html );
        $this->assert_element_attribute( $html, 'contactno', 'readonly', true );
        $this->assert_element_attribute( $html, 'acceptance', 'disabled', true );
        self::assertStringNotContainsString( 'id="entrySubmit"', $html );
        self::assertStringNotContainsString( 'id="tournamentWithdraw"', $html );
    }

    public function test_player_profile_and_contact_details_rendering(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $player = $this->create_default_player( [
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => 'Roger Federer',
            'gender'       => 'M',
            'age'          => 40,
            'contactno'    => '07123456789',
            'user_email'   => 'roger@example.com',
            'btm'          => 12345678,
        ] );

        $html = $this->render_template( [ 'player' => $player ] );

        self::assertStringContainsString( 'RF', $html );
        self::assertStringContainsString( 'Roger Federer', $html );
        self::assertMatchesRegularExpression( '/data-bs-title="Gender"[^>]*>\s*<span>Male<\/span>/', $html );
        self::assertMatchesRegularExpression( '/data-bs-title="Playing age for tournament"[^>]*>\s*<span>40<\/span>/', $html );
        self::assertMatchesRegularExpression( '/<input[^>]*id="contactno"[^>]*value="07123456789"/', $html );
        self::assertMatchesRegularExpression( '/<input[^>]*id="contactemail"[^>]*value="roger@example\.com"/', $html );
        self::assertMatchesRegularExpression( '/<input[^>]*id="btm"[^>]*value="12345678"/', $html );
    }

    public function test_player_age_fallback_distinction_between_profile_and_event_filter(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $player = $this->create_default_player( [
            'age' => null,
        ] );

        $html = $this->render_template( [ 'player' => $player ] );

        // Profile age uses translated 'Unknown'
        self::assertMatchesRegularExpression( '/data-bs-title="Playing age for tournament"[^>]*>\s*<span>Unknown<\/span>/', $html );
        // Event filter text uses lowercase 'unknown'
        self::assertStringContainsString( 'playing age (unknown)', $html );
    }

    public function test_wtn_ratings_and_tooltips_rendering_and_missing_keys(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        // Player with only Singles rating (Doubles key missing)
        $player = $this->create_default_player( [
            'wtn' => [ 'S' => '2.5' ],
        ] );

        $html = $this->render_template( [ 'player' => $player ] );

        self::assertStringContainsString( 'data-bs-title="World Tennis Number for Singles"', $html );
        self::assertStringContainsString( 'data-bs-title="World Tennis Number for Doubles"', $html );
        self::assertMatchesRegularExpression( '/<span class="tag-pair__title">Singles<\/span>\s*<span class="tag-pair__value">2\.5<\/span>/', $html );
        self::assertMatchesRegularExpression( '/<span class="tag-pair__title">Doubles<\/span>\s*<span class="tag-pair__value"><\/span>/', $html );
    }

    public function test_single_club_membership_rendering(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $html = $this->render_template( [
            'club_memberships' => [ $this->create_club_player_dto( 10, 'Custom Tennis Club', 'CTC' ) ],
        ] );

        self::assertStringContainsString( 'Custom Tennis Club', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" id="clubId" name="clubId" value="10"\s*\/>/', $html );
    }

    public function test_multiple_club_memberships_rendering(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $html = $this->render_template( [
            'club_memberships' => [
                $this->create_club_player_dto( 1, 'Leigh Tennis Club', 'LTC' ),
                $this->create_club_player_dto( 2, 'Westcliff Tennis Club', 'WTC' ),
            ],
            'tournament_entry' => (object) [ 'club_id' => 2 ],
        ] );

        self::assertMatchesRegularExpression( '/<select class="form-select" size="1" name="clubId" id="clubId"/', $html );
        self::assertMatchesRegularExpression( '/<option value="" disabled[^>]*>\s*Select club\s*<\/option>/', $html );
        self::assertMatchesRegularExpression( '/<option value="1"[^>]*>\s*Leigh Tennis Club\s*<\/option>/', $html );
        self::assertMatchesRegularExpression( '/<option value="2"[^>]*selected=[\'"]selected[\'"][^>]*>\s*Westcliff Tennis Club\s*<\/option>/', $html );
    }

    public function test_zero_club_memberships_rendering(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $html = $this->render_template( [
            'club_memberships' => [],
        ] );

        self::assertStringContainsString( 'No club', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" id="clubId" name="clubId" value=""\s*\/>/', $html );
    }

    public function test_single_club_membership_hidden_id_distinct_from_get_club(): void {
        // membership club_id 30 with distinct name and shortcode
        $html = $this->render_template( [
            'club_memberships' => [ $this->create_club_player_dto( 30, 'Different Club Name', 'DCN' ) ],
        ] );

        self::assertStringContainsString( 'Different Club Name', $html );
        // Should use club_id 30 for the hidden input
        self::assertMatchesRegularExpression( '/<input type="hidden" id="clubId" name="clubId" value="30"\s*\/>/', $html );
    }

    public function test_multiple_club_memberships_empty_tournament_entry_selects_placeholder(): void {
        $entries_to_test = [
            null,
            (object) [ 'club_id' => null ],
            (object) [ 'club_id' => '' ],
            (object) [ 'club_id' => 0 ],
            (object) [],
        ];

        foreach ( $entries_to_test as $entry ) {
            $html = $this->render_template( [
                'club_memberships' => [
                    $this->create_club_player_dto( 1, 'Leigh', 'LTC' ),
                    $this->create_club_player_dto( 2, 'Westcliff', 'WTC' ),
                ],
                'tournament_entry' => $entry,
            ] );

            self::assertMatchesRegularExpression( '/<option value="" disabled\s+selected=[\'"]selected[\'"][^>]*>\s*Select club\s*<\/option>/', $html );
            self::assertDoesNotMatchRegularExpression( '/<option value="1"[^>]*selected/', $html );
            self::assertDoesNotMatchRegularExpression( '/<option value="2"[^>]*selected/', $html );
        }
    }

    public function test_multiple_club_memberships_numeric_string_id_match(): void {
        $html = $this->render_template( [
            'club_memberships' => [
                $this->create_club_player_dto( 1, 'Leigh', 'LTC' ),
                $this->create_club_player_dto( 2, 'Westcliff', 'WTC' ),
            ],
            'tournament_entry' => (object) [ 'club_id' => '2' ],
        ] );

        self::assertMatchesRegularExpression( '/<option value="2"[^>]*selected=[\'"]selected[\'"][^>]*>\s*Westcliff\s*<\/option>/', $html );
    }

    public function test_multiple_club_memberships_readonly_attribute(): void {
        $memberships = [
            $this->create_club_player_dto( 1, 'Leigh', 'LTC' ),
            $this->create_club_player_dto( 2, 'Westcliff', 'WTC' ),
        ];

        // Test readonly (changes NOT allowed: own player on closed/not-open tournament)
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $html_readonly = $this->render_template( [
            'tournament'       => $this->create_default_tournament( [ 'is_open' => false, 'is_closed' => false ] ),
            'club_memberships' => $memberships,
        ] );
        $this->assert_element_attribute( $html_readonly, 'clubId', 'readonly', true );

        // Test NOT readonly (changes allowed: own player on open tournament)
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $html_editable = $this->render_template( [
            'tournament'       => $this->create_default_tournament( [ 'is_open' => true ] ),
            'club_memberships' => $memberships,
        ] );
        $this->assert_element_attribute( $html_editable, 'clubId', 'readonly', false );
    }

    public function test_singles_and_doubles_event_rendering_when_editable(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $events = [
            (object) [
                'id'   => 101,
                'name' => "Men's Singles",
                'type' => 'MS',
            ],
            (object) [
                'id'   => 102,
                'name' => "Men's Doubles",
                'type' => 'MD',
            ],
        ];

        $html = $this->render_template( [
            'events' => $events,
        ] );

        // Singles event (101): not doubles, enabled checkbox, no partner controls
        self::assertMatchesRegularExpression( '/<input class="form-check-input form-check--event hasModal" id="event-101" name="event\[101]" type="checkbox" value="101" aria-controls="conditional-event-101"/', $html );
        $this->assert_element_attribute( $html, 'event-101', 'disabled', false );
        $this->assert_element_attribute( $html, 'event-101', 'checked', false );
        self::assertMatchesRegularExpression( '/<div class="col-6\s*" id="conditional-event-101">/', $html );
        self::assertStringNotContainsString( 'id="partner-101"', $html );
        self::assertStringNotContainsString( 'id="partnerId-101"', $html );

        // Doubles event (102): is-doubles class, enabled checkbox, partner modal trigger and hidden inputs
        self::assertMatchesRegularExpression( '/<input class="form-check-input form-check--event hasModal" id="event-102" name="event\[102]" type="checkbox" value="102" aria-controls="conditional-event-102"/', $html );
        $this->assert_element_attribute( $html, 'event-102', 'disabled', false );
        self::assertMatchesRegularExpression( '/<div class="col-6 is-doubles" id="conditional-event-102">/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partner\[102]" id="partner-102" value=""\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partnerId\[102]" id="partnerId-102" value=""\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<a href="[^"]*" class="tournamentEventEntry" data-event-id="102" data-action="open-partner-modal">\s*<span id="partnerName-102"><\/span>\s*<\/a>/', $html );
        self::assertMatchesRegularExpression( '/<div id="partner-102Feedback" class="invalid-feedback"><\/div>/', $html );
    }

    public function test_singles_and_doubles_event_rendering_when_readonly(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 99; // Third-party view
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $events = [
            (object) [
                'id'   => 101,
                'name' => "Men's Singles",
                'type' => 'MS',
            ],
            (object) [
                'id'   => 102,
                'name' => "Men's Doubles",
                'type' => 'MD',
            ],
        ];

        $html = $this->render_template( [
            'events' => $events,
        ] );

        // Both checkboxes should be disabled
        $this->assert_element_attribute( $html, 'event-101', 'disabled', true );
        $this->assert_element_attribute( $html, 'event-102', 'disabled', true );

        // Doubles event has hidden partner inputs and span, but NO modal anchor
        self::assertMatchesRegularExpression( '/<div class="col-6 is-doubles" id="conditional-event-102">/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partner\[102]" id="partner-102" value=""\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partnerId\[102]" id="partnerId-102" value=""\s*\/>/', $html );
        self::assertStringNotContainsString( 'data-action="open-partner-modal"', $html );
        self::assertMatchesRegularExpression( '/<span id="partnerName-102"><\/span>/', $html );
    }

    public function test_entered_events_withdraw_allowed_and_pricing_calculation(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'   => false,
            'is_closed' => true,
        ] );

        $events = [
            (object) [
                'id'   => 101,
                'name' => "Men's Singles",
                'type' => 'MS',
            ],
            (object) [
                'id'   => 102,
                'name' => "Men's Doubles",
                'type' => 'MD',
            ],
        ];

        $player_entries = [
            101 => (object) [
                'partner_id'   => null,
                'partner_name' => null,
            ],
        ];

        $html = $this->render_template( [
            'tournament'     => $tournament,
            'events'         => $events,
            'player_entries' => $player_entries,
        ] );

        // Event 101 is entered: checked, and NOT disabled because withdrawal is allowed
        $this->assert_element_attribute( $html, 'event-101', 'checked', true );
        $this->assert_element_attribute( $html, 'event-101', 'disabled', false );
        self::assertMatchesRegularExpression( '/<span class="event-price" id="event-price-fmt-101">\s*£10\s*<\/span>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" class="event-price-amt" name="event-price\[101]" id="event-price-101" value="10"\s*\/>/', $html );

        // Event 102 is NOT entered: unchecked, and disabled because tournament is closed
        $this->assert_element_attribute( $html, 'event-102', 'checked', false );
        $this->assert_element_attribute( $html, 'event-102', 'disabled', true );
        self::assertMatchesRegularExpression( '/<span class="event-price" id="event-price-fmt-102">\s*<\/span>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" class="event-price-amt" name="event-price\[102]" id="event-price-102" value=""\s*\/>/', $html );

        // Metadata inputs
        self::assertMatchesRegularExpression( '/<input type="hidden" name="eventsEntered" id="eventsEntered" value="1"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="tournamentEvents" value="101,102"\s*\/>/', $html );

        // Footer pricing: 10 (event) + 5 (competition) = 15 total
        self::assertMatchesRegularExpression( '/<div class="price-cost" id="priceCostTotalFmt">\s*Total:\s*£15\s*<\/div>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="priceCostTotal" id="priceCostTotal" value="15"\s*\/>/', $html );
    }

    public function test_entered_doubles_event_with_partner_and_existing_payments(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament( [
            'is_open'  => true,
            'payments' => 15,
        ] );

        $events = [
            (object) [
                'id'   => 102,
                'name' => "Men's Doubles",
                'type' => 'MD',
            ],
        ];

        $player_entries = [
            102 => (object) [
                'partner_id'   => 88,
                'partner_name' => 'Andy Murray',
            ],
        ];

        $html = $this->render_template( [
            'tournament'     => $tournament,
            'events'         => $events,
            'player_entries' => $player_entries,
        ] );

        // Checkbox checked
        $this->assert_element_attribute( $html, 'event-102', 'checked', true );

        // Partner inputs and display
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partner\[102]" id="partner-102" value="88"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partnerId\[102]" id="partnerId-102" value="88"\s*\/>/', $html );
        self::assertStringContainsString( 'Andy Murray', $html );
        self::assertMatchesRegularExpression( '/<span id="partnerName-102">Andy Murray<\/span>/', $html );

        // Payments displayed
        self::assertMatchesRegularExpression( '/<div class="price-cost" id="pricePaidTotalFmt">\s*Paid:\s*£15\s*<\/div>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="pricePaidTotal" id="pricePaidTotal" value="15"\s*\/>/', $html );
    }

    public function test_event_fee_visibility_preserves_zero_and_empty_comparison_semantics(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        foreach ( [ 0 => true, 'null' => false, 'empty' => false ] as $fee_key => $expected_visible ) {
            $event_fee = match ( $fee_key ) {
                0       => 0,
                'null'  => null,
                'empty' => '',
            };
            $tournament = $this->create_default_tournament();
            $tournament->fees->event = $event_fee;

            $html = $this->render_template( [ 'tournament' => $tournament ] );
            $fee_markup_pattern = '/<span class="event-price" id="event-price-fmt-101">/';

            self::assertSame( $expected_visible, 1 === preg_match( $fee_markup_pattern, $html ), sprintf( 'Unexpected event fee visibility for %s.', var_export( $event_fee, true ) ) );
        }
    }

    public function test_empty_events_list_renders_warning(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $html = $this->render_template( [
            'events' => [],
        ] );

        self::assertStringContainsString( 'There are no events that you are eligible to enter.', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="eventsEntered" id="eventsEntered" value="0"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="tournamentEvents" value=""\s*\/>/', $html );
    }

    public function test_acceptance_and_hidden_tournament_metadata_fields(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $html = $this->render_template();

        // Hidden metadata fields
        self::assertMatchesRegularExpression( '/<input type="hidden" name="tournamentId" id="tournamentId" value="1"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="tournamentDateEnd" id="tournamentDateEnd" value="2026-08-01"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="season" id="season" value="2026"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="playerId" id="playerId" value="42"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" id="playerGender" value="M"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" id="competitionFee" name="competitionFee" value="5"\s*\/>/', $html );

        // Acceptance switch
        self::assertMatchesRegularExpression( '/<input class="form-check-input" id="acceptance" name="acceptance" type="checkbox" role="switch" aria-checked="false"\s*\/>/', $html );
        self::assertStringContainsString( '<a href="/rules/tournament-rules" target="_blank">the rules of the tournament</a>', $html );
        self::assertStringContainsString( 'I agree to abide by <a href="/rules/tournament-rules" target="_blank">the rules of the tournament</a>.', $html );
    }
}
