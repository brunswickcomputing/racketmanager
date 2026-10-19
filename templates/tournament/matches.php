<?php
/**
 * Template for tournament matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Tournament_Matches_View_Model;

/** @var Tournament_Matches_View_Model $vm */
?>
<script>
    jQuery(document).ready(function() {
    });
</script>
<div class="container">
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="container">
                    <input type="hidden" id="tournamentId" value="<?php echo esc_attr( $vm->tournament_id ); ?>" />
                    <?php
                    if ( $vm->has_many_dates ) {
                        require RACKETMANAGER_PATH . 'templates/includes/tournament-date-selection.php';
                    } else {
                        ?>
                        <div class="d-block d-md-none">
                            <?php require RACKETMANAGER_PATH . 'templates/includes/tournament-date-selection.php'; ?>
                        </div>
                        <ul class="d-none d-md-flex nav nav-tabs frontend match-date-list" id="match_date_tabs" role="tablist">
                            <?php
                            foreach ( $vm->match_dates as $tab ) {
                                ?>
                                <li class="nav-item nav-link <?php echo esc_attr( $tab->selected_class ); ?>" role="presentation">
                                    <a href="<?php echo esc_attr( $tab->matches_link ); ?>" data-value="<?php echo esc_attr( $tab->date ); ?>" class="nav-link__value tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $vm->tournament_id ); ?>" data-season="" data-link="<?php echo esc_attr( $tab->matches_link ); ?>" data-link-id="<?php echo esc_attr( $tab->date ); ?>" data-link-type="matches">
                                        <span class="date__weekday">
                                            <?php echo esc_html( $tab->weekday ); ?>
                                        </span>
                                        <span class="date__day">
                                            <?php echo esc_html( $tab->day ); ?>
                                        </span>
                                        <span class="date__month">
                                            <?php echo esc_html( $tab->month ); ?>
                                        </span>
                                    </a>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </div>
                <?php
                if ( $vm->has_matches ) {
                    ?>
                    <h3 class="header"><?php esc_html_e( 'Match Schedule', 'racketmanager' ); ?></h3>
                    <?php require RACKETMANAGER_PATH . 'templates/includes/nav-pills.php'; ?>
                    <ul class="match-group">
                        <?php
                        foreach ( $vm->match_groups as $group ) {
                            ?>
                            <li class="match-group__item">
                                <div class="match-group__wrapper">
                                    <h5 class="match-group__header"><?php echo esc_html( $group->header ); ?></h5>
                                    <ul class="match-group">
                                        <?php
                                        foreach ( $group->fixtures as $card ) {
                                            require RACKETMANAGER_PATH . 'templates/tournament/match.php';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                } else {
                    esc_html_e( 'No matches', 'racketmanager' );
                }
                ?>
            </div>
        </div>
    </div>
</div>
