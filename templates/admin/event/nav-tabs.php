<?php
declare( strict_types=1 );
/**
 * Event tabs administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use BackedEnum;
use Racketmanager\Admin\View_Models\League_Event_Page_View_Model;
use Racketmanager\Admin\View_Models\League_Matches_Page_View_Model;
use stdClass;

/** @var string $tab */
/** @var object $event */
/** @var string $season */
/** @var League_Event_Page_View_Model|League_Matches_Page_View_Model|stdClass $vm */
$tab              = $tab ?? $vars['tab'] ?? $vm->tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
$event            = $event ?? $vars['event'] ?? $vm->event;
$season           = $season ?? $vars['season'] ?? $vm->season;
$competition_type = $event->competition->type instanceof BackedEnum ? $event->competition->type->value : (string) ( $event->competition->type ?? '' );
?>
<div class="row justify-content-end">
    <div class="col-auto racketmanager_breadcrumb">
        <a href="<?php echo esc_url( manage_url( $competition_type . 's' ) ); ?>"><?php echo esc_html( ucfirst( $competition_type ) ); ?>s</a> &raquo; <a href="<?php echo esc_url( manage_url( $competition_type . 's', 'seasons', [ 'competition_id' => (string) $event->competition->id ] ) ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <a href="
        <?php
        echo esc_url(
            manage_url(
                $competition_type . 's',
                'overview',
                [
                    'competition_id' => (string) $event->competition->id,
                    'season'         => $season,
                ]
            )
        );
        ?>
        "><?php echo esc_html( $season ); ?></a> &raquo; <?php echo esc_html( $event->name ); ?>
    </div>
</div>
<div class="row justify-content-between">
    <div class="col-auto">
        <h1><?php echo esc_html( $event->name ); ?></h1>
    </div>
</div>

<?php
if ( isset( $this ) && method_exists( $this, 'show_message' ) ) {
    $this->show_message();
}
?>
<div class="row">
    <div class="container">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                    <!-- Nav tabs -->
                    <ul class="nav nav-pills" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <?php
                            if ( 'leagues' === $tab ) {
                                ?>
                                <button class="nav-link active" id="leagues-tab" data-bs-toggle="tab" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="true"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></button>
                                <?php
                            } else {
                                ?>
                                <a class="nav-link" id="leagues-tab" href="
                                <?php
                                echo esc_url(
                                    manage_url(
                                        $competition_type . 's',
                                        'event',
                                        [
                                            'competition_id' => (string) $event->competition->id,
                                            'season'   => $season,
                                            'event_id' => (string) $event->id,
                                        ]
                                    )
                                );
                                ?>
                                                                            " type="button" role="tab"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></a>
                                <?php
                            }
                            ?>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="setup-tab" href="
                            <?php
                            echo esc_url(
                                manage_url(
                                    $competition_type . 's',
                                    'setup-event',
                                    [
                                        'competition_id' => (string) $event->competition->id,
                                        'season'         => $season,
                                        'event_id'       => (string) $event->id,
                                    ]
                                )
                            );
                            ?>
                            " type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="constitution-tab" href="
                            <?php
                            echo esc_url(
                                manage_url(
                                    $competition_type . 's',
                                    'constitution',
                                    [
                                        'competition_id' => (string) $event->competition->id,
                                        'season'         => $season,
                                        'event_id'       => (string) $event->id,
                                    ]
                                )
                            );
                            ?>
                            " type="button" role="tab"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <?php
                            if ( 'matches' === $tab ) {
                                ?>
                                <button class="nav-link active" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
                                <?php
                            } else {
                                ?>
                                <a class="nav-link" id="matches-tab" href="
                                <?php
                                echo esc_url(
                                    manage_url(
                                        $competition_type . 's',
                                        'matches',
                                        [
                                            'competition_id' => (string) $event->competition->id,
                                            'season'   => $season,
                                            'event_id' => (string) $event->id,
                                        ]
                                    )
                                );
                                ?>
                                                                            " type="button" role="tab"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></a>
                                <?php
                            }
                            ?>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="config-tab" href="
                            <?php
                            echo esc_url(
                                manage_url(
                                    $competition_type . 's',
                                    'event-config',
                                    [
                                        'competition_id' => (string) $event->competition->id,
                                        'season'         => $season,
                                        'event_id'       => (string) $event->id,
                                    ]
                                )
                            );
                            ?>
                            " type="button" role="tab"><?php esc_html_e( 'Configuration', 'racketmanager' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>
