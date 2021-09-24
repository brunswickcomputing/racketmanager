<?php
    /**
     * Send debug code to the Javascript console
     * @param string $message Optional message that will be sent the the error_log before the backtrace
     */
    function debug_to_console($data) {
        if (is_array($data) || is_object($data)) {
            if (is_array($data)) error_log('PHP: array');
                else error_log('PHP: object');
            echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
            error_log('PHP: "'.json_encode($data).'"');
        } else {
            echo("<script>console.log('PHP: ".$data."');</script>");
            error_log('PHP: "'.$data.'"');
        }
    }
    
	/*
	 * Send the output from a backtrace to the error_log
	 * @param string $message Optional message that will be sent the the error_log before the backtrace
	 */
	function log_trace($message = '') {
		$trace = debug_backtrace();
		if ($message) {
			error_log($message);
		}
		$caller = array_shift($trace);
		$function_name = $caller['function'];
		error_log(sprintf('%s: Called from %s:%s', $function_name, $caller['file'], $caller['line']));
		foreach ($trace as $entry_id => $entry) {
			$entry['file'] = isset($entry['file']) ? $entry['file'] : '-';
			$entry['line'] = isset($entry['line']) ? $entry['line'] : '-';
			if (empty($entry['class'])) {
				error_log(sprintf('%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line']));
			} else {
				error_log(sprintf('%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line']));
			}
		}
	}

    /*
     * Sort multi array
     * @param array array to be sorted
     * @param array columns to sort by
     */
    function array_msort($array, $cols) {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_'.$k] = strtolower($row[$col]);
            }
        }

        $eval = 'array_multisort(';

        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }

        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;
    }
    
    /*
     * Create formatted url
     */
    function create_new_url_querystring() {
        add_rewrite_rule(
                       '^leagues/(.+?)-(.+?)-([0-9]{1})/?$',
                       'index.php?pagename=leagues%2F$matches[1]-leagues%2F$matches[1]-$matches[2]&league_name=$matches[1]%20$matches[2]%20$matches[3]',
                       'top'
                       );
        add_rewrite_rule('^leagues/(.+?)-(.+?)-([0-9]{1})/([0-9]{4})?$',
                         'index.php?pagename=leagues%2F$matches[1]-leagues%2F$matches[1]-$matches[2]&league_name=$matches[1]%20$matches[2]%20$matches[3]&season=$matches[4]',
                       'top'
                       );
        add_rewrite_rule( '^club/(.+?)/?$','index.php?pagename=club&club_name=$matches[1]','top');

        add_rewrite_tag('%league_name%','([^/]*)');
        add_rewrite_tag('%league_id%','([^/]*)');
        add_rewrite_tag('%season%','([0-9]{4})');
        add_rewrite_tag('%match_day%','([0-9]{1,2})');
        add_rewrite_tag('%team%','(.+?)');
        add_rewrite_tag('%club_name%','(.+?)');
        add_rewrite_tag('%match_date%','(.+?)');
    }
    add_action('init', 'create_new_url_querystring');

    /*
     * Create calendar download
     */
    function leaguemanager_calendar_download() {
        global $league;

        if ( isset( $_GET["team_id"] )  && isset( $_GET["league_id"] ) && isset( $_GET["season"] ) && isset( $_GET["leaguemanager_export"] ) ) {
            define('DATE_ICAL', 'Ymd\THis');
            $league_id = $_GET["league_id"];
            $league = get_league($league_id);
            $season = $_GET["season"];
            $team_id = $_GET["team_id"];
            $teamname = $_GET["team"];
            $matches = $league->getMatches( array("season" => $season, "team_id" => $team_id, "match_day" => -1) );
            $filename = $season."-".sanitize_title($league->title)."-".sanitize_title($teamname).".ics";
            $contents = "BEGIN:VCALENDAR\n";
            $contents .= "VERSION:2.0\n";
            $contents .= "PRODID:-//TENNIS CALENDAR//NONSGML Events //EN\n";
            $contents .= "CALSCALE:GREGORIAN\n";
            $contents .= "DTSTAMP:".date('Ymd\THis')."\n";
            foreach ( $matches AS $match ) {
                $match = get_match($match->id);
                $contents .= "BEGIN:VEVENT\n";
                $contents .= "UID:".$match->id."\n";
                $contents .= "DTSTAMP:".mysql2date('Ymd\THis', $match->date)."\n";
                $contents .= "DTSTART:".mysql2date('Ymd\THis', $match->date)."\n";
                $contents .= "DTEND:".date('Ymd\THis', strtotime('+2 hours',strtotime($match->date)))."\n";
                $contents .= "SUMMARY:".$match->match_title."\n";
                $contents .= "LOCATION:".$match->location."\n";
                $contents .= "END:VEVENT\n";
            }
            $contents .= "END:VCALENDAR";
            header('Content-Type: text/calendar');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            echo $contents;
            exit();
        }
    }
    add_action('init', 'leaguemanager_calendar_download');

    /**
     * Output and Get SVG.
     * Output and get the SVG markup for an icon in the Leaguemanager_SVG_Icons class.
     *
     * @param string $svg_name The name of the icon.
     */
    function leaguemanager_the_svg( $svg_name ) {
        echo leaguemanager_get_svg( $svg_name );
    }

    /**
     * Get information about the SVG icon.
     *
     * @param string $svg_name The name of the icon.
     */
    function leaguemanager_get_svg( $svg_name, $group = 'ui', $color = '' ) {

        // Make sure that only our allowed tags and attributes are included.
        $svg = wp_kses(
            Leaguemanager_SVG_Icons::get_svg( $svg_name ),
            array(
                'svg'     => array(
                    'class'       => true,
                    'xmlns'       => true,
                    'width'       => true,
                    'height'      => true,
                    'viewbox'     => true,
                    'aria-hidden' => true,
                    'role'        => true,
                    'focusable'   => true,
                ),
                'path'    => array(
                    'fill'      => true,
                    'fill-rule' => true,
                    'd'         => true,
                    'transform' => true,
                ),
                'polygon' => array(
                    'fill'      => true,
                    'fill-rule' => true,
                    'points'    => true,
                    'transform' => true,
                    'focusable' => true,
                ),
            )
        );

        if ( ! $svg ) {
            return false;
        }
        return $svg;
    }
?>
