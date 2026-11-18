<?php
/**
 * League Event administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\League_Constitution_Page_View_Model;

/** @var League_Constitution_Page_View_Model $vm */
$event               = $vm->event;
$season              = $vm->season;
$seasons             = $vm->seasons;
$leagues             = $vm->leagues;
$event_seasons       = $vm->event_seasons;
$num_teams           = $vm->num_teams;
$league_active_teams = $vm->league_active_teams;
$teams               = $vm->teams;
$constitution_action = $vm->constitution_action;
$constitution_exists = $vm->constitution_exists;
$updates_allowed     = $vm->updates_allowed;
$latest_season       = $vm->latest_season;
$latest_event_season = $vm->latest_event_season;
$standing_status     = $vm->standing_status;
$has_content         = $vm->has_content;
$event_id            = $vm->event_id;

/** @var object $event */
/** @var string $season */
/** @var array  $seasons */
/** @var array  $leagues */
/** @var int    $num_teams */
/** @var array  $league_active_teams */
$competition_type    = $event->competition->type instanceof \BackedEnum ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
$league_active_teams = $league_active_teams ?? array();
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="<?php echo esc_url( manage_url( $competition_type . 's' ) ); ?>"><?php echo esc_html( ucfirst( $competition_type ) ); ?>s</a> &raquo; <a href="<?php echo esc_url( manage_url( $competition_type . 's', 'seasons', [ 'competition_id' => (string) $event->competition->id ] ) ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <a href="<?php echo esc_url( manage_url( $competition_type . 's', 'overview', [ 'competition_id' => (string) $event->competition->id, 'season' => (string) $season ] ) ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <a href="<?php echo esc_url( manage_url( $competition_type . 's', 'event', [ 'competition_id' => (string) $event->competition->id, 'season' => (string) $season, 'event_id' => (string) $event->id ] ) ); ?>"><?php echo esc_html( $event->name ); ?></a> &raquo; <?php esc_html_e( 'Constitution', 'racketmanager' ); ?>
        </div>
    </div>
    <div class="row justify-content-between">
        <div class="col-auto">
            <h1><?php echo esc_html__( 'Constitution', 'racketmanager' ) . ' - ' . esc_html( $event->name ) . ' ' . esc_html( $season ); ?></h1>
        </div>
    </div>

    <div id="">
        <?php
        if ( $num_teams ) {
            ?>
            <div class="mb-3">
                <div class="row">
                    <div class="col-6 col-md-3 text-bg-dark"><?php esc_html_e( 'League', 'racketmanager' ); ?></div>
                    <div class="col-4 col-md-1 text-end text-bg-dark"><?php esc_html_e( 'Num teams', 'racketmanager' ); ?></div>
                </div>
                <?php
                foreach ( $leagues as $league ) {
                    $active_teams = $league_active_teams[ $league->id ] ?? 0;
                    if ( $active_teams ) {
                        ?>
                        <div class="row">
                            <div class="col-6 col-md-3">
                                <?php echo esc_html( $league->name ?? $league->title ); ?>
                            </div>
                            <div class="col-4 col-md-1 text-end">
                                <?php echo esc_html( $active_teams ); ?>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
        }
        ?>
        <?php
        require RACKETMANAGER_PATH . 'templates/admin/event/constitution.php';
        ?>
    </div>
