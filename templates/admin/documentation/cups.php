<?php
/**
 * Documentation: Cups
 *
 * @package RacketManager
 * @subpackage Templates/Admin/Documentation
 */

namespace Racketmanager;

?>
<div class="container">
  <!--suppress HtmlUnknownAnchorTarget -->
    <a href="#top" class="alignright top-link"><?php esc_html_e( 'Top', 'racketmanager' ); ?></a>
  <p>The cups page shows all the cup competitions that have been created.</p>
  <p>Clicking on cup name takes the user to the cup competition page.</p>
  <h3 id="notifyCups"><?php esc_html_e( 'Notify Entry Open', 'racketmanager' ); ?></h3>
  <p>Match secretaries for all clubs can be notified when cup entries are allowed.</p>
  <p>Selecting the season and cup type (summer or winter) and then pressing the "Notify cup entry open" button sends an email to match secretaries for all clubs with a link the cup entry form.</p>
</div>
