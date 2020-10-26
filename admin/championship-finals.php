<div class="championship-block">
    <div class="tablenav">
        <form action="admin.php" method="get" style="display: inline;">
            <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']) ?>" />
            <input type="hidden" name="subpage" value="<?php echo htmlspecialchars($_GET['subpage']) ?>" />
            <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />

            <select size="1" name="final" id="final">
                <?php foreach ( $league->championship->getFinals() AS $final ) : ?>
                <option value="<?php echo $final['key'] ?>"<?php selected($league->championship->getCurrentFinalKey(), $final['key']) ?>><?php echo $final['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="jquery-ui-tab" value="<?php echo $tab ?>" class="jquery_ui_tab_index" />
            <input type="submit" class="button-secondary" value="<?php _e( 'Show', 'leaguemanager' ) ?>" />
        </form>
        <form action="admin.php" method="get" style="display: inline;">
            <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page']) ?>" />
            <input type="hidden" name="subpage" value="match" />
            <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />

            <!-- Bulk Actions -->
            <select name="mode" size="1">
                <option value="-1" selected="selected"><?php _e('Actions', 'leaguemanager') ?></option>
                <option value="add"><?php _e('Add Matches', 'leaguemanager')?></option>
                <option value="edit"><?php _e( 'Edit Matches', 'leaguemanager' ) ?></option>
            </select>

            <select size="1" name="final" id="final1">
            <?php foreach ( $league->championship->getFinals() AS $final ) { ?>
                <option value="<?php echo $final['key'] ?>"><?php echo $final['name'] ?></option>
            <?php } ?>
            </select>
            <input type="hidden" name="jquery-ui-tab" value="<?php echo $tab ?>" class="jquery_ui_tab_index" />
            <input type="submit" class="button-secondary" value="<?php _e( 'Go', 'leaguemanager' ) ?>" />
        </form>
    </div>

    <?php $final = $league->championship->getFinals('current'); ?>
    <?php $matches = $league->getMatches( array("final" => (!empty($final['key']) ? $final['key'] : '' ), "orderby" => array("id" => "ASC")) ); ?>

    <form method="post" action="">
        <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
        <input type="hidden" name="season" value="<?php echo $league->current_season['name'] ?>" />
        <input type="hidden" name="round" value="<?php echo $final['round'] ?>" />
        <input type="hidden" name="jquery-ui-tab" value="<?php echo $tab ?>" class="jquery_ui_tab_index" />

<?php if ( $matches ) { ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e( '#', 'leaguemanager' ) ?></th>
                    <th><?php _e( 'ID', 'leaguemanager' ) ?></th>
                    <th><?php _e( 'Date','leaguemanager' ) ?></th>
                    <th style="text-align: center;"><?php _e( 'Match','leaguemanager' ) ?></th>
                    <th><?php _e( 'Location','leaguemanager' ) ?></th>
        <?php if ( isset($league->entryType) && $league->entryType == 'player' ) {
            } else { ?>
                    <th><?php _e( 'Begin','leaguemanager' ) ?></th>
        <?php } ?>
        <?php do_action( 'matchtable_header_'.$league->sport ); ?>
                    <th style="text-align: center;"><?php _e( 'Score', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody id="the-list-<?php echo $final['key'] ?>" class="lm-form-table">
        <?php for ( $i = 1; $i <= ( isset($final['num_matches']) ? $final['num_matches'] : 0 ); $i++ ) {
            $match = isset($matches[0]) ? $matches[$i-1] : '';
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
        ?>
                <tr class="<?php echo $class ?>">
                    <td><?php echo $i ?><input type="hidden" name="matches[<?php echo $match->id ?>]" value="<?php echo $match->id ?>" /><input type="hidden" name="home_team[<?php echo $match->id ?>]" value="<?php echo $match->home_team ?>" /><input type="hidden" name="away_team[<?php echo $match->id ?>]" value="<?php echo $match->away_team ?>" /></td>
                    <td><?php echo $match->id ?></td>
                    <td><?php echo ( isset($match->date) ) ? mysql2date(get_option('date_format'), $match->date) : 'N/A' ?></td>
                    <td class="match-title"><a href="admin.php?page=leaguemanager&amp;subpage=match&amp;league_id=<?php echo $league->id ?>&amp;edit=<?php echo $match->id ?>"><?php echo $match->getTitle() ?></a></td>
                    <td><?php echo ( isset($match->location) ) ? $match->location : 'N/A' ?></td>
            <?php if ( isset($league->entryType) && $league->entryType == 'player' ) {
            } else { ?>
                    <td><?php echo ( isset($match->hour) ) ? mysql2date(get_option('time_format'), $match->date) : 'N/A' ?></td>
            <?php } ?>
            <?php do_action( 'matchtable_columns_'.$league->sport, ( ( isset($match) ) ? $match : '' ) ) ?>
                    <td>
                        <input class="points" type="text" size="2" style="text-align: center;" id="home_points[<?php echo $match->id ?>]" name="home_points[<?php echo $match->id ?>]" value="<?php echo ((isset($match->home_points)) ? $match->home_points : '') ?>" /> : <input class="points" type="text" size="2" style="text-align: center;" id="away_points[<?php echo $match->id ?>]" name="away_points[<?php echo $match->id ?>]" value="<?php echo ((isset($match->away_points)) ? $match->away_points : '') ?>" />
                    </td>
                </tr>
        <?php } ?>
            </tbody>
        </table>

        <p class="submit"><input type="submit" name="updateFinalResults" value="<?php _e( 'Save Results','leaguemanager' ) ?>" class="button-primary" /></p>
    <?php } ?>
    </form>
</div>
<?php require('match-modal.php'); ?>

