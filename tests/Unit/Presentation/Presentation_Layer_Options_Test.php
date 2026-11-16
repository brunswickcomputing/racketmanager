<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Architectural regression test to ensure that the Presentation layer
 * contains zero calls to global get_option() and zero use function get_option imports.
 */
final class Presentation_Layer_Options_Test extends TestCase {

    /**
     * Data provider yielding all PHP files in src/php/Presentation/.
     *
     * @return array<string, array{string, string}>
     */
    public static function presentation_php_files_provider(): array {
        $presentation_dir = dirname( __DIR__, 3 ) . '/src/php/Presentation';
        if ( ! is_dir( $presentation_dir ) ) {
            return [];
        }

        $directory = new RecursiveDirectoryIterator( $presentation_dir );
        $iterator  = new RecursiveIteratorIterator( $directory );
        $php_files = new RegexIterator( $iterator, '/^.+\.php$/i', RegexIterator::GET_MATCH );

        $files = [];
        foreach ( $php_files as $file_match ) {
            $file_path = $file_match[0];
            $relative  = str_replace( $presentation_dir . '/', '', $file_path );
            $files[ $relative ] = [ $file_path, $relative ];
        }

        ksort( $files );

        return $files;
    }

    /**
     * Ensure no Presentation file calls global get_option().
     *
     * @param string $file_path Absolute path to file.
     * @param string $relative_path Path relative to Presentation directory.
     */
    #[DataProvider( 'presentation_php_files_provider' )]
    public function test_file_does_not_call_global_get_option( string $file_path, string $relative_path ): void {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $code   = (string) file_get_contents( $file_path );
        $tokens = token_get_all( $code );
        $count  = count( $tokens );
        $calls  = [];

        for ( $i = 0; $i < $count; $i++ ) {
            $token = $tokens[ $i ];
            if ( ! is_array( $token ) ) {
                continue;
            }

            $id            = $token[0];
            $is_name_token = T_STRING === $id
                || ( defined( 'T_NAME_QUALIFIED' ) && T_NAME_QUALIFIED === $id )
                || ( defined( 'T_NAME_FULLY_QUALIFIED' ) && T_NAME_FULLY_QUALIFIED === $id );

            if ( ! $is_name_token ) {
                continue;
            }

            $name = strtolower( ltrim( $token[1], '\\' ) );
            if ( 'get_option' !== $name ) {
                continue;
            }

            // Check preceding token (ignoring whitespace and comments).
            $prev = $i - 1;
            while ( $prev >= 0 && is_array( $tokens[ $prev ] ) && in_array( $tokens[ $prev ][0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) ) {
                --$prev;
            }
            $prev_id = $prev >= 0 ? ( is_array( $tokens[ $prev ] ) ? $tokens[ $prev ][0] : $tokens[ $prev ] ) : null;

            // Check following token (ignoring whitespace and comments).
            $next = $i + 1;
            while ( $next < $count && is_array( $tokens[ $next ] ) && in_array( $tokens[ $next ][0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true ) ) {
                ++$next;
            }
            $next_char = $next < $count ? ( is_array( $tokens[ $next ] ) ? $tokens[ $next ][1] : $tokens[ $next ] ) : null;

            if ( '(' === $next_char ) {
                $is_method_call  = T_OBJECT_OPERATOR === $prev_id || ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) && T_NULLSAFE_OBJECT_OPERATOR === $prev_id );
                $is_function_def = T_FUNCTION === $prev_id;

                if ( ! $is_method_call && ! $is_function_def ) {
                    $calls[] = $token[2]; // line number
                }
            }
        }

        $this->assertSame(
            [],
            $calls,
            sprintf( 'File "%s" contains direct global get_option() calls on line(s): %s', $relative_path, implode( ', ', $calls ) )
        );
    }

    /**
     * Ensure no Presentation file imports global get_option.
     *
     * @param string $file_path Absolute path to file.
     * @param string $relative_path Path relative to Presentation directory.
     */
    #[DataProvider( 'presentation_php_files_provider' )]
    public function test_file_does_not_import_global_get_option( string $file_path, string $relative_path ): void {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $code    = (string) file_get_contents( $file_path );
        $tokens  = token_get_all( $code );
        $count   = count( $tokens );
        $imports = [];

        for ( $i = 0; $i < $count; $i++ ) {
            $token = $tokens[ $i ];
            if ( ! is_array( $token ) || T_USE !== $token[0] ) {
                continue;
            }

            $line      = $token[2];
            $statement = '';
            $j         = $i + 1;
            while ( $j < $count ) {
                $t          = $tokens[ $j ];
                $char       = is_array( $t ) ? $t[1] : $t;
                $statement .= $char;
                if ( ';' === $char ) {
                    break;
                }
                ++$j;
            }

            if ( preg_match( '/\bfunction\s+(?:.*\\\\)?get_option\b/i', $statement ) ) {
                $imports[] = $line;
            }
        }

        $this->assertSame(
            [],
            $imports,
            sprintf( 'File "%s" contains unused "use function get_option;" on line(s): %s', $relative_path, implode( ', ', $imports ) )
        );
    }

    /**
     * High-level regression guard ensuring the Presentation directory exists and contains PHP files.
     */
    public function test_presentation_layer_files_exist(): void {
        $files = self::presentation_php_files_provider();
        $this->assertNotEmpty( $files, 'Expected Presentation layer files to be found.' );
    }
}
