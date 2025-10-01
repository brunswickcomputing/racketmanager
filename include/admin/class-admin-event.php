<?php
/**
 * RacketManager-Admin API: RacketManager-admin-event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Event
 */

namespace Racketmanager\admin;

use Racketmanager\Event;
use Racketmanager\validator\Validator_Config;
use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_tournament;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Event panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Event extends Admin_Display {

    /**
     * Constructor
     */
    public function __construct() {
    }
    /**
     * Handle config page function
     *
     * @return void
     */
    public function display_config_page(): void {
        $validator = new Validator_Config();
        $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator->competition( $competition_id );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->err_msgs[0], true );
            $this->show_message();
            return;
        }
        $competition = get_competition( $competition_id );
        global $racketmanager;
        $season        = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
        }
        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $event_id ) {
            $new_event = false;
            $validator = $validator->event( $event_id );
            if ( empty( $validator->error ) ) {
                $event                      = get_event( $event_id );
                $event->config              = (object) $event->settings;
                $event->config->num_sets    = $event->num_sets;
                $event->config->num_rubbers = $event->num_rubbers;
                if ( isset( $_POST['updateEventConfig'] ) ) {
                    $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-event-config' );
                    if ( empty( $validator->error ) ) {
                        $event_id_passed = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
                        $validator       = $validator->compare( $event_id_passed, $event->id );
                    }
                    if ( empty( $validator->error ) ) {
                        $event->config = $this->get_config_input();
                        $validator     = $this->handle_config_update( $event->config, $competition );
                        if ( empty( $validator->error ) ) {
                            $updates       = $event->set_config( $event->config );
                            if ( $updates ) {
                                $this->set_message( __( 'Event config updated', 'racketmanager' ) );
                            } else {
                                $this->set_message( $this->no_updates, 'warning' );
                            }
                        }
                    }
                    if ( ! empty( $validator->error ) ) {
                        if ( empty( $validator->msg ) ) {
                            $this->set_message( $this->errors_found, true );
                        } else {
                            $this->set_message( $validator->msg, true );
                        }
                    }
                }
            } else {
                $this->set_message( $validator->err_msgs[0], true );
            }
        } else {
            $new_event = true;
            if ( isset( $_POST['addEventConfig'] ) ) {
                $event     = new stdClass();
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-event-config' );
                if ( empty( $validator->error ) ) {
                    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
                    if ( ! empty( $event_id ) ) {
                        $this->set_message( __( 'Event id invalid for new event', 'racketmanager' ), true );
                    } else {
                        $config        = $this->get_config_input();
                        $event->config = $config;
                        $validator     = $this->handle_config_update( $config, $competition, $new_event );
                        if ( empty( $validator->error ) ) {
                            $event->name           = $config->name;
                            $event->competition_id = $competition->id;
                            $event->num_sets       = $config->num_sets;
                            $event->num_rubbers    = $config->num_rubbers;
                            $event->type           = $config->type;
                            $event                 = new Event( $event );
                            $new_event             = false;
                            ?>
                            <script>
                                let url = new URL(window.location.href);
                                url.searchParams.append('event_id', <?php echo esc_attr( $event->id ); ?>);
                                history.pushState('', '', url.toString());
                            </script>
                            <?php
                            $add_season = false;
                            foreach ( $competition->seasons as $competition_season ) {
                                if ( $competition_season['name'] === $competition->current_season['name'] ) {
                                    $add_season = true;
                                }
                                if ( $add_season ) {
                                    $event_season['name']           = $competition_season['name'];
                                    $event_season['home_away']      = $competition_season['home_away'];
                                    $event_season['num_match_days'] = $competition_season['num_match_days'];
                                    $event_season['match_dates']    = $competition_season['match_dates'];
                                    $event->add_season( $event_season );
                                }
                            }
                            $league_id = $event->add_league( $event->name );
                            if ( $league_id && $competition->is_championship ) {
                                $config->primary_league = $league_id;
                                $league_title = $event->name . ' ' . __( 'Plate', 'racketmanager' );
                                $event->add_league( $league_title );
                            }
                            $event->config = $config;
                            $updates       = $event->set_config( $config );
                            if ( $updates ) {
                                $this->set_message( __( 'Event added', 'racketmanager' ) );
                            } elseif ( empty( $racketmanager->error_messages ) ) {
                                $this->set_message( $this->no_updates, 'warning' );
                            } else {
                                $this->set_message( $this->errors_found, true );
                            }
                        } else {
                            if ( empty( $validator->msg ) ) {
                                $this->set_message( $this->errors_found, true );
                            } else {
                                $this->set_message( $validator->msg, true );
                            }
                        }
                    }
                }
            }
        }
        $this->show_message();
        $tab        = 'general';
        $is_invalid = false;
        require_once RACKETMANAGER_PATH . 'admin/includes/event-config.php';
    }

    /**
     * Function to get config from form
     *
     * @return stdClass
     */
    private function get_config_input(): object {
        $config                     = new stdClass();
        $config->name               = isset( $_POST['event_name'] ) ? sanitize_text_field( wp_unslash( $_POST['event_name'] ) ) : null;
        $config->type               = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
        $config->age_limit          = isset( $_POST['age_limit'] ) ? sanitize_text_field( wp_unslash( $_POST['age_limit'] ) ) : null;
        $config->age_offset         = isset( $_POST['age_offset'] ) ? sanitize_text_field( wp_unslash( $_POST['age_offset'] ) ) : null;
        $config->scoring            = isset( $_POST['scoring'] ) ? sanitize_text_field( wp_unslash( $_POST['scoring'] ) ) : null;
        $config->num_sets           = isset( $_POST['num_sets'] ) ? intval( $_POST['num_sets'] ) : null;
        $config->num_rubbers        = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
        $config->reverse_rubbers    = isset( $_POST['reverse_rubbers'] ) ? intval( $_POST['reverse_rubbers'] ) : null;
        $config->match_days_allowed = isset( $_POST['match_days_allowed'] ) ? wp_unslash( $_POST['match_days_allowed'] ) : null;
        $config->offset             = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : null;
        $config->primary_league     = isset( $_POST['primary_league'] ) ? intval( $_POST['primary_league'] ) : null;
        return $config;
    }

    /**
     * Function to validate config for update
     *
     * @param object $config
     * @param object $competition
     * @param bool $new
     *
     * @return object|stdClass
     */
    private function handle_config_update( object $config, object $competition, bool $new = false ): object {
        $validator = new Validator_Config();
        $validator = $validator->name( $config->name );
        $validator = $validator->type( $config->type );
        $validator = $validator->age_limit( $config->age_limit );
        $validator = $validator->age_offset( $config->age_offset );
        $validator = $validator->scoring( $config->scoring );
        $validator = $validator->num_sets( $config->num_sets );
        if ( $competition->is_team_entry ) {
            $validator = $validator->num_rubbers( $config->num_rubbers );
        }
        if ( ! $competition->is_tournament ) {
            $validator = $validator->offset( $config->offset );
        }
        if ( $competition->is_championship && ! $new ) {
            $validator = $validator->primary_league( $config->primary_league );
        }
        return $validator->get_details();
    }
}
