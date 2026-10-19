<?php
/**
 * Template page for the Event
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $event: current event
 *  $seasons: array of all seasons
 *  $curr_season: current season
 *  $tab: current tab
 *  $menu_options: array of menu options
 *  $is_singular: bool
 *  $competition_type: string
 *  $event_seo_name: string
 */

namespace Racketmanager;

/** @var object $event */
/** @var array $menu_options */
/** @var bool $is_singular */
/** @var string $competition_type */
/** @var string $event_seo_name */

$is_singular      = $is_singular ?? false;
?>
<div id="league-tables">
    <?php
    require RACKETMANAGER_PATH . 'templates/includes/event-header.php';
    ?>
    <div id="pageContentTab">
        <nav class="navbar navbar-expand-lg">
            <div class="">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                    <ul class="nav nav-pills frontend" id="myTab" role="tablist">
                        <?php
                        foreach ( $menu_options as $option ) {
                            if ( $option['available'] ) {
                                $singular_class = $option['selected'] && ! empty( $is_singular ) ? 'is-singular' : null;
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link tabData <?php echo $option['selected'] ? 'active' : null; ?> <?php echo esc_attr( $singular_class ); ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" data-type="event" data-type-id="<?php echo esc_attr( $event->id ); ?>" data-season="<?php echo esc_attr( $event->current_season['name'] ); ?>" data-name="<?php echo esc_attr( $event_seo_name ?? '' ); ?>" data-competition-type="<?php echo esc_attr( $competition_type ); ?>"><?php echo esc_attr( $option['description'] ); ?></button>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="tab-content" id="eventTabContent">
            <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
            <?php
            foreach ( $menu_options as $option ) {
                if ( $option['available'] ) {
                    ?>
                    <div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
                        <?php
                        if ( $option['selected'] ) {
                            $function_name = 'Racketmanager\event_' . $option['name'];
                            if ( function_exists( $function_name ) ) {
                                $function_name(
                                    $event->id,
                                    array(
                                        'season'   => $event->current_season['name'],
                                        'template' => $option['template'],
                                    )
                                );
                            } else {
                                /* translators: %s: function name */
                                printf( esc_html__( 'function %s does not exist', 'racketmanager' ), esc_attr( $function_name ) );
                            }
                        }
                        ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>
