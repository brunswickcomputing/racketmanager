<?php
/**
 * League import mapper.
 *
 * @package RacketManager
 * @subpackage Services/Admin/Import
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Import;

/**
 * Maps CSV line columns to custom match and team data structures.
 */
final class League_Import_Mapper {

    /**
     * Map CSV line columns to custom match data structure.
     *
     * @param array<int|string, mixed> $custom Existing custom data array.
     * @param array<int, mixed> $line Raw CSV line row.
     * @param int $match_id Match identifier.
     * @param int $col Starting column index.
     * @param array<string, mixed> $fields_match Field definitions.
     * @param string $sport Optional sport identifier for filter hook.
     *
     * @return array<int|string, mixed> Updated custom data array.
     */
    public function map_matches( array $custom, array $line, int $match_id, int $col, array $fields_match, string $sport = '' ): array {
        if ( ! empty( $fields_match ) ) {
            if ( ! isset( $custom[ $match_id ] ) || ! is_array( $custom[ $match_id ] ) ) {
                $custom[ $match_id ] = array();
            }

            foreach ( $fields_match as $key => $data ) {
                $keys = $data['keys'] ?? null;
                if ( is_array( $keys ) && ! empty( $keys ) && is_array( reset( $keys ) ) ) {
                    foreach ( $keys as $k => $v ) {
                        $col_val    = isset( $line[ $col ] ) ? (string) $line[ $col ] : '';
                        $p          = '' !== $col_val ? explode( '-', $col_val ) : array( '', '' );
                        $x          = array();
                        $x[ $v[0] ] = $p[0] ?? '';
                        $x[ $v[1] ] = $p[1] ?? '';

                        $custom[ $match_id ][ $key ][ $k ] = $x;

                        ++ $col;
                    }
                } else {
                    if ( is_array( $keys ) && ! empty( $keys ) ) {
                        $col_val       = isset( $line[ $col ] ) ? (string) $line[ $col ] : '';
                        $p             = '' !== $col_val ? explode( '-', $col_val ) : array( '', '' );
                        $x             = array();
                        $x[ $keys[0] ] = $p[0] ?? '';
                        $x[ $keys[1] ] = $p[1] ?? '';
                    } else {
                        $x = isset( $line[ $col ] ) && '' !== $line[ $col ] ? (string) $line[ $col ] : '';
                    }

                    $custom[ $match_id ][ $key ] = $x;

                    ++ $col;
                }
            }
        }

        $sport = strtolower( trim( $sport ) );
        if ( '' !== $sport && function_exists( 'apply_filters' ) ) {
            $custom = apply_filters( "racketmanager_import_matches_$sport", $custom, $line, $match_id, $col );
        }

        return $custom;
    }

    /**
     * Map CSV line columns to the custom team data structure.
     *
     * @param array<string, mixed> $custom Existing custom data array.
     * @param array<int, mixed> $line Raw CSV line row.
     * @param int $col Starting column index.
     * @param array<string, mixed> $fields_team Field definitions.
     * @param string $sport Optional sport identifier for filter hook.
     *
     * @return array<string, mixed> Updated custom data array.
     */
    public function map_teams( array $custom, array $line, int $col, array $fields_team, string $sport = '' ): array {
        if ( ! empty( $fields_team ) ) {
            foreach ( $fields_team as $key => $data ) {
                $keys = $data['keys'] ?? null;
                if ( is_array( $keys ) && ! empty( $keys ) ) {
                    $col_val        = isset( $line[ $col ] ) ? (string) $line[ $col ] : '';
                    $p              = '' !== $col_val ? explode( '-', $col_val ) : array( '', '' );
                    $x              = array();
                    $x[ $keys[0] ]  = $p[0] ?? '';
                    $x[ $keys[1] ]  = $p[1] ?? '';
                    $custom[ $key ] = $x;
                } else {
                    $custom[ $key ] = $line[ $col ] ?? 0;
                }

                ++ $col;
            }
        }

        $sport = strtolower( trim( $sport ) );
        if ( '' !== $sport && function_exists( 'apply_filters' ) ) {
            $custom = apply_filters( "racketmanager_import_teams_$sport", $custom, $line, $col );
        }

        return $custom;
    }
}
