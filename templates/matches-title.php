<?php if ( $match->winner_id == 0 ) {
  $matchTitle = $match->match_title;
} else {
  if ( $match->winner_id == $match->home_team ) {
    $homeTeam = "<span class='winner'>".$match->teams['home']->title."</span>";
  } else {
    $homeTeam = $match->teams['home']->title;
  }
  if ( $match->winner_id == $match->away_team ) {
    $awayTeam = "<span class='winner'>".$match->teams['away']->title."</span>";
  } else {
    $awayTeam = $match->teams['away']->title;
  }
  $matchTitle = $homeTeam." - ".$awayTeam;
}
?>
