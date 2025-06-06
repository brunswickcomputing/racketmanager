<?php
/**
 * Template for competition teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
    <div class="module__banner">
        <h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
    </div>
    <div class="module__content">
        <div class="module-container">
            <?php
            if ( ! empty( $competition->teams ) ) {
                ?>
                <div class="col-12">
                    <div class="row mb-2 row-header">
                        <div class="col-4">
                            <?php esc_html_e( 'Team', 'racketmanager' ); ?>
                        </div>
                        <div class="col-4">
                            <?php esc_html_e( 'Club', 'racketmanager' ); ?>
                        </div>
                    <?php
                    if ( $competition->is_championship ) {
                        ?>
                        <div class="col-3">
                            <?php esc_html_e( 'Draw', 'racketmanager' ); ?>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="col-3">
                            <?php esc_html_e( 'League', 'racketmanager' ); ?>
                        </div>
                        <?php
                    }
                    ?>
                    <?php
                    if ( $competition->is_team_entry ) {
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
                    foreach ( $competition->teams as $team ) {
                        $league_link = $competition->type . '/' . seo_url( $team->league_title ) . '/' . $competition->current_season['name'] . '/';
                        $club_link   = '/' . seo_url( $competition->name ) . '/' . $competition->current_season['name'] . '/club/' . seo_url( $team->club->shortcode ) . '/';
                        ?>
                        <div class="row mb-2 row-list">
                            <div class="col-4" name="<?php esc_html_e( 'Team', 'racketmanager' ); ?>">
                                <a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
                                    <?php echo esc_html( $team->name ); ?>
                                </a>
                            </div>
                            <div class="col-4" name="<?php esc_html_e( 'club', 'racketmanager' ); ?>">
                                <a href="<?php echo esc_attr( $club_link ); ?>" class="tabDataLink" data-type="competition" data-type-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $competition->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $club_link ); ?>" data-link-id="<?php echo esc_attr( $team->club->id ); ?>" data-link-type="clubs">
                                    <?php echo esc_html( $team->club->shortcode ); ?>
                                </a>
                            </div>
                            <div class="col-3" name="<?php esc_html_e( 'league', 'racketmanager' ); ?>">
                                <a href="/<?php echo esc_attr( $league_link ); ?>">
                                    <?php echo esc_html( $team->league_title ); ?>
                                </a>
                            </div>
                            <?php
                            if ( $competition->is_team_entry ) {
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
