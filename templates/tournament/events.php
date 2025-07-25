<?php
/**
 * Template for tournament events
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
?>
<div class="container">
<?php
if ( empty( $event ) ) {
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
                        <div class="col-8">
                            <?php esc_html_e( 'Event', 'racketmanager' ); ?>
                        </div>
                        <div class="col-3">
                            <?php esc_html_e( 'Entries', 'racketmanager' ); ?>
                        </div>
                    </div>
                    <?php
                    foreach ( $tournament->events as $event ) {
                        ?>
                        <div class="row mb-2 row-list">
                            <div class="col-1" name="<?php esc_html_e( 'Favourite', 'racketmanager' ); ?>">
                                <?php
                                $url_link       = $tournament->link . 'event/' . seo_url( $event->name ) . '/';
                                $hidden         = true;
                                $favourite_type = 'competition';
                                $favourite_id   = $event->id;
                                require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                                ?>
                            </div>
                            <div class="col-8" name="<?php esc_html_e( 'Event', 'racketmanager' ); ?>">
                                <a href="<?php echo esc_url( $url_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $url_link ); ?>" data-link-id="<?php echo esc_attr( $event->id ); ?>" data-link-type="events">
                                    <?php echo esc_html( $event->name ); ?>
                                </a>
                            </div>
                            <div class="col-3" name="<?php esc_html_e( 'Draw size', 'racketmanager' ); ?>">
                                <?php
                                    echo esc_html( $event->team_count );
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title">
                <?php echo esc_html( $event->name ); ?>
                <?php
                $draw_link      = $tournament->link . 'draw/' . seo_url( $event->name ) . '/';
                $competition    = $event;
                $favourite_type = 'competition';
                $favourite_id   = $event->id;
                require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                ?>
            </h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <dl>
                    <dt><?php esc_html_e( 'Draw', 'racketmanager' ); ?></dt>
                    <dd>
                        <a href="<?php echo esc_html( $draw_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $draw_link ); ?>" data-link-id="<?php echo esc_attr( $event->id ); ?>" data-link-type="draws">
                            <?php echo esc_html( $event->name ); ?>
                        </a>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php echo esc_html__( 'Entries', 'racketmanager' ) . ' (' . esc_html( count( $event->teams ) ) . ')'; ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="row mb-2 row-header">
                        <?php
                        if ( ! empty( $event->num_seeds ) ) {
                            ?>
                            <div class="col-1" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php esc_html_e( 'Seed', 'racketmanager' ); ?>">#</div>
                            <?php
                        }
                        ?>
                        <div class="col-8">
                            <?php esc_html_e( 'Player', 'racketmanager' ); ?>
                        </div>
                        <div class="col-3 team-rating">
                            <?php esc_html_e( 'Rating', 'racketmanager' ); ?>
                        </div>
                    </div>
                    <?php
                    foreach ( $event->teams as $entry ) {
                        ?>
                        <div class="row row-list">
                            <div class="col-1">
                                <?php
                                if ( ! empty( $event->num_seeds ) ) {
                                    if ( intval( $entry->rank ) <= intval( $event->num_seeds ) ) {
                                        echo esc_html( $entry->rank );
                                    }
                                    ?>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="col-8" name="<?php esc_html_e( 'Player', 'racketmanager' ); ?>">
                                <?php
                                if ( ! empty( $entry->players ) ) {
                                    foreach ( $entry->players as $player ) {
                                        $player_link = $tournament->link . 'player/' . seo_url( $player->display_name ) . '/';
                                        ?>
                                        <div class="team-player">
                                            <a href="<?php echo esc_attr( $player_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
                                                <?php echo esc_html( wp_unslash( $player->display_name ) ); ?>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <div class="col-3 team-rating">
                                <?php echo esc_html( $entry->rating ); ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
</div>
