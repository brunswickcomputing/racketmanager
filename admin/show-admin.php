<?php
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">

	<h1><?php _e( 'Racketmanager Administration', 'racketmanager' ) ?></h1>

	<div class="container">
		<!-- Nav tabs -->
		<ul class="nav nav-pills" id="myTab" role="tablist">
			<li class="nav-item" role="presentation">
				<button class="nav-link active" id="seasons-tab" data-bs-toggle="pill" data-bs-target="#seasons" type="button" role="tab" aria-controls="seasons" aria-selected="true"><?php _e( 'Seasons', 'racketmanager' ) ?></button>
			</li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane active show fade" id="seasons" role="tabpanel" aria-labelledby="seasons-tab">
				<h2 class="header"><?php _e( 'Seasons', 'racketmanager' ) ?></h2>
				<?php include('main/seasons.php'); ?>
			</div>
		</div>
	</div>
</div>
