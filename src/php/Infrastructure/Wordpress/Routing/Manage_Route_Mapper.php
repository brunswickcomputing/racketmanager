<?php
declare( strict_types=1 );
/**
 * Manage Route Mapper
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Routing
 */

namespace Racketmanager\Infrastructure\Wordpress\Routing;

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
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;

/**
 * Class Manage_Route_Mapper
 *
 * Maps clean frontend management section slugs to WordPress admin page slugs and screen classes.
 */
final readonly class Manage_Route_Mapper {

    /**
     * Map of section slug to admin page query slug.
     *
     * @var array<string, string>
     */
    private const array SECTION_TO_ADMIN_PAGE = [
        'overview'      => 'racketmanager-overview',
        'competitions'  => 'racketmanager',
        'clubs'         => 'racketmanager-clubs',
        'leagues'       => 'racketmanager-leagues',
        'cups'          => 'racketmanager-cups',
        'tournaments'   => 'racketmanager-tournaments',
        'results'       => 'racketmanager-results',
        'players'       => 'racketmanager-players',
        'seasons'       => 'racketmanager-seasons',
        'finances'      => 'racketmanager-finances',
        'settings'      => 'racketmanager-settings',
        'import'        => 'racketmanager-import',
        'documentation' => 'racketmanager-doc',
        'doc'           => 'racketmanager-doc',
    ];

    /**
     * Map of admin page query slug to clean section slug.
     *
     * @var array<string, string>
     */
    private const array ADMIN_PAGE_TO_SECTION = [
        'racketmanager-overview'      => 'overview',
        'racketmanager'               => 'competitions',
        'racketmanager-clubs'         => 'clubs',
        'racketmanager-leagues'       => 'leagues',
        'racketmanager-cups'          => 'cups',
        'racketmanager-tournaments'   => 'tournaments',
        'racketmanager-results'       => 'results',
        'racketmanager-players'       => 'players',
        'racketmanager-seasons'       => 'seasons',
        'racketmanager-finances'      => 'finances',
        'racketmanager-settings'      => 'settings',
        'racketmanager-import'        => 'import',
        'racketmanager-doc'           => 'documentation',
        'racketmanager-documentation' => 'documentation',
    ];

    /**
     * Map of section slug to handling screen class or view model.
     *
     * @var array<string, class-string>
     */
    private const array SECTION_TO_SCREEN_CLASS = [
        'overview'      => Overview_Page_View_Model::class,
        'competitions'  => Admin_Index::class,
        'clubs'         => Admin_Club::class,
        'leagues'       => Admin_League::class,
        'cups'          => Admin_Cup::class,
        'tournaments'   => Admin_Tournament::class,
        'results'       => Admin_Result::class,
        'players'       => Admin_Player::class,
        'seasons'       => Admin_Season::class,
        'finances'      => Admin_Finances::class,
        'settings'      => Admin_Options::class,
        'import'        => Admin_Import::class,
        'documentation' => Documentation_Page_View_Model::class,
        'doc'           => Documentation_Page_View_Model::class,
    ];

    /**
     * Normalize section slug alias (e.g. 'doc' -> 'documentation').
     *
     * @param string $section Section slug.
     *
     * @return string
     */
    public function normalize_section( string $section ): string {
        $section = strtolower( trim( $section ) );
        if ( 'doc' === $section ) {
            return 'documentation';
        }
        return $section;
    }

    /**
     * Map a section slug to its legacy wp-admin page slug.
     *
     * @param string $section Section slug.
     *
     * @return string
     */
    public function section_to_admin_page( string $section ): string {
        $normalized = $this->normalize_section( $section );
        return self::SECTION_TO_ADMIN_PAGE[ $normalized ] ?? 'racketmanager';
    }

    /**
     * Map a wp-admin page slug to its clean section slug.
     *
     * @param string $admin_page Admin page slug.
     *
     * @return string
     */
    public function admin_page_to_section( string $admin_page ): string {
        $page = strtolower( trim( $admin_page ) );
        return self::ADMIN_PAGE_TO_SECTION[ $page ] ?? 'competitions';
    }

    /**
     * Get the screen class associated with a section slug.
     *
     * @param string $section Section slug.
     *
     * @return class-string|null
     */
    public function section_to_screen_class( string $section ): ?string {
        $normalized = $this->normalize_section( $section );
        return self::SECTION_TO_SCREEN_CLASS[ $normalized ] ?? null;
    }

    /**
     * Check whether a section slug is recognized.
     *
     * @param string $section Section slug.
     *
     * @return bool
     */
    public function is_valid_section( string $section ): bool {
        $normalized = $this->normalize_section( $section );
        return isset( self::SECTION_TO_ADMIN_PAGE[ $normalized ] );
    }
}
