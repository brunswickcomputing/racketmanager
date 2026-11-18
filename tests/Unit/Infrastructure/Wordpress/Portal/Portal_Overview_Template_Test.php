<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Overview_Option_Item;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Php_View_Renderer;

final class Portal_Overview_Template_Test extends TestCase {

    private const string EXPECTED_SERVICE_SUMMARY = 'RacketManager is a centralized administrative platform designed for system and league administrators to govern racket sports competitions, knockout cups, tournaments, and club structures. It streamlines competition configuration, scorecard audits, player eligibility enforcement, club accounts, and seasonal lifecycle management.';

    private const array EXPECTED_SERVICE_PILLARS = [
        'Competition Governance'   => 'Configure multi-division leagues, knockout cup brackets, and seeded tournaments with flexible scoring parameters and division structures.',
        'Scorecard & Eligibility'  => 'Audit match scorecards, enforce player eligibility rules, review disputed matches, and validate sanction lead times.',
        'Club & Player Registries' => 'Maintain master club registries, venue directories, team allocations, player profiles, and duplicate account merges.',
        'System & Fiscal Control'  => 'Manage seasonal rollovers, league entry fees, club balance ledgers, and automated PDF invoice generation.',
    ];

    private const array EXPECTED_OPTIONS = [
        'leagues'       => [
            'title'        => 'Leagues',
            'category'     => 'Competition Management',
            'description'  => 'Administer multi-division leagues, assign team constitutions, generate schedules, and track live standings.',
            'badge'        => 'Competitive',
            'capabilities' => [
                'Division constitutions & seeding',
                'Automated round-robin scheduling',
                'Bonus points & dynamic league tables',
            ],
        ],
        'cups'          => [
            'title'        => 'Cups',
            'category'     => 'Competition Management',
            'description'  => 'Orchestrate single-elimination and knockout cup competitions with preliminary stages and dynamic brackets.',
            'badge'        => 'Competitive',
            'capabilities' => [
                'Knockout bracket generation',
                'Preliminary round configurations',
                'Championship finals integration',
            ],
        ],
        'tournaments'   => [
            'title'        => 'Tournaments',
            'category'     => 'Competition Management',
            'description'  => 'Coordinate seeded tournaments, individual player draws, order of play, and championship finals.',
            'badge'        => 'Competitive',
            'capabilities' => [
                'Seeded singles & doubles draws',
                'Daily order of play scheduling',
                'Tournament plan & event configuration',
            ],
        ],
        'clubs'         => [
            'title'        => 'Clubs & Teams',
            'category'     => 'Organization & Membership',
            'description'  => 'Maintain club directories, venue facilities, contact officers, team squads, and administrative allocations.',
            'badge'        => 'Registry',
            'capabilities' => [
                'Club contacts & venue details',
                'Team squads & player allocations',
                'Club officers & administrative roles',
            ],
        ],
        'players'       => [
            'title'        => 'Players',
            'category'     => 'Organization & Membership',
            'description'  => 'Oversee the master player registry, review club registration requests, and merge duplicate profiles.',
            'badge'        => 'Registry',
            'capabilities' => [
                'Master player registry & ratings',
                'Club registration request approvals',
                'Duplicate profile detection & merging',
            ],
        ],
        'results'       => [
            'title'        => 'Results & Checker',
            'category'     => 'Match Audits & Verification',
            'description'  => 'Review submitted scorecards, verify match outcomes, resolve disputes, and run automated eligibility checks.',
            'badge'        => 'Auditing',
            'capabilities' => [
                'Scorecard review & confirmation',
                'Automated player eligibility audits',
                'Challenged results & dispute audit',
            ],
        ],
        'seasons'       => [
            'title'        => 'Seasons',
            'category'     => 'System & Financial Governance',
            'description'  => 'Define seasonal periods, match days, date configurations, and historical season archives.',
            'badge'        => 'Lifecycle',
            'capabilities' => [
                'Seasonal period management',
                'Match day configurations',
                'Historical competition archiving',
            ],
        ],
        'finances'      => [
            'title'        => 'Finances & Invoices',
            'category'     => 'System & Financial Governance',
            'description'  => 'Track league fees, manage club charges, record payments, and generate professional PDF invoices.',
            'badge'        => 'Financial',
            'capabilities' => [
                'Club charge itemization',
                'Invoice generation & PDF export',
                'Payment status & balance tracking',
            ],
        ],
        'settings'      => [
            'title'        => 'Settings & Rules',
            'category'     => 'System & Financial Governance',
            'description'  => 'Configure global league rules, scoring systems, result submission permissions, and email notifications.',
            'badge'        => 'System',
            'capabilities' => [
                'Scoring rules & point systems',
                'Lead times & eligibility thresholds',
                'Notification email routing',
            ],
        ],
        'import'        => [
            'title'        => 'Import Tools',
            'category'     => 'System & Financial Governance',
            'description'  => 'Safely import clubs, teams, players, and match schedules in bulk from structured external datasets.',
            'badge'        => 'Tools',
            'capabilities' => [
                'Bulk dataset processing',
                'Clubs & player roster import',
                'Historical data reconciliation',
            ],
        ],
        'documentation' => [
            'title'        => 'Documentation',
            'category'     => 'System & Financial Governance',
            'description'  => 'Comprehensive operational manuals and technical guidelines for league and system administrators.',
            'badge'        => 'Reference',
            'capabilities' => [
                'Administrator operations manual',
                'Results auditing & checker workflows',
                'System governance guidelines',
            ],
        ],
    ];

    private Simple_Container $container;
    private Manage_Url_Resolver $resolver;
    private Php_View_Renderer $renderer;
    private mixed $previous_translate_callback = null;

    protected function setUp(): void {
        parent::setUp();
        global $racketmanager;
        $this->container                   = new Simple_Container();
        $this->resolver                    = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        $this->container->set( Manage_Url_Resolver_Interface::class, fn() => $this->resolver );
        $racketmanager                     = (object) [ 'container' => $this->container ];
        $this->previous_translate_callback = $GLOBALS['wp_stubs_translate_callback'] ?? null;

        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    protected function tearDown(): void {
        global $racketmanager;
        $racketmanager = null;
        if ( null === $this->previous_translate_callback ) {
            unset( $GLOBALS['wp_stubs_translate_callback'] );
        } else {
            $GLOBALS['wp_stubs_translate_callback'] = $this->previous_translate_callback;
        }
        parent::tearDown();
    }

    public function test_overview_view_model_constructs_default_service_options(): void {
        $vm = new Overview_Page_View_Model();

        $this->assertSame( 'Overview', $vm->title );
        $this->assertSame( 'RacketManager', $vm->service_name );
        $this->assertSame( self::EXPECTED_SERVICE_SUMMARY, $vm->service_summary );
        $this->assertSame( self::EXPECTED_SERVICE_PILLARS, $vm->service_pillars );
        $this->assertCount( 11, $vm->options );

        // Ensure no references to captains or match secretaries remain in overview text.
        $this->assertStringNotContainsStringIgnoringCase( 'captain', $vm->service_summary );
        $this->assertStringNotContainsStringIgnoringCase( 'secretar', $vm->service_summary );
        foreach ( $vm->service_pillars as $pillar_title => $pillar_desc ) {
            $this->assertStringNotContainsStringIgnoringCase( 'captain', $pillar_title );
            $this->assertStringNotContainsStringIgnoringCase( 'secretar', $pillar_title );
            $this->assertStringNotContainsStringIgnoringCase( 'captain', $pillar_desc );
            $this->assertStringNotContainsStringIgnoringCase( 'secretar', $pillar_desc );
        }
        foreach ( $vm->options as $option ) {
            $this->assertStringNotContainsStringIgnoringCase( 'captain', $option->description );
            $this->assertStringNotContainsStringIgnoringCase( 'secretar', $option->description );
            foreach ( $option->capabilities as $cap ) {
                $this->assertStringNotContainsStringIgnoringCase( 'captain', $cap );
                $this->assertStringNotContainsStringIgnoringCase( 'secretar', $cap );
            }
        }

        $expected_slugs = array_keys( self::EXPECTED_OPTIONS );

        $actual_slugs = array_map( static fn( Overview_Option_Item $opt ) => $opt->slug, $vm->options );
        $this->assertSame( $expected_slugs, $actual_slugs );
        $this->assertNotContains( 'competitions', $actual_slugs );

        foreach ( $vm->options as $option ) {
            $this->assertArrayHasKey( $option->slug, self::EXPECTED_OPTIONS );
            $expected = self::EXPECTED_OPTIONS[ $option->slug ];

            $this->assertSame( $expected['title'], $option->title, sprintf( 'Default title mismatch for %s', $option->slug ) );
            $this->assertSame( $expected['category'], $option->category, sprintf( 'Default category mismatch for %s', $option->slug ) );
            $this->assertSame( $expected['badge'], $option->badge, sprintf( 'Default badge mismatch for %s', $option->slug ) );
            $this->assertSame( $expected['description'], $option->description, sprintf( 'Default description mismatch for %s', $option->slug ) );
            $this->assertSame( $expected['capabilities'], $option->capabilities, sprintf( 'Default capabilities mismatch for %s', $option->slug ) );
        }

        $template_vars = $vm->to_template_vars();
        $this->assertArrayHasKey( 'title', $template_vars );
        $this->assertArrayHasKey( 'service_summary', $template_vars );
        $this->assertArrayHasKey( 'options', $template_vars );
    }

    public function test_overview_option_item_to_array(): void {
        $item = new Overview_Option_Item(
            'leagues',
            'Leagues',
            'Competition Management',
            'Manage divisions and schedules',
            'https://example.test/manage/leagues',
            'Competitive',
            [ 'Feature 1', 'Feature 2' ]
        );

        $array = $item->to_array();
        $this->assertSame( 'leagues', $array['slug'] );
        $this->assertSame( 'Leagues', $array['title'] );
        $this->assertSame( 'Competition Management', $array['category'] );
        $this->assertSame( 'Competitive', $array['badge'] );
        $this->assertSame( [ 'Feature 1', 'Feature 2' ], $array['capabilities'] );
    }

    public function test_renders_portal_overview_template(): void {
        $vm   = new Overview_Page_View_Model();
        $html = $this->renderer->render_to_string( 'portal/overview', $vm );

        $this->assertStringContainsString( 'RacketManager &mdash; Overview', $html );

        // Guard against captain/secretary wording in rendered template
        $this->assertStringNotContainsStringIgnoringCase( 'captain', $html );
        $this->assertStringNotContainsStringIgnoringCase( 'secretar', $html );

        // Verify Quick Start callout and its CTA are absent from overview template
        $this->assertStringNotContainsStringIgnoringCase( 'Quick Start', $html );
        $this->assertStringNotContainsString( 'Competition Configurations', $html );
        $this->assertStringNotContainsString( '/manage/competitions', $html );

        // Verify administrative pillars rendered in expected order
        preg_match_all( '#<h3 class="h6 fw-bold text-primary mb-2">([^<]+)</h3>#', $html, $pillar_matches );
        $rendered_pillars = array_map( static fn( string $t ) => html_entity_decode( trim( $t ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $pillar_matches[1] );
        $this->assertSame(
            [
                'Competition Governance',
                'Scorecard & Eligibility',
                'Club & Player Registries',
                'System & Fiscal Control',
            ],
            $rendered_pillars
        );

        // Verify category headings rendered in exact order
        preg_match_all( '#<h2 class="h4 pb-2 mb-3 border-bottom">([^<]+)</h2>#', $html, $category_matches );
        $rendered_categories = array_map( static fn( string $t ) => html_entity_decode( trim( $t ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $category_matches[1] );
        $this->assertSame(
            [
                'Competition Management',
                'Organization & Membership',
                'Match Audits & Verification',
                'System & Financial Governance',
            ],
            $rendered_categories
        );

        // Verify option card titles rendered in exact admin lifecycle sequence (without standalone Competitions card)
        preg_match_all( '#<h3 class="h5 mb-0 text-dark">([^<]+)</h3>#', $html, $card_matches );
        $rendered_card_titles = array_map( static fn( string $t ) => html_entity_decode( trim( $t ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), $card_matches[1] );
        $this->assertSame(
            [
                'Leagues',
                'Cups',
                'Tournaments',
                'Clubs & Teams',
                'Players',
                'Results & Checker',
                'Seasons',
                'Finances & Invoices',
                'Settings & Rules',
                'Import Tools',
                'Documentation',
            ],
            $rendered_card_titles
        );
        $this->assertNotContains( 'Competitions', $rendered_card_titles );

        // Verify "Open %s" button links with respective URLs in exact sequence
        preg_match_all( '#<a\s+href="([^"]+)"\s+class="btn btn-primary btn-sm w-100">\s*(?:Open\s+([^&]+)\s*&rarr;)?#s', $html, $action_matches, PREG_SET_ORDER );
        $expected_urls = [
            home_url( '/manage/leagues' ),
            home_url( '/manage/cups' ),
            home_url( '/manage/tournaments' ),
            home_url( '/manage/clubs' ),
            home_url( '/manage/players' ),
            home_url( '/manage/results' ),
            home_url( '/manage/seasons' ),
            home_url( '/manage/finances' ),
            home_url( '/manage/settings' ),
            home_url( '/manage/import' ),
            home_url( '/manage/documentation' ),
        ];
        $actual_urls = array_map( static fn( array $m ) => $m[1], $action_matches );
        $this->assertSame( $expected_urls, $actual_urls );
    }

    public function test_overview_view_model_uses_gettext_for_all_default_strings(): void {
        $previous_callback                      = $GLOBALS['wp_stubs_translate_callback'] ?? null;
        $translated_calls                       = [];
        $GLOBALS['wp_stubs_translate_callback'] = static function ( string $text, string $domain = '' ) use ( &$translated_calls ): string {
            $translated_calls[] = [
                'text'   => $text,
                'domain' => $domain,
            ];
            return 'tx:' . $text;
        };

        try {
            $vm = new Overview_Page_View_Model();

            // 1. Page title and service summary must be translated with exact text.
            $this->assertSame( 'tx:Overview', $vm->title );
            $this->assertSame( 'tx:' . self::EXPECTED_SERVICE_SUMMARY, $vm->service_summary );

            // 2. Pillar titles and descriptions must be translated with exact text.
            $this->assertCount( count( self::EXPECTED_SERVICE_PILLARS ), $vm->service_pillars );
            foreach ( self::EXPECTED_SERVICE_PILLARS as $expected_pillar_title => $expected_pillar_desc ) {
                $tx_title = 'tx:' . $expected_pillar_title;
                $this->assertArrayHasKey( $tx_title, $vm->service_pillars, sprintf( 'Pillar title "%s" should be translated', $expected_pillar_title ) );
                $this->assertSame( 'tx:' . $expected_pillar_desc, $vm->service_pillars[ $tx_title ], sprintf( 'Pillar description for "%s" should be translated', $expected_pillar_title ) );
            }

            // 3. Option categories, badges, titles, descriptions, and capabilities must be translated with exact text.
            $this->assertCount( count( self::EXPECTED_OPTIONS ), $vm->options );
            foreach ( $vm->options as $option ) {
                $this->assertArrayHasKey( $option->slug, self::EXPECTED_OPTIONS );
                $expected = self::EXPECTED_OPTIONS[ $option->slug ];

                $this->assertSame( 'tx:' . $expected['title'], $option->title, sprintf( 'Option title "%s" should be translated', $option->title ) );
                $this->assertSame( 'tx:' . $expected['category'], $option->category, sprintf( 'Option category "%s" should be translated', $option->category ) );
                $this->assertSame( 'tx:' . $expected['badge'], $option->badge, sprintf( 'Option badge "%s" should be translated', $option->badge ) );
                $this->assertSame( 'tx:' . $expected['description'], $option->description, sprintf( 'Option description for "%s" should be translated', $option->slug ) );

                $expected_caps = array_map( static fn( string $cap ) => 'tx:' . $cap, $expected['capabilities'] );
                $this->assertSame( $expected_caps, $option->capabilities, sprintf( 'Capabilities for option "%s" should be translated', $option->slug ) );
            }

            // 4. Verify all translations used the 'racketmanager' domain.
            $this->assertNotEmpty( $translated_calls );
            foreach ( $translated_calls as $call ) {
                $this->assertSame( 'racketmanager', $call['domain'], sprintf( 'Expected "racketmanager" domain for string "%s"', $call['text'] ) );
            }

            // 5. Verify consolidation/deduplication: categories and badges translated exactly once.
            $text_counts = array_count_values( array_column( $translated_calls, 'text' ) );

            $expected_deduplicated_categories = [
                'Competition Management',
                'Organization & Membership',
                'Match Audits & Verification',
                'System & Financial Governance',
            ];
            foreach ( $expected_deduplicated_categories as $cat ) {
                $this->assertArrayHasKey( $cat, $text_counts );
                $this->assertSame( 1, $text_counts[ $cat ], sprintf( 'Category "%s" should only be translated once (deduplicated)', $cat ) );
            }

            $expected_deduplicated_badges = [
                'Competitive',
                'Registry',
                'Auditing',
                'Lifecycle',
                'Financial',
                'System',
                'Tools',
                'Reference',
            ];
            foreach ( $expected_deduplicated_badges as $badge ) {
                $this->assertArrayHasKey( $badge, $text_counts );
                $this->assertSame( 1, $text_counts[ $badge ], sprintf( 'Badge "%s" should only be translated once (deduplicated)', $badge ) );
            }
        } finally {
            if ( null === $previous_callback ) {
                unset( $GLOBALS['wp_stubs_translate_callback'] );
            } else {
                $GLOBALS['wp_stubs_translate_callback'] = $previous_callback;
            }
        }
    }

    public function test_overview_view_model_supports_custom_constructor_overrides(): void {
        $custom_pillars = [
            'Custom Pillar Title' => 'Custom Pillar Description',
        ];
        $custom_option  = new Overview_Option_Item(
            'custom-slug',
            'Custom Option Title',
            'Custom Category',
            'Custom Option Description',
            'https://example.test/manage/custom',
            'Custom Badge',
            [ 'Custom Capability 1', 'Custom Capability 2' ]
        );

        $vm = new Overview_Page_View_Model(
            title: 'Custom Title',
            service_name: 'Custom Service',
            service_summary: 'Custom summary statement.',
            service_pillars: $custom_pillars,
            options: [ $custom_option ]
        );

        $this->assertSame( 'Custom Title', $vm->title );
        $this->assertSame( 'Custom Service', $vm->service_name );
        $this->assertSame( 'Custom summary statement.', $vm->service_summary );
        $this->assertSame( $custom_pillars, $vm->service_pillars );
        $this->assertSame( [ $custom_option ], $vm->options );

        // Render template with custom view model to verify presentation compatibility.
        $html = $this->renderer->render_to_string( 'portal/overview', $vm );

        $this->assertStringContainsString( 'Custom Service &mdash; Custom Title', $html );
        $this->assertStringContainsString( 'Custom summary statement.', $html );
        $this->assertStringContainsString( 'Custom Pillar Title', $html );
        $this->assertStringContainsString( 'Custom Pillar Description', $html );
        $this->assertStringContainsString( 'Custom Option Title', $html );
        $this->assertStringContainsString( 'Custom Category', $html );
        $this->assertStringContainsString( 'Custom Badge', $html );
        $this->assertStringContainsString( 'Custom Option Description', $html );
        $this->assertStringContainsString( 'Custom Capability 1', $html );
        $this->assertStringContainsString( 'Custom Capability 2', $html );
        $this->assertStringContainsString( 'https://example.test/manage/custom', $html );
    }

    public function test_overview_view_model_constructor_empty_value_and_null_semantics(): void {
        // Empty string and empty array semantics.
        $vm_empty = new Overview_Page_View_Model(
            title: '',
            service_name: '',
            service_summary: '',
            service_pillars: [],
            options: []
        );

        $this->assertSame( '', $vm_empty->title );
        $this->assertSame( '', $vm_empty->service_name );
        $this->assertSame( '', $vm_empty->service_summary );
        // Empty service pillars array triggers fallback to 4 default pillars.
        $this->assertCount( 4, $vm_empty->service_pillars );
        $this->assertArrayHasKey( 'Competition Governance', $vm_empty->service_pillars );
        // Empty options array preserves empty list.
        $this->assertSame( [], $vm_empty->options );

        // Explicit null arguments fall back to translated defaults.
        $vm_nulls = new Overview_Page_View_Model(
            title: null,
            service_name: null,
            service_summary: null,
            service_pillars: [],
            options: null
        );

        $this->assertSame( 'Overview', $vm_nulls->title );
        $this->assertSame( 'RacketManager', $vm_nulls->service_name );
        $this->assertSame( self::EXPECTED_SERVICE_SUMMARY, $vm_nulls->service_summary );
        $this->assertSame( self::EXPECTED_SERVICE_PILLARS, $vm_nulls->service_pillars );
        $this->assertCount( 11, $vm_nulls->options );

        // Positional constructor arguments compatibility.
        $vm_positional = new Overview_Page_View_Model(
            'Positional Title',
            'Positional Service',
            'Positional Summary',
            [ 'Pos Pillar' => 'Pos Desc' ],
            []
        );

        $this->assertSame( 'Positional Title', $vm_positional->title );
        $this->assertSame( 'Positional Service', $vm_positional->service_name );
        $this->assertSame( 'Positional Summary', $vm_positional->service_summary );
        $this->assertSame( [ 'Pos Pillar' => 'Pos Desc' ], $vm_positional->service_pillars );
        $this->assertSame( [], $vm_positional->options );
    }
}
