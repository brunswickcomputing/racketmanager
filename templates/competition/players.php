<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
if ( empty( $competition->player ) ) {
    $player_list  = $competition->players;
    $player_link  = $player_link ?? '';
    $player_links = $player_links ?? [];
    require RACKETMANAGER_PATH . 'templates/includes/player-list-names.php';
} else {
    $player = $competition->player;
    require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
    ?>
    <div class="page_content row">
        <div class="page-content__main col-12 col-lg-6">
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <div class="module">
                            <?php
                            foreach ( $player->matches as $key => $player_matches ) {
                                $league      = $player_matches['league'];
                                $event_link  = $match_group_links[ (string) $key ]['event_link'] ?? '';
                                $league_link = $match_group_links[ (string) $key ]['league_link'] ?? '';
                                ?>
                                <h4 class="module-divider">
                                    <span class="module-divider__body">
                                        <?php if ( '' !== $event_link ) : ?>
                                            <a href="<?php echo esc_attr( $event_link ); ?>">
                                                <span class="nav-link__value">
                                                    <?php echo esc_html( $league->event->name ); ?>
                                                </span>
                                            </a>
                                        <?php else : ?>
                                            <span class="nav-link__value">
                                                <?php echo esc_html( $league->event->name ); ?>
                                            </span>
                                        <?php endif; ?>&nbsp;&#8226;&nbsp;
                                        <?php if ( '' !== $league_link ) : ?>
                                            <a href="<?php echo esc_attr( $league_link ); ?>">
                                                <span class="nav-link__value">
                                                    <?php echo esc_html( $league->title ); ?>
                                                </span>
                                            </a>
                                        <?php else : ?>
                                            <span class="nav-link__value">
                                                <?php echo esc_html( $league->title ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </h4>
                                <?php
                                foreach ( $player_matches['matches'] as $match ) {
                                    echo match_detail( $match->id, $competition->player->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-content__sidebar col-12 col-lg-6">
            <?php
            if ( ! empty( $competition->player->statistics ) ) {
                $player_statistics = $competition->player->statistics;
                require RACKETMANAGER_PATH . 'templates/includes/player-statistics.php';
            }
            ?>
        </div>
    </div>
    <?php
}
?>
