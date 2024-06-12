<?php
/**
 * Template for match for teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="modal" id="scoreStatusModal" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered modal-lg">
		<div class="modal-content">
			<div class="modal-header modal__header">
				<h4 class="modal-title"><?php esc_html_e( 'Score status', 'racketmanager' ); ?></h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="container-fluid">
					<div class="row">
						<div class="col-sm-6">
							<select class="form-select">
								<option value="" disabled selected><?php esc_html_e( 'Status', 'racketmanager' ); ?></option>
							</select>
						</div>
						<div class="col-sm-6">
							<ul class="list list--naked">
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary">Save changes</button>
			</div>
		</div>
	</div>
</div>
