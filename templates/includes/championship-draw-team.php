<?php
/**
 * Template for championship draw team output
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $team */
/** @var object $match */
$is_player_entry = ! empty( $match->league->event->competition->is_player_entry );
if ( -1 === ( $team->id ?? 0 ) && $is_player_entry ) {
    echo '&nbsp;<br/>' . esc_html( (string) ( $team->title ?? '' ) );
} elseif ( empty( $team->player ) && empty( $team->players ) ) {
    $team_url = $team->team_url ?? ( $team_urls[ $team->id ?? 0 ] ?? null );
    if ( ! empty( $team_url ) ) {
        ?>
            <a href="<?php echo esc_url( $team_url ); ?>">
            <?php
    }
    $event_type = $match->league->event->type ?? '';
    if ( $event_type instanceof \BackedEnum ) {
        $event_type = $event_type->value;
    }
    $team_title = (string) ( $team->title ?? '' );
    $team_name  = match ( substr( (string) $event_type, 0, 1 ) ) {
        'M'     => str_replace( 'Mens ', '', $team_title ),
        'W'     => str_replace( 'Ladies ', '', $team_title ),
        'X'     => str_replace( 'Mixed ', '', $team_title ),
        default => $team_title,
    };
    echo esc_html( $team_name );
    if ( ! empty( $team_url ) ) {
        ?>
            </a>
        <?php
    }
} else {
    $players_list = ! empty( $team->players ) ? $team->players : ( $team->player ?? [] );
    foreach ( $players_list as $player ) {
        $player_name = trim( is_object( $player ) && method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : (string) ( $player->title ?? $player ) );
        $player_id   = is_object( $player ) && method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? 0 );
        $player_url  = is_object( $player ) && isset( $player->player_url ) ? $player->player_url : ( $player_urls[ $player_id ] ?? ( $player_urls[ $player_name ] ?? null ) );
        ?>
            <div class="draw-player">
            <?php
            if ( ! empty( $player_url ) ) {
                ?>
                    <a href="<?php echo esc_url( $player_url ); ?>">
                    <?php
            }
            ?>
            <?php echo esc_html( $player_name ); ?>
                <?php
                if ( ! empty( $player_url ) ) {
                    ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php
    }
}
