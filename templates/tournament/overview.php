<?php
/**
 * Template for tournament overview
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Tournament_Date_View_Model;

/** @var object $tournament */
/** @var ?Tournament_Date_View_Model $tournament_dates */

$dates_vm = $tournament_dates ?? null;
?>
<div class="container">
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Overview', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="container tournament-meta">
                    <ul class="module tournament-meta__info">
                        <li class="tournament-meta__info_block">
                            <div class="text--low-opacity text--small">
                                <?php esc_html_e( 'Venue', 'racketmanager' ); ?>
                            </div>
                            <div class="tournament-meta__title">
                                <span class="meta-link">
                                    <span class="meta-link__value">
                                        <?php echo esc_html( (string) ( $tournament->venue_name ?? '' ) ); ?>
                                    </span>
                                </span>
                            </div>
                        </li>
                        <li class="tournament-meta__info_block">
                            <div class="text--low-opacity text--small">
                                <?php esc_html_e( 'Events', 'racketmanager' ); ?>
                            </div>
                            <div class="tournament-meta__title">
                                <span class="meta-link">
                                    <span class="meta-link__value">
                                        <?php echo esc_html( (string) ( $tournament->num_events ?? '' ) ); ?>
                                    </span>
                                </span>
                            </div>
                        </li>
                        <li class="tournament-meta__info_block">
                            <div class="text--low-opacity text--small">
                                <?php esc_html_e( 'Entries', 'racketmanager' ); ?>
                            </div>
                            <div class="tournament-meta__title">
                                <span class="meta-link">
                                    <span class="meta-link__value">
                                        <?php echo esc_html( (string) ( $tournament->num_entries ?? '' ) ); ?>
                                    </span>
                                </span>
                            </div>
                        </li>
                    </ul>
                    <div class="tournament-meta__timeline">
                        <ol class="list--timeline-labelled list--timeline list has-custom-icon">
                            <li class="list__item is-entry-open <?php echo ( 'open' === ( $tournament->phase ?? '' ) ) ? 'is-current is-success' : null; ?>">
                                <div class="list__value">
                                    <?php esc_html_e( 'Entry opens', 'racketmanager' ); ?>
                                </div>
                                <div class="list__meta">
                                    <?php echo esc_html( $dates_vm?->date_open_display ?? 'N/A' ); ?>
                                </div>
                            </li>
                            <li class="list__item is-entry-closed <?php echo ( 'closed' === ( $tournament->phase ?? '' ) ) ? 'is-current is-warning' : null; ?>">
                                <div class="list__value">
                                    <?php esc_html_e( 'Closing deadline', 'racketmanager' ); ?>
                                </div>
                                <div class="list__meta">
                                    <?php echo esc_html( $dates_vm?->date_closing_display ?? 'N/A' ); ?>
                                </div>
                            </li>
                            <?php
                            $withdrawal_display = $dates_vm?->date_withdrawal_display ?? 'N/A';
                            if ( ! empty( $tournament->date_withdrawal ) || 'N/A' !== $withdrawal_display ) {
                                ?>
                                <li class="list__item is-withdrawal-deadline <?php echo ( 'withdraw' === ( $tournament->phase ?? '' ) ) ? 'is-current is-warning' : null; ?>">
                                    <div class="list__value">
                                        <?php esc_html_e( 'Withdrawal deadline', 'racketmanager' ); ?>
                                    </div>
                                    <div class="list__meta">
                                        <?php echo esc_html( $withdrawal_display ); ?>
                                    </div>
                                </li>
                                <?php
                            }
                            ?>
                            <li class="list__item is-started <?php echo ( 'in_progress' === ( $tournament->phase ?? '' ) ) ? ' is-current is-success' : null; ?>">
                                <div class="list__value">
                                    <?php esc_html_e( 'Start tournament', 'racketmanager' ); ?>
                                </div>
                                <div class="list__meta">
                                    <?php echo esc_html( $dates_vm?->date_start_display ?? 'N/A' ); ?>
                                </div>
                            </li>
                            <li class="list__item is-finished <?php echo ( 'end' === ( $tournament->phase ?? '' ) ) ? ' is-current is-danger' : null; ?>">
                                <div class="list__value">
                                    <?php esc_html_e( 'End of tournament', 'racketmanager' ); ?>
                                </div>
                                <div class="list__meta">
                                    <?php echo esc_html( $dates_vm?->date_end_display ?? ( $dates_vm?->date_display ?? 'TBC' ) ); ?>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
