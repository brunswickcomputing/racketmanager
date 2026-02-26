<?php
/**
 * Template for tournament event
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
/** @var object $event */
/** @var array  $entries */
?>
<div class="container">
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
            <h3 class="module__title"><?php echo esc_html__( 'Entries', 'racketmanager' ) . ' (' . esc_html( count( $event->entries ) ) . ')'; ?></h3>
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
                    foreach ( $event->entries as $entry ) {
                        ?>
                        <div class="row row-list">
                            <div class="col-1">
                                <?php
                                $num_seeds = $event->num_seeds;
                                if ( ! empty( $num_seeds ) ) {
                                    if ( intval( $entry->rank ) <= intval( $num_seeds ) ) {
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
                                        $player_link = $tournament->link . 'player/' . seo_url( $player->name ) . '/';
                                        ?>
                                        <div class="team-player">
                                            <a href="<?php echo esc_attr( $player_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
                                                <?php echo esc_html( wp_unslash( $player->name ) ); ?>
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
</div>
