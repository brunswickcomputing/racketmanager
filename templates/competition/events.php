<?php
/**
 * Template for competition events
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var array  $events */
/** @var array<int, string> $event_links */
$event_links = $event_links ?? [];
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
                    $event_id   = is_object( $event ) ? (int) ( $event->event_id ?? ( $event->id ?? 0 ) ) : (int) ( $event['event_id'] ?? ( $event['id'] ?? 0 ) );
                    $event_link = $event_links[ $event_id ] ?? '';
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
                            <?php if ( '' !== $event_link ) : ?>
                                <a href="<?php echo esc_attr( $event_link ); ?>">
                                    <?php echo esc_html( $event->event_name ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html( $event->event_name ); ?>
                            <?php endif; ?>
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
