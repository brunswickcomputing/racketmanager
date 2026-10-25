<?php
/**
 * Template for match list when multiple matches found for show match screen
 *
 * @deprecated 10.0.0 Orphan template (no caller in plugin source). Retained ONLY as a legacy
 * compatibility exception for custom themes; scheduled for removal in 11.0.0.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

if ( function_exists( '_deprecated_file' ) ) {
    _deprecated_file( __FILE__, '10.0.0', null, 'Orphan template retained for custom theme backward compatibility; scheduled for removal in 11.0.0.' );
}

global $racketmanager;

use Racketmanager\Util\Util;

/** @var object $league */
/** @var string $season */
/** @var string $home_team */
/** @var string $away_team */
/** @var array $matches */
/** @var string $action */

$competition_type = '';
if ( isset( $league->event->competition->type ) ) {
    $competition_type = $league->event->competition->type instanceof \BackedEnum
        ? $league->event->competition->type->value
        : (string) $league->event->competition->type;
}
$event_name     = $league->event->name ?? ( $league->title ?? ( $league->name ?? '' ) );
$season_str     = (string) ( $season ?? '' );
$home_team_name = is_object( $home_team ?? null ) ? ( $home_team->name ?? $home_team->title ?? '' ) : (string) ( $home_team ?? '' );
$away_team_name = is_object( $away_team ?? null ) ? ( $away_team->name ?? $away_team->title ?? '' ) : (string) ( $away_team ?? '' );
$date_format    = $racketmanager->date_format ?? get_option( 'date_format', 'd/m/Y' );
?>
    <div id="match-header" class="team-match-header module module--dark module--card row">
        <div class="module__content">
            <div class="module-container">
                <div class="text-center">
                    <a href="/<?php echo esc_attr( $competition_type ); ?>s/<?php echo esc_attr( seo_url( $event_name ) ); ?>/<?php echo esc_attr( $season_str ); ?>/">
                        <span class="nav-link__value"><?php echo esc_html( $event_name ); ?></span>
                    </a>
                    <div class="text-center mb-3">
                        <?php
                        if ( ! empty( $round ) ) {
                            ?>
                            <span><?php echo esc_html( Util::get_final_name( $round ) ); ?></span>
                            <?php
                        } elseif ( ! empty( $match_day ) ) {
                            ?>
                            <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match_day ); ?></span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="team-match">
                    <div class="media no-score">
                        <div class="media__wrapper">
                            <div class="media__content">
                                <h2 class="team-match__name is-team-1" title="<?php echo esc_html( ucwords( $home_team_name ) ); ?>">
                                    <a href="" class="nav--link">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( ucwords( $home_team_name ) ); ?>
                                        </span>
                                    </a>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <div class="media media--reverse no-score">
                        <div class="media__wrapper">
                            <div class="media__content">
                                <h2 class="team-match__name is-team-2" title="<?php echo esc_html( ucwords( $away_team_name ) ); ?>">
                                    <a href="" class="nav--link">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( ucwords( $away_team_name ) ); ?>
                                        </span>
                                    </a>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title">
                <?php esc_html_e( 'Matches', 'racketmanager' ); ?>
            </h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                $matches_list = is_iterable( $matches ?? null ) ? $matches : [];
                foreach ( $matches_list as $match ) {
                    $match_leg  = is_object( $match ) ? ( $match->leg ?? null ) : ( $match['leg'] ?? null );
                    $match_link = is_object( $match ) ? ( $match->link ?? null ) : ( $match['link'] ?? null );
                    $match_date = is_object( $match ) ? ( $match->date ?? null ) : ( $match['date'] ?? null );
                    if ( ! empty( $match_leg ) && ! empty( $match_link ) ) {
                        if ( ! empty( $action ) ) {
                            $match_link .= $action . '/';
                        }
                    } else {
                        $match_link = null;
                    }
                    $formatted_date = ! empty( $match_date ) ? mysql2date( $date_format, $match_date ) : '';
                    ?>
                    <div class="match-row row justify-content-center">
                        <div class="col-6 col-md-2"><time datetime="<?php echo esc_attr( (string) $match_date ); ?>"><?php echo esc_html( (string) $formatted_date ); ?></time></div>
                        <?php
                        if ( ! empty( $match_leg ) ) {
                            ?>
                            <div class="col-6 col-md-2">
                                <a style="display: flex;" href="<?php echo esc_attr( (string) $match_link ); ?>">
                                    <?php echo esc_html__( 'Leg', 'racketmanager' ) . ' ' . esc_html( $match_leg ); ?>
                                </a>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
