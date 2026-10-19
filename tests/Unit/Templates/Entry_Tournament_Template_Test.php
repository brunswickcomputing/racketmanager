<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

final class Entry_Tournament_Template_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private const string ALERT_WARNING = 'alert--warning';
    private const string ALERT_INFO    = 'alert--info';
    private const string ALERT_BASE    = 'alert_rm mt-3 alert--';

    private const string MSG_ENTRIES_CLOSED      = 'Tournament entries are now closed.';
    private const string MSG_STILL_WITHDRAW      = 'You can still withdraw from events.';
    private const string MSG_LATEST_DETAILS      = 'These are the latest entry details.';
    private const string MSG_NOT_CURRENTLY_OPEN  = 'Tournament not currently open for entries';
    private const string MSG_THIRD_PARTY_CHANGES = 'You can not make changes to a entry form for someone else.';

    private const string ID_ENTRY_SUBMIT         = 'entrySubmit';
    private const string ID_TOURNAMENT_WITHDRAW  = 'tournamentWithdraw';
    private const string ID_CONTACT_NO           = 'contactno';
    private const string ID_ACCEPTANCE           = 'acceptance';
    private const string ID_CLUB_ID              = 'clubId';
    private const string ID_EVENT_101            = 'event-101';
    private const string ID_EVENT_102            = 'event-102';

    private const string ATTR_READONLY           = 'readonly';
    private const string ATTR_DISABLED           = 'disabled';
    private const string ATTR_CHECKED            = 'checked';

    private const string CLUB_LEIGH              = 'Leigh';
    private const string CLUB_WESTCLIFF          = 'Westcliff';
    private const string CLUB_LEIGH_FULL         = 'Leigh Tennis Club';
    private const string CLUB_WESTCLIFF_FULL     = 'Westcliff Tennis Club';
    private const string CLUB_CODE_LTC           = 'LTC';
    private const string CLUB_CODE_WTC           = 'WTC';

    private const string EVENT_MENS_SINGLES      = "Men's Singles";
    private const string EVENT_MENS_DOUBLES      = "Men's Doubles";
    private const string EVENT_CODE_MS           = 'MS';
    private const string EVENT_CODE_MD           = 'MD';

    private const string PLAYER_ROGER_FEDERER    = 'Roger Federer';
    private const string REGEX_OPTION_CLOSE      = '\s*<\/option>/';

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
                'name' => self::CLUB_LEIGH_FULL,
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
        $club_memberships = $context['club_memberships'] ?? [ $this->create_club_player_dto( 1, self::CLUB_LEIGH_FULL, self::CLUB_CODE_LTC ) ];
        $events           = $context['events'] ?? [
            (object) [
                'id'   => 101,
                'name' => self::EVENT_MENS_SINGLES,
                'type' => self::EVENT_CODE_MS,
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
        $tournament = (object) [
            'id'           => 1,
            'name'         => 'Summer Open 2026',
            'date_end'     => '2026-08-01',
            'season'       => '2026',
            'date_closing' => '2026-07-20',
            'date_start'   => '2026-07-25',
            'date'         => '2026-08-01',
            'is_open'      => true,
            'is_closed'    => false,
            'num_entries'  => 2,
            'payments'     => 0,
            'fees'         => (object) [
                'competition' => 5,
                'event'       => 10,
            ],
        ];

        foreach ( $overrides as $k => $v ) {
            $tournament->$k = $v;
        }

        return $tournament;
    }

    private function create_default_player( array $overrides = [] ): object {
        $player = (object) [
            'id'           => 42,
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => self::PLAYER_ROGER_FEDERER,
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

    #[DataProvider( 'tournament_permission_state_provider' )]
    public function test_tournament_entry_permissions_and_alerts(
        int $user_id,
        bool $user_can,
        bool $is_open,
        bool $is_closed,
        array $entries,
        ?string $expected_alert_class,
        array $expected_alert_texts,
        bool $expected_readonly,
        bool $expected_acceptance_disabled,
        bool $can_submit,
        bool $can_withdraw,
        array $expected_events_checked = [],
        array $expected_events_disabled = [],
        array $expected_custom_patterns = []
    ): void {
        $GLOBALS['wp_stubs_current_user_id']  = $user_id;
        $GLOBALS['wp_stubs_current_user_can'] = $user_can;

        $tournament = $this->create_default_tournament( [
            'is_open'   => $is_open,
            'is_closed' => $is_closed,
        ] );

        $html = $this->render_template( [
            'tournament'     => $tournament,
            'player_entries' => $entries,
        ] );

        if ( null === $expected_alert_class ) {
            self::assertStringNotContainsString( self::ALERT_BASE, $html );
        } else {
            self::assertStringContainsString( $expected_alert_class, $html );
            foreach ( $expected_alert_texts as $alert_text ) {
                self::assertStringContainsString( $alert_text, $html );
            }
        }

        $this->assert_element_attribute( $html, self::ID_CONTACT_NO, self::ATTR_READONLY, $expected_readonly );
        $this->assert_element_attribute( $html, self::ID_ACCEPTANCE, self::ATTR_DISABLED, $expected_acceptance_disabled );

        if ( $can_submit ) {
            self::assertStringContainsString( 'id="' . self::ID_ENTRY_SUBMIT . '"', $html );
        } else {
            self::assertStringNotContainsString( 'id="' . self::ID_ENTRY_SUBMIT . '"', $html );
        }

        if ( $can_withdraw ) {
            self::assertStringContainsString( 'id="' . self::ID_TOURNAMENT_WITHDRAW . '"', $html );
        } else {
            self::assertStringNotContainsString( 'id="' . self::ID_TOURNAMENT_WITHDRAW . '"', $html );
        }

        foreach ( $expected_events_checked as $event_id => $checked ) {
            $this->assert_element_attribute( $html, "event-{$event_id}", self::ATTR_CHECKED, $checked );
        }

        foreach ( $expected_events_disabled as $event_id => $disabled ) {
            $this->assert_element_attribute( $html, "event-{$event_id}", self::ATTR_DISABLED, $disabled );
        }

        foreach ( $expected_custom_patterns as $pattern ) {
            self::assertMatchesRegularExpression( $pattern, $html );
        }
    }

    public static function tournament_permission_state_provider(): array {
        return [
            'own player open tournament is editable without alerts' => [
                'user_id'                      => 42,
                'user_can'                     => false,
                'is_open'                      => true,
                'is_closed'                    => false,
                'entries'                      => [],
                'expected_alert_class'         => null,
                'expected_alert_texts'         => [],
                'expected_readonly'            => false,
                'expected_acceptance_disabled' => false,
                'can_submit'                   => true,
                'can_withdraw'                 => true,
                'expected_events_checked'      => [],
                'expected_events_disabled'     => [],
                'expected_custom_patterns'     => [ '/<span class="tag-pair__value">\s*1\.0\s*<\/span>/' ],
            ],
            'own player closed tournament allows withdrawal with warning alert' => [
                'user_id'                      => 42,
                'user_can'                     => false,
                'is_open'                      => false,
                'is_closed'                    => true,
                'entries'                      => [],
                'expected_alert_class'         => self::ALERT_WARNING,
                'expected_alert_texts'         => [ self::MSG_ENTRIES_CLOSED, self::MSG_STILL_WITHDRAW ],
                'expected_readonly'            => true,
                'expected_acceptance_disabled' => false,
                'can_submit'                   => true,
                'can_withdraw'                 => true,
            ],
            'own player not open entered shows info alert and disables actions' => [
                'user_id'                      => 42,
                'user_can'                     => false,
                'is_open'                      => false,
                'is_closed'                    => false,
                'entries'                      => [
                    101 => (object) [
                        'partner_id'   => null,
                        'partner_name' => null,
                    ],
                ],
                'expected_alert_class'         => self::ALERT_INFO,
                'expected_alert_texts'         => [ self::MSG_ENTRIES_CLOSED, self::MSG_LATEST_DETAILS ],
                'expected_readonly'            => true,
                'expected_acceptance_disabled' => true,
                'can_submit'                   => false,
                'can_withdraw'                 => false,
                'expected_events_checked'      => [ 101 => true ],
                'expected_events_disabled'     => [ 101 => true ],
            ],
            'own player not open not entered shows warning alert and disables actions' => [
                'user_id'                      => 42,
                'user_can'                     => false,
                'is_open'                      => false,
                'is_closed'                    => false,
                'entries'                      => [],
                'expected_alert_class'         => self::ALERT_WARNING,
                'expected_alert_texts'         => [ self::MSG_NOT_CURRENTLY_OPEN ],
                'expected_readonly'            => true,
                'expected_acceptance_disabled' => true,
                'can_submit'                   => false,
                'can_withdraw'                 => false,
            ],
            'manager not open tournament is editable without alerts' => [
                'user_id'                      => 99,
                'user_can'                     => true,
                'is_open'                      => false,
                'is_closed'                    => false,
                'entries'                      => [],
                'expected_alert_class'         => null,
                'expected_alert_texts'         => [],
                'expected_readonly'            => false,
                'expected_acceptance_disabled' => false,
                'can_submit'                   => true,
                'can_withdraw'                 => false,
            ],
            'manager closed tournament is editable without alerts' => [
                'user_id'                      => 99,
                'user_can'                     => true,
                'is_open'                      => false,
                'is_closed'                    => true,
                'entries'                      => [],
                'expected_alert_class'         => null,
                'expected_alert_texts'         => [],
                'expected_readonly'            => false,
                'expected_acceptance_disabled' => false,
                'can_submit'                   => true,
                'can_withdraw'                 => true,
            ],
            'third party open tournament shows info alert and disables changes' => [
                'user_id'                      => 99,
                'user_can'                     => false,
                'is_open'                      => true,
                'is_closed'                    => false,
                'entries'                      => [],
                'expected_alert_class'         => self::ALERT_INFO,
                'expected_alert_texts'         => [ self::MSG_THIRD_PARTY_CHANGES ],
                'expected_readonly'            => true,
                'expected_acceptance_disabled' => true,
                'can_submit'                   => false,
                'can_withdraw'                 => false,
            ],
            'third party closed tournament keeps warning but disables actions' => [
                'user_id'                      => 99,
                'user_can'                     => false,
                'is_open'                      => false,
                'is_closed'                    => true,
                'entries'                      => [],
                'expected_alert_class'         => self::ALERT_WARNING,
                'expected_alert_texts'         => [ self::MSG_ENTRIES_CLOSED, self::MSG_STILL_WITHDRAW ],
                'expected_readonly'            => true,
                'expected_acceptance_disabled' => true,
                'can_submit'                   => false,
                'can_withdraw'                 => false,
            ],
        ];
    }

    public function test_player_profile_and_contact_details_rendering(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $player = $this->create_default_player( [
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => self::PLAYER_ROGER_FEDERER,
            'gender'       => 'M',
            'age'          => 40,
            'contactno'    => '07123456789',
            'user_email'   => 'roger@example.com',
            'btm'          => 12345678,
        ] );

        $html = $this->render_template( [ 'player' => $player ] );

        self::assertStringContainsString( 'RF', $html );
        self::assertStringContainsString( self::PLAYER_ROGER_FEDERER, $html );
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

    private static function option_pattern( string $value, string $label, bool $selected = false ): string {
        $selected_attr = $selected ? '[^>]*selected=[\'"]selected[\'"]' : '';
        return '/<option value="' . preg_quote( $value, '/' ) . '"' . $selected_attr . '[^>]*>\s*' . preg_quote( $label, '/' ) . self::REGEX_OPTION_CLOSE;
    }

    #[DataProvider( 'provide_club_membership_rendering_cases' )]
    public function test_club_membership_rendering(
        array $memberships_config,
        ?object $tournament_entry,
        ?string $expected_contained_text,
        string $expected_element_pattern,
        array $expected_option_patterns = []
    ): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $memberships = array_map(
            fn( array $cfg ) => $this->create_club_player_dto( $cfg['id'], $cfg['name'], $cfg['shortcode'] ?? '' ),
            $memberships_config
        );

        $context = [ 'club_memberships' => $memberships ];
        if ( null !== $tournament_entry ) {
            $context['tournament_entry'] = $tournament_entry;
        }

        $html = $this->render_template( $context );

        if ( null !== $expected_contained_text ) {
            self::assertStringContainsString( $expected_contained_text, $html );
        }

        self::assertMatchesRegularExpression( $expected_element_pattern, $html );

        foreach ( $expected_option_patterns as $pattern ) {
            self::assertMatchesRegularExpression( $pattern, $html );
        }
    }

    public static function provide_club_membership_rendering_cases(): array {
        return [
            'single club membership rendering' => [
                'memberships_config'       => [
                    [ 'id' => 10, 'name' => 'Custom Tennis Club', 'shortcode' => 'CTC' ],
                ],
                'tournament_entry'         => null,
                'expected_contained_text'  => 'Custom Tennis Club',
                'expected_element_pattern' => '/<input type="hidden" id="clubId" name="clubId" value="10"\s*\/>/',
                'expected_option_patterns' => [],
            ],
            'single club membership hidden id distinct from get club' => [
                'memberships_config'       => [
                    [ 'id' => 30, 'name' => 'Different Club Name', 'shortcode' => 'DCN' ],
                ],
                'tournament_entry'         => null,
                'expected_contained_text'  => 'Different Club Name',
                'expected_element_pattern' => '/<input type="hidden" id="clubId" name="clubId" value="30"\s*\/>/',
                'expected_option_patterns' => [],
            ],
            'zero club memberships rendering' => [
                'memberships_config'       => [],
                'tournament_entry'         => null,
                'expected_contained_text'  => 'No club',
                'expected_element_pattern' => '/<input type="hidden" id="clubId" name="clubId" value=""\s*\/>/',
                'expected_option_patterns' => [],
            ],
            'multiple club memberships rendering' => [
                'memberships_config'       => [
                    [ 'id' => 1, 'name' => self::CLUB_LEIGH_FULL, 'shortcode' => self::CLUB_CODE_LTC ],
                    [ 'id' => 2, 'name' => self::CLUB_WESTCLIFF_FULL, 'shortcode' => self::CLUB_CODE_WTC ],
                ],
                'tournament_entry'         => (object) [ 'club_id' => 2 ],
                'expected_contained_text'  => null,
                'expected_element_pattern' => '/<select class="form-select" size="1" name="clubId" id="clubId"/',
                'expected_option_patterns' => [
                    '/<option value="" disabled[^>]*>\s*Select club' . self::REGEX_OPTION_CLOSE,
                    self::option_pattern( '1', self::CLUB_LEIGH_FULL ),
                    self::option_pattern( '2', self::CLUB_WESTCLIFF_FULL, true ),
                ],
            ],
        ];
    }

    #[DataProvider( 'provide_empty_tournament_entry_cases' )]
    public function test_multiple_club_memberships_empty_tournament_entry_selects_placeholder( ?object $entry ): void {
        $html = $this->render_template( [
            'club_memberships' => [
                $this->create_club_player_dto( 1, self::CLUB_LEIGH, self::CLUB_CODE_LTC ),
                $this->create_club_player_dto( 2, self::CLUB_WESTCLIFF, self::CLUB_CODE_WTC ),
            ],
            'tournament_entry' => $entry,
        ] );

        self::assertMatchesRegularExpression( '/<option value="" disabled\s+selected=[\'"]selected[\'"][^>]*>\s*Select club' . self::REGEX_OPTION_CLOSE, $html );
        self::assertDoesNotMatchRegularExpression( '/<option value="1"[^>]*selected/', $html );
        self::assertDoesNotMatchRegularExpression( '/<option value="2"[^>]*selected/', $html );
    }

    public static function provide_empty_tournament_entry_cases(): array {
        return [
            'null tournament entry'       => [ null ],
            'null club_id object'         => [ (object) [ 'club_id' => null ] ],
            'empty string club_id object' => [ (object) [ 'club_id' => '' ] ],
            'zero club_id object'         => [ (object) [ 'club_id' => 0 ] ],
            'empty object'                => [ (object) [] ],
        ];
    }

    public function test_multiple_club_memberships_numeric_string_id_match(): void {
        $html = $this->render_template( [
            'club_memberships' => [
                $this->create_club_player_dto( 1, self::CLUB_LEIGH, self::CLUB_CODE_LTC ),
                $this->create_club_player_dto( 2, self::CLUB_WESTCLIFF, self::CLUB_CODE_WTC ),
            ],
            'tournament_entry' => (object) [ 'club_id' => '2' ],
        ] );

        self::assertMatchesRegularExpression( self::option_pattern( '2', self::CLUB_WESTCLIFF, true ), $html );
    }

    public function test_multiple_club_memberships_readonly_attribute(): void {
        $memberships = [
            $this->create_club_player_dto( 1, self::CLUB_LEIGH, self::CLUB_CODE_LTC ),
            $this->create_club_player_dto( 2, self::CLUB_WESTCLIFF, self::CLUB_CODE_WTC ),
        ];

        // Test readonly (changes NOT allowed: own player on closed/not-open tournament)
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $html_readonly = $this->render_template( [
            'tournament'       => $this->create_default_tournament( [ 'is_open' => false, 'is_closed' => false ] ),
            'club_memberships' => $memberships,
        ] );
        $this->assert_element_attribute( $html_readonly, self::ID_CLUB_ID, self::ATTR_READONLY, true );

        // Test NOT readonly (changes allowed: own player on open tournament)
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;
        $html_editable = $this->render_template( [
            'tournament'       => $this->create_default_tournament( [ 'is_open' => true ] ),
            'club_memberships' => $memberships,
        ] );
        $this->assert_element_attribute( $html_editable, self::ID_CLUB_ID, self::ATTR_READONLY, false );
    }

    public function test_singles_and_doubles_event_rendering_when_editable(): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $events = [
            (object) [
                'id'   => 101,
                'name' => self::EVENT_MENS_SINGLES,
                'type' => self::EVENT_CODE_MS,
            ],
            (object) [
                'id'   => 102,
                'name' => self::EVENT_MENS_DOUBLES,
                'type' => self::EVENT_CODE_MD,
            ],
        ];

        $html = $this->render_template( [
            'events' => $events,
        ] );

        // Singles event (101): not doubles, enabled checkbox, no partner controls
        self::assertMatchesRegularExpression( '/<input class="form-check-input form-check--event hasModal" id="' . preg_quote( self::ID_EVENT_101, '/' ) . '" name="event\[101]" type="checkbox" value="101" aria-controls="conditional-event-101"/', $html );
        $this->assert_element_attribute( $html, self::ID_EVENT_101, self::ATTR_DISABLED, false );
        $this->assert_element_attribute( $html, self::ID_EVENT_101, self::ATTR_CHECKED, false );
        self::assertMatchesRegularExpression( '/<div class="col-6\s*" id="conditional-event-101">/', $html );
        self::assertStringNotContainsString( 'id="partner-101"', $html );
        self::assertStringNotContainsString( 'id="partnerId-101"', $html );

        // Doubles event (102): is-doubles class, enabled checkbox, partner modal trigger and hidden inputs
        self::assertMatchesRegularExpression( '/<input class="form-check-input form-check--event hasModal" id="' . preg_quote( self::ID_EVENT_102, '/' ) . '" name="event\[102]" type="checkbox" value="102" aria-controls="conditional-event-102"/', $html );
        $this->assert_element_attribute( $html, self::ID_EVENT_102, self::ATTR_DISABLED, false );
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
                'name' => self::EVENT_MENS_SINGLES,
                'type' => self::EVENT_CODE_MS,
            ],
            (object) [
                'id'   => 102,
                'name' => self::EVENT_MENS_DOUBLES,
                'type' => self::EVENT_CODE_MD,
            ],
        ];

        $html = $this->render_template( [
            'events' => $events,
        ] );

        // Both checkboxes should be disabled
        $this->assert_element_attribute( $html, self::ID_EVENT_101, self::ATTR_DISABLED, true );
        $this->assert_element_attribute( $html, self::ID_EVENT_102, self::ATTR_DISABLED, true );

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
                'name' => self::EVENT_MENS_SINGLES,
                'type' => self::EVENT_CODE_MS,
            ],
            (object) [
                'id'   => 102,
                'name' => self::EVENT_MENS_DOUBLES,
                'type' => self::EVENT_CODE_MD,
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
        $this->assert_element_attribute( $html, self::ID_EVENT_101, self::ATTR_CHECKED, true );
        $this->assert_element_attribute( $html, self::ID_EVENT_101, self::ATTR_DISABLED, false );
        self::assertMatchesRegularExpression( '/<span class="event-price" id="event-price-fmt-101">\s*£10\s*<\/span>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" class="event-price-amt" name="event-price\[101]" id="event-price-101" value="10"\s*\/>/', $html );

        // Event 102 is NOT entered: unchecked, and disabled because tournament is closed
        $this->assert_element_attribute( $html, self::ID_EVENT_102, self::ATTR_CHECKED, false );
        $this->assert_element_attribute( $html, self::ID_EVENT_102, self::ATTR_DISABLED, true );
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
                'name' => self::EVENT_MENS_DOUBLES,
                'type' => self::EVENT_CODE_MD,
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
        $this->assert_element_attribute( $html, self::ID_EVENT_102, self::ATTR_CHECKED, true );

        // Partner inputs and display
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partner\[102]" id="partner-102" value="88"\s*\/>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="partnerId\[102]" id="partnerId-102" value="88"\s*\/>/', $html );
        self::assertStringContainsString( 'Andy Murray', $html );
        self::assertMatchesRegularExpression( '/<span id="partnerName-102">Andy Murray<\/span>/', $html );

        // Payments displayed
        self::assertMatchesRegularExpression( '/<div class="price-cost" id="pricePaidTotalFmt">\s*Paid:\s*£15\s*<\/div>/', $html );
        self::assertMatchesRegularExpression( '/<input type="hidden" name="pricePaidTotal" id="pricePaidTotal" value="15"\s*\/>/', $html );
    }

    #[DataProvider( 'provide_event_fee_visibility_cases' )]
    public function test_event_fee_visibility_preserves_zero_and_empty_comparison_semantics( mixed $event_fee, bool $expected_visible ): void {
        $GLOBALS['wp_stubs_current_user_id']  = 42;
        $GLOBALS['wp_stubs_current_user_can'] = false;

        $tournament = $this->create_default_tournament();
        $tournament->fees->event = $event_fee;

        $html = $this->render_template( [ 'tournament' => $tournament ] );
        $fee_markup_pattern = '/<span class="event-price" id="event-price-fmt-101">/';

        self::assertSame( $expected_visible, 1 === preg_match( $fee_markup_pattern, $html ), sprintf( 'Unexpected event fee visibility for %s.', var_export( $event_fee, true ) ) );
    }

    public static function provide_event_fee_visibility_cases(): array {
        return [
            'zero numeric fee is visible'     => [ 0, true ],
            'null fee is not visible'         => [ null, false ],
            'empty string fee is not visible' => [ '', false ],
        ];
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
