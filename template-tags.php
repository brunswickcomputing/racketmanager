<?php
    /**
     * Template tags
     */

    /**
     * get league ID
     *
     * @return int
     * @category template-tags
     */
    function get_league_id() {
        global $league;
        return $league->id;
    }
    /**
     * print league ID
     *
     * @category template-tags
     */
    function the_league_id() {
        echo get_league_id();
    }

    /**
     * get league title
     *
     * @return string
     * @category template-tags
     */
    function get_league_title() {
        global $league;
        return $league->title;
    }

    /**
     * print league title
     *
     * @category template-tags
     */
    function the_league_title() {
        echo get_league_title();
    }

    /**
     * get current season
     *
     * @return string
     * @category template-tags
     */
    function get_current_season() {
        global $league;
        return $league->current_season['name'];
    }
    /**
     * print current season
     *
     * @category template-tags
     */
    function the_current_season() {
        echo get_current_season();
    }

    /**
     * get current match day
     *
     * @return int
     * @category template-tags
     */
    function get_current_match_day() {
        global $league;
        return $league->match_day;
    }

    /**
     * get number of match days
     *
     * @return int
     * @category template-tags
     */
    function get_num_match_days() {
        global $league;
        return $league->num_match_days;
    }

    /**
     * get specific template
     *
     * @param string $template
     * @return string
     * @category template-tags
     */
    function get_league_template($template = "" ) {
        global $league;

        if (!empty($template) && isset($league->templates[$template]))
            return $league->templates[$template];

        return "";
    }

    /**
     * print current match day
     *
     * @category template-tags
     */
    function the_current_match_day() {
        echo get_current_match_day();
    }

    /**
     * check if a specific standings columns is activated for display
     *
     * @param string $key
     * @return boolean
     * @category template-tags
     */
    function show_standings($key) {
        global $league;

        if (isset($league->standings[$key]) && $league->standings[$key] == 1)
            return true;

        return false;
    }

    /**
     * get League point rule
     *
     * @return string
     * @category template-tags
     */
    function get_league_pointrule() {
        global $league;
        return $league->point_rule;
    }

    /**
     * get total number of teams
     *
     * @return int
     * @category template-tags
     */
    function get_num_teams_total() {
        global $league;
        return $league->num_teams_total;
    }

    /**
     * display standings header
     *
     * @category template-tags
     */
    function the_standings_header() {
        global $league;
        $league->displayStandingsHeader();
    }
    /**
     * display standings columns
     *
     * @category template-tags
     */
    function the_standings_columns() {
        global $league, $team;
        $league->displayStandingsColumns($team, get_league_pointrule());
    }

    /**
     * test whether league has teams or we are in the loop
     *
     * @return boolean
     */
    function have_teams() {
        global $league;

        if ( $league->current_team + 1 < count($league->teams) ) {
            return true;
        } elseif ( $league->current_team == count($league->teams)-1 && count($league->teams) > 0 ) {
            // End of Loop
            $league->current_team = -1;
        }

        $league->in_the_team_loop = false;
        return false;
    }
    /**
     * loop through teams
     *
     */
    function the_team() {
        global $league, $team;

        $league->in_the_team_loop = true;

        // Loop start
        if ( $league->current_team == -1 ) {
        }
        // Increment team count
        $league->current_team++;
        $team = $league->teams[$league->current_team];
    }

    /**
     * get team ID
     *
     * @return int
     * @category template-tags
     */
    function get_team_id() {
        global $team;
        return $team->id;
    }
    /**
     * print team ID
     *
     * @category template-tags
     */
    function the_team_id() {
        echo get_team_id();
    }

    /**
     * get team name
     *
     * @return string
     * @category template-tags
     */
    function get_team_name() {
        global $team;
        return $team->title;
    }
    /**
     * print team name
     *
     * @category template-tags
     */
    function the_team_name() {
        echo get_team_name();
    }

    /**
     * print team name URL
     *
     * @param string $url
     * @category template-tags
     */
    function the_team_name_url($url = true) {
        global $team;

        if ( $url )
            echo '<a href="'.get_team_url().'">'.get_team_name().'</a>';
        else
            the_team_name();
    }

    /**
     * print team CSS class
     *
     * @category template-tags
     */
    function the_team_class() {
        global $team;

        echo $team->class;
    }

    /**
     * get team URL
     *
     * @return string
     * @category template-tags
     */
    function get_team_url() {
        global $team;
        return $team->pageURL;
    }
    /**
     * print team URL
     *
     * @category template-tags
     */
    function the_team_url() {
        echo get_team_url();
    }

    /**
     * get team rank
     *
     * @return int
     * @category template-tags
     */
    function get_team_rank() {
        global $team;
        return $team->rank;
    }

    /**
     * print team rank
     *
     * @category template-tags
     */
    function the_team_rank() {
        echo get_team_rank();
    }

    /**
     * print team status
     *
     * @category template-tags
     */
    function the_team_status() {
        global $team;
        echo $team->status;
    }

    /**
     * print formatted team points
     *
     * @param string $ind
     * @category template-tags
     */
    function the_team_points($ind = "primary") {
        global $team;
        echo $team->pointsFormatted[$ind];
    }

    /**
     * print adjusted team points
     *
     * @category template-tags
     */
    function the_team_points_adjust() {
        global $team;
        echo $team->add_points;
    }

    /**
     * print number of done matches of team
     *
     * @category template-tags
     */
    function num_done_matches() {
        global $team;
        echo $team->done_matches;
    }
    /**
     * print number of won matches of team
     *
     * @category template-tags
     */
    function num_won_matches() {
        global $team;
        echo $team->won_matches;
    }
    /**
     * print number of lost matches of team
     *
     * @category template-tags
     */
    function num_lost_matches() {
        global $team;
        echo $team->lost_matches;
    }
    /**
     * print number of draw matches of team
     *
     * @category template-tags
     */
    function num_draw_matches() {
        global $team;
        echo $team->draw_matches;
    }
    /**
     * print win percentage
     *
     * @category template-tags
     */
    function win_percentage() {
        global $team;
        echo $team->winPercent;
    }

    /**
     * check if team has a next match
     *
     * @return boolean
     * @category template-tags
     */
    function has_next_match() {
        global $team, $match;

        $match = $team->getNextMatch();

        if ($match)
            return true;

        return false;
    }
    /**
     * check if team has a previous match
     *
     * @return boolean
     * @category template-tags
     */
    function has_prev_match() {
        global $team, $match;

        $match = $team->getPrevMatch();

        if ($match)
            return true;

        return false;
    }

    /**
     * print last5 matches column for team
     *
     * @category template-tags
     */
    function the_last5_matches($url = true) {
        global $team;

        echo $team->last5($url);
    }

    /**
     * check if match is selected
     *
     * @return boolean
     * @category template-tags
     */
    function is_single_match() {
        global $league;
        return $league->is_selected_match;
    }

    /**
     * test whether league has matches or we are in the loop
     *
     * @return boolean
     */
    function have_matches() {
        global $league;

        if (!isset($league->matches)) return false;

        if ( $league->current_match + 1 < count($league->matches) ) {
            return true;
        } elseif ( $league->current_match == count($league->matches)-1 && count($league->matches) > 0 ) {
            // End of Loop
            $league->current_match = -1;
        }

        $league->in_the_match_loop = false;
        return false;
    }
    /**
     * loop through matches
     *
     */
    function the_match() {
        global $league, $match;

        $league->in_the_match_loop = true;

        // Loop start
        if ( $league->current_match == -1 ) {

        }

        // Increment dataset count
        $league->current_match++;
        $match = $league->matches[$league->current_match];
    }

    /**
     * display single match
     *
     * @param string $template
     */
    function the_single_match( $template = "" ) {
        global $league;
        echo do_shortcode("[match id='".$league->current_match."' template='".$template."']");
    }

    /**
     * print matches pagination
     *
     * @param string $start_el
     * @param string $end_el
     * @category template-tags
     */
    function the_matches_pagination($start_el = "<p class='racketmanager-pagination page-numbers'>", $end_el = "</p>") {
        global $league;

        if ( !empty($league->pagination_matches) )
            echo $start_el . $league->pagination_matches . $end_el;
    }

    /**
     * print Match CSS class
     *
     * @category template-tags
     */
    function the_match_class() {
        global $match;
        echo $match->class;
    }

    /**
     * print Match title
     *
     * @param boolean $show_logo
     * @category template-tags
     */
    function the_match_title($show_logo = true) {
        global $match;

        echo $match->getTitle($show_logo);
    }

    /**
     * get Match day
     *
     * @return int
     * @category template-tags
     */
    function get_match_day() {
        global $match;
        return $match->match_day;
    }
    /**
     * print Match day
     *
     * @category template-tags
     */
    function the_match_day() {
        echo get_match_day();
    }

    /**
     * print Match date
     *
     * @param string $format
     * @category template-tags
     */
    function the_match_date($format = '') {
        global $match;

        if ($format == '')
            echo $match->match_date;
        else
            echo mysql2date($format, $match->date);
    }

    /**
     * print Match time
     *
     * @category template-tags
     */
    function the_match_time() {
        global $match;
        if ( $match->start_time == "00:00" ) {
          echo '';
        } else {
          echo $match->start_time;
        }
    }

    /**
     * print Match location
     *
     * @category template-tags
     */
    function the_match_location() {
        global $match;
        echo $match->location;
    }

    /**
     * get Match score
     *
     * @return string
     * @category template-tags
     */
    function get_match_score() {
        global $match;
        return $match->score;
    }
    /**
     * print Match score
     *
     * @category template-tags
     */
    function the_match_score() {
        echo get_match_score();
    }

    /**
     * print Match URL
     *
     * @category template-tags
     */
    function the_match_url() {
        global $match;
        echo $match->pageURL;
    }

    /**
     * check if match has report
     *
     * @return boolean
     * @category template-tags
     */
    function match_has_report() {
        global $match;

        if ($match->post_id != 0)
            return true;

        return false;
    }
    /**
     * print Match report link
     *
     * @category template-tags
     */
    function the_match_report() {
        global $match;
        echo $match->report;
    }

    /**
     * get match template type
     *
     * @return string
     * @category template-tags
     */
    function get_match_template_type() {
        global $league;
        return $league->matches_template_type;
    }

    /**
     * print match CSS class for list
     *
     * @category template-tags
     */
    function the_matchlist_class() {
        if (get_match_template_type() == 'accordion')
            echo 'jquery-ui-accordion';
        elseif (get_match_template_type() == 'tabs')
            echo 'jquery-ui-tabs';
    }
    /**
     * print match container CSS class for jQuery UI Tabs
     *
     * @category template-tags
     */
    function the_matchbox_class() {
        if (get_match_template_type() == 'tabs')
            echo 'jquery-ui-tab';
    }
    /**
     * print match header CSS class for jQuery UI Tabs & Accordion
     *
     * @return string
     * @category template-tags
     */
    function the_matchbox_header_class() {
        if (in_array(get_match_template_type(), array('tabs', 'accordion')))
            echo 'header';
    }
    /**
     * print match content CSS class for jQuery UI Tabs & Accordion
     *
     * @return string
     * @category template-tags
     */
    function the_matchbox_content_class() {
        if (in_array(get_match_template_type(), array('tabs', 'accordion')))
            echo 'match-content';
    }

    /**
     * print crosstable field
     *
     * @param int $i
     * @category template-tags
     */
    function the_crosstable_field($i) {
        global $league, $team;

        echo $league->getCrosstableField($team->id, $league->teams[$i-1]->id, $team->home);
    }

    /**
    * wrapper tags
    */

    /**
    * display one club
    *
    * @param int $club_id
    * @param array $args additional arguments as associative array (optional)
    * @category template-tags

    */
   function racketmanager_club( $club_id, $args = array() ) {
       $defaults = array('template' => '');
       $args = array_merge($defaults, $args);
       $args['club_id'] = intval($club_id);

       $shortcode = "[club";
       foreach ($args AS $key => $value)
           $shortcode .= " ".$key."='".$value."'";
       $shortcode .= "]";
       echo do_shortcode($shortcode);
   }

    /**
     * display player list
     *
     * @param int|string $league_id
     * @param array $args additional arguments as associative array (optional)
     * @category template-tags

     */
    function racketmanager_players( $league_id, $args = array() ) {
        global $league;

        $defaults = array('season' => false, 'template' => '', 'group' => false);
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);

        $shortcode = "[players";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
     * display standings table
     *
     * @param int $league_id League ID
     * @param array $args associative array of parameters, see default values (optional)
     * @category template-tags
     */
    function racketmanager_standings( $league_id, $args = array() ) {
        global $league;

        $defaults = array( 'season' => false, 'template' => '', 'group' => false, 'home' => 0 );
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[standings";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
     * display crosstable table
     *
     * @param int $league_id
     * @param array $args associative array of parameters, see default values (optional)
     * @category template-tags
     */
    function racketmanager_crosstable( $league_id, $args = array() ) {
        global $league;

        $defaults = array('season' => false, 'group' => '', 'template' => '', 'mode' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[crosstable";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

     /**
     * display matches table
     *
     * @param int $league_id
     * @param array $args associative array of parameters, see default values (optional)
     * @category template-tags
     */
    function racketmanager_matches( $league_id, $args = array() ) {
        global $league;

        $defaults = array('season' => '', 'template' => '', 'mode' => '', 'limit' => 'true', 'match_day' => -1, 'group' => false, 'roster' => false, 'order' => false, 'show_match_day_selection' => '', 'show_team_selection' => '', 'time' => '', 'team' => 0, 'home_only' => 'false', 'match_date' => false, 'dateformat' => '', 'timeformat' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[matches";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";

        echo do_shortcode($shortcode);
    }

    /**
     * display one match
     * @param int $match_id
     * @param array $args additional arguments as associative array (optional)
     * @category template-tags
     */
    function racketmanager_match( $match_id, $args = array() ) {
        $defaults = array('template' => '');
        $args = array_merge($defaults, $args);
        $args['id'] = $match_id;

        $shortcode = "[match";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
    * display team list
    *
    * @param int|string $league_id
    * @param array $args additional arguments as associative array (optional)
    * @category template-tags
    */
    function racketmanager_teams( $league_id, $args = array() ) {
        global $league;

        $defaults = array('season' => false, 'template' => '', 'group' => false);
        $args = array_merge($defaults, $args);
        $args['league_id'] = intval($league_id);

        $shortcode = "[teams";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
    * display one team manually
    *
    * @param int $team_id
    * @param array $args additional arguments as associative array (optional)
    * @return void
    */
    function racketmanager_team( $team_id, $args = array() ) {
        $defaults = array('template' => '');
        $args = array_merge($defaults, $args);
        $args['id'] = $team_id;

        $shortcode = "[team";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
    * display championship manually
    *
    * @param int $league_id
    * @param array $args additional arguments as associative array (optional)
    * @return void
    */
    function racketmanager_championship( $league_id, $args = array() ) {
        global $league;

        $defaults = array('template' => '', 'season' => false);
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[championship";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
    * display championship manually
    *
    * @param int $league_id
    * @param array $args additional arguments as associative array (optional)
    * @return void
    */
    function racketmanager_archive( $league_id, $args = array() ) {
        $defaults = array('template' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[leaguearchive";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
    * display league
    *
    * @param int $league_id
    * @param array $args additional arguments as associative array (optional)
    * @return void
    */
    function racketmanager_league( $league_id, $args = array() ) {
        $defaults = array('season' => false, 'template' => '');
        $args = array_merge($defaults, $args);
        $args['league_id'] = $league_id;

        $shortcode = "[league";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }

    /**
     * display results table
     *
     * @param int $club_id affilated Club id
     * @param array $args associative array of parameters, see default values (optional)
     * @category template-tags
     */
    function racketmanager_results( $clubId, $args = array() ) {
        global $racketmanager;

        $args['affiliatedclub'] = $clubId;
        $args['days'] = 3;

        $shortcode = "[latestresults";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        echo do_shortcode($shortcode);
    }
    /**
     * display match email
     *
     * @param int $match_id match id
     * @param array $args associative array of parameters, see default values (optional)
     * @category template-tags
     */
    function racketmanager_match_notification( $matchId, $args = array() ) {
        global $racketmanager;

        $args['match'] = $matchId;

        $shortcode = "[matchnotification";
        foreach ($args AS $key => $value)
            $shortcode .= " ".$key."='".$value."'";
        $shortcode .= "]";
        $matchMessage = do_shortcode($shortcode);
        return $matchMessage;
    }


?>
