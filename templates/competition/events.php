<?php
/**
 * Template for competition events
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var array  $events */
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Events', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div class="col-12 col-md-6">
                <div class="row mb-2 row-header">
                    <div class="col-1"></div>
                    <div class="col-5">
                        <?php esc_html_e( 'Event', 'racketmanager' ); ?>
                    </div>
                    <div class="col-2 text-end">
                        <?php esc_html_e( 'Leagues', 'racketmanager' ); ?>
                    </div>
                    <div class="col-2 text-end">
                        <?php esc_html_e( 'Teams', 'racketmanager' ); ?>
                    </div>
                    <div class="col-2 text-end">
                        <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                    </div>
                </div>
                <?php
                foreach ( $events as $event ) {
                    ?>
                    <div class="row mb-2 row-list">
                        <div class="col-1" name="<?php esc_html_e( 'Favourite', 'racketmanager' ); ?>">
                        <?php
                        $hidden         = true;
                        $favourite_type = 'competition';
                        $favourite_id   = $event->event_id;
                        require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                        ?>
                        </div>
                        <div class="col-5" name="<?php esc_html_e( 'Event', 'racketmanager' ); ?>">
                            <a href="/<?php echo esc_html( $competition->type ); ?>s/<?php echo esc_html( seo_url( $event->event_name ) ); ?>/<?php echo esc_html( $competition->current_season['name'] ); ?>/">
                                <?php echo esc_html( $event->event_name ); ?>
                            </a>
                        </div>
                        <div class="col-2 text-end" name="<?php esc_html_e( 'Leagues', 'racketmanager' ); ?>">
                            <?php echo empty( $event->num_leagues ) ? null : esc_html( $event->num_leagues ); ?>
                        </div>
                        <div class="col-2 text-end" name="<?php esc_html_e( 'Teams', 'racketmanager' ); ?>">
                            <?php echo empty( $event->num_teams ) ? null : esc_html( $event->num_teams ); ?>
                        </div>
                        <div class="col-2 text-end" name="<?php esc_html_e( 'Players', 'racketmanager' ); ?>">
                            <?php echo empty( $event->num_players ) ? null : esc_html( $event->num_players ); ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
