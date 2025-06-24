<?php
/**
 * Event matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $event */
?>
<div class="container">
    <div class="row table-header">
        <div class="d-none d-lg-block col-1"><?php esc_html_e( 'Match day', 'racketmanager' ); ?></div>
        <div class="col-4 col-lg-2"><?php esc_html_e( 'Match date', 'racketmanager' ); ?></div>
        <div class="col-8 col-lg-5"><?php esc_html_e( 'Match', 'racketmanager' ); ?></div>
        <div class="d-none d-lg-block col-3"><?php esc_html_e( 'League', 'racketmanager' ); ?></div>
    </div>

    <?php
    if ( $event->get_season_event() ) {
        $matches = $this->get_matches(
            array(
                'event_id' => $event->id,
                'season'   => $event->get_season_event()['name'],
                'orderby'  => array(
                    'match_day' => 'ASC',
                    'date'      => 'ASC',
                    'league_id' => 'ASC',
                    'home_team' => 'ASC',
                ),
            )
        );
        if ( $matches ) {
            $match_day = '';
            foreach ( $matches as $match ) {
                if ( $match->match_day !== $match_day ) {
                    $match_day = $match->match_day;
                    ?>
                    <div class="row table-row">
                        <div class="col-12"><?php echo esc_html_e( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match_day ); ?></div>
                    </div>
                    <?php
                }
                ?>
                <div class="row table-row">
                    <div class="d-none d-lg-block col-1"></div>
                    <div class="col-4 col-lg-2"><?php echo esc_html( $match->date ); ?></div>
                    <div class="col-8 col-lg-5"><?php echo esc_html( $match->match_title ); ?></div>
                    <div class="d-none d-lg-block col-3"><?php echo esc_html( $match->league->title ); ?></div>
                </div>
                <?php
            }
        }
    }
    ?>
</div>
