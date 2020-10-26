<?php
/**
Template page for the specific match date match table in tennis

The following variables are usable:
	
	$league: contains data of current league
	$matches: contains all matches for current league
	$teams: contains teams of current league in an assosiative array
	$season: current season
	
	You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
    global $wp_query;
    $postID = $wp_query->post->ID;
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
                       $('.date_picker').datepicker({
                                                    dateFormat : 'yy-mm-dd',
                                                    firstDay: 1,
                                                    showOtherMonths: true,
                                                    selectOtherMonths: true,
                                                    changeYear: true,
                                                    changeMonth: true
                                                    });
                       });
</script>
<?php
    if (isset($_GET['match']) ) {
        leaguemanager_match(intval($_GET['match']));
    } else {
        $match_date = get_query_var('match_date');
        if ( $match_date == '' ) {
            if (isset($_GET['match_date'])) {
                $match_date = $_GET['match_date'];
            }
        }
        if ( $match_date == '' ) {
            $match_date = date("Y-m-d");
        }
    ?>

<div id="leaguemanager_match_selections" class="">
    <form method="get" action="<?php echo get_permalink($postID); ?>" id="leaguemanager_daily_matches">
    <input type="hidden" name="page_id" value="<?php echo $postID ?>" />

        <input type="text" name="match_date" id="match_date" class="form-control date_picker" value="<?php echo($match_date) ?>" />
    <input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
    </form>
</div>
    <?php if ( $matches ) { ?>
        <table class='leaguemanager matchtable' summary='' title='<?php echo __( 'Daily Match Plan', 'leaguemanager' ) ?>'>
            <thead>
                <tr>
                    <th class='match'><?php _e( 'Match', 'leaguemanager' ) ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $matches AS $match ) { ?>
                <tr class='<?php echo $match->class ?>'>
                    <td class='match'>
                        <?php echo $match->start_time." ".$match->location ?><br /><?php echo $match->title ?>
                    </td>
                </tr>

            <?php } ?>
            </tbody>
        </table>

        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo $league->pagination ?>
            </div>
        </div>
	<?php } else { ?>
        <p><?php echo __( 'No Matches on selected day', 'leaguemanager' ) ?></p>
	<?php }?>

<?php } ?>
