<?php
/**
 * League Event administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var object $event */
/** @var string $season */
/** @var array  $seasons */
/** @var array  $leagues */
/** @var int    $num_teams */
/** @var array  $league_active_teams */
$competition_type    = $event->competition->type instanceof \BackedEnum ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
$leagues             = $leagues ?? array();
$num_teams           = $num_teams ?? 0;
$league_active_teams = $league_active_teams ?? array();
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition_type ); ?>s"><?php echo esc_html( ucfirst( $competition_type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition_type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $event->competition->id ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition_type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $event->competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition_type ); ?>s&amp;view=event&amp;competition_id=<?php echo esc_attr( $event->competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>&amp;event_id=<?php echo esc_attr( $event->id ); ?>"><?php echo esc_html( $event->name ); ?></a> &raquo; <?php esc_html_e( 'Constitution', 'racketmanager' ); ?>
        </div>
    </div>
    <div class="row justify-content-between">
        <div class="col-auto">
            <h1><?php echo esc_html__( 'Constitution', 'racketmanager' ) . ' - ' . esc_html( $event->name ) . ' ' . esc_html( $season ); ?></h1>
        </div>
    </div>

    <?php
    if ( method_exists( $this, 'show_message' ) ) {
        $this->show_message();
    }
    ?>
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
                                <?php echo esc_html( $league->title ); ?>
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
