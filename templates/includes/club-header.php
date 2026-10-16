<?php
/**
 *
 * Template page to display single club header
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $club: club object
 *  $vm: Club_View_Model instance
 */

namespace Racketmanager;

use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\View_Models\Club_View_Model;

/** @var Club_View_Model|null $vm */
/** @var object|null $club */
$club_presenter = $club_presenter ?? new Club_Presenter();
$standalone     = $vm->standalone ?? ( ! empty( $standalone ) );
$heading_type   = $vm->heading_tag ?? $club_presenter->get_heading_tag( $standalone, $header_level ?? null );
$club_link      = $vm->link ?? ( isset( $club ) ? $club_presenter->get_link( $club ) : '' );
$club_name      = $vm->name ?? ( isset( $club ) ? $club_presenter->get_display_name( $club ) : '' );
$initials       = $vm->initials ?? ( isset( $club ) ? $club_presenter->get_initials( $club ) : '' );
$club_id        = $vm->id ?? ( $club->id ?? 0 );
?>
<div class="page-subhead">
    <div class="media">
        <div class="media__wrapper">
            <div class="media__img">
                <span class="profile-icon">
                    <span class="profile-icon__abbr">
                        <?php echo esc_html( $initials ); ?>
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
                $favourite_id   = $club_id;
                require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
                ?>
            </li>
        </ul>
    </div>
</div>
