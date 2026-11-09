<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use InvalidArgumentException;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use TypeError;

/**
 * Parses and validates constructor options for Fixture_Presentation_Adapter.
 */
class Fixture_Presentation_Adapter_Options_Parser {
    /**
     * Allowed keys for optional constructor arguments.
     *
     * @var string[]
     */
    public const array ALLOWED_OPTION_KEYS = array(
        'link',
        'is_update_allowed',
        'prev_home_title',
        'prev_away_title',
        'option_gateway',
    );

    /**
     * Error message suffix for unexpected argument types.
     *
     * @var string
     */
    private const string TYPE_ERROR_GIVEN_SUFFIX = ' given';

    /**
     * Parse constructor options into an associative map.
     *
     * @param array $options Variadic options containing positional, named, or dictionary arguments.
     *
     * @return array{link: string, is_update_allowed: ?object, prev_home_title: ?string, prev_away_title: ?string, option_gateway: ?Option_Gateway_Interface}
     *
     * @throws InvalidArgumentException When options contain unknown keys, collisions, or malformed dictionaries.
     * @throws TypeError                When an option value does not match its expected type.
     */
    public function parse( array $options ): array {
        $extracted   = $this->extract_options_dictionary( $options );
        $options     = $extracted['options'];
        $dict        = $extracted['dict'];
        $is_pos_dict = $extracted['is_pos_dict'];

        $this->validate_option_keys( $options, $is_pos_dict );

        $link              = $this->pick_option( $options, 'link', 0, $dict, '' );
        $is_update_allowed = $this->pick_option( $options, 'is_update_allowed', 1, $dict, null );
        $prev_home_title   = $this->pick_option( $options, 'prev_home_title', 2, $dict, null );
        $prev_away_title   = $this->pick_option( $options, 'prev_away_title', 3, $dict, null );
        $option_gateway    = $this->pick_option( $options, 'option_gateway', 4, $dict, null );

        $this->validate_option_types(
            $link,
            $is_update_allowed,
            $prev_home_title,
            $prev_away_title,
            $option_gateway
        );

        return array(
            'link'              => $link,
            'is_update_allowed' => $is_update_allowed,
            'prev_home_title'   => $prev_home_title,
            'prev_away_title'   => $prev_away_title,
            'option_gateway'    => $option_gateway,
        );
    }

    /**
     * Extract and validate options dictionary from variadic options if present.
     *
     * @param array $options Variadic options passed to constructor.
     *
     * @return array{options: array, dict: array, is_pos_dict: bool}
     *
     * @throws InvalidArgumentException When dictionary argument is invalid or duplicated.
     */
    public function extract_options_dictionary( array $options ): array {
        $has_pos_dict   = array_key_exists( 0, $options ) && is_array( $options[0] );
        $has_named_dict = array_key_exists( 'options', $options );

        if ( $has_pos_dict && $has_named_dict ) {
            throw new InvalidArgumentException( "Cannot pass both positional dictionary and 'options' named parameter" );
        }

        $is_pos_dict = false;
        $dict        = array();

        if ( $has_pos_dict ) {
            $dict        = $options[0];
            $is_pos_dict = true;
            unset( $options[0] );
        } elseif ( $has_named_dict ) {
            if ( ! is_array( $options['options'] ) ) {
                throw new InvalidArgumentException( "Option 'options' must be an array" );
            }
            $dict = $options['options'];
            unset( $options['options'] );
        }

        foreach ( array_keys( $dict ) as $key ) {
            if ( ! in_array( $key, self::ALLOWED_OPTION_KEYS, true ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException( "Unknown option '$key' in options dictionary" );
            }
        }

        return array(
            'options'     => $options,
            'dict'        => $dict,
            'is_pos_dict' => $is_pos_dict,
        );
    }

    /**
     * Validate option keys in variadic options.
     *
     * @param array $options     Remaining variadic options.
     * @param bool  $is_pos_dict Whether options originally contained a positional dictionary.
     *
     * @return void
     *
     * @throws InvalidArgumentException When option keys are unknown or positional indices are invalid.
     */
    public function validate_option_keys( array $options, bool $is_pos_dict ): void {
        foreach ( array_keys( $options ) as $key ) {
            if ( is_string( $key ) && ! in_array( $key, self::ALLOWED_OPTION_KEYS, true ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException( "Unknown option '$key'" );
            }
            if ( is_int( $key ) && $is_pos_dict ) {
                throw new InvalidArgumentException( 'Positional options cannot follow a positional options dictionary' );
            }
            if ( is_int( $key ) && ( $key < 0 || $key > 4 ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new InvalidArgumentException( "Invalid positional option index $key" );
            }
        }
    }

    /**
     * Pick option value by named key, positional index, or dictionary entry.
     *
     * @param array  $options       Variadic options.
     * @param string $key           Named option key.
     * @param int    $index         Positional option index.
     * @param array  $dict          Options dictionary.
     * @param mixed  $default_value Default value if option is omitted.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException When an option is passed both positionally and by name.
     */
    public function pick_option( array $options, string $key, int $index, array $dict, mixed $default_value ): mixed {
        if ( array_key_exists( $key, $options ) && array_key_exists( $index, $options ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException( "Option '$key' was passed both positionally and by name" );
        }

        if ( array_key_exists( $key, $options ) ) {
            return $options[ $key ];
        }

        if ( array_key_exists( $index, $options ) ) {
            return $options[ $index ];
        }

        return array_key_exists( $key, $dict ) ? $dict[ $key ] : $default_value;
    }

    /**
     * Validate types of parsed constructor options against legacy signatures.
     *
     * @param mixed $link              Fixture link.
     * @param mixed $is_update_allowed Update permissions object.
     * @param mixed $prev_home_title   Previous home match title.
     * @param mixed $prev_away_title   Previous away match title.
     * @param mixed $option_gateway    Option gateway.
     *
     * @return void
     *
     * @throws TypeError When any option type violates the expected contract.
     */
    public function validate_option_types(
        mixed $link,
        mixed $is_update_allowed,
        mixed $prev_home_title,
        mixed $prev_away_title,
        mixed $option_gateway
    ): void {
        if ( ! is_string( $link ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new TypeError( "Option 'link' must be of type string, " . get_debug_type( $link ) . self::TYPE_ERROR_GIVEN_SUFFIX );
        }

        if ( null !== $is_update_allowed && ! is_object( $is_update_allowed ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new TypeError( "Option 'is_update_allowed' must be of type ?object, " . get_debug_type( $is_update_allowed ) . self::TYPE_ERROR_GIVEN_SUFFIX );
        }

        if ( null !== $prev_home_title && ! is_string( $prev_home_title ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new TypeError( "Option 'prev_home_title' must be of type ?string, " . get_debug_type( $prev_home_title ) . self::TYPE_ERROR_GIVEN_SUFFIX );
        }

        if ( null !== $prev_away_title && ! is_string( $prev_away_title ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new TypeError( "Option 'prev_away_title' must be of type ?string, " . get_debug_type( $prev_away_title ) . self::TYPE_ERROR_GIVEN_SUFFIX );
        }

        if ( null !== $option_gateway && ! ( $option_gateway instanceof Option_Gateway_Interface ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new TypeError( "Option 'option_gateway' must be of type ?" . Option_Gateway_Interface::class . ', ' . get_debug_type( $option_gateway ) . self::TYPE_ERROR_GIVEN_SUFFIX );
        }
    }
}
