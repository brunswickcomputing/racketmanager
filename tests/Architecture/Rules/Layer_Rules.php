<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Rules;

use InvalidArgumentException;
use Racketmanager\Tests\Architecture\Support\Alias_Map;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;
use Racketmanager\Tests\Architecture\Support\Rule_Kind;
use Racketmanager\Tests\Architecture\Support\Rule_Mode;

/**
 * The architecture guardrail catalogue (docs/ddd-architecture-audit-2026-10.md, Appendix A.1).
 *
 * Rules are data: add a Layer_Rule here and it runs as its own PHPUnit case in the matching
 * `*_Layer_Test` / `Cross_Cutting_Test` data provider. Rule ids are prefixed by the layer they
 * protect (`domain.`, `application.`, `services.`, `repositories.`, `all.`, `templates.`, `admin.`,
 * `scanner.`); the prefix is also the baseline key namespace.
 *
 * Modes:
 *  - STRICT  — the rule has zero violations today and must stay at zero (no baseline entries allowed).
 *  - RATCHET — known debt is recorded in tests/Architecture/baseline.json; only new debt fails.
 *    When a ratchet rule reaches zero, flip it to STRICT here.
 */
final class Layer_Rules {
    public const string DOMAIN         = 'src/php/Domain/';
    public const string APPLICATION    = 'src/php/Application/';
    public const string SERVICES       = 'src/php/Services/';
    public const string REPOSITORIES   = 'src/php/Repositories/';
    public const string INFRASTRUCTURE = 'src/php/Infrastructure/';
    public const string PRESENTATION   = 'src/php/Presentation/';
    public const string ADMIN          = 'src/php/Admin/';
    public const string REST           = 'src/php/Rest/';
    public const string UTIL           = 'src/php/Util/';
    public const string PUBLIC         = 'src/php/Public/';
    public const string SRC            = 'src/php/';
    public const string TEMPLATES      = 'templates/';
    public const string TEMPLATE_TAGS  = 'template-tags.php';

    /** Superglobals that signal HTTP-request parsing in a layer that must not know about HTTP. */
    private const array SUPERGLOBALS = [ '_POST', '_GET', '_REQUEST', '_SERVER', '_COOKIE', '_FILES' ];

    /** Every class under Presentation\ or Admin\, plus the top-level Racketmanager\Admin god class itself. */
    private const string PRESENTATION_OR_ADMIN = '/^Racketmanager\\\\(Presentation\\\\|Admin(\\\\|$))/i';

    /**
     * WordPress runtime functions that make Domain code untestable without the WP runtime
     * (persistence, hooks, user context, i18n, escaping/sanitising, time, URLs, serialisation).
     *
     * Families are globs (`wp_*`, `sanitize_*`, `esc_*`, `get_*_meta`); everything else is explicit.
     * No pattern may match a PHP built-in function — Layer_Rules_Test checks this against
     * get_defined_functions()['internal'], so avoid open-ended families such as `get_*` or `is_*`.
     */
    public const array WORDPRESS_FUNCTIONS = [
        // Families.
        'wp_*',
        'sanitize_*',
        'esc_*',
        'get_*_meta',
        'add_*_meta',
        'update_*_meta',
        'delete_*_meta',
        // i18n.
        '__',
        '_e',
        '_n',
        '_x',
        '_ex',
        '_nx',
        '_n_noop',
        '_nx_noop',
        'translate',
        'number_format_i18n',
        'date_i18n',
        // Options, transients, metadata, cache (wp_cache_* is covered by wp_*).
        'get_option',
        'add_option',
        'update_option',
        'delete_option',
        'get_site_option',
        'update_site_option',
        'get_transient',
        'set_transient',
        'delete_transient',
        'get_site_transient',
        'set_site_transient',
        'delete_site_transient',
        'get_metadata',
        'add_metadata',
        'update_metadata',
        'delete_metadata',
        // Hooks.
        'apply_filters',
        'apply_filters_ref_array',
        'do_action',
        'do_action_ref_array',
        'add_action',
        'add_filter',
        'remove_action',
        'remove_filter',
        'has_action',
        'has_filter',
        'did_action',
        'current_filter',
        // Users and capabilities.
        'get_userdata',
        'get_user_by',
        'get_users',
        'get_current_user_id',
        'is_user_logged_in',
        'current_user_can',
        'user_can',
        'username_exists',
        'email_exists',
        'get_role',
        // Time.
        'mysql2date',
        'current_time',
        'current_datetime',
        'get_gmt_from_date',
        'get_date_from_gmt',
        'human_time_diff',
        // URLs and request context.
        'home_url',
        'site_url',
        'admin_url',
        'content_url',
        'plugins_url',
        'plugin_dir_url',
        'plugin_dir_path',
        'plugin_basename',
        'rest_url',
        'get_permalink',
        'get_home_url',
        'get_site_url',
        'get_admin_url',
        'add_query_arg',
        'remove_query_arg',
        'get_query_var',
        'is_admin',
        'is_multisite',
        'check_admin_referer',
        'check_ajax_referer',
        'nocache_headers',
        'status_header',
        // Data helpers.
        'absint',
        'maybe_serialize',
        'maybe_unserialize',
        'is_serialized',
        'stripslashes_deep',
        'stripslashes_from_strings_only',
        'is_email',
        'is_wp_error',
        'remove_accents',
        'trailingslashit',
        'untrailingslashit',
        'zeroise',
        'checked',
        'selected',
        'disabled',
        'shortcode_atts',
        'do_shortcode',
        'add_shortcode',
        'wpautop',
        'make_clickable',
        'size_format',
        // Posts, blog info and locale.
        'get_post',
        'get_posts',
        'get_page_by_path',
        'get_the_title',
        'get_bloginfo',
        'get_locale',
        'determine_locale',
        'load_plugin_textdomain',
    ];

    /**
     * Root-namespace helper functions from functions.php that act as a hidden service locator.
     * Patterns are namespaced so the global fallback of an unqualified call (e.g. a root `\seo_url()`)
     * is not counted.
     */
    private const array LEGACY_HELPERS = [
        'Racketmanager\get_league',
        'Racketmanager\get_event',
        'Racketmanager\get_team',
        'Racketmanager\get_club',
        'Racketmanager\get_player',
        'Racketmanager\get_league_team',
        'Racketmanager\get_user',
        'Racketmanager\get_message',
        'Racketmanager\seo_url',
        'Racketmanager\un_seo_url',
        'Racketmanager\get_*_repository_for_helpers',
        'Racketmanager\get_*_query_service',
    ];

    /** Domain entity classes whose from_object() and from_array() hydration helpers are forbidden in production. */
    public const array DOMAIN_ENTITIES = [
        'Racketmanager\Domain\Club\Club',
        'Racketmanager\Domain\Club\Club_Player',
        'Racketmanager\Domain\Club\Club_Role',
        'Racketmanager\Domain\Communication\Message',
        'Racketmanager\Domain\Communication\Results_Report',
        'Racketmanager\Domain\Competition\Competition',
        'Racketmanager\Domain\Competition\Event',
        'Racketmanager\Domain\Competition\League',
        'Racketmanager\Domain\Competition\League_Team',
        'Racketmanager\Domain\Finance\Charge',
        'Racketmanager\Domain\Finance\Invoice',
        'Racketmanager\Domain\Fixture\Fixture',
        'Racketmanager\Domain\Fixture\Rubber',
        'Racketmanager\Domain\Player\Player',
        'Racketmanager\Domain\Player\Player_Error',
        'Racketmanager\Domain\Player\User',
        'Racketmanager\Domain\Result\Results_Checker',
        'Racketmanager\Domain\Season\Season',
        'Racketmanager\Domain\Team\Team',
        'Racketmanager\Domain\Tournament\Tournament',
        'Racketmanager\Domain\Tournament\Tournament_Entry',
        'Racketmanager\Infrastructure\Legacy\Legacy_User',
    ];

    /** Domain entity files that define the backwards-compatible delegation wrappers and are exempt from domain.no_lenient_from_object. */
    public const array DOMAIN_ENTITY_FILES = [
        self::DOMAIN . 'Club/Club.php',
        self::DOMAIN . 'Club/Club_Player.php',
        self::DOMAIN . 'Club/Club_Role.php',
        self::DOMAIN . 'Communication/Message.php',
        self::DOMAIN . 'Communication/Results_Report.php',
        self::DOMAIN . 'Competition/Competition.php',
        self::DOMAIN . 'Competition/Event.php',
        self::DOMAIN . 'Competition/League.php',
        self::DOMAIN . 'Competition/League_Team.php',
        self::DOMAIN . 'Finance/Charge.php',
        self::DOMAIN . 'Finance/Invoice.php',
        self::DOMAIN . 'Fixture/Fixture.php',
        self::DOMAIN . 'Fixture/Rubber.php',
        self::DOMAIN . 'Player/Player.php',
        self::DOMAIN . 'Player/Player_Error.php',
        self::DOMAIN . 'Player/User.php',
        self::DOMAIN . 'Result/Results_Checker.php',
        self::DOMAIN . 'Season/Season.php',
        self::DOMAIN . 'Team/Team.php',
        self::DOMAIN . 'Tournament/Tournament.php',
        self::DOMAIN . 'Tournament/Tournament_Entry.php',
    ];

    /**
     * Helpers that functions.php already flags as deprecated (they trigger E_USER_DEPRECATED): no caller
     * may remain. Both the namespaced and the global spelling are listed because no root function of
     * these names exists, so either resolution is a bug.
     */
    private const array DEPRECATED_HELPERS = [
        'Racketmanager\get_match',
        'Racketmanager\get_rubber',
        'Racketmanager\get_competition',
        'Racketmanager\match_add_players',
        'Racketmanager\get_club_role',
        'Racketmanager\to_legacy_user',
        'get_match',
        'get_rubber',
        'get_competition',
        'match_add_players',
        'get_club_role',
        'to_legacy_user',
    ];

    /** @var ?array<string, Layer_Rule> */
    private static ?array $rules = null;

    /**
     * @return array<string, Layer_Rule> Every rule keyed by id, in catalogue order.
     */
    public static function all(): array {
        if ( null !== self::$rules ) {
            return self::$rules;
        }

        $rules = [];
        foreach ( [ ...self::domain(), ...self::application(), ...self::services_and_repositories(), ...self::cross_cutting() ] as $rule ) {
            if ( isset( $rules[ $rule->id ] ) ) {
                throw new InvalidArgumentException( sprintf( 'Duplicate architecture rule id `%s`.', $rule->id ) );
            }
            $rules[ $rule->id ] = $rule;
        }

        return self::$rules = $rules;
    }

    /**
     * Rules whose id starts with one of the given prefixes, e.g. `domain.`.
     *
     * @param string ...$prefixes
     *
     * @return array<string, Layer_Rule>
     */
    public static function for_prefix( string ...$prefixes ): array {
        return array_filter(
            self::all(),
            static function ( Layer_Rule $rule ) use ( $prefixes ): bool {
                foreach ( $prefixes as $prefix ) {
                    if ( str_starts_with( $rule->id, $prefix ) ) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    public static function get( string $id ): Layer_Rule {
        return self::all()[ $id ] ?? throw new InvalidArgumentException( sprintf( 'Unknown architecture rule `%s`.', $id ) );
    }

    /**
     * @return array<int,string>
     */
    public static function ids(): array {
        return array_keys( self::all() );
    }

    /**
     * Data-provider shape: `[ rule id => [ Layer_Rule ] ]` so every rule is a named PHPUnit case.
     *
     * @return iterable<string, array{0: Layer_Rule}>
     */
    public static function provide( string ...$prefixes ): iterable {
        foreach ( self::for_prefix( ...$prefixes ) as $id => $rule ) {
            yield $id => [ $rule ];
        }
    }

    /**
     * @return array<int, Layer_Rule>
     */
    private static function domain(): array {
        return [
            new Layer_Rule(
                'domain.no_wpdb',
                'Domain code must not touch $wpdb',
                'Persistence belongs in Repositories/; inject a Repositories\Interfaces\* port (audit §4, L7).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                [ 'wpdb' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'domain.no_outer_layer_imports',
                'Domain code must not reference outer layers (Repositories, Services, Infrastructure, Admin, Presentation, Application, Rest, Public, Gateways)',
                'Invert the dependency: declare a port in Domain/Ports and let the outer layer implement it (audit §4).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_CLASS_REFERENCE,
                [ '/^Racketmanager\\\\(Repositories|Services|Infrastructure|Admin|Presentation|Application|Rest|Public|Gateways)\\\\/i' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'domain.no_superglobals',
                'Domain code must not read superglobals',
                'Parse HTTP input in Infrastructure/Wordpress/Request and pass typed values into the domain (audit L3).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                self::SUPERGLOBALS,
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'domain.no_wordpress_functions',
                'Domain code must not call WordPress runtime functions (wp_*, sanitize_*, esc_*, __/_e/_n, options, hooks, users, time, URLs, absint/maybe_unserialize/stripslashes_deep)',
                'Parse HTTP input in Infrastructure/Wordpress/Request; take time from Domain/Ports/Clock_Interface; decode serialized columns with Domain/Shared/Serialized_Column, compare slugs with Domain/Shared/Slug and encode JSON with Domain/Value_Objects/Json_Encoder (audit L3, P1-4 done).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_FUNCTION_CALL,
                self::WORDPRESS_FUNCTIONS,
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'domain.no_util_static',
                'Domain code must not call Util / Util_Lookup statics',
                'Backlog P3-13: inject a formatter/scheduler port instead of Util::seo_url/amend_date/clear_scheduled_event (audit L4).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_STATIC_CALL,
                [ 'classes' => [ 'Racketmanager\Util\Util', 'Racketmanager\Util\Util_Lookup' ] ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'domain.no_untyped_constructor',
                'Domain entities must hydrate from typed DTOs, not ?object/stdClass',
                'Entities must hydrate from a typed *_Hydration_DTO (or be constructed empty) — active-record ?object/stdClass constructors are forbidden (audit L1, P2-8 done).',
                [ self::DOMAIN ],
                Rule_Kind::FORBIDDEN_CONSTRUCTOR_PARAM_TYPE,
                [ 'object', 'stdClass' ],
                Rule_Mode::STRICT,
                [ self::DOMAIN . 'DTO/' ]
            ),
            new Layer_Rule(
                'domain.no_lenient_from_object',
                'Production code must hydrate Domain entities via validating factories, not Entity::from_object() or from_array()',
                'Call the validating factory (e.g. Club_Factory::from_object() / Club_Factory::from_array()) instead of the legacy entity forwarders (audit L1, P2-8/8a done).',
                [ self::SRC, 'functions.php', self::TEMPLATE_TAGS ],
                Rule_Kind::FORBIDDEN_STATIC_CALL,
                [
                    'classes' => self::DOMAIN_ENTITIES,
                    'methods' => [ 'from_object', 'from_array', 'from_database' ],
                ],
                Rule_Mode::STRICT,
                self::DOMAIN_ENTITY_FILES
            ),
        ];
    }

    /**
     * @return array<int, Layer_Rule>
     */
    private static function application(): array {
        return [
            new Layer_Rule(
                'application.no_wpdb',
                'Application code must not touch $wpdb',
                'Persistence belongs in Repositories/; inject a Repositories\Interfaces\* port (audit L7).',
                [ self::APPLICATION ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                [ 'wpdb' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'application.no_global_racketmanager',
                'Application and Domain code must not use the global $racketmanager service locator',
                'Inject the collaborator through the constructor via the container providers (audit L5).',
                [ self::APPLICATION, self::DOMAIN ],
                Rule_Kind::FORBIDDEN_GLOBAL,
                [ 'racketmanager' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'application.no_superglobals',
                'Application code must not read superglobals',
                'Parse HTTP input in Infrastructure/Wordpress/Request and pass a typed query/command (audit §5).',
                [ self::APPLICATION ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                self::SUPERGLOBALS,
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'application.no_concrete_repositories',
                'Application code must depend on Repositories\Interfaces\*, never on concrete repositories',
                'Type-hint the Repositories\Interfaces\* port and bind the implementation in a *_Service_Provider (audit §4).',
                [ self::APPLICATION ],
                Rule_Kind::FORBIDDEN_CLASS_REFERENCE,
                [ '/^Racketmanager\\\\Repositories\\\\(?!Interfaces\\\\)/i' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'application.no_presentation_imports',
                'Application code must not depend on Presentation or Admin classes',
                'Backlog P2-11: return application DTOs from handlers and apply presenters in the shortcode/admin adapters (audit §5).',
                [ self::APPLICATION ],
                Rule_Kind::FORBIDDEN_DEPENDENCY,
                [ self::PRESENTATION_OR_ADMIN ],
                Rule_Mode::STRICT
            ),
        ];
    }

    /**
     * @return array<int, Layer_Rule>
     */
    private static function services_and_repositories(): array {
        return [
            new Layer_Rule(
                'services.no_wpdb',
                'Services must not run raw $wpdb queries',
                'Persistence belongs in Repositories/; inject a Repositories\Interfaces\* port (e.g. Player_Statistics_Repository_Interface) or add a transaction-owning repository method such as Team_Repository::save_with_players() (audit L7, P2-6 done).',
                [ self::SERVICES ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                [ 'wpdb' ],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'services.no_global_racketmanager',
                'Services, Infrastructure, Presentation, Rest, Util and Public code must not use the global $racketmanager service locator',
                'Backlog P3-12: inject the collaborator through the container provider instead of global $racketmanager / $GLOBALS[\'racketmanager\'] (audit L5).',
                [ self::SERVICES, self::INFRASTRUCTURE, self::PRESENTATION, self::REST, self::UTIL, self::PUBLIC ],
                Rule_Kind::FORBIDDEN_GLOBAL,
                [ 'racketmanager' ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'services.no_presentation_imports',
                'Services must not depend on Presentation or Admin classes',
                'Backlog P2-11: keep presenters in the adapters; services return domain objects or application DTOs (audit §5).',
                [ self::SERVICES ],
                Rule_Kind::FORBIDDEN_DEPENDENCY,
                [ self::PRESENTATION_OR_ADMIN ],
                Rule_Mode::STRICT,
                [ self::SERVICES . 'Container/Container_Bootstrap.php' ]
            ),
            new Layer_Rule(
                'repositories.no_service_imports',
                'Repositories must not depend on Services',
                'Backlog P2-7: repositories depend on Domain only; move orchestration into the service that calls the repository (audit §5).',
                [ self::REPOSITORIES ],
                Rule_Kind::FORBIDDEN_DEPENDENCY,
                [ '/^Racketmanager\\\\Services\\\\/i' ],
                Rule_Mode::RATCHET
            ),
        ];
    }

    /**
     * @return array<int, Layer_Rule>
     */
    private static function cross_cutting(): array {
        return [
            new Layer_Rule(
                'all.no_legacy_helper_calls',
                'Layered code and template-tags.php must not call the root helper functions (get_league, get_event, get_team, get_club, get_player, get_league_team, get_user, get_message, seo_url, un_seo_url, get_*_repository_for_helpers, get_*_query_service)',
                'Inject the Repositories\Interfaces\* port or the *_Query_Service; use Domain\Shared\Seo_Slug for slugs (audit L6, P2-7 done).',
                [ self::SRC, self::TEMPLATE_TAGS ],
                Rule_Kind::FORBIDDEN_FUNCTION_CALL,
                self::LEGACY_HELPERS,
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'all.no_deprecated_helper_calls',
                'Nothing may call a @deprecated helper (get_match, get_rubber, get_competition, match_add_players, get_club_role, to_legacy_user)',
                'Use the repository/service named in the helper\'s @deprecated tag (audit §6.2).',
                [ self::SRC, self::TEMPLATES, self::TEMPLATE_TAGS ],
                Rule_Kind::FORBIDDEN_FUNCTION_CALL,
                self::DEPRECATED_HELPERS,
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'all.no_get_instance_outside_bootstrap',
                'Only the composition root may call RacketManager::get_instance() / Admin::get_instance()',
                'Resolve the dependency from the container in the entry point (Plugin_Bootstrap) and pass it in (audit L5).',
                [ self::SRC ],
                Rule_Kind::FORBIDDEN_STATIC_CALL,
                [
                    'classes' => [ 'Racketmanager\RacketManager', 'Racketmanager\Admin' ],
                    'methods' => [ 'get_instance' ],
                ],
                Rule_Mode::RATCHET,
                [ self::INFRASTRUCTURE . 'Wordpress/Bootstrap/', self::SRC . 'RacketManager.php', self::SRC . 'Admin.php' ]
            ),
            new Layer_Rule(
                'all.no_alias_class_references',
                'No code may reference a deprecated class alias from src/php/aliases.php',
                'Reference the PSR-4 target class listed in src/php/aliases.php; aliases are removed in v11.0 (audit §6.1).',
                [ self::SRC, self::TEMPLATES, 'functions.php', self::TEMPLATE_TAGS ],
                Rule_Kind::FORBIDDEN_CLASS_REFERENCE,
                Alias_Map::alias_names(),
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'all.strict_types',
                'Every src/php file declares strict_types=1',
                'Add `declare( strict_types=1 );` directly after the opening `<?php` tag; coerce at the call site with explicit casts rather than loosening declared types (audit L12, P1-5 done).',
                [ self::SRC ],
                Rule_Kind::REQUIRE_STRICT_TYPES,
                [],
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'all.no_extract',
                'Renderers must not use extract() to build template scope',
                'Backlog P3-14: pass an explicit $vm view model to the template instead of extract() (audit L11).',
                [ self::SRC ],
                Rule_Kind::FORBIDDEN_FUNCTION_CALL,
                [ 'extract' ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'templates.no_globals_or_db',
                'Templates must not read $wpdb or superglobals',
                'Backlog P3-14: compute the value in the controller/query handler and expose it on the $vm view model (audit L10).',
                [ self::TEMPLATES ],
                Rule_Kind::FORBIDDEN_VARIABLE,
                [ 'wpdb', ...self::SUPERGLOBALS ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'templates.no_global_racketmanager',
                'Templates must not use the global $racketmanager service locator',
                'Backlog P3-14: resolve the service in the shortcode/admin adapter and pass the result through the view model (audit L10).',
                [ self::TEMPLATES ],
                Rule_Kind::FORBIDDEN_GLOBAL,
                [ 'racketmanager' ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'templates.no_legacy_helper_calls',
                'Templates must not call the root helper functions',
                'Backlog P1-5a: pass the resolved entity / slug through the view model instead of calling get_league()/seo_url() in the template (audit L6).',
                [ self::TEMPLATES ],
                Rule_Kind::FORBIDDEN_FUNCTION_CALL,
                self::LEGACY_HELPERS,
                Rule_Mode::STRICT
            ),
            new Layer_Rule(
                'admin.no_template_require',
                'Admin screens must not require templates/admin/* inline',
                'Backlog P2-9: migrate the screen to Admin/Controllers/* + PRG and render through Php_View_Renderer with a $vm (audit L9).',
                [ self::ADMIN ],
                Rule_Kind::FORBIDDEN_INCLUDE,
                [ '*templates/admin/*' ],
                Rule_Mode::RATCHET
            ),
            new Layer_Rule(
                'scanner.parse_error',
                'Every scanned PHP file parses with php-parser',
                'Fix the syntax error; a file that does not parse is invisible to every other guardrail.',
                [],
                Rule_Kind::NO_PARSE_ERRORS,
                [],
                Rule_Mode::STRICT
            ),
        ];
    }
}
