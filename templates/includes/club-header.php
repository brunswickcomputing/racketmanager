<?php
/**
 *
 * Template page to display single club
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *  $club_players: club Players object
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

use Racketmanager\Presenters\Club_Presenter;

/** @var object $club */
$club_presenter = $club_presenter ?? new Club_Presenter();
$standalone     = ! empty( $standalone );
$heading_type   = $club_presenter->get_heading_tag( $standalone, $header_level ?? null );
$club_link      = $club_presenter->get_link( $club );
$club_name      = $club_presenter->get_display_name( $club );
?>
<div class="page-subhead">
    <div class="media">
        <div class="media__wrapper">
            <div class="media__img">
                <span class="profile-icon">
                    <span class="profile-icon__abbr">
                        <?php
                        $words    = explode( ' ', $club->shortcode );
                        $initials = null;
                        foreach ( $words as $w ) {
                            $initials .= $w[0];
                        }
                        echo esc_html( $initials );
                        ?>
                    </span>
                </span>
            </div>
            <div class="media__content">
                <<?php echo esc_html( $heading_type ); ?> class="media__title">
                    <?php
                    if ( ! $standalone ) {
                        ?>
                        <a href="<?php echo esc_url( $club_link ); ?>">
                        <?php
                    }
                    echo esc_html( $club_name );
                    if ( ! $standalone ) {
                        ?>
                        </a>
                        <?php
                    }
                    ?>
                </<?php echo esc_html( $heading_type ); ?>>
            </div>
        </div>
        <ul class="media__icons">
            <li class="media__icons-item">
                <?php
                $favourite_type = 'club';
                $favourite_id   = $club->id;
                require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
                ?>
            </li>
        </ul>
    </div>
</div>
