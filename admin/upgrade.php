<?php /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/**
 * Upgrade routine for database and settings
 *
 * @package Racketmanager
 */

/**
 * Racketmanager_upgrade() - update routine for older version
 */
function racketmanager_upgrade(): void {
    global $wpdb, $racketmanager;

    $options   = $racketmanager->options;
    $installed = $options['dbversion'] ?? null;

    echo esc_html__( 'Upgrade database structure...', 'racketmanager' ) . "<br />\n";
    $wpdb->show_errors();
    if ( ! $installed ) {
        $old_options = get_option( 'racketmanager' );
        if ( $old_options ) {
            $options   = $old_options;
            $installed = $options['dbversion'];
        }
    }
    if ( version_compare( $installed, '9.0.1', '<' ) ) {
        echo esc_html__( 'starting 9.0.1 upgrade', 'racketmanager' ) . "<br />\n";
        $wpdb->query( "UPDATE $wpdb->racketmanager_teams SET `roster` = NULL, `custom` = NULL WHERE `roster` LIKE '%cat_id%'");
        $tournaments = $racketmanager->get_tournaments( array( 'orderby' => array( 'date' => 'ASC' ) ) );
        foreach ( $tournaments as $tournament ) {
            if ( 'junior' !== $tournament->competition->age_group ) {
                echo esc_html__( 'processing', 'racketmanager' ) . ' ' . $tournament->name . "<br />\n";
                $entries = $tournament->get_entries();
                foreach ( $entries as $entry ) {
                    if ( empty( $entry->club_id ) ) {
                        $player = Racketmanager\get_player( $entry->id );
                        $player_clubs = $player->get_clubs();
                        if ( $player_clubs ) {
                            $player_club = end( $player_clubs );
                            $player_club_id = $player_club->id;
                            $wpdb->query( "UPDATE $wpdb->racketmanager_tournament_entries SET `club_id` = " . $player_club_id . " WHERE `id` = " . $entry->entry_id );
                        }
                    }
                }
            }
        }
        $invalid_items = range( 115, 0 );
        foreach ( $invalid_items as $item ) {
            $tables = $wpdb->get_results( "SELECT `id`, `custom` FROM {$wpdb->racketmanager_table} WHERE `custom` LIKE '%i:" . $item . ";a:1:{s:7:\"points2\"%'");
            foreach ( $tables as $table ) {
                $custom = unserialize( $table->custom );
                $item_range = range( 0, $item );
                foreach ( $item_range as $range ) {
                    if ( isset( $custom[ $range ] ) ) {
                        unset( $custom[ $range ] );
                    }
                }
                $table->custom = serialize( $custom );
                $wpdb->query( "UPDATE {$wpdb->racketmanager_table} SET `custom` = '" . $table->custom . "' WHERE `id` = " . $table->id );
            }
        }
    }
    if ( version_compare( $installed, '9.2.0', '<' ) ) {
        echo esc_html__( 'starting 9.2.0 upgrade', 'racketmanager' ) . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_rubbers} DROP `final`" );
    }
    if ( version_compare( $installed, '9.2.1', '<' ) ) {
        echo esc_html__( 'starting 9.2.1 upgrade', 'racketmanager' ) . "<br />\n";
        $competitions = $racketmanager->get_competitions();
        foreach ( $competitions as $competition ) {
            $update = false;
            if ( isset( $competition->settings['point_format2'] ) ) {
                if ( ! isset( $competition->settings['point_2_format'] ) ) {
                    $competition->settings['point_2_format'] = $competition->settings['point_format2'];
                }
                unset( $competition->settings['point_format2'] );
                $update = true;
            }
            if ( $update ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->racketmanager_competitions} SET `settings` = %s WHERE `id` = %d",
                        maybe_serialize( $competition->settings ),
                        $competition->id
                    )
                );
            }
        }
    }
    if ( version_compare( $installed, '9.2.2', '<' ) ) {
        echo esc_html__( 'starting 9.2.2 upgrade', 'racketmanager' ) . "<br />\n";
        $competitions = $racketmanager->get_competitions();
        foreach ( $competitions as $competition ) {
            $update = false;
            if ( isset( $competition->settings['min_start_time_weekday'] ) ) {
                if ( ! isset( $competition->settings['start_time']['weekday']['min'] ) ) {
                    $competition->settings['start_time']['weekday']['min'] = $competition->settings['min_start_time_weekday'];
                }
                unset( $competition->settings['min_start_time_weekday'] );
                $update = true;
            }
            if ( isset( $competition->settings['max_start_time_weekday'] ) ) {
                if ( ! isset( $competition->settings['start_time']['weekday']['max'] ) ) {
                    $competition->settings['start_time']['weekday']['max'] = $competition->settings['max_start_time_weekday'];
                }
                unset( $competition->settings['max_start_time_weekday'] );
                $update = true;
            }
            if ( isset( $competition->settings['min_start_time_weekend'] ) ) {
                if ( ! isset( $competition->settings['start_time']['weekend']['min'] ) ) {
                    $competition->settings['start_time']['weekend']['min'] = $competition->settings['min_start_time_weekend'];
                }
                unset( $competition->settings['min_start_time_weekend'] );
                $update = true;
            }
            if ( isset( $competition->settings['max_start_time_weekend'] ) ) {
                if ( ! isset( $competition->settings['start_time']['weekend']['max'] ) ) {
                    $competition->settings['start_time']['weekend']['max'] = $competition->settings['max_start_time_weekend'];
                }
                unset( $competition->settings['max_start_time_weekend'] );
                $update = true;
            }
            if ( $update ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->racketmanager_competitions} SET `settings` = %s WHERE `id` = %d",
                        maybe_serialize( $competition->settings ),
                        $competition->id
                    )
                );
            }
        }
    }
    if ( version_compare( $installed, '9.2.3', '<' ) ) {
        echo esc_html__( 'starting 9.2.3 upgrade', 'racketmanager' ) . "<br />\n";
        $events = $racketmanager->get_events();
        foreach ( $events as $event ) {
            $update = false;
            if ( isset( $event->settings['point_format2'] ) ) {
                unset( $event->settings['point_format2'] );
                $update = true;
            }
            if ( $update ) {
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE {$wpdb->racketmanager_events} SET `settings` = %s WHERE `id` = %d",
                        maybe_serialize( $event->settings ),
                        $event->id
                    )
                );
            }
        }
    }
    if ( version_compare( $installed, '9.3.0', '<' ) ) {
        echo esc_html__( 'starting 9.3.0 upgrade', 'racketmanager' ) . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_table} CHANGE `points2_plus` `points_2_plus` INT NOT NULL DEFAULT '0';" );
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_table} CHANGE `points2_minus` `points_2_minus` INT NOT NULL DEFAULT '0';" );
        $tables = $wpdb->get_results( "SELECT `id`, `custom` FROM {$wpdb->racketmanager_table} WHERE `custom` LIKE '%{s:7:\"points2\"%'");
        foreach ( $tables as $table ) {
            $custom = unserialize( $table->custom );
            if ( isset( $custom['points2'] ) ) {
                unset( $custom['points2'] );
            }
            $table->custom = serialize( $custom );
            $wpdb->query( "UPDATE {$wpdb->racketmanager_table} SET `custom` = '" . $table->custom . "' WHERE `id` = " . $table->id );
        }
    }
    if ( version_compare( $installed, '9.5.0', '<' ) ) {
        echo esc_html__( 'starting 9.5.0 upgrade', 'racketmanager' ) . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_invoices} ADD `purchase_order` VARCHAR(50) NULL AFTER `payment_reference`" );
    }
    /*
    * Update version and dbversion
    */
    $options['dbversion'] = RACKETMANAGER_DBVERSION;
    $options['version']   = RACKETMANAGER_VERSION;

    update_option( 'racketmanager', $options );
    flush_rewrite_rules();
    echo esc_html__( 'finished', 'racketmanager' ) . "<br />\n";
    $wpdb->hide_errors();
}

/**
 * racketmanager_upgrade_page() - This page shows up , when the database version doesn't fit to the script RACKETMANAGER_DBVERSION constant.
 *
 * @return void Upgrade Message
 */
function racketmanager_upgrade_page(): void {
    $filepath = admin_url() . 'admin.php?page=' . htmlspecialchars( $_GET['page'] );

    if ( isset( $_GET['upgrade'] ) && 'now' === $_GET['upgrade'] ) {
        racketmanager_do_upgrade( $filepath );
    } else {
        ?>
        <div class="wrap">
            <h2><?php _e( 'Upgrade RacketManager', 'racketmanager' ); ?></h2>
            <p><?php _e( 'Your database for RacketManager is out-of-date, and must be upgraded before you can continue.', 'racketmanager' ); ?>
            <p><?php _e( 'The upgrade process may take a while, so please be patient.', 'racketmanager' ); ?></p>
            <h3><a class="button" href="<?php echo $filepath; ?>&amp;upgrade=now"><?php _e( 'Start upgrade now', 'racketmanager' ); ?>...</a></h3>
        </div>
        <?php
    }
}

/**
 * racketmanager_do_upgrade() - Proceed the upgrade routine
 *
 * @param mixed $filepath
 * @return void
 */
function racketmanager_do_upgrade( mixed $filepath ): void {
    ?>
<div class="wrap">
    <h2><?php _e( 'Upgrade RacketManager', 'racketmanager' ); ?></h2>
    <p><?php racketmanager_upgrade(); ?></p>
    <p><?php _e( 'Upgrade successful', 'racketmanager' ); ?></p>
    <h3><a class="button" href="<?php echo $filepath; ?>"><?php _e( 'Continue', 'racketmanager' ); ?>...</a></h3>
</div>
    <?php
}

?>
