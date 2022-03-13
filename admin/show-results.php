<?php
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">

	<h1><?php _e("Results", "racketmanager") ?></h1>

	<div class="container">
		<!-- Nav tabs -->
		<ul class="nav nav-pills" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
				<button class="nav-link" id="resultschecker-tab" data-bs-toggle="pill" data-bs-target="#resultschecker" type="button" role="tab" aria-controls="resultschecker" aria-selected="false">Results Checker</button>
			</li>
			<li class="nav-item" role="presentation">
				<button class="nav-link" id="results-tab" data-bs-toggle="pill" data-bs-target="#results" type="button" role="tab" aria-controls="results" aria-selected="true">Results</button>
			</li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
      <div class="tab-pane fade" id="resultschecker" role="tabpanel" aria-labelledby="resultschecker-tab">
				<h2 class="header"><?php _e( 'Results Checker', 'racketmanager' ) ?></h2>
				<?php include('results/results-checker.php'); ?>
			</div>
			<div class="tab-pane fade" id="results" role="tabpanel" aria-labelledby="results-tab">
				<h2 class="header"><?php _e( 'Pending Results', 'racketmanager' ) ?></h2>
				<?php include('results/results.php'); ?>
			</div>
		</div>
	</div>
</div>
