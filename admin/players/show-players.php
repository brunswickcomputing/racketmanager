<?php
/**
 * Players administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

?>
<div class="container">
	<?php require 'nav-tabs.php'; ?>
	<div class="row">
		<!-- Tab panes -->
		<div class="tab-content">
			<div class="tab-pane active" id="players" role="tabpanel">
				<?php require 'players.php'; ?>
			</div>
		</div>
	</div>
</div>
