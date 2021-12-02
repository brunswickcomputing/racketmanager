<div class="championship-block">
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col"><?php _e( 'Round', 'leaguemanager' ) ?></th>
                <th scope="col" colspan="<?php echo ($league->championship->num_teams_first_round > 4) ? 4 : $league->championship->num_teams_first_round; ?>" style="text-align: center;"><?php _e( 'Matches', 'leaguemanager' ) ?></th>
            </tr>
        </thead>
        <tbody id="the-list-finals" class="lm-form-table"><?php
foreach ( $league->championship->getFinals() AS $final ) {
    $class = ( 'alternate' == $class ) ? '' : 'alternate';
    $matches = $league->getMatches( array("final" => $final['key'], "orderby" => array("id" => "ASC")));
?>
            <tr class="<?php echo $class ?>">
                <th scope="row" style="padding-left: 1em;"><strong><?php echo $final['name'] ?></strong></th><?php
    for ( $i = 1; $i <= $final['num_matches']; $i++ ) {
        ((isset($matches[0])) ? $match = $matches[$i-1] : 0);
        $colspan = ( $league->championship->num_teams_first_round/2 >= 4 ) ? ceil(4/$final['num_matches']) : ceil(($league->championship->num_teams_first_round/2)/$final['num_matches']); ?>
                <td colspan="<?php echo $colspan ?>" style="text-align: center;"><?php
        if ( isset($match) ) {
            if ( $final['key'] == 'final' ) { ?>
                    <p><span id="final_home" style="margin-right: 0.5em;"></span><?php echo $match->getTitle(); ?><span id="final_away" style="margin-left: 0.5em;"></span></p><?php
            } else { ?>
                    <p><?php echo $match->getTitle(); ?></p><?php
            }
            if ( $match->home_points != NULL && $match->away_points != NULL ) {
                if ( $final['key'] == 'final' ) {
                    $field_id = ( $match->winner_id == $match->home_team ) ? "final_home" : "final_away";
                    $img = '<img style="vertical-align: middle;" src="'.LEAGUEMANAGER_URL . '/admin/icons/cup.png" />';?>
                    <script type="text/javascript">
                        jQuery('span#<?php echo $field_id ?>').html('<?php echo addslashes_gpc($img) ?>').fadeIn('fast');
                    </script><?php
                }
                $match->score = sprintf("%d:%d", $match->home_points, $match->away_points);?>
                <p><strong><?php echo $match->score ?></strong></p><?php
            } else { ?>
                <p>-:-</p><?php
            }
        } ?>
                </td><?php
        if ( $i%4 == 0 && $i < $final['num_matches'] ) { ?>
            </tr>
            <tr class="<?php echo $class ?>"><th>&#160;</th><?php
        }
    } ?>
            </tr><?php
 } ?>
        </tbody>
    </table>
</div>
