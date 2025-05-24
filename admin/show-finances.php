<?php
/**
 * Finances administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $racketmanager_tab */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
	activaTab('<?php echo esc_html( $racketmanager_tab ); ?>');
});
</script>
<div class="container">

	<h1><?php esc_html_e( 'Racketmanager Finances', 'racketmanager' ); ?></h1>
	<div class="row">
		<div class="container">
			<!-- Nav tabs -->
			<ul class="nav nav-tabs" id="myTab" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="charges-tab" data-bs-toggle="tab" data-bs-target="#racketmanager-charges" type="button" role="tab" aria-controls="racketmanager-charges" aria-selected="true"><?php esc_html_e( 'Charges', 'racketmanager' ); ?></button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#racketmanager-invoices" type="button" role="tab" aria-controls="racketmanager-invoices" aria-selected="true"><?php esc_html_e( 'Invoices', 'racketmanager' ); ?></button>
				</li>
			</ul>
		</div>
	</div>
	<div class="row">
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane fade" id="racketmanager-charges" role="tabpanel" aria-labelledby="charges-tab">
				<h2 class="header"><?php esc_html_e( 'Charges', 'racketmanager' ); ?></h2>
				<?php require 'finances/charges.php'; ?>
			</div>
			<div class="tab-pane fade" id="racketmanager-invoices" role="tabpanel" aria-labelledby="invoices-tab">
				<h2 class="header"><?php esc_html_e( 'Invoices', 'racketmanager' ); ?></h2>
				<?php require 'finances/invoices.php'; ?>
			</div>
		</div>
	</div>
</div>
