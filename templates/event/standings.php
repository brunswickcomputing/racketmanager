<?php
/**
 * Template for event standings
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
/** @var array<int, string> $league_links */
$league_links = $league_links ?? [];
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div id="leagues">
                <?php
                foreach ( $event->leagues as $league ) {
                    $league_id = is_object( $league ) ? (int) ( $league->id ?? 0 ) : (int) ( $league['id'] ?? 0 );
                    $href      = $league_links[ $league_id ] ?? '';
                    ?>
                    <!-- Standings Table -->
                    <div id="standings-archive">
                        <h4 class="header">
                            <?php if ( '' !== $href ) : ?>
                                <a href="<?php echo esc_url( $href ); ?>">
                                    <?php echo esc_html( $league->title ); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html( $league->title ); ?>
                            <?php endif; ?>
                            <?php
                            $favourite_type = 'league';
                            $favourite_id   = $league->id;
                            require RACKETMANAGER_PATH . 'templates/includes/favourite.php';
                            ?>
                        </h4>
                        <?php
                        league_standings(
                            $league->id,
                            array(
                                'season'   => $event->current_season['name'],
                                'template' => '',
                            )
                        );
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
