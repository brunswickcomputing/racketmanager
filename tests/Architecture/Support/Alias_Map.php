<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use RuntimeException;

/**
 * Static catalogue of retired backward-compatibility class aliases.
 *
 * Prior to v11.0, these 63 aliases were registered in `src/php/aliases.php`.
 * With v11.0, the runtime aliases file has been deleted, but the architecture
 * guardrail `all.no_alias_class_references` continues to strictly forbid any
 * references to these legacy alias names across the codebase.
 */
final class Alias_Map {
    /**
     * Exact catalogue of 63 retired legacy alias names mapped to canonical PSR-4 targets.
     *
     * @var array<string, string>
     */
    public const array RETIRED_ALIASES = [
        'Racketmanager\Application\Fixture\Services\Fixture_Detail_Service'   => 'Racketmanager\Application\Fixture\Services\Fixture_Details_Assembler',
        'Racketmanager\Domain\Championship'                                   => 'Racketmanager\Domain\Tournament\Championship',
        'Racketmanager\Domain\Championship_Settings'                          => 'Racketmanager\Domain\Tournament\Championship_Settings',
        'Racketmanager\Domain\Charge'                                         => 'Racketmanager\Domain\Finance\Charge',
        'Racketmanager\Domain\Club'                                           => 'Racketmanager\Domain\Club\Club',
        'Racketmanager\Domain\Club_Factory'                                   => 'Racketmanager\Domain\Club\Club_Factory',
        'Racketmanager\Domain\Club_Player'                                    => 'Racketmanager\Domain\Club\Club_Player',
        'Racketmanager\Domain\Club_Role'                                      => 'Racketmanager\Domain\Club\Club_Role',
        'Racketmanager\Domain\DTO\Account\Add_Favourite_Command'              => 'Racketmanager\Application\Account\Commands\Add_Favourite_Command',
        'Racketmanager\Domain\DTO\Account\Delete_Messages_Command'            => 'Racketmanager\Application\Account\Commands\Delete_Messages_Command',
        'Racketmanager\Domain\DTO\Account\Login_Command'                      => 'Racketmanager\Application\Account\Commands\Login_Command',
        'Racketmanager\Domain\DTO\Account\Reset_Password_Command'             => 'Racketmanager\Application\Account\Commands\Reset_Password_Command',
        'Racketmanager\Domain\DTO\Account\Update_Account_Command'             => 'Racketmanager\Application\Account\Commands\Update_Account_Command',
        'Racketmanager\Domain\DTO\Club\Club_Player_Request_Command'           => 'Racketmanager\Application\Club\Commands\Club_Player_Request_Command',
        'Racketmanager\Domain\DTO\Club\Club_Players_Remove_Command'           => 'Racketmanager\Application\Club\Commands\Club_Players_Remove_Command',
        'Racketmanager\Domain\DTO\Club\Set_Club_Role_Command'                 => 'Racketmanager\Application\Club\Commands\Set_Club_Role_Command',
        'Racketmanager\Domain\DTO\Club\Update_Club_Command'                   => 'Racketmanager\Application\Club\Commands\Update_Club_Command',
        'Racketmanager\Domain\DTO\Competition\Cup_Entry_Request_DTO'          => 'Racketmanager\Infrastructure\Wordpress\Request\Competition\Cup_Entry_Request_DTO',
        'Racketmanager\Domain\DTO\Competition\League_Entry_Request_DTO'       => 'Racketmanager\Infrastructure\Wordpress\Request\Competition\League_Entry_Request_DTO',
        'Racketmanager\Domain\DTO\Finance\Set_Purchase_Order_Command'         => 'Racketmanager\Application\Finance\Commands\Set_Purchase_Order_Command',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request'        => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Date_Update_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Option_Request'             => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Option_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Reset_Request'              => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Reset_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request'       => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Status_Options_Request'     => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Options_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request'      => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Status_Update_Request',
        'Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request'       => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Switch_Teams_Request',
        'Racketmanager\Domain\DTO\Fixture\Rubber_Status_Options_Request'       => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Rubber_Status_Options_Request',
        'Racketmanager\Domain\DTO\Fixture\Team_Result_Confirmation_Request'   => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Confirmation_Request',
        'Racketmanager\Domain\DTO\Fixture\Team_Result_Update_Request'         => 'Racketmanager\Infrastructure\Wordpress\Request\Fixture\Team_Result_Update_Request',
        'Racketmanager\Domain\DTO\Player\Search_Players_Command'              => 'Racketmanager\Infrastructure\Wordpress\Request\Player\Search_Players_Command',
        'Racketmanager\Domain\DTO\Tournament\Championship_Rounds_Request_DTO' => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Championship_Rounds_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Contact_Request_DTO'  => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Contact_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO'    => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Entry_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO' => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Config_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO'   => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Finals_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Information_Request_DTO' => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Information_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO'  => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Partner_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Payment_Command'      => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Payment_Command',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO'          => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Request_DTO',
        'Racketmanager\Domain\DTO\Tournament\Tournament_Withdrawal_Command'   => 'Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Withdrawal_Command',
        'Racketmanager\Domain\DTOs\Results_Checker_Data'                       => 'Racketmanager\Domain\DTO\Result\Results_Checker_Data',
        'Racketmanager\Domain\Invoice'                                        => 'Racketmanager\Domain\Finance\Invoice',
        'Racketmanager\Domain\Message'                                        => 'Racketmanager\Domain\Communication\Message',
        'Racketmanager\Domain\Player'                                         => 'Racketmanager\Domain\Player\Player',
        'Racketmanager\Domain\Player_Error'                                   => 'Racketmanager\Domain\Player\Player_Error',
        'Racketmanager\Domain\Player_Ranking'                                 => 'Racketmanager\Domain\Player\Player_Ranking',
        'Racketmanager\Domain\Results_Checker'                                => 'Racketmanager\Domain\Result\Results_Checker',
        'Racketmanager\Domain\Results_Report'                                 => 'Racketmanager\Domain\Communication\Results_Report',
        'Racketmanager\Domain\Season'                                         => 'Racketmanager\Domain\Season\Season',
        'Racketmanager\Domain\Team'                                           => 'Racketmanager\Domain\Team\Team',
        'Racketmanager\Domain\Tournament'                                     => 'Racketmanager\Domain\Tournament\Tournament',
        'Racketmanager\Domain\Tournament_Entry'                               => 'Racketmanager\Domain\Tournament\Tournament_Entry',
        'Racketmanager\Domain\User'                                           => 'Racketmanager\Domain\Player\User',
        'Racketmanager\Gateways\Account\User_Favourites_Gateway_Interface'     => 'Racketmanager\Application\Account\Ports\User_Favourites_Gateway_Interface',
        'Racketmanager\Gateways\Mail\Mailer_Interface'                        => 'Racketmanager\Application\Mail\Mailer_Interface',
        'Racketmanager\Gateways\Option\Option_Gateway_Interface'               => 'Racketmanager\Application\Ports\Option_Gateway_Interface',
        'Racketmanager\Gateways\Payment\Payment_Gateway_Interface'             => 'Racketmanager\Application\Payment\Ports\Payment_Gateway_Interface',
        'Racketmanager\Gateways\Payment\Payment_Intent_Result'                => 'Racketmanager\Application\Payment\Ports\Payment_Intent_Result',
        'Racketmanager\Services\Contracts\Data_Exporter_Interface'            => 'Racketmanager\Application\Export\Ports\Data_Exporter_Interface',
        'Racketmanager\Services\Contracts\Wtn_Api_Client_Interface'           => 'Racketmanager\Domain\Ports\Rating_Gateway_Interface',
        'Racketmanager\Services\Export\DTO\Export_Criteria'                   => 'Racketmanager\Application\Export\DTO\Export_Criteria',
        'Racketmanager\Services\View\Shortcode_Template_Renderer'              => 'Racketmanager\Presentation\View\Shortcode_Template_Renderer',
    ];

    /**
     * Catalogue of 2 retired admin view model aliases from Legacy_View_Model_Aliases.php.
     *
     * @var array<string, string>
     */
    public const array RETIRED_VIEW_MODEL_ALIASES = [
        'Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model'    => 'Racketmanager\Admin\View_Models\Contact_Page_View_Model',
        'Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model' => 'Racketmanager\Admin\View_Models\Teams_List_Page_View_Model',
    ];

    /**
     * Cache mapping path to alias map.
     *
     * @var array<string, array<string, string>> alias => target, memoised per file path.
     */
    private static array $cache = [];


    /**
     * Load the alias map from path or static catalogue.
     *
     * @param string|null $path File path or null for static catalogue.
     * @return array<string, string> alias name => target class, both without a leading backslash.
     */
    public static function load( ?string $path = null ): array {
        if ( null === $path ) {
            return self::RETIRED_ALIASES;
        }

        if ( isset( self::$cache[ $path ] ) ) {
            return self::$cache[ $path ];
        }

        $code = is_file( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( false === $code ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RuntimeException( sprintf( 'Alias map %s could not be read.', $path ) );
        }

        $ast = new ParserFactory()->createForNewestSupportedVersion()->parse( $code ) ?? [];

        /**
         * Assignments found in AST.
         *
         * @var array<int, Expr\Assign> $assignments
         */
        $assignments = new NodeFinder()->find(
            $ast,
            static fn( Node $node ): bool => $node instanceof Expr\Assign
                && $node->var instanceof Expr\Variable
                && 'aliases' === $node->var->name
                && $node->expr instanceof Expr\Array_
        );
        if ( [] === $assignments ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RuntimeException( sprintf( 'Alias map %s does not declare an `$aliases = [ … ]` array.', $path ) );
        }

        $map = [];
        /**
         * Alias array expression.
         *
         * @var Expr\Array_ $array
         */
        $array = $assignments[0]->expr;
        foreach ( $array->items as $item ) {
            if ( ! $item->value instanceof Scalar\String_ ) {
                continue;
            }
            $alias  = ltrim( $item->value->value, '\\' );
            $target = '';
            if ( $item->key instanceof Expr\ClassConstFetch && $item->key->class instanceof Node\Name ) {
                $target = ltrim( $item->key->class->toString(), '\\' );
            } elseif ( $item->key instanceof Scalar\String_ ) {
                $target = ltrim( $item->key->value, '\\' );
            }
            $map[ $alias ] = $target;
        }
        ksort( $map, SORT_STRING );

        self::$cache[ $path ] = $map;
        return $map;
    }

    /**
     * Get deprecated alias class names.
     *
     * With no path, returns the retired class aliases followed by the retired admin view model aliases.
     *
     * @param string|null $path File path or null for static catalogue.
     * @return array<int,string> Deprecated alias class names.
     */
    public static function alias_names( ?string $path = null ): array {
        if ( null === $path ) {
            return [ ...array_keys( self::RETIRED_ALIASES ), ...array_keys( self::RETIRED_VIEW_MODEL_ALIASES ) ];
        }

        return array_keys( self::load( $path ) );
    }

    /**
     * Reset the cached alias map.
     */
    public static function reset_cache(): void {
        self::$cache = [];
    }
}
