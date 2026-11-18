<?php
declare( strict_types=1 );
/**
 * Overview Page View Model
 *
 * @package RacketManager
 * @subpackage Admin/View_Models
 */

namespace Racketmanager\Admin\View_Models;

/**
 * Class Overview_Page_View_Model
 *
 * Holds presentation state for the frontend portal overview screen explaining the service and options.
 */
final readonly class Overview_Page_View_Model {

    /**
     * @var array<Overview_Option_Item>
     */
    public array $options;

    /**
     * @var array<string, string>
     */
    public array $service_pillars;

    /**
     * @param string                           $title           Page title.
     * @param string                           $service_name    Platform/service name.
     * @param string                           $service_summary Overview description of the service.
     * @param array<string, string>            $service_pillars Core functional pillars.
     * @param array<Overview_Option_Item>|null $options         Available management options.
     */
    public function __construct(
        public string $title = 'Overview',
        public string $service_name = 'RacketManager',
        public string $service_summary = 'RacketManager is a centralized administrative platform designed to manage racket sports leagues, knockout cups, tournaments, and club competitions. It streamlines scheduling, scorecard verification, player eligibility, financial accounting, and league standings for administrators, match secretaries, and players.',
        array $service_pillars = [],
        ?array $options = null,
    ) {
        $this->service_pillars = ! empty( $service_pillars )
            ? $service_pillars
            : [
                'Competitions & Formats'    => 'Orchestrate structured leagues, knockout cup brackets, and seeded tournaments with flexible scoring formats and division structures.',
                'Scorecard & Rule Checking' => 'Automate match validation, eligibility enforcement, lead-time rules, appearance counts, and approval workflows.',
                'Clubs, Teams & Players'    => 'Maintain venue directories, squad rosters, player transfers, and registration request processing in real time.',
                'Financial Operations'      => 'Manage league entry fees, club charges, payment recording, and automated PDF invoice generation.',
            ];

        $this->options = null !== $options ? $options : $this->build_default_options();
    }

    /**
     * Build the default list of management options explaining the service capabilities.
     *
     * @return array<Overview_Option_Item>
     */
    private function build_default_options(): array {
        return [
            new Overview_Option_Item(
                'competitions',
                'Competitions',
                'Competition Management',
                'Configure tournament and league competitions, modes of play, entry specifications, and demographic categories.',
                $this->resolve_url( 'competitions' ),
                'Core',
                [ 'Championship, League & Cup modes', 'Club & individual entry levels', 'Demographic age groupings' ]
            ),
            new Overview_Option_Item(
                'leagues',
                'Leagues',
                'Competition Management',
                'Administer multi-division leagues, assign team constitutions, generate schedules, and track live standings.',
                $this->resolve_url( 'leagues' ),
                'Competitive',
                [ 'Division constitutions & seeding', 'Automated round-robin scheduling', 'Bonus points & dynamic league tables' ]
            ),
            new Overview_Option_Item(
                'cups',
                'Cups',
                'Competition Management',
                'Orchestrate single-elimination and knockout cup competitions with preliminary stages and dynamic brackets.',
                $this->resolve_url( 'cups' ),
                'Competitive',
                [ 'Knockout bracket generation', 'Preliminary round configurations', 'Championship finals integration' ]
            ),
            new Overview_Option_Item(
                'tournaments',
                'Tournaments',
                'Competition Management',
                'Coordinate seeded tournaments, individual player draws, order of play, and championship finals.',
                $this->resolve_url( 'tournaments' ),
                'Competitive',
                [ 'Seeded singles & doubles draws', 'Daily order of play scheduling', 'Tournament plan & event configuration' ]
            ),
            new Overview_Option_Item(
                'clubs',
                'Clubs & Teams',
                'Club & Match Operations',
                'Maintain club directories, venue facilities, match secretaries, team squads, and administrative roles.',
                $this->resolve_url( 'clubs' ),
                'Operations',
                [ 'Club contacts & venue details', 'Team squads & player allocations', 'Secretary & administrative roles' ]
            ),
            new Overview_Option_Item(
                'results',
                'Results & Checker',
                'Club & Match Operations',
                'Review submitted scorecards, verify match outcomes, resolve disputes, and run automated eligibility checks.',
                $this->resolve_url( 'results' ),
                'Operations',
                [ 'Scorecard review & confirmation', 'Automated player eligibility checks', 'Challenged results & dispute audit' ]
            ),
            new Overview_Option_Item(
                'players',
                'Players',
                'Club & Match Operations',
                'Oversee the master player registry, review club registration requests, and merge duplicate profiles.',
                $this->resolve_url( 'players' ),
                'Operations',
                [ 'Player registry & statistics', 'Club registration requests', 'Duplicate profile detection & merging' ]
            ),
            new Overview_Option_Item(
                'seasons',
                'Seasons',
                'Administration & Setup',
                'Define seasonal periods, match days, date configurations, and historical season archives.',
                $this->resolve_url( 'seasons' ),
                'Setup',
                [ 'Seasonal period management', 'Match day configurations', 'Historical competition archiving' ]
            ),
            new Overview_Option_Item(
                'finances',
                'Finances & Invoices',
                'Administration & Setup',
                'Track league fees, manage club charges, record payments, and generate professional PDF invoices.',
                $this->resolve_url( 'finances' ),
                'Financial',
                [ 'Club charge itemization', 'Invoice generation & PDF export', 'Payment status & balance tracking' ]
            ),
            new Overview_Option_Item(
                'settings',
                'Settings & Rules',
                'Administration & Setup',
                'Configure global league rules, scoring systems, result submission permissions, and email notifications.',
                $this->resolve_url( 'settings' ),
                'System',
                [ 'Scoring rules & point systems', 'Lead times & eligibility thresholds', 'Notification email routing' ]
            ),
            new Overview_Option_Item(
                'import',
                'Import Tools',
                'Administration & Setup',
                'Safely import clubs, teams, players, and match schedules in bulk from structured external datasets.',
                $this->resolve_url( 'import' ),
                'Tools',
                [ 'Bulk dataset processing', 'Clubs & player roster import', 'Historical data reconciliation' ]
            ),
            new Overview_Option_Item(
                'documentation',
                'Documentation',
                'Administration & Setup',
                'Comprehensive operational guides and instructions for administrators, match secretaries, and players.',
                $this->resolve_url( 'documentation' ),
                'Reference',
                [ 'Administrator reference guide', 'Results entry & checker workflow', 'System architecture guidelines' ]
            ),
        ];
    }

    /**
     * Resolve management URL safely.
     *
     * @param string $section Section slug.
     *
     * @return string
     */
    private function resolve_url( string $section ): string {
        if ( function_exists( 'manage_url' ) ) {
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
