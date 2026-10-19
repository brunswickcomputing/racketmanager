<?php
/**
 * Template page for a team
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Team_Row_View_Model;

/** @var Team_Row_View_Model $team */
if ( $team instanceof Team_Row_View_Model ) {
    $row = $team;
} else {
    throw new \UnexpectedValueException( 'Expected Team_Row_View_Model, got ' . get_debug_type( $team ) );
}
?>
<div class="">
    <div class="media">
        <div class="media__wrapper">
            <div class="media__img">
                <span class="profile-icon">
                    <span class="profile-icon__abbr">
                        <?php echo esc_html( $row->initials ); ?>
                    </span>
                </span>
            </div>
            <div class="media__content">
                <h4 class="media__title">
                    <a href="<?php echo esc_attr( $row->link ); ?>" <?php echo $row->data_attrs; ?>>
                        <span><?php echo esc_html( $row->title ); ?></span>
                    </a>
                </h4>
            </div>
            <div class="media__aside">
                <?php
                if ( ! empty( $row->calendar_url ) ) {
                    ?>
                    <a href="<?php echo esc_url( $row->calendar_url ); ?>" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
                        <svg width="32" height="32" class="icon ">
                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-fill' ); ?>"></use>
                        </svg>
                        <span class="nav-link__value text-uppercase">
                            <?php esc_html_e( 'Calendar', 'racketmanager' ); ?>
                        </span>
                    </a>
                    <?php
                }
                ?>
                <?php
                $favourite_type = 'team';
                $favourite_id   = $row->id;
                require RACKETMANAGER_PATH . 'templates/includes/favourite-button.php';
                ?>
            </div>
        </div>
    </div>

    <ul class="list list--flex list--indent">
        <?php
        if ( $row->is_team_entry ) {
            ?>
            <li class="list__item">
                <span class="nav--link">
                    <svg width="16" height="16" class="icon-team">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons-extra.svg#icon-team' ); ?>"></use>
                    </svg>
                    <span class="nav-link__value" id="captain-name<?php echo esc_attr( (string) $row->suffix ); ?>">
                        <?php echo esc_html( (string) $row->captain_name ); ?>
                    </span>
                </span>
            </li>
            <?php
        }
        ?>
        <?php
        if ( is_user_logged_in() ) {
            if ( $row->is_player_entry ) {
                foreach ( $row->players as $team_player ) {
                    ?>
                    <h4 class="subheading">
                        <?php echo esc_html( $team_player['display_name'] ?? '' ); ?>
                    </h4>
                    <?php
                    if ( ! empty( $team_player['contactno'] ) ) {
                        ?>
                        <li class="list__item">
                            <a href="tel:<?php echo esc_html( $team_player['contactno'] ); ?>" class="nav--link" rel="nofollow">
                                <svg width="16" height="16" class="">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
                                </svg>
                                <span class="nav-link__value">
                                    <?php echo esc_html( $team_player['contactno'] ); ?>
                                </span>
                            </a>
                        </li>
                        <?php
                    }
                    if ( ! empty( $team_player['email'] ) ) {
                        ?>
                        <li class="list__item">
                            <a href="mailto:<?php echo esc_html( $team_player['email'] ); ?>" class="nav--link">
                                <svg width="16" height="16" class="">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
                                </svg>
                                <span class="nav-link__value">
                                    <?php echo esc_html( $team_player['email'] ); ?>
                                </span>
                            </a>
                        </li>
                        <?php
                    }
                }
            } else {
                if ( ! empty( $row->captain_contactno ) ) {
                    ?>
                    <li class="list__item">
                        <a href="tel:<?php echo esc_html( $row->captain_contactno ); ?>" class="nav--link" rel="nofollow">
                            <svg width="16" height="16" class="">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#telephone-fill' ); ?>"></use>
                            </svg>
                            <span class="nav-link__value" id="captain-contact-no<?php echo esc_attr( (string) $row->suffix ); ?>">
                                <?php echo esc_html( $row->captain_contactno ); ?>
                            </span>
                        </a>
                    </li>
                    <?php
                }
                if ( ! empty( $row->captain_contactemail ) ) {
                    ?>
                    <li class="list__item">
                        <a href="mailto:<?php echo esc_html( $row->captain_contactemail ); ?>" class="nav--link">
                            <svg width="16" height="16" class="">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#envelope-fill' ); ?>"></use>
                            </svg>
                            <span class="nav-link__value" id="captain-contact-email<?php echo esc_attr( (string) $row->suffix ); ?>">
                                <?php echo esc_html( $row->captain_contactemail ); ?>
                            </span>
                        </a>
                    </li>
                    <?php
                }
            }
        }
        ?>
        <?php
        if ( ! empty( $row->match_day ) ) {
            ?>
            <li class="list__item">
                <span class="nav--link">
                    <svg width="16" height="16" class="icon-team">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
                    </svg>
                    <span class="nav-link__value">
                        <?php echo esc_html( $row->match_day ); ?>
                    </span>
                </span>
            </li>
            <?php
        }
        ?>
        <?php
        if ( ! empty( $row->match_time_formatted ) ) {
            ?>
            <li class="list__item">
                <span class="nav--link">
                    <svg width="16" height="16" class="icon-team">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#clock-fill' ); ?>"></use>
                    </svg>
                    <span class="nav-link__value">
                        <?php echo esc_html( $row->match_time_formatted ); ?>
                    </span>
                </span>
            </li>
            <?php
        }
        ?>
    </ul>
</div>
