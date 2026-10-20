<?php
/**
 *
 * Template page for the Archive
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $vm: League_Archive_View_Model (or $archive_vm)
 *  $league: current league (for backwards compatibility)
 *  $seasons: array of all seasons (for backwards compatibility)
 *  $leagues: array of all leagues (for backwards compatibility)
 *  $tab: current active tab string
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\League_Archive_View_Model;

/** @var League_Archive_View_Model $archive_vm */
/** @var League_Archive_View_Model $vm */
/** @var object $league */
/** @var array $seasons */
/** @var array $leagues */
/** @var string $tab */

$vm = $archive_vm ?? $vm;
?>
<div id="archive-<?php echo esc_html( (string) $vm->league_id ); ?>" class="archive">
    <div class="page-subhead competition">
        <div class="media competition-head">
            <div class="media__wrapper">
                <div class="media__img">
                    <svg width="16" height="16" class="media__img-element--icon">
                        <use xlink:href="<?php echo esc_url( $vm->icon_url ); ?>"></use>
                    </svg>
                </div>
                <div class="media__content">
                    <h1 class="media__title"><?php echo esc_html( $vm->league_title ); ?></h1>
                    <div class="media__content-subinfo">
                        <?php if ( ! empty( $vm->event_name ) ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <a href="<?php echo esc_url( $vm->event_url ); ?>">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $vm->event_name ); ?>
                                        </span>
                                    </a>
                                </span>
                                <span>&nbsp;&#8226&nbsp;</span>
                                <span class="nav--link">
                                    <a href="<?php echo esc_url( $vm->competition_url ); ?>">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $vm->competition_name ); ?>
                                        </span>
                                    </a>
                                </span>
                            </small>
                        <?php endif; ?>
                        <?php if ( $vm->has_date_range && ! empty( $vm->date_start_display ) && ! empty( $vm->date_end_display ) ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <svg width="32" height="32" class="icon ">
                                            <use xlink:href="<?php echo esc_url( $vm->calendar_icon_url ); ?>"></use>
                                        </svg>
                                        <?php echo esc_html( $vm->date_start_display ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( $vm->date_end_display ); ?>
                                    </span>
                                </span>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="media__aside">
                    <form method="get" action="<?php echo esc_url( $vm->form_action ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $vm->post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_attr( $vm->pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="form-floating">
                                <select class="form-select" size="1" name="season" id="season">
                                    <?php foreach ( $vm->season_options as $option ) : ?>
                                        <option value="<?php echo esc_attr( $option['name'] ); ?>"<?php echo ! empty( $option['selected'] ) ? ' selected="selected"' : ''; ?>>
                                            <?php echo esc_html( $option['label'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="season"><?php echo esc_html( $vm->season_selection ); ?></label>
                            </div>
                        </div>
                    </form>
                    <?php
                    require 'includes/favourite-button.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php require 'league-selections.php'; ?>
    <?php if ( $vm->is_championship ) : ?>
        <?php if ( function_exists( 'Racketmanager\racketmanager_championship' ) ) {
            racketmanager_championship( 0, [ 'season' => $vm->championship_season ] );
        } ?>
    <?php else : ?>
        <div id="pageContentTab">
            <nav class="navbar navbar-expand-lg">
                <div class="">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                        <!-- Nav tabs -->
                        <ul class="nav nav-pills frontend" id="myTab" role="tablist">
                            <?php foreach ( $vm->menu_options as $option ) : ?>
                                <?php if ( ! empty( $option['available'] ) ) : ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link tabData <?php echo ! empty( $option['selected'] ) ? 'active' : ''; ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo ! empty( $option['selected'] ) ? 'true' : 'false'; ?>" data-type="league" data-type-id="<?php echo esc_attr( (string) $vm->league_id ); ?>" data-season="<?php echo esc_attr( $vm->current_season_name ); ?>" data-name="<?php echo esc_attr( $vm->league_seo_name ); ?>" data-competition-type="<?php echo esc_attr( $vm->competition_type ); ?>"><?php echo esc_html( $option['description'] ); ?></button>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Tab panes -->
            <div class="tab-content" id="leagueTabContent">
                <?php
                if ( defined( 'RACKETMANAGER_PATH' ) && file_exists( RACKETMANAGER_PATH . 'templates/includes/loading.php' ) ) {
                    require RACKETMANAGER_PATH . 'templates/includes/loading.php';
                }
                ?>
                <?php foreach ( $vm->menu_options as $option ) : ?>
                    <?php if ( ! empty( $option['available'] ) ) : ?>
                        <div class="tab-pane <?php echo ! empty( $option['selected'] ) ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
                            <?php
                            if ( ! empty( $option['selected'] ) ) {
                                $function_name = 'Racketmanager\league_' . $option['name'];
                                if ( function_exists( $function_name ) ) {
                                    $args = [
                                        'season'   => $vm->current_season_name,
                                        'template' => get_league_template( $option['name'] ),
                                        'post_id'  => $vm->post_id,
                                    ];
                                    $function_name( $vm->league_id, $args );
                                } else {
                                    /* translators: %s: function name */
                                    printf( esc_html__( 'function %s does not exist', 'racketmanager' ), esc_attr( $function_name ) );
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
