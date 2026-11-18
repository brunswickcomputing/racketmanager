<?php
declare( strict_types=1 );
/**
 * Overview Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

use function Racketmanager\manage_url;

/**
 * Class Overview_Page_View_Model
 *
 * Holds presentation state for the frontend portal overview screen explaining the service and options.
 */
final readonly class Overview_Page_View_Model {

    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $service_name;

    /**
     * @var string
     */
    public string $service_summary;

    /**
     * @var array<Overview_Option_Item>
     */
    public array $options;

    /**
     * @var array<string, string>
     */
    public array $service_pillars;

    /**
     * @param string|null                      $title           Page title.
     * @param string|null                      $service_name    Platform/service name.
     * @param string|null                      $service_summary Overview description of the service.
     * @param array<string, string>            $service_pillars Core functional pillars.
     * @param array<Overview_Option_Item>|null $options         Available management options.
     */
    public function __construct(
        ?string $title = null,
        ?string $service_name = null,
        ?string $service_summary = null,
        array $service_pillars = [],
        ?array $options = null,
    ) {
        $this->title           = $title ?? __( 'Overview', 'racketmanager' );
        $this->service_name    = $service_name ?? 'RacketManager';
        $this->service_summary = $service_summary ?? __( 'RacketManager is a centralized administrative platform designed for system and league administrators to govern racket sports competitions, knockout cups, tournaments, and club structures. It streamlines competition configuration, scorecard audits, player eligibility enforcement, club accounts, and seasonal lifecycle management.', 'racketmanager' );
        $this->service_pillars = ! empty( $service_pillars )
            ? $service_pillars
            : [
                __( 'Competition Governance', 'racketmanager' )   => __( 'Configure multi-division leagues, knockout cup brackets, and seeded tournaments with flexible scoring parameters and division structures.', 'racketmanager' ),
                __( 'Scorecard & Eligibility', 'racketmanager' )  => __( 'Audit match scorecards, enforce player eligibility rules, review disputed matches, and validate sanction lead times.', 'racketmanager' ),
                __( 'Club & Player Registries', 'racketmanager' ) => __( 'Maintain master club registries, venue directories, team allocations, player profiles, and duplicate account merges.', 'racketmanager' ),
                __( 'System & Fiscal Control', 'racketmanager' )  => __( 'Manage seasonal rollovers, league entry fees, club balance ledgers, and automated PDF invoice generation.', 'racketmanager' ),
            ];

        $this->options = null !== $options ? $options : $this->build_default_options();
    }

    /**
     * Build the default list of management options explaining the service capabilities.
     *
     * @return array<Overview_Option_Item>
     */
    private function build_default_options(): array {
        $categories = [
            'comp' => __( 'Competition Management', 'racketmanager' ),
            'org'  => __( 'Organization & Membership', 'racketmanager' ),
            'ver'  => __( 'Match Audits & Verification', 'racketmanager' ),
            'gov'  => __( 'System & Financial Governance', 'racketmanager' ),
        ];

        $badges = [
            'comp' => __( 'Competitive', 'racketmanager' ),
            'reg'  => __( 'Registry', 'racketmanager' ),
            'aud'  => __( 'Auditing', 'racketmanager' ),
            'life' => __( 'Lifecycle', 'racketmanager' ),
            'fin'  => __( 'Financial', 'racketmanager' ),
            'sys'  => __( 'System', 'racketmanager' ),
            'tool' => __( 'Tools', 'racketmanager' ),
            'ref'  => __( 'Reference', 'racketmanager' ),
        ];

        $items = [
            [ 'leagues', __( 'Leagues', 'racketmanager' ), 'comp', __( 'Administer multi-division leagues, assign team constitutions, generate schedules, and track live standings.', 'racketmanager' ), 'comp', [ __( 'Division constitutions & seeding', 'racketmanager' ), __( 'Automated round-robin scheduling', 'racketmanager' ), __( 'Bonus points & dynamic league tables', 'racketmanager' ) ] ],
            [ 'cups', __( 'Cups', 'racketmanager' ), 'comp', __( 'Orchestrate single-elimination and knockout cup competitions with preliminary stages and dynamic brackets.', 'racketmanager' ), 'comp', [ __( 'Knockout bracket generation', 'racketmanager' ), __( 'Preliminary round configurations', 'racketmanager' ), __( 'Championship finals integration', 'racketmanager' ) ] ],
            [ 'tournaments', __( 'Tournaments', 'racketmanager' ), 'comp', __( 'Coordinate seeded tournaments, individual player draws, order of play, and championship finals.', 'racketmanager' ), 'comp', [ __( 'Seeded singles & doubles draws', 'racketmanager' ), __( 'Daily order of play scheduling', 'racketmanager' ), __( 'Tournament plan & event configuration', 'racketmanager' ) ] ],
            [ 'clubs', __( 'Clubs & Teams', 'racketmanager' ), 'org', __( 'Maintain club directories, venue facilities, contact officers, team squads, and administrative allocations.', 'racketmanager' ), 'reg', [ __( 'Club contacts & venue details', 'racketmanager' ), __( 'Team squads & player allocations', 'racketmanager' ), __( 'Club officers & administrative roles', 'racketmanager' ) ] ],
            [ 'players', __( 'Players', 'racketmanager' ), 'org', __( 'Oversee the master player registry, review club registration requests, and merge duplicate profiles.', 'racketmanager' ), 'reg', [ __( 'Master player registry & ratings', 'racketmanager' ), __( 'Club registration request approvals', 'racketmanager' ), __( 'Duplicate profile detection & merging', 'racketmanager' ) ] ],
            [ 'results', __( 'Results & Checker', 'racketmanager' ), 'ver', __( 'Review submitted scorecards, verify match outcomes, resolve disputes, and run automated eligibility checks.', 'racketmanager' ), 'aud', [ __( 'Scorecard review & confirmation', 'racketmanager' ), __( 'Automated player eligibility audits', 'racketmanager' ), __( 'Challenged results & dispute audit', 'racketmanager' ) ] ],
            [ 'seasons', __( 'Seasons', 'racketmanager' ), 'gov', __( 'Define seasonal periods, match days, date configurations, and historical season archives.', 'racketmanager' ), 'life', [ __( 'Seasonal period management', 'racketmanager' ), __( 'Match day configurations', 'racketmanager' ), __( 'Historical competition archiving', 'racketmanager' ) ] ],
            [ 'finances', __( 'Finances & Invoices', 'racketmanager' ), 'gov', __( 'Track league fees, manage club charges, record payments, and generate professional PDF invoices.', 'racketmanager' ), 'fin', [ __( 'Club charge itemization', 'racketmanager' ), __( 'Invoice generation & PDF export', 'racketmanager' ), __( 'Payment status & balance tracking', 'racketmanager' ) ] ],
            [ 'settings', __( 'Settings & Rules', 'racketmanager' ), 'gov', __( 'Configure global league rules, scoring systems, result submission permissions, and email notifications.', 'racketmanager' ), 'sys', [ __( 'Scoring rules & point systems', 'racketmanager' ), __( 'Lead times & eligibility thresholds', 'racketmanager' ), __( 'Notification email routing', 'racketmanager' ) ] ],
            [ 'import', __( 'Import Tools', 'racketmanager' ), 'gov', __( 'Safely import clubs, teams, players, and match schedules in bulk from structured external datasets.', 'racketmanager' ), 'tool', [ __( 'Bulk dataset processing', 'racketmanager' ), __( 'Clubs & player roster import', 'racketmanager' ), __( 'Historical data reconciliation', 'racketmanager' ) ] ],
            [ 'documentation', __( 'Documentation', 'racketmanager' ), 'gov', __( 'Comprehensive operational manuals and technical guidelines for league and system administrators.', 'racketmanager' ), 'ref', [ __( 'Administrator operations manual', 'racketmanager' ), __( 'Results auditing & checker workflows', 'racketmanager' ), __( 'System governance guidelines', 'racketmanager' ) ] ],
        ];

        return array_map(
            fn( array $item ): Overview_Option_Item => new Overview_Option_Item(
                $item[0],
                $item[1],
                $categories[ $item[2] ],
                $item[3],
                $this->resolve_url( $item[0] ),
                $badges[ $item[4] ],
                $item[5],
            ),
            $items
        );
    }

    /**
     * Resolve management URL safely.
     *
     * @param string $section Section slug.
     *
     * @return string
     */
    private function resolve_url( string $section ): string {
        if ( function_exists( 'Racketmanager\manage_url' ) ) {
            return manage_url( $section );
        }
        if ( function_exists( 'home_url' ) ) {
            return home_url( '/manage/' . $section );
        }
        return '/manage/' . $section;
    }

    /**
     * Convert to template variables array.
     *
     * @return array<string, mixed>
     */
    public function to_template_vars(): array {
        return [
            'title'           => $this->title,
            'service_name'    => $this->service_name,
            'service_summary' => $this->service_summary,
            'service_pillars' => $this->service_pillars,
            'options'         => $this->options,
        ];
    }
}
