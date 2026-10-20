<?php
/**
 * Template for event teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
/** @var array<int, array{team_link: string, club_link: string, league_link: string}> $team_links */
$team_links = $team_links ?? [];
?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <?php
            if ( ! empty( $event->teams ) ) {
                ?>
                <div class="col-12">
                    <div class="row mb-2 row-header">
                        <div class="col-4">
                            <?php esc_html_e( 'Team', 'racketmanager' ); ?>
                        </div>
                        <?php
                        if ( ! $event->is_box ) {
                            ?>
                            <div class="col-4">
                                <?php esc_html_e( 'Club', 'racketmanager' ); ?>
                            </div>
                            <?php
                        }
                        ?>
                        <div class="col-3">
                            <?php esc_html_e( 'League', 'racketmanager' ); ?>
                        </div>
                        <?php
                        if ( ! $event->is_box ) {
                            ?>
                            <div class="d-none d-sm-block col-1 text-end">
                                <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                            </div>
                            <div class="d-sm-none col-1 text-end">
                                <?php esc_html_e( 'Pls', 'racketmanager' ); ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                    foreach ( $event->teams as $team ) {
                        $team_id     = is_object( $team ) ? (int) ( $team->id ?? 0 ) : (int) ( $team['id'] ?? 0 );
                        $team_link   = $team_links[ $team_id ]['team_link'] ?? '';
                        $club_link   = $team_links[ $team_id ]['club_link'] ?? '';
                        $league_link = $team_links[ $team_id ]['league_link'] ?? '';
                        ?>
                        <div class="row mb-2 row-list">
                            <div class="col-4" name="<?php esc_html_e( 'Team', 'racketmanager' ); ?>">
                                <?php if ( '' !== $team_link ) : ?>
                                    <a href="<?php echo esc_attr( $team_link ); ?>">
                                        <?php echo esc_html( $team->name ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $team->name ); ?>
                                <?php endif; ?>
                            </div>
                            <?php
                            if ( ! $event->is_box ) {
                                ?>
                                <div class="col-4" name="<?php esc_html_e( 'club', 'racketmanager' ); ?>">
                                    <?php if ( '' !== $club_link ) : ?>
                                        <a href="<?php echo esc_attr( $club_link ); ?>" class="tabDataLink" data-type="event" data-type-id="<?php echo esc_attr( (string) $event->id ); ?>" data-season="<?php echo esc_attr( (string) ( $event->current_season['name'] ?? '' ) ); ?>" data-link="<?php echo esc_attr( $club_link ); ?>" data-link-id="<?php echo esc_attr( (string) ( $team->club->id ?? '' ) ); ?>" data-link-type="clubs">
                                            <?php echo esc_html( $team->club->shortcode ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html( $team->club->shortcode ); ?>
                                    <?php endif; ?>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="col-3" name="<?php esc_html_e( 'league', 'racketmanager' ); ?>">
                                <?php if ( '' !== $league_link ) : ?>
                                    <a href="<?php echo esc_attr( $league_link ); ?>">
                                        <?php echo esc_html( $team->league_title ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $team->league_title ); ?>
                                <?php endif; ?>
                            </div>
                            <?php
                            if ( ! $event->is_box ) {
                                ?>
                                <div class="col-1 text-end">
                                    <?php echo esc_html( $team->player_count ); ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            } else {
                esc_html_e( 'No teams found', 'racketmanager' );
            }
            ?>
        </div>
    </div>
</div>
