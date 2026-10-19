<?php
/**
 * Template page for Tournament
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $tournaments: array of all tournaments
 *  $tournament_details: Tournament_Details_DTO
 *  $tournament_header: Tournament_Header_View_Model
 *  $menu_options: array of menu options
 *  $post_id: int|string
 *  $tournament_seo_name: string
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;

/** @var Tournament_Details_DTO $tournament_details */
/** @var array $tournaments */
/** @var array $menu_options */
/** @var string|int $post_id */
/** @var string $tournament_seo_name */

$tournament          = $tournament_details->tournament;
$competition         = $tournament_details->competition;
$entry_option        = true;
$post_id             = $post_id ?? '';
$tournament_seo_name = $tournament_seo_name ?? '';
?>
<div id="tournament-<?php echo esc_html( $tournament->id ); ?>" class="tournament">
    <div id="pageContentTab">
        <div class="container">
            <?php
            require 'includes/tournament-header.php';
            ?>
            <?php require 'tournament-selections.php'; ?>
            <nav class="navbar navbar-expand-lg">
                <div class="">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                        <!-- Nav tabs -->
                        <ul class="nav nav-pills frontend" id="myTab" role="tablist">
                            <?php
                            foreach ( $menu_options as $option ) {
                                if ( $option['available'] ) {
                                    ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link tabData <?php echo $option['selected'] ? 'active' : null; ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season="" data-name="<?php echo esc_attr( $tournament_seo_name ?? '' ); ?>" data-competition-type=""><?php echo esc_attr( $option['description'] ); ?></button>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
        <!-- Tab panes -->
        <div class="tab-content" id="tournamentTabContent">
            <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
            <?php
            foreach ( $menu_options as $option ) {
                if ( $option['available'] ) {
                    ?>
                    <div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
                        <?php
                        if ( $option['selected'] ) {
                            $function_name = 'Racketmanager\tournament_' . $option['name'];
                            if ( function_exists( $function_name ) ) {
                                $function_name( $tournament->id, array() );
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
