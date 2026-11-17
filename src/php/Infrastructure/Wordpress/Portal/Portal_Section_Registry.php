<?php
declare( strict_types=1 );
/**
 * Portal Section Registry
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

use Racketmanager\Admin\Admin_Club;
use Racketmanager\Admin\Admin_Cup;
use Racketmanager\Admin\Admin_Finances;
use Racketmanager\Admin\Admin_Import;
use Racketmanager\Admin\Admin_Index;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Admin\Admin_Options;
use Racketmanager\Admin\Admin_Player;
use Racketmanager\Admin\Admin_Result;
use Racketmanager\Admin\Admin_Season;
use Racketmanager\Admin\Admin_Tournament;
use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;

/**
 * Class Portal_Section_Registry
 *
 * Registry of all administrative management sections, their capabilities, and migration rollout status.
 */
final class Portal_Section_Registry {

    /**
     * Map of section slug to configuration.
     *
     * @var array<string, Portal_Section_Config>
     */
    private array $sections = [];

    /**
     * @param array<string, Portal_Section_Config>|null $custom_sections Optional custom section configs.
     */
    public function __construct( ?array $custom_sections = null ) {
        if ( null !== $custom_sections ) {
            foreach ( $custom_sections as $config ) {
                $this->register( $config );
            }
            return;
        }

        $this->register_default_sections();
    }

    /**
     * Populate standard RacketManager sections.
     */
    private function register_default_sections(): void {
        $defaults = [
            new Portal_Section_Config( 'competitions', 'racketmanager', Admin_Index::class, 'racket_manager', 'competitions', true, 'Competitions' ),
            new Portal_Section_Config( 'clubs', 'racketmanager-clubs', Admin_Club::class, 'racket_manager', 'clubs', true, 'Clubs' ),
            new Portal_Section_Config( 'leagues', 'racketmanager-leagues', Admin_League::class, 'racket_manager', 'leagues', false, 'Leagues' ),
            new Portal_Section_Config( 'cups', 'racketmanager-cups', Admin_Cup::class, 'racket_manager', 'cups', false, 'Cups' ),
            new Portal_Section_Config( 'tournaments', 'racketmanager-tournaments', Admin_Tournament::class, 'racket_manager', 'tournaments', false, 'Tournaments' ),
            new Portal_Section_Config( 'results', 'racketmanager-results', Admin_Result::class, 'racket_manager', 'results', false, 'Results' ),
            new Portal_Section_Config( 'players', 'racketmanager-players', Admin_Player::class, 'racket_manager', 'players', false, 'Players' ),
            new Portal_Section_Config( 'seasons', 'racketmanager-seasons', Admin_Season::class, 'racketmanager_settings', 'seasons', false, 'Seasons' ),
            new Portal_Section_Config( 'finances', 'racketmanager-finances', Admin_Finances::class, 'racket_manager', 'finances', false, 'Finances' ),
            new Portal_Section_Config( 'settings', 'racketmanager-settings', Admin_Options::class, 'racketmanager_settings', 'settings', false, 'Settings' ),
            new Portal_Section_Config( 'import', 'racketmanager-import', Admin_Import::class, 'import_leagues', 'import', false, 'Import' ),
            new Portal_Section_Config( 'documentation', 'racketmanager-doc', Documentation_Page_View_Model::class, 'view_leagues', 'documentation', false, 'Documentation' ),
        ];

        foreach ( $defaults as $config ) {
            $this->register( $config );
        }
    }

    /**
     * Normalise a slug for consistent lookup.
     *
     * @param string $slug Slug value to normalise.
     *
     * @return string Normalised slug value.
     */
    private function normalize_slug( string $slug ): string {
        $slug = strtolower( trim( $slug ) );
        if ( 'doc' === $slug ) {
            return 'documentation';
        }
        return $slug;
    }

    /**
     * Register a portal section configuration by its normalised slug.
     *
     * @param Portal_Section_Config $config Section configuration to register.
     *
     * @return void/**
     * */
    public function register( Portal_Section_Config $config ): void {
        $this->sections[ $this->normalize_slug( $config->slug ) ] = $config;
    }

    /**
     * Return the registered section for the given slug, if it exists.
     *
     * @param string $slug Section slug.
     *
     * @return Portal_Section_Config|null Matching section configuration, or null when not found.
     */
    public function get( string $slug ): ?Portal_Section_Config {
        return $this->sections[ $this->normalize_slug( $slug ) ] ?? null;
    }

    /**
     * Determine whether a section is registered for the given slug.
     *
     * @param string $slug Section slug to check.
     *
     * @return bool True if the section exists, false otherwise.
     */
    public function has( string $slug ): bool {
        return isset( $this->sections[ $this->normalize_slug( $slug ) ] );
    }

    /**
     * Determine whether the configuration for the given slug is marked as ported.
     *
     * @param string $slug Configuration slug.
     *
     * @return bool True if the configuration exists and is ported, false otherwise.
     */
    public function is_ported( string $slug ): bool {
        $config = $this->get( $slug );
        return $config ? $config->is_ported : false;
    }

    /**
     * Set whether the specified section has been ported.
     *
     * @param string $slug Section slug.
     * @param bool   $is_ported Whether the section is ported.
     *
     * @return void
     */
    public function set_ported( string $slug, bool $is_ported ): void {
        $config = $this->get( $slug );
        if ( $config ) {
            $this->sections[ $this->normalize_slug( $slug ) ] = $config->with_ported( $is_ported );
        }
    }

    /**
     * Return all registered sections.
     *
     * @return array<string, Portal_Section_Config>
     */
    public function all(): array {
        return $this->sections;
    }

    /**
     * Return only sections that have been ported to the portal shell.
     *
     * @return array<string, Portal_Section_Config>
     */
    public function get_ported_sections(): array {
        return array_filter(
            $this->sections,
            static fn( Portal_Section_Config $config ): bool => $config->is_ported
        );
    }
}
