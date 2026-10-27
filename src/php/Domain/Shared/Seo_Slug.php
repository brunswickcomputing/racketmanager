<?php
declare( strict_types=1 );
/**
 * Seo_Slug API: Seo_Slug class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/Shared
 */

namespace Racketmanager\Domain\Shared;

/**
 * Pure-PHP URL slug codec used by RacketManager rewrite rules and link builders.
 *
 * `encode()` is the historical `seo_url()` algorithm (lower-case, strip everything outside
 * `[a-z0-9_\s-]`, collapse whitespace, `-` → `_`, whitespace → `-`) and `decode()` the historical
 * `un_seo_url()` (`-` → space, `_` → `-`, lower-case). Both are byte-identical to the legacy helpers:
 * non-ASCII characters are stripped rather than transliterated and leading/trailing whitespace is
 * kept as a dash, because the resulting slugs are matched by rewrite tags and stored in e-mails.
 *
 * This is NOT WordPress `sanitize_title()`; use {@see Slug::normalise()} when comparing names
 * against WordPress-generated slugs.
 */
final class Seo_Slug {

    /**
     * Create an SEO friendly string (legacy `seo_url()`).
     *
     * @param string $value query string.
     *
     * @return string
     */
    public static function encode( string $value ): string {
        // Lower case everything.
        $value = strtolower( $value );
        // Make alphanumeric (removes all other characters).
        $value = preg_replace( '/[^a-z0-9_\s-]/', '', $value );
        // Clean up multiple whitespaces.
        $value = preg_replace( '/\s+/', ' ', $value );
        // Convert dash to underscore.
        $value = str_replace( '-', '_', $value );
        // Convert whitespaces to dash.
        return preg_replace( '/\s/', '-', $value );
    }

    /**
     * Reverse an SEO friendly string (legacy `un_seo_url()`).
     *
     * @param string $value query string.
     *
     * @return string
     */
    public static function decode( string $value ): string {
        // Convert dash to whitespaces.
        $value = str_replace( '-', ' ', $value );
        // Convert underscore to dash.
        $value = str_replace( '_', '-', $value );
        // Lower case everything.
        return strtolower( $value );
    }
}
