<?php
/**
 * Template for competition overview
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Competition_Overview_View_Model;

/** @var object|null $competition */
/** @var object|null $overview */
/** @var array|null $competition_season */
/** @var Competition_Overview_View_Model|null $overview_vm */

$vm = $overview_vm ?? null;
if ( ! $vm instanceof Competition_Overview_View_Model ) {
    return;
}
?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Overview', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="container tournament-meta">
                    <ul class="module tournament-meta__info">
                        <?php if ( ! empty( $vm->venue_name ) ) : ?>
                            <li class="tournament-meta__info_block">
                                <div class="text--low-opacity text--small">
                                    <?php esc_html_e( 'Venue', 'racketmanager' ); ?>
                                </div>
                                <div class="tournament-meta__title">
                                    <span class="meta-link">
                                        <span class="meta-link__value">
                                            <?php echo esc_html( $vm->venue_name ); ?>
                                        </span>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>
                        <li class="tournament-meta__info_block">
                            <div class="text--low-opacity text--small">
                                <?php esc_html_e( 'Events', 'racketmanager' ); ?>
                            </div>
                            <div class="tournament-meta__title">
                                <span class="meta-link">
                                    <span class="meta-link__value">
                                        <?php echo esc_html( (string) $vm->num_events ); ?>
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
                                        <?php echo esc_html( (string) $vm->num_teams ); ?>
                                    </span>
                                </span>
                            </div>
                        </li>
                        <?php if ( ! empty( $vm->num_players ) ) : ?>
                            <li class="tournament-meta__info_block">
                                <div class="text--low-opacity text--small">
                                    <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                                </div>
                                <div class="tournament-meta__title">
                                    <span class="meta-link">
                                        <span class="meta-link__value">
                                            <?php echo esc_html( (string) $vm->num_players ); ?>
                                        </span>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if ( ! empty( $vm->grade ) ) : ?>
                            <li class="tournament-meta__info_block">
                                <div class="text--low-opacity text--small">
                                    <?php esc_html_e( 'Grade', 'racketmanager' ); ?>
                                </div>
                                <div class="tournament-meta__title">
                                    <span class="meta-link">
                                        <span class="meta-link__value">
                                            <?php echo esc_html( $vm->grade ); ?>
                                        </span>
                                    </span>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="tournament-meta__timeline">
                        <ol class="list--timeline-labelled list--timeline list has-custom-icon">
                            <?php foreach ( $vm->timeline_items as $item ) : ?>
                                <li class="list__item <?php echo esc_attr( $item['item_class'] ); ?> <?php echo ! empty( $item['current_class'] ) ? esc_attr( $item['current_class'] ) : ''; ?>">
                                    <div class="list__value">
                                        <?php echo esc_html( $item['label'] ); ?>
                                    </div>
                                    <div class="list__meta">
                                        <?php echo esc_html( $item['date'] ); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
