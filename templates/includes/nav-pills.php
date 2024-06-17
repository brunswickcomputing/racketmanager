<?php
/**
 * Template for pills tabs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
					<div class="module mt-3">
						<ul class="nav nav-pills justify-content-center">
							<li class="nav-item">
								<a class="nav-link active" onclick="Racketmanager.switchTab(this)" id="tab-list" data-tabid="tab-list">
									<svg width="16" height="16" class="icon icon-list">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#list' ); ?>"></use>
									</svg>
									<?php echo esc_html_e( 'List view', 'racketmanager' ); ?>
								</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" onclick="Racketmanager.switchTab(this)" id="tab-grid" data-tabid="tab-grid">
									<svg width="16" height="16" class="icon icon-grid">
										<use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#grid' ); ?>"></use>
									</svg>
									<?php echo esc_html_e( 'Grid view', 'racketmanager' ); ?>
								</a>
							</li>
						</ul>
					</div>
