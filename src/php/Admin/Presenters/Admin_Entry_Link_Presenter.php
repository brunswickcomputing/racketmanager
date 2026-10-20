<?php
/**
 * Admin entry link presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Util\Util;

/**
 * Builds entry URL mappings for admin competition season pages.
 */
final readonly class Admin_Entry_Link_Presenter {

    /**
     * Build entry URLs map for competition club entries.
     *
     * @param object|array             $competition Competition entity or array.
     * @param string                   $season      Season identifier/name.
     * @param array<int|string, mixed> $entries     List of club entries.
     * @return array<string, string> Map of shortcode and ID to entry URL.
     */
    public function club_entry_urls( object|array $competition, string $season, array $entries ): array {
        $entry_urls = array();
        if ( empty( $entries ) ) {
            return $entry_urls;
        }

        $comp_name = is_object( $competition ) ? (string) ( $competition->name ?? '' ) : (string) ( $competition['name'] ?? '' );
        if ( '' === $comp_name ) {
            return $entry_urls;
        }

        $comp_slug = Util::seo_url( $comp_name );
        foreach ( $entries as $club ) {
            $club_shortcode = is_object( $club ) ? (string) ( $club->shortcode ?? '' ) : (string) ( $club['shortcode'] ?? '' );
            $club_id        = is_object( $club ) ? ( $club->id ?? null ) : ( $club['id'] ?? null );
            $club_slug      = Util::seo_url( $club_shortcode );
            $url            = '/entry-form/' . $comp_slug . '/' . $season . '/' . $club_slug . '/';

            if ( '' !== $club_shortcode ) {
                $entry_urls[ $club_shortcode ] = $url;
            }
            if ( null !== $club_id && '' !== (string) $club_id ) {
                $entry_urls[ (string) $club_id ] = $url;
            }
        }

        return $entry_urls;
    }

    /**
     * Build player entry URLs map for tournament overview categorized entries.
     *
     * @param object|array             $tournament          Tournament entity or array.
     * @param array<int|string, mixed> $categorized_entries Categorized player entries.
     * @return array<string, string> Map of player name and ID to entry URL.
     */
    public function player_entry_urls( object|array $tournament, array $categorized_entries ): array {
        $player_entry_urls = array();

        $tournament_name = is_object( $tournament ) ? (string) ( $tournament->name ?? '' ) : (string) ( $tournament['name'] ?? '' );
        if ( '' === $tournament_name ) {
            return $player_entry_urls;
        }

        $tournament_slug = Util::seo_url( $tournament_name );
        foreach ( $categorized_entries as $groups ) {
            if ( ! is_array( $groups ) ) {
                continue;
            }
            foreach ( $groups as $players ) {
                if ( ! is_array( $players ) ) {
                    continue;
                }
                foreach ( $players as $player ) {
                    $player_name = is_object( $player ) ? (string) ( $player->display_name ?? '' ) : (string) ( $player['display_name'] ?? '' );
                    $player_id   = is_object( $player ) ? (string) ( $player->id ?? $player->ID ?? '' ) : (string) ( $player['id'] ?? '' );
                    $url         = '/tournament/entry-form/' . $tournament_slug . '/player/' . Util::seo_url( $player_name ) . '/';

                    if ( '' !== $player_name ) {
                        $player_entry_urls[ $player_name ] = $url;
                    }
                    if ( '' !== $player_id ) {
                        $player_entry_urls[ $player_id ] = $url;
                    }
                }
            }
        }

        return $player_entry_urls;
    }
}
