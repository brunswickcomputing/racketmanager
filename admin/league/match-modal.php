<?php
/**
 * Template for match modal screen
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

?>
<div class="modal fade" id="modalMatch" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5><?php esc_html_e( 'Match Details', 'racketmanager' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="matchrubbers" class="">
					<div id="matchHeader"></div>
				</div>
				<div id="viewMatchRubbers" style="display:none">
					<div id="splash">
						<div class="d-flex justify-content-center">
							<div class="spinner-border" role="status">
							<span class="visually-hidden">Loading...</span>
							</div>
						</div>
					</div>
					<div id="showMatchRubbers"></div>
				</div>
			</div>
		</div>
	</div>
</div>
