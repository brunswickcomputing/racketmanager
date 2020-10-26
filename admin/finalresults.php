<div class="championship-block">
    <table class="widefat">
    <thead>
    <tr>
        <th scope="col"><?php _e( 'Round', 'leaguemanager' ) ?></th>
        <th scope="col" colspan="<?php echo ($num_first_round > 4) ? 4 : $num_first_round; ?>" style="text-align: center;"><?php _e( 'Matches', 'leaguemanager' ) ?></th>
    </tr>
    <tbody id="the-list-finals" class="lm-form-table">
    <?php foreach ( $championship->getFinals() AS $final ) : $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
    <?php
        if ( $matches = $leaguemanager->getMatches( array("league_id" => $league->id, "season" => $season['name'], "final" => $final['key'], "orderby" => array("id" => "ASC"))) ) {
            $teams = $leaguemanager->getTeams( array("league_id" => $league->id, "season" => $season['name']), 'ARRAY' );
            $teams2 = $championship->getFinalTeams( $final, 'ARRAY' );
        }
    ?>
        <tr class="<?php echo $class ?>">
            <th scope="row" style="padding-left: 1em;"><strong><?php echo $final['name'] ?></strong></th>
            <?php for ( $i = 1; $i <= $final['num_matches']; $i++ ) : ((isset($matches[0])) ? $match = $matches[$i-1] : 0); ?>
            <?php $colspan = ( $num_first_round/2 >= 4 ) ? ceil(4/$final['num_matches']) : ceil(($num_first_round/2)/$final['num_matches']); ?>
            <td colspan="<?php echo $colspan ?>" style="text-align: center;">
                <?php if ( isset($match) ) : ?>

                <?php
                $match->hadPenalty = $match->hadPenalty = ( isset($match->penalty) && $match->penalty['home'] != '' && $match->penalty['away'] != '' ) ? true : false;
                $match->hadOvertime = $match->hadOvertime = ( isset($match->overtime) && $match->overtime['home'] != '' && $match->overtime['away'] != '' ) ? true : false;
                
                $title = $championship->getChampionshipMatchTitle($match, $teams, $teams2);

                ?>
                <?php //if ( isset($teams[$match->home_team]) && isset($teams[$match->away_team]) ) : ?>
                    <?php if ( $final['key'] == 'final' ) : ?>
                    <p><span id="final_home" style="margin-right: 0.5em;"></span><?php echo $title;//printf('%s &#8211; %s', $teams[$match->home_team]['title'], $teams[$match->away_team]['title']) ?><span id="final_away" style="margin-left: 0.5em;"></span></p>
                    <?php else : ?>
                    <p><?php echo $title;//printf('%s &#8211; %s', $teams[$match->home_team]['title'], $teams[$match->away_team]['title']) ?></p>
                    <?php endif; ?>

                    <?php if ( $match->home_points != NULL && $match->away_points != NULL ) : ?>
                        <?php if ( $final['key'] == 'final' ) : ?>
                        <?php $field_id = ( $match->winner_id == $match->home_team ) ? "final_home" : "final_away"; ?>
                        <script type="text/javascript">
                            <?php $img = '<img style="vertical-align: middle;" src="'.LEAGUEMANAGER_URL . '/admin/icons/cup.png" />'; ?>
                            jQuery('span#<?php echo $field_id ?>').html('<?php echo addslashes_gpc($img) ?>').fadeIn('fast');
                        </script>
                        <?php endif; ?>

                        <?php
                        if ( $match->hadPenalty )
                            $match->score = sprintf("%d:%d", $match->overtime['home'] + $match->penalty['home'], $match->overtime['away'] + $match->penalty['away'])." "._x( 'o.P.', 'leaguemanager' );
                        elseif ( $match->hadOvertime )
                            $match->score = sprintf("%d:%d", $match->overtime['home'], $match->overtime['away'])." "._x( '(AET)', 'leaguemanager' );
                        else
                            $match->score = sprintf("%d:%d", $match->home_points, $match->away_points);
                        ?>
                        <p><strong><?php echo $match->score ?></strong></p>
                    <?php else : ?>
                        <p>-:-</p>
                    <?php endif; ?>
                <?php //else : ?>
                    <!--&#8211;-->
                <?php //endif; ?>

                <?php endif; ?>
            </td>
            <?php if ( $i%4 == 0 && $i < $final['num_matches'] ) : ?>
            </tr><tr class="<?php echo $class ?>"><th>&#160;</th>
            <?php endif; ?>

            <?php endfor; ?>
        </tr>
    <?php endforeach ?>
    </tbody>
    </table>
</div>

