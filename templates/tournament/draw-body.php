<?php
/**
 * Template for tournament draw body
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $draw */
/** @var array $matches */
?>
<div class="module__content">
            <div class="module-container">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs frontend" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true"><?php esc_html_e( 'General', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="draw-matches-tab" data-bs-toggle="pill" data-bs-target="#draw-matches" type="button" role="tab" aria-controls="draw-matches" aria-selected="true"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
                    </li>
                </ul>
                <!-- Tab panes -->
                <div class="tab-content">
                    <div class="tab-pane active show fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <?php
                        foreach ( $draw->leagues as $league ) {
                            ?>
                            <div>
                                <h4 class="header">
                                    <?php echo esc_html( $league->title ); ?>
                                </h4>
                                <?php
                                $finals   = $league->finals;
                                $champion = null;
                                require RACKETMANAGER_PATH . 'templates/includes/championship-draw.php';
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="draw-matches" role="tabpanel" aria-labelledby="draw-matches-tab">
                        <div class="container tournament-matches">
                            <?php
                            foreach ( $matches as $no => $match ) {
                                ?>
                                <?php require RACKETMANAGER_PATH . 'templates/tournament/match.php'; ?>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<script type="text/javascript">
    const matchLinks = document.querySelectorAll('.score-row__wrapper');
    matchLinks.forEach(el => el.addEventListener('click', function (e) {
        Racketmanager.viewMatch(e)
    }));
</script>
