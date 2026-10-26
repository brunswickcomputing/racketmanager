<?php
declare( strict_types=1 );
/**
 * Slug API: Slug class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/Shared
 */

namespace Racketmanager\Domain\Shared;

/**
 * Pure-PHP slug normaliser used by Domain code to compare names against URL slugs.
 *
 * Produces the same result as WordPress `sanitize_title()` (`remove_accents()` followed by
 * `sanitize_title_with_dashes( …, '', 'save' )`): tags are stripped, Latin accents are removed,
 * the string is lower-cased, characters outside the Latin script are kept as percent-encoded UTF-8
 * octets (`%ce%b1`), and every run of non-alphanumerics becomes a single `-` with leading/trailing
 * dashes trimmed. It is intended for *comparisons* (both sides normalised through the same
 * function), not for generating persisted slugs; callers must treat an empty result as "no slug"
 * rather than as a value that can match.
 */
final class Slug {

    /**
     * Normalise a name or slug into a comparable `lower-case-dashed` form.
     *
     * @param string $value Name or slug.
     *
     * @return string
     */
    public static function normalise( string $value ): string {
        // Same step order as WordPress sanitize_title() → sanitize_title_with_dashes( $title, '', 'save' ).
        $value = strip_tags( $value );
        $value = self::remove_accents( $value );

        // Preserve escaped octets, drop stray percent signs, restore the octets.
        $value = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $value ) ?? $value;
        $value = str_replace( '%', '', $value );
        $value = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $value ) ?? $value;

        if ( preg_match( '//u', $value ) ) { // seems_utf8().
            $value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : $value;
            $value = self::utf8_uri_encode( $value, 200 );
        }
        $value = strtolower( $value );

        // 'save' context: nbsp / en dash / em dash become hyphens, typographic noise is stripped, × → x.
        $value = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $value );
        $value = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $value );
        $value = str_replace( '/', '-', $value );
        $value = str_replace( self::STRIPPED_OCTETS, '', $value );
        $value = str_replace( '%c3%97', 'x', $value );

        $value = preg_replace( '/&.+?;/', '', $value ) ?? $value; // Kill HTML entities.
        $value = str_replace( '.', '-', $value );
        $value = preg_replace( '/[^%a-z0-9 _-]/', '', $value ) ?? $value; // Apostrophes etc. are dropped, not dashed.
        $value = preg_replace( '/\s+/', '-', $value ) ?? $value;
        $value = preg_replace( '/-+/', '-', $value ) ?? $value;
        return trim( $value, '-' );
    }

    /**
     * Percent-encode every non-ASCII byte of a UTF-8 string as a lower-case `%xx` octet.
     *
     * Port of WordPress `utf8_uri_encode( $string, $length )`: ASCII passes through, each multibyte
     * character is emitted as a whole and encoding stops once `$length` output bytes would be exceeded.
     *
     * @param string $value  UTF-8 string.
     * @param int    $length Maximum output length in bytes (0 = unlimited).
     *
     * @return string
     */
    private static function utf8_uri_encode( string $value, int $length = 0 ): string {
        $encoded        = '';
        $values         = array();
        $num_octets     = 1;
        $encoded_length = 0;
        $string_length  = strlen( $value );

        for ( $i = 0; $i < $string_length; $i++ ) {
            $byte = ord( $value[ $i ] );
            if ( $byte < 128 ) {
                if ( $length && ( $encoded_length + 1 ) > $length ) {
                    break;
                }
                $encoded .= chr( $byte );
                ++$encoded_length;
                continue;
            }
            if ( 0 === count( $values ) ) {
                $num_octets = $byte < 224 ? 2 : ( $byte < 240 ? 3 : 4 );
            }
            $values[] = $byte;
            if ( $length && ( $encoded_length + ( $num_octets * 3 ) ) > $length ) {
                break;
            }
            if ( count( $values ) === $num_octets ) {
                foreach ( $values as $octet ) {
                    $encoded .= '%' . dechex( $octet );
                }
                $encoded_length += $num_octets * 3;
                $values          = array();
                $num_octets      = 1;
            }
        }
        return $encoded;
    }

    /**
     * Replace accented Latin characters with their ASCII base letters.
     *
     * @param string $value UTF-8 string.
     *
     * @return string
     */
    private static function remove_accents( string $value ): string {
        if ( ! preg_match( '/[\x80-\xff]/', $value ) ) {
            return $value;
        }
        if ( class_exists( \Normalizer::class ) ) {
            $decomposed = \Normalizer::normalize( $value, \Normalizer::FORM_D );
            if ( false !== $decomposed ) {
                $value = preg_replace( '/\p{Mn}+/u', '', $decomposed ) ?? $decomposed;
            }
        }
        return strtr( $value, self::LIGATURES );
    }

    /** Percent-encoded octets that `sanitize_title_with_dashes( …, 'save' )` strips entirely. */
    private const array STRIPPED_OCTETS = array(
        '%c2%ad', // Soft hyphen.
        '%c2%a1', // ¡
        '%c2%bf', // ¿
        '%c2%ab', // «
        '%c2%bb', // »
        '%e2%80%b9', // ‹
        '%e2%80%ba', // ›
        '%e2%80%98', // ‘
        '%e2%80%99', // ’
        '%e2%80%9c', // “
        '%e2%80%9d', // ”
        '%e2%80%9a', // ‚
        '%e2%80%9b', // ‛
        '%e2%80%9e', // „
        '%e2%80%9f', // ‟
        '%e2%80%a2', // •
        '%c2%a9', // ©
        '%c2%ae', // ®
        '%c2%b0', // °
        '%e2%80%a6', // …
        '%e2%84%a2', // ™
        '%c2%b4', // ´
        '%cb%8a', // ˊ
        '%cc%81', // Combining acute.
        '%cd%81', // Combining acute tone mark.
        '%cc%80', // Combining grave.
        '%cc%84', // Combining macron.
        '%cc%8c', // Combining caron.
        '%ca%bb', // ʻ
        '%ca%bd', // ʽ
        '%ca%bc', // ʼ
        '%ca%be', // ʾ
    );

    /** Characters that canonical decomposition does not reduce to ASCII (mirrors WordPress `remove_accents()`). */
    private const array LIGATURES = array(
        'Æ' => 'AE',
        'æ' => 'ae',
        'Ø' => 'O',
        'ø' => 'o',
        'Œ' => 'OE',
        'œ' => 'oe',
        'ß' => 's', // WordPress maps ß → s unless the locale is de_*.
        'Þ' => 'TH',
        'þ' => 'th',
        'Ð' => 'D',
        'ð' => 'd',
        'Ł' => 'L',
        'ł' => 'l',
        'Đ' => 'D',
        'đ' => 'd',
        'Ħ' => 'H',
        'ħ' => 'h',
        'ı' => 'i',
        'Ŀ' => 'L',
        'ŀ' => 'l',
        'ŉ' => 'n',
        'Ŧ' => 'T',
        'ŧ' => 't',
        '€' => 'E',
        '£' => '',
    );
}
