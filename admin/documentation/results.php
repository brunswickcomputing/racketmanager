<div class="container">
  <!-- Nav tabs -->
  <ul class="nav nav-pills" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active show" id="resultschecker-tab" data-bs-toggle="pill" data-bs-target="#resultschecker" type="button" role="tab" aria-controls="resultschecker" aria-selected="true"><?php _e( 'Results Checker', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="resultsview-tab" data-bs-toggle="pill" data-bs-target="#resultsview" type="button" role="tab" aria-controls="resultsview" aria-selected="false"><?php _e( 'Results', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="container tab-content">
    <div class="tab-pane active show fade" id="resultschecker" role="tabpanel" aria-labelledby="resultschecker-tab">
      <h3 class="header"><?php _e( 'Results Checker', 'racketmanager' ) ?></h3>
      <p>The results checker page of Racketmanager shows any player checks that have been failed whenever a match result is input.</p>
      <p>For any of these checks to be applied, the relevant <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">setting</a> must be entered on the "Player Checks" tab.</p>
      <dl>
        <dt><?php _e( 'Player Registration lead time', 'racketmanager' ) ?></dt>
        <dd>This checks how long a player must be registered before they are eligible to play.</dd>
        <dt><?php _e( 'End of season eligibility', 'racketmanager' ) ?></dt>
        <dd>This checks how many rounds at the end of the season do not allow new players to be registered.</dd>
        <dt><?php _e( 'Locked players', 'racketmanager' ) ?></dt>
        <dd>This checks how many matches a player may play for a higher team before they are locked to that team.</dd>
        <dt><?php _e( 'Approval/Deletion', 'racketmanager' ) ?></dt>
        <dd>Each check can be either marked as approved or can be deleted.</dd>
      </dl>
      <p>If the match result is updated, any records for the match and player are regenerated.</p>
      <p>However, if a player is swapped for another player in the match result, the original player entry will remain in the list of result checks. It can then be deleted if required.</p>
    </div>
    <div class="tab-pane fade" id="resultsview" role="tabpanel" aria-labelledby="resultsview-tab">
      <h3 class="header"><?php _e( 'Results', 'racketmanager' ) ?></h3>
      <a href="#top" class="alignright top-link"><?php _e( 'Top', 'racketmanager' ) ?></a>
      <p>The results page of Racketmanager shows any results that have been entered by users that need administration approval.</p>
      <p>The ability for users to enter match results themselves is controlled by the <a href="<?php echo get_admin_url() ?>admin.php?page=racketmanager-settings">settings</a> on the "Match Results" tab.</p>
      <p>If an email address is set on this screen, an email notification will be sent to this address whenever a match result is entered by users.</p>
      <dl>
        <dt><?php _e( 'Minimum level to update results', 'racketmanager' ) ?></dt>
        <dd>There are three options that control who can enter results:</dd>
        <dl>
          <dt><?php _e( 'None', 'racketmanager' ) ?></dt>
          <dd>Results are not able to be entered by users.</dd>
          <dt><?php _e( 'Captain', 'racketmanager' ) ?></dt>
          <dd>Captains of the team involved in the match are able to enter the results.</dd>
          <dt><?php _e( 'Player', 'racketmanager' ) ?></dt>
          <dd>Any player registered with the club of the team involved in the match are able to enter the results.</dd>
        </dl>
        <dt><?php _e( 'Result Entry', 'racketmanager' ) ?></dt>
        <dd>There are two options that control who can enter results:</dd>
        <dl>
          <dt><?php _e( 'Home', 'racketmanager' ) ?></dt>
          <dd>Results must be entered by the home team with approval by the away team.</dd>
          <dt><?php _e( 'Either', 'racketmanager' ) ?></dt>
          <dd>Results can be entered by either the home or away team. Approval is required by the alternative team.</dd>
        </dl>
        <dt><?php _e( 'Result Confirmation', 'racketmanager' ) ?></dt>
        <dd>There are two options that control how match result confirmation is handled:</dd>
        <dl>
          <dt><?php _e( 'None', 'racketmanager' ) ?></dt>
          <dd>Match results must be confirmed by the league administrator. Rubber results are available to view as these have already been entered.</dd>
          <dt><?php _e( 'Automatic', 'racketmanager' ) ?></dt>
          <dd>Match results are automatically updated from the result entry on the frontend. If this value is set, the only matches that are shown on this screen are where the opposing sides disagree with the result.</dd>
        </dl>
      </dl>
    </div>
  </div>
</div>
