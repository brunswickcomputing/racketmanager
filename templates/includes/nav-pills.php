<?php
/**
 * Template for pills tabs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
                    <div class="d-none d-md-block module mt-3">
                        <ul class="nav nav-pills justify-content-center">
                            <li class="nav-item">
                                <button class="nav-link active" onclick="Racketmanager.switchTab(this)" id="tab-list" data-tabid="tab-list">
                                    <svg width="16" height="16" class="icon icon-list">
                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#list' ); ?>"></use>
                                    </svg>
                                    <?php esc_html_e( 'List view', 'racketmanager' ); ?>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" onclick="Racketmanager.switchTab(this)" id="tab-grid" data-tabid="tab-grid">
                                    <svg width="16" height="16" class="icon icon-grid">
                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#grid' ); ?>"></use>
                                    </svg>
                                    <?php esc_html_e( 'Grid view', 'racketmanager' ); ?>
                                </button>
                            </li>
                        </ul>
                    </div>
