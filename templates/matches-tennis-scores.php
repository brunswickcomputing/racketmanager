<?php
    global $wp_query;
    $postID = isset($wp_query->post->ID) ? $wp_query->post->ID : "";
?>
            <table class='leaguemanager matchtable' summary='' title='<?php echo __( 'Match Plan', 'leaguemanager' )." ".$league->title ?>'>
            <thead>
                <tr>
<?php if ( $league->mode == 'championship' ) { ?>
                    <th><?php _e( '#', 'leaguemanager' ) ?></th>
<?php } ?>
                    <th colspan="2" class='match'><?php _e( 'Match', 'leaguemanager' ) ?></th>
                    <th class='score'><?php _e( 'Score', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php $matchday = isset($_GET['match_day']) ? $_GET['match_day'] : $leaguemanager->getMatchDay(); ?>
            <?php foreach ( $matches AS $no => $match ) { ?>
                <?php if ( $league->mode == 'default' && $matchday != $match->match_day ) { ?>
                <tr class='match-day-row'>
                    <th colspan="3" class='match']>Week <?php echo $match->match_day; ?></th>
                </tr>
                <?php
                    $matchday = $match->match_day;
                } ?>

                <tr class='match-row rubber-view <?php echo $match->class ?>'>
<?php if ( $league->mode == 'championship' ) { ?>
                    <td><?php echo $no ?></td>
<?php } ?>
                    <?php if ( isset($match->num_rubbers) && $match->num_rubbers > 0 ) {
                        if ($match->winner_id != 0) { ?>
                            <?php if ( $match->home_team == -1 || $match->away_team == -1 ) { ?>
                                <td class='angledir'></td>
                            <?php } else { ?>
                                <td class='angledir'><i class='fa fa-angle-down'></i></td>
                            <?php } ?>
                        <?php } else { ?>
                            <td><a href="#" class='fa fa-print ' id="<?php echo $match->id ?>" onclick="Leaguemanager.printScoreCard(event, this)"></a></td>
                        <?php } ?>
                    <?php } else { ?>
                        <td class='angledir'></td>
                    <?php } ?>
                    <td class='match'>
                        <?php echo mysql2date('l, j F Y', $match->date)." ".$match->start_time." ".$match->location ?><br /><?php echo $match->title ?> <?php echo $match->report ?>
                    </td>
                    <td class='score'>
                        <?php if (isset($league->num_rubbers)) {
                            echo $match->score;
                        } elseif ( isset($match->sets) ) {
                            $sets = array();
                            foreach ( (array)$match->sets AS $j => $set ) {
                                if ( $set['player1'] != "" && $set['player2'] != "" ) {
                                    if ( $match->winner_id == $match->away_team )
                                        $sets[] = sprintf($league->point_format2, $set['player2'], $set['player1']);
                                    else
                                        $sets[] = sprintf($league->point_format2, $set['player1'], $set['player2']);
                                }
                            }
                            implode(", ", $sets);
                        } ?>
                    </td>

                    </tr>
                <?php if ( isset($match->num_rubbers) && $match->num_rubbers > 0 && ($match->winner_id != 0) ) { ?>
                <tr class='match-rubber-row <?php echo $match->class ?>'>
                    <td colspan="<?php if ( $league->mode == 'championship' ) echo '4'; else echo '3' ?>">
                        <table id='rubbers_<?php echo $match->id ?>'>
                            <tbody>
                    <?php foreach ($match->rubbers as $rubber) { ?>
                        <?php if ( $rubber->homePlayer1 != 0 && $rubber->awayPlayer1 != 0 ) { ?>
                                <tr class='rubber-row <?php echo $match->class ?>'>
                                    <td><?php echo $rubber->rubber_number ?></td>
                                    <td class='playername'><?php echo $rubber->home_player_1_name ?></td>
                                    <td class='playername'><?php echo $rubber->home_player_2_name ?></td>
                                    <?php if ( isset($rubber->sets) ) {
                                        foreach ($rubber->sets as $set) { ?>
                                            <?php if ( ($set['player1'] !== '') && ( $set['player2'] !== '' )) { ?>
                                                <td class='score'><?php echo $set['player1']?> - <?php echo $set['player2']?></td>
                                            <?php } else { ?>
                                                <td class='score'></td>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                    <td class='playername'><?php echo $rubber->away_player_1_name ?></td>
                                    <td class='playername'><?php echo $rubber->away_player_2_name ?></td>
                                </tr>
                        <?php } ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                        <?php } ?>


            <?php } ?>
            </tbody>
        </table>

<?php if ( isset($league->pagination) ) { ?>
        <div class='tablenav'>
            <div class='tablenav-pages'>
                <?php echo $league->pagination ?>
            </div>
        </div>
<?php } ?>
        <div id='showMatchRubbers' style='display:none'></div>
