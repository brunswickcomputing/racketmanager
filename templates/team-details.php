<?php
/**
 * Template page for Team List
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $object league or event object
 *  $teams: all teams of league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var object $object */
/** @var object $team_details */
$team = $team_details->team;
$club = $team_details->club;
$envelope           = RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#envelope-fill';
$telephone          = RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#telephone-fill';
$user_can_edit_team = $vm->user_can_edit_team;
$calendar_url       = $vm->calendar_url;
if ( isset( $object->competition ) ) {
    $object_competition = $object->competition;
    $object_event       = $object;
    $object_type        = 'event';
} else {
    $object_competition = $object->event->competition;
    $object_event       = $object->event;
    $object_type        = 'league';
}
$competition_type = $object_competition->type instanceof \BackedEnum ? $object_competition->type->value : (string) ( $object_competition->type ?? '' );
$show_wtn         = $vm->show_wtn;
$format           = $vm->format;
$help_text        = $vm->help_text;
$standings_link   = $vm->standings_link;
$club_link        = $vm->club_link;
$match_time_val   = $vm->match_time_formatted ?? '';
?>
    <div class="page-subhead">
        <div class="media">
            <div class="media__wrapper">
                <div class="media__img">
                    <span class="profile-icon">
                        <span class="profile-icon__abbr">
                            <?php
                            $words    = array_filter( explode( ' ', (string) ( $team->get_name() ?? '' ) ) );
                            $initials = '';
                            foreach ( $words as $w ) {
                                if ( '' !== $w ) {
                                    $initials .= $w[0];
                                }
                            }
                            echo esc_html( $initials );
                            ?>
                        </span>
                    </span>
                </div>
                <div class="media__content">
                    <h3 class="media__title">
                        <span><?php echo esc_html( $team->get_name() ); ?></span>
                        <?php
                        $favourite_type = 'team';
                        $favourite_id   = $team->get_id();
                        require 'includes/favourite-button.php';
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>
    <div class="page_content row">
        <div class="page-content__main col-12 col-lg-7">
            <?php
            if ( ! $object->is_championship ) {
                ?>
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title"><?php esc_html_e( 'Standings', 'racketmanager' ); ?></h3>
                        <div class="module__aside">
                            <button class="btn btn--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( $object->id ); ?>" data-season="<?php echo esc_attr( $object->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $standings_link ); ?>" data-link-id="" data-link-type="standings" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'View standings', 'racketmanager' ); ?>">
                                <svg width="32" height="32" class="icon ">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#table' ); ?>"></use>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <ul class="list list--grid list--bordered-left">
                                <li class="list__item">
                                    <div class="stats">
                                        <div class="stats__body">
                                            <span class="stats__title"><?php esc_html_e( 'Standing', 'racketmanager' ); ?></span>
                                            <span class="stats__value standing-status"><?php echo esc_html( $object->team->standings->rank ); ?></span>
                                        </div>
                                    </div>
                                </li>
                                <li class="list__item">
                                    <div class="stats">
                                        <div class="stats__body">
                                            <span class="stats__title"><?php esc_html_e( 'Played', 'racketmanager' ); ?></span>
                                            <span class="stats__value"><?php echo esc_html( $object->team->standings->done_matches ); ?></span>
                                        </div>
                                    </div>
                                </li>
                                <li class="list__item">
                                    <div class="stats">
                                        <div class="stats__body">
                                            <span class="stats__title"><?php esc_html_e( 'Points', 'racketmanager' ); ?></span>
                                            <span class="stats__value"><?php echo esc_html( $object->team->standings->points_formatted['primary'] ); ?></span>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            <?php
            if ( $object->team->matches ) {
                ?>
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
                        <div class="module__aside">
                            <a href="<?php echo esc_url( $calendar_url ); ?>" class="btn btn--link calendar-add" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Add Matches to Calendar', 'racketmanager' ); ?>" >
                                <svg width="32" height="32" class="icon ">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-fill' ); ?>"></use>
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <div class="module">
                                <?php
                                $matches = $object->team->matches;
                                if ( $object_competition->is_player_entry ) {
                                    foreach ( $matches as $card ) {
                                        if ( $card instanceof \Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model ) {
                                            require RACKETMANAGER_PATH . 'templates/tournament/match.php';
                                        } else {
                                            require RACKETMANAGER_PATH . 'templates/tournament/match-unavailable.php';
                                        }
                                    }
                                } else {
                                    require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="page-content__sidebar col-12 col-lg-5">
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title">
                        <?php
                        if ( $object_competition->is_player_entry ) {
                            esc_html_e( 'Contact details', 'racketmanager' );
                        } else {
                            esc_html_e( 'Team captain', 'racketmanager' );
                        }
                        ?>
                    </h3>
                    <?php
                    if ( $user_can_edit_team ) {
                        ?>
                        <div class="module__aside">
                            <button class="btn btn--link" id="teamEditLink" data-action="open-team-edit-modal" data-team-id="<?php echo esc_attr( $team->get_id() ); ?>" data-event-id="<?php echo esc_attr( $object_event->id ); ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Edit team', 'racketmanager' ); ?>">
                                <svg width="16" height="16" class="icon ">
                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil-fill' ); ?>"></use>
                                </svg>
                            </button>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <?php
                        if ( $object_competition->is_team_entry ) {
                            ?>
                            <h4 class="subheading" id="captain-name-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $team->get_id() ); ?>">
                                <?php echo esc_html( $object->team->info->captain ); ?>
                            </h4>
                            <?php
                        }
                        ?>
                        <ul class="list list--naked">
                            <?php
                            if ( is_user_logged_in() ) {
                                if ( $object_competition->is_player_entry ) {
                                    foreach ( $object->team->info->players as $team_player ) {
                                        ?>
                                        <h4 class="subheading">
                                            <?php echo esc_html( $team_player->display_name ); ?>
                                        </h4>
                                        <?php
                                        if ( ! empty( $team_player->contactno ) ) {
                                            ?>
                                            <li class="list__item">
                                                <a href="tel:<?php echo esc_html( $team_player->contactno ); ?>" class="nav--link" rel="nofollow">
                                                    <svg width="16" height="16" class="">
                                                        <use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
                                                    </svg>
                                                    <span class="nav--link">
                                                        <span class="nav-link__value" id="captain-contact-no">
                                                            <?php echo esc_html( $team_player->contactno ); ?>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <?php
                                        }
                                        if ( ! empty( $team_player->email ) ) {
                                            ?>
                                            <li class="list__item">
                                                <a href="mailto:<?php echo esc_html( $team_player->email ); ?>" class="nav--link">
                                                    <svg width="16" height="16" class="">
                                                        <use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
                                                    </svg>
                                                    <span class="nav--link">
                                                        <span class="nav-link__value" id="captain-contact-email">
                                                            <?php echo esc_html( $team_player->email ); ?>
                                                        </span>
                                                    </span>
                                                </a>
                                            </li>
                                            <?php
                                        }
                                    }
                                } else {
                                    if ( ! empty( $object->team->info->contactno ) ) {
                                        ?>
                                        <li class="list__item">
                                            <a href="tel:<?php echo esc_html( $object->team->info->contactno ); ?>" class="nav--link" rel="nofollow">
                                                <svg width="16" height="16" class="">
                                                    <use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
                                                </svg>
                                                <span class="nav--link">
                                                    <span class="nav-link__value" id="captain-contact-no-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $team->get_id() ); ?>">
                                                        <?php echo esc_html( $object->team->info->contactno ); ?>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    if ( ! empty( $object->team->info->contactemail ) ) {
                                        ?>
                                        <li class="list__item">
                                            <a href="mailto:<?php echo esc_html( $object->team->info->contactemail ); ?>" class="nav--link">
                                                <svg width="16" height="16" class="">
                                                    <use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
                                                </svg>
                                                <span class="nav--link">
                                                    <span class="nav-link__value" id="captain-contact-email-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $team->get_id() ); ?>">
                                                        <?php echo esc_html( $object->team->info->contactemail ); ?>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                }
                            }
                            ?>
                            <?php
                            if ( $object_competition->is_team_entry ) {
                                ?>
                                <li class="list__item">
                                    <span class="nav--link">
                                        <svg width="16" height="16" class="icon-team">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-day-fill' ); ?>"></use>
                                        </svg>
                                        <span class="nav-link__value" id="team-match-day-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $team->get_id() ); ?>">
                                            <?php echo esc_html( $object->team->info->match_day ); ?>
                                        </span>
                                    </span>
                                </li>
                                <li class="list__item">
                                    <span class="nav--link">
                                        <svg width="16" height="16" class="icon-team">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#clock-fill' ); ?>"></use>
                                        </svg>
                                        <span class="nav-link__value" id="team-match-time-<?php echo esc_attr( $object_event->id ); ?>-<?php echo esc_attr( $team->get_id() ); ?>">
                                            <?php echo esc_html( $match_time_val ); ?>
                                        </span>
                                    </span>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php
            if ( $object_competition->is_team_entry ) {
                ?>
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title">
                            <?php esc_html_e( 'Club', 'racketmanager' ); ?>
                        </h3>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <h4 class="subheading">
                                <a href="<?php echo esc_attr( ! empty( $club_link ) ? $club_link : '#' ); ?>">
                                    <span><?php echo esc_html( $team_details->club->name ); ?></span>
                                </a>
                            </h4>
                            <ul class="list list--naked">
                                <li class="list__item">
                                    <span class="nav--link">
                                        <svg width="16" height="16" class="icon-marker">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons.svg#icon-marker' ); ?>"></use>
                                        </svg>
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $team_details->club->address ); ?>
                                        </span>
                                    </span>
                                </li>
                                <?php if ( ! empty( $team_details->match_secretary->display_name ) ) : ?>
                                <li class="list__item">
                                    <span class="nav--link">
                                        <svg width="16" height="16" class="icon-captain">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons.svg#icon-captain' ); ?>"></use>
                                        </svg>
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $team_details->match_secretary->display_name ); ?>
                                        </span>
                                    </span>
                                </li>
                                <?php endif; ?>
                                <?php
                                if ( is_user_logged_in() ) {
                                    if ( ! empty( $team_details->match_secretary->contactno ) ) {
                                        ?>
                                        <li class="list__item">
                                            <a href="tel:<?php echo esc_html( $team_details->match_secretary->contactno ); ?>" class="nav--link" rel="nofollow">
                                                <svg width="16" height="16" class="">
                                                    <use xlink:href="<?php echo esc_url( $telephone ); ?>"></use>
                                                </svg>
                                                <span class="nav--link">
                                                    <span class="nav-link__value">
                                                        <?php echo esc_html( $team_details->match_secretary->contactno ); ?>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                    <?php
                                    if ( ! empty( $team_details->match_secretary->email ) ) {
                                        ?>
                                        <li class="list__item">
                                            <a href="mailto:<?php echo esc_html( $team_details->match_secretary->email ); ?>" class="nav--link">
                                                <svg width="16" height="16" class="">
                                                    <use xlink:href="<?php echo esc_url( $envelope ); ?>"></use>
                                                </svg>
                                                <span class="nav--link">
                                                    <span class="nav-link__value">
                                                        <?php echo esc_html( $team_details->match_secretary->email ); ?>
                                                    </span>
                                                </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                }
                                ?>
                                <?php
                                if ( ! empty( $team_details->club->website ) ) {
                                    ?>
                                    <li class="list__item">
                                        <a href="<?php echo esc_html( $team_details->club->website ); ?>" class="nav--link" target="_blank" rel="noopener nofollow">
                                            <svg width="16" height="16" class="icon-globe">
                                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#globe' ); ?>"></use>
                                            </svg>
                                            <span class="nav--link">
                                                <span class="nav-link__value">
                                                    <?php echo esc_html( $team_details->club->website ); ?>
                                                </span>
                                            </span>
                                        </a>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <div class="col-12">
                                <ol class="list list--bordered list--count">
                                    <?php
                                    foreach ( $object->team->players as $player ) {
                                        $selected_player = false;
                                        $player_id = method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? null );
                                        if ( intval( $player_id ) === get_current_user_id() ) {
                                            $selected_player = true;
                                        }
                                        $player_link = $vm->player_links[ $player_id ] ?? '';
                                        ?>
                                        <li class="list__item <?php echo empty( $selected_player ) ? null : 'is-selected'; ?>">
                                            <div class="media">
                                                <div class="media__wrapper">
                                                    <div class="media__img">
                                                        <div class="profile-icon">
                                                            <span class="profile-icon__abbr">
                                                                <?php
                                                                $player_initials = substr( $player->get_firstname(), 0, 1 ) . substr( $player->get_surname(), 0, 1 );
                                                                echo esc_html( $player_initials );
                                                                ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="media__content">
                                                        <div class="flex-container">
                                                            <div class="flex-item flex-item--grow">
                                                                <p class="media__title">
                                                                    <a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="<?php echo esc_attr( $object_type ); ?>" data-type-id="<?php echo esc_attr( $object->id ); ?>" data-season="<?php echo esc_attr( $object->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
                                                                        <span class="nav-link__value">
                                                                            <?php echo esc_html( $player->get_fullname() ); ?>
                                                                        </span>
                                                                    </a>
                                                                </p>
                                                                <?php
                                                                if ( $show_wtn ) {
                                                                    ?>
                                                                    <div class="media__content-subinfo">
                                                                        <small class="media__subheading">
                                                                            <span class="nav--link">
                                                                                <span class="nav-link__value" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_attr( $help_text ); ?>"><?php echo esc_attr( $player->wtn[ $format ] ); ?></span>
                                                                            </span>
                                                                        </small>
                                                                    </div>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </div>
                                                            <div class="progress-bar-container">
                                                                <?php
                                                                if ( $player->played ) {
                                                                    ?>
                                                                    <div class="clearfix">
                                                                        <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                                                        <span class="pull-right"><?php echo esc_html( $player->matches_won ) . '-' . esc_html( $player->matches_lost ) . ' (' . esc_html( $player->played ) . ')'; ?></span>
                                                                    </div>
                                                                    <div class="progress">
                                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
                                                                    </div>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="modal" id="teamModal"></div>
    <?php require RACKETMANAGER_PATH . 'templates/includes/modal-loading.php'; ?>
    <?php

