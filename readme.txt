=== Racketmanager ===
Contributors: Paul Moffat
Tags: league management, sport, widget, league, tennis, tournament
Requires at least: 5.4
Tested up to: 5.4.2
Stable tag: 5.4.2

Plugin to manage and present Sports Leagues

== Description ==

This Plugin is designed to manage rackets leagues and tournaments and display them on your blog.

**Features**

* easy adding of teams and matches
* weekly-based ordering of matches with bulk editing mechanism
* automatic or manual saving of standings table
* automatic or drag & drop ranking of teams
* link posts with specific match for match reports
* unlimited number of widgets
* modular setup for easy implementation of sport types
* dynamic match statistics
* Championship mode with preliminary and k/o rounds

**Translations**

== Installation ==

To install the plugin to the following steps

1. Unzip the zip-file and upload the content to your Wordpress Plugin directory.
2. Activate the plugin via the admin plugin page.

== Screenshots ==
1. Main page for selected League
2. League Preferences
3. Adding of up to 15 matches simultaneously for one date
4. Easy insertion of tags via TinyMCE Button
5. Widget control panel
6. Example of 'Last 5' (shows 'Last 3') Functionality
7. Match Report

== Credits ==
The RacketManager icons were designed by Yusuke Kamiyamane (http://p.yusukekamiyamane.com/)
Based on the leaguemanager plugin by Kolja Schleich and LaMonte Forthun

== Changelog ==

= 8.35.0 =
* BUGFIX: handle non-numeric league ref
* UPDATE: enable stripe webhooks for stripe events
* UPDATE: check tournament entries for outstanding payments against invoices
* UPDATE: invoices handle previous payments
* UPDATE: separate club and player invoices

= 8.34.0 =
* BUGFIX: use correct stripe is_live flag
* BUGFIX: update invoice amounts correctly
* BUGFIX: set correct valie for no offset
* BUGFIX: ensure player tournament entry can be viewed
* UPDATE: show withdrawal date for tournament
* UPDATE: separate out tournament entries
* UPDATE: allow withdrawals from tournaments

= 8.33.0 =
* BUGFIX: remove extra '<' character
* BUGFIX: use passed season for competition tabs
* BUGFIX: ensure withdrawn teams at bottom of ranking
* BUGFIX: fix db field column
* BUGFIX: allow tournament entry correctly
* UPDATE: add competition grade
* UPDATE: add tournament withdrawal dates
* UPDATE: set tournament open/close/withdrawal dates based on start date and competition grade
* UPDATE: improve tournament finances section
* UPDATE: improve competition config settings
* UPDATE: improve event config settings
* UPDATE: add league admin secton
* NEW:    enable payments with stripe

= 8.32.0 =
* BUGFIX: check $message set
* UPDATE: set cup scheduled events only when not closed
* UPDATE: schedule competition open and reminder emails
* UPDATE: show open and closing dates in competition overviews
* UPDATE: check for possible 2nd round teams in consolation events
* UPDATE: advance teams with bye on first round start

= 8.31.0 =
* UPDATE: remove matach score reset from admin
* UPDATE: allow reset of match score in result entry
* UPDATE: allow teams to be added for consolation leagues
* UPDATE: no seeds in consolation events

= 8.30.0 =
* BUGFIX: initialise variable
* BUGFIX: pass season through to shortcode
* BUGFIX: ensure season set correctly
* BUGFIX: set correct points for share
* NEW:    cup admin function revamp

= 8.29.0 =
* BUGFIX: ensure partner loading indicator in place
* BUGFIX: ensure club entry displays correctly
* BUGFIX: ignore link_id when not provided
* UPDATE: set invalid player status for handled results
* UPDATE: show players with warnings on result entry

= 8.28.0 =
* BUGFIX: make standings work in API
* BUGFIX: add missing permission_callback
* UPDATE: remove cors set
* UPDATE: use modal for partner in doubles event
* UPDATE: adjust tiebreak points display
* UPDATE: fetch tab data without page refresh
* UPDATE: remove postponed option

= 8.27.0 =
* BUGFIX: remove extra '.' from select fields
* UPDATE: use club_id in place of affiliatedclub
* UPDATE: add validation to rest api

= 8.26.0 =
* BUGFIX: use correct database table alias
* BUGFIX: do not overwrite details due to reuse of index key
* UPDATE: show seeds and ratings for teams in tournament events
* UPDATE: load translations after init
* UPDATE: remove sponsor from matchcard
* UPDATE: do not notify favourites when match involves a bye
* UPDATE: set match link for tournament match
* UPDATE: remove results warning when player updated btm / year of birth before match confirmed
* UPDATE: change url when teams switched

= 8.25.0 =
* BUGFIX: ensure messages displayed for tournament update
* BUGFIX: set season from tournament
* BUGFIX: do not set players when team entered
* BUGFIX: use correct name for rating points field
* BUGFIX: check season set
* BUGFIX: handle competition code for tournament add
* BUGFIX: set open date
* BUGFIX: use correct team title for withdrawn message
* UPDATE: set season to live when tournament created
* UPDATE: show player ratings in player header
* UPDATE: allow tournament entry form view when tournament no longer open
* UPDATE: show inline error messages
* UPDATE: check btm and email not used elsewhere
* UPDATE: schedule player ratings for all players
* UPDATE: show player ratings on tournament entry
* UPDATE: run tournament ratings when tournament open
* UPDATE: check partner not already entered
* UPDATE: ensure players only entered into an tournament event once  
* UPDATE: use scores from matches to calculate league points
* UPDATE: only get matches for current season
* UPDATE: store team rating against team in table
* UPDATE: set team rating for tournament entry

= 8.24.0 =
* BUGFIX: handle dates not set
* BUGFIX: set display name correctly after only first or surname change
* BUGFIX: set closed state only when past close date
* BUGFIX: set close time to end of day
* BUGFIX: set closed state only when past close date
* BUGFIX: handle empty start date for event
* BUGFIX: handle season not found
* BUGFIX: use correct player reference
* BUGFIX: use camelCase for player id to allow consistent checking
* BUGFIX: set correct error class
* BUGFIX: handle existing player
* BUGFIX: use correct link for tournament entry
* BUGFIX: handle old tournament entry links
* BUGFIX: handle player not found
* UPDATE: user bootstrap form error messages and highlighting
* UPDATE: display player club memberships
* UPDATE: change club page layout
* UPDATE: set tournament round dates
* UPDATE: use player ratings

= 8.23.1 =
* BUGFIX: check player found

= 8.23.0 =
* BUGFIX: set stopped set correctly
* BUGFIX: initialise search results
* BUGFIX: use correct team type
* BUGFIX: pass link to popover for player clubs
* BUGFIX: handle player update correctly
* BUGFIX: handle walkovers correctly

= 8.22.0 =
* BUGFIX: use input competition type
* BUGFIX: do not set complete flag too early
* BUGFIX: get team info
* BUGFIX: handle player with no matches
* BUGFIX: set date_open for tournament competition
* BUGFIX: direct to correct page for match
* BUGFIX: only show contact details when logged in
* BUGFIX: prevent error when generating player link
* UPDATE: move match message to left of match lists
* UPDATE: add tournament entries
* UPDATE: set first team set score when second team entered only
* UPDATE: get team and player counts for event
* UPDATE: allow player search by lta number
* UPDATE: allow player search from query string
* UPDATE: check if lta number is used
* UPDATE: check if email address is used
* UPDATE: show tie break score
* UPDATE: update matches when team withdrawn
* UPDATE: send email when team withdrawn
* UPDATE: send single email when contacting teams including match secretaries
* UPDATE: simplify tournament admin

= 8.21.0 =
* BUGFIX: show tournaments list when no active tournament
* BUGFIX: use correct class for message deletion
* UPDATE: show winners on last day of competition
* UPDATE: show winners on last day of tournament
* UPDATE: use button to trigger tournament open notifications
* UPDATE: only retrieve confirmed teams for entries
* UPDATE: allow competition entry from list if open

= 8.20.0 =
* UPDATE: format competition start date in overview
* UPDATE: show first active tournament when none specified

= 8.19.0 =
* BUGFIX: prevent competition warning message when no match dates
* UPDATE: speed up favourites
* UPDATE: add indexes to speed performance
* UPDATE: allow player search and view

= 8.18.1 =
* BUGFIX: unable to register a player with a new club with an existing date of birth
* BUGFIX: remove redundant order of play code

= 8.18.0 =
* BUGFIX: calculate win pct only when played matches
* UPDATE: notify admin when user added to club
* UPDATE: allow cancelled status for matches
* UPDATE: check for player clash when scheduling tournament finals

= 8.17.0 =
* BUGFIX: fix entry page display 
* BUGFIX: handle event season not set
* BUGFIX: handle competition season not found
* BUGFIX: unable to register a player with a new club with an existing lta number
* UPDATE: add player name alphabet list lookup
* UPDATE: show player stats for each rubber type

= 8.16.1 =
* BUGFIX: handle cup match semi final results correctly
* BUGFIX: format match list screen correctly

= 8.16.0 =
* BUGFIX: handle system teams
* BUGFIX: get team details for match season (#22)
* BUGFIX: initialise variable
* BUGFIX: ensure is_player_captain returns correct result
* BUGFIX: set correct team name
* BUGFIX: set correct walkover reference
* BUGFIX: set correct match status for walkover
* BUGFIX: set scorestatus correctly
* BUGFIX: reset fields correctly* UPDATE: set dates and venue
* BUGFIX: allow cup to proceed to final
* UPDATE: do not display score for byes
* UPDATE: show player stats for club in event view
* UPDATE: redirect to latest tournament
* UPDATE: add reset password modal
* UPDATE: check flag correctly
* UPDATE: update competition headers format
* UPDATE: highlight when logged in player featured
* UPDATE: indent team details display
* UPDATE: format seo strings correctly
* UPDATE: add competitions list pages
* UPDATE: add new pages
* UPDATE: handle withdrawn team matches
* UPDATE: add team players table

= 8.15.0 =
* BUGFIX: set match completed when admin updates
* BUGFIX: use correct reference for away walkover player
* BUGFIX: handle boys and girls teams
* BUGFIX: link to correct page
* UPDATE: get count of players

= 8.14.0 =
* BUGFIX: return non player teams
* BUGFIX: ensure headers and footers always included for emails
* BUGFIX: prevent sql error
* BUGFIX: set type in settings
* UPDATE: use player age at end of event
* UPDATE: new relegation type for finishing 2nd bottom
* UPDATE: strikethrough withdrawn teams in constitution
* UPDATE: allow constitution to be sent from admin and in draft
* UPDATE: do not show seeds in consolation draws

= 8.13.0 =
* BUGFIX: club details already returned by league_team class (#21)
* BUGFIX: cup matches should not use bonus for straight sets (#20)
* BUGFIX: update league tables when match complete (#19)
* BUGFIX: show comments for match result input
* BUGFIX: handle no club set
* BUGFIX: handle league not found
* BUGFIX: handle match not found
* BUGFIX: use correct option
* BUGFIX: allow for missing values
* UPDATE: show latest results format based on competition type
* UPDATE: show when team withdrawn in tournament
* UPDATE: competition entry open by season
* UPDATE: use player name when entered for walkover (#18)

= 8.12.0 =
* BUGFIX: check for fixed match dates correctly
* UPDATE: move set calculation javascript inline (#17)
* UPDATE: giving a walkover does not qualify for a plate competition
* UPDATE: allow latest result by competition
* UPDATE: enable unplayed main draw tams in consolation

= 8.11.0 =
* UPDATE: update tournament match header when date changed

= 8.10.0 =
* BUGFIX: check for event not found
* UPDATE: update player details when registering at new club (#15)
* BUGFIX: allow all selection
* BUGFIX: only show messages for current user
* UPDATE: highlight match where player has interest
* BUGFIX: use correct delimiter to allow wpdb substitution
* UPDATE: redirect to homepage after logout
* UPDATE: use capability not role
* UPDATE: handle logged in user redirect
* UPDATE: handle errors better
* BUGFIX: handle result comment not set
* BUGFIX: handle shared sets correctly
* UPDATE: handle errors better
* BUGFIX: set team and role correctly
* UPDATE: remove link formating
* UPDATE: use data from match object
* UPDATE: add original match date
* BUGFIX: set match status correctly
* UPDATE: allow tournament match date setting
* UPDATE: allow match changes menu
* UPDATE: allow match date update
* UPDATE: set correct html status on error
* UPDATE: allow swapping of home and away teams
* UPDATE: display match header correctly for small screens
* UPDATE: display more information in title for tournament match
* UPDATE: use correct term
* UPDATE: send email when match date changes
* UPDATE: highlight when match has player warnings
* UPDATE: enter result on result screen
* UPDATE: use ineligible class for player errors
* UPDATE: allow match entry when result unconfirmed
* UPDATE: show all player errors
* BUGFIX: notify teams of next match correctly
* UPDATE: allow user to delete messages by read/unread
* UPDATE: check player checks at confirmation
* UPDATE: allow reset of match status
* UPDATE: use abandoned status for team matches

= 8.9.0 =
* BUGFIX: allow file uploads with events
* BUGFIX: fix match edit button
* BUGFIX: handle boys/girls teams
* BUGFIX: ensure banner header correct width on small screen
* BUGFIX: handle security and correct response
* BUGFIX: handle no event found for league
* BUGFIX: handle walkover not set
* BUGFIX: handle league not found
* BUGFIX: allow add team
* UPDATE: use Bootstrap 5.3 on CDN
* UPDATE: format draws to show on smaller screen
* UPDATE: use menu bar for smaller screens
* UPDATE: tidy up account page
* UPDATE: tidy up favourites page
* UPDATE: store/view emails for user
* UPDATE: to play in plate must had at most 2 matches one of which was a bye
* UPDATE: allow result chase for any competition type
* UPDATE: handle tournament result outstanding
* UPDATE: only check played rounds for fixed match days

= 8.8.0 =
* BUGFIX: esnure match status button always visible
* UPDATE: allow grid and list views for tournament matches
* UPDATE: make result entry button thinner

= 8.7.0 =
* BUGFIX: allow upcoming matches to be returned
* BUGFIX: fix random sorting
* BUGFIX: get players for boys and girls events
* BUGFIX: handle no players found
* BUGFIX: handle tie break for fast4
* BUGFIX: send correct message when result entered
* BUGFIX: set correct match link
* BUGFIX: set correct winner for walkover
* BUGFIX: update host
* UPDATE: allow match and rubber status to be set
* UPDATE: handle postponed status

= 8.6.0 =
* BUGFIX: fix new player doubles team name
* NEW:    allow box leagues
* NEW:    user rest_api
* UPDATE: add check for lta tennis number on result entry
* UPDATE: add age limit checks on result entry
* UPDATE: allow any club player to submit league entry
* UPDATE: move competition code to competition not season
* UPDATE: handle invalid players automatically

= 8.5.1 =
* BUGFIX: mark team withdrawn when team id set
* BUGFIX: handle first season for entry notification

= 8.5.0 =
* BUGFIX: only use event days when match day restriction
* BUGFIX: prevent notification when no leagues
* UPDATE: introduce boys and girls events
* UPDATE: allow new team entry for league

= 8.4.0 =
* BUGFIX: check for team status
* BUGFIX: set correct number of events
* BUGFIX: set points correctly for shared matches
* BUGFIX: handle only one player through to next round
* BUGFIX: apply walkover penalties to match correctly
* BUGFIX: handle MTB games correctly
* UPDATE: add page titles
* UPDATE: handle retired team scores
* UPDATE: handle new match scoring
* UPDATE: handle LTA competition code
* UPDATE: add player year of birth
* UPDATE: restrict tournament entries by age
* UPDATE: allow team captains to amend player details

= 8.3.0 =
* BUGFIX: use time period in result emails correctly
* BUGFIX: validate tiebreak score
* BUGFIX: display match_day
* BUGFIX: display crossfield correctly when selecting by team
* BUGFIX: ensure first round identified correctly
* BUGFIX handle home and away correctly
* UPDATE: handle withdrawn team

= 8.2.0 =
* BUGFIX: count distinct players
* BUGFIX: make display fit easier
* BUGFIX: allow updates by match secretary or player
* BUGFIX: fix email address value
* BUGFIX: show only one comments box
* BUGFIX: use correct referrer
* UPDATE: speed up query
* UPDATE: use walkover values from point rules
* UPDATE: move championship event settings
* UPDATE: add team as favourite and change favourite displays

= 8.1.1 =
* BUGFIX: handle empty match status
* BUGFIX: set update allowed correctly if team player
* BUGFIX: allow match comments on team match result entry

= 8.1.0 =
* BUGFIX: remove extraneous '"'
* BUGFIX: delete cached rubber after update
* BUGFIX: ensure rewrites work
* BUGFIX: only get championship matches 
* BUGFIX: set walkover correctly
* BUGFIX: fix get_rubber
* BUGFIX: use correct field
* UPDATE: set correct set score
* UPDATE: use banner header for archive
* UPDATE: get system record indicator for player
* UPDATE: use separate table for rubber players
* UPDATE: remove datatables javascript
* UPDATE: add index in match_id to speed up queries
* UPDATE: make count players more efficient
* UPDATE: move player stats totals calculations
* UPDATE: move player get_matches to player class
* UPDATE: improve standings display
* UPDATE: improve crosstable view
* UPDATE: send email when handling result check
* UPDATE: fix tournament plan display
* UPDATE: send player registration emails to requestor

= 8.0.0 =
* NEW:    introduce events between competitions and leagues
* NEW:    tournament Features
* UPDATE: look and feel for pages with background colour
* UPDATE: numerous code quality changes
* UPDATE: cups with home and away ability

= 7.6.0 =
* UPDATE: handle match updates when player registered to both teams
* UPDATE: store time of cluyb player creation
* UPDATE: calculate standings for completed matches only
* BUGFIX: handle player checks correctly

= 7.5.0 =
* UPDATE: automatically chase missing results and confirmations
* UPDATE: highlight overdue results and confirmations

= 7.4.1 =
* UPDATE: make player request user title attribute
* UPDATE: separate out player search filter
* BUGFIX: make roster club players
* BUGFIX: get correct league for tournament entry

= 7.4.0 =
* UPDATE: rename function to getplayers for club
* UPDATE: add new player class
* UPDATE: rename roster to clubplayers
* UPDATE: move players admin to own section
* UPDATE: use firstname not firstName
* UPDATE: set user login to lowercase
* UPDATE: rename roster requests to club player requests
* UPDATE: rename roster to club players
* UPDATE: standardise club player creation
* UPDATE: allow player edit
* UPDATE: separate out match filter from action
* UPDATE: setup league entry
* BUGFIX: make editeamplayer public function

= 7.3.1 =
* BUGFIX: fix check for match tie-break player played
* BUGFIX: message functions now in main class
* BUGFIX: remove unused $link
* UPDATE: use correct team class
* UPDATE: update club teams when shortcode changed

= 7.3.0 =
* UPDATE: restructure main code class
* UPDATE: add roster entry level option
* UPDATE: add lta number required option
* UPDATE: rename BTM to LTA tennis number
* UPDATE: add new relegation option
* UPDATE: add walkover/shared rubber result entry
* UPDATE: add walkover rules for rubber/match scores
* UPDATE: allow multiple league schedule attempts
* UPDATE: move results checker to match class
* UPDATE: add unregistered player to results check
* BUGFIX: display match scores correctly (issue #13)
* BUGFIX: make order of play screens work correctly
* BUGFIX: handle order of play screen when no entries

= 7.2.1 =
* BUGFIX: update club address from front end (issue #10)
* BUGFIX: display tournament competitions correctly (issue #9)

= 7.2.0 =
* UPDATE: move recaptcha keys to keys settings
* UPDATE: add google maps api keys to settings (issue #7)
* BUGFIX: use correct text domain
* BUGFIX: get competition details correctly
* UPDATE: handle deleted teams in championship (issue #8)

= 7.1.0 =
* UPDATE: move uninstall to own file
* UPDATE: add date and due dates to Invoices
* UPDATE: allow invoice reprint
* UPDATE: expand invoice search criteria
* UPDATE: remove club location picker
* BUGFIX: use correct class for add_racketmanager_page

= 7.0.0 =
* UPDATE: restructure code
* UPDATE: add finance section for invoices
* UPDATE: style message sent confirmation when chasing result
* BUGFIX: show response when club updated more than once
* BUGFIX: handle no seasons for competition
* BUGFIX: handle disabled indicator for players correctly

= 6.29.0 =
* UPDATE: handle trailing slash on RACKETMANAGER_URL
* UPDATE: change menu icon

= 6.28.0 =
* UPDATE: only send one email when result entered

= 6.27.0 =
* BUGFIX: show account screen correctly
* BUGFIX: update club info from front end
* BUGFIX: use current season to get team info
* BUGFIX: update match secretary contact number correctly
* UPDATE: show error in login page header
* UPDATE: handle user display page after resetting password
* UPDATE: add search by name for players
* UPDATE: update player details for tournament entry
* UPDATE: make scheduling error messages more informative
* UPDATE: change scheduling pairing to random order
* UPDATE: add player name to registration email
* UPDATE: highlight notification messages correctly
* UPDATE: move forms to separate templates subfolder
* UPDATE: code cleanup - remove sonarlint warnings
* UPDATE: handle shared set scores easier
* UPDATE: custom email change email
* UPDATE: change display of clubs admin screen
* UPDATE: add update player function to allow player locking
* UPDATE: player lock check in match result
* UPDATE: change admin result display for smaller screens

= 6.26.3 =
* BUGFIX: fix email from address on league contact page
* BUGFIX: use correct title on order of play schedule
* BUGFIX: remove cc league secretary from fixture email

= 6.26.2 =
* BUGFIX: handle no rubbers on tennis match score
* BUGFIX: change order of paired teams for scheduling
* BUGFIX: display away team name correctly if previous match not completed

= 6.26.1 =
* BUGFIX: ignore withdrawn teams from scheduling
* BUGFIX: sort teams with dummy entries correctly within scheduling

= 6.26.0 =
* UPDATE: ignore withdrawn teams from scheduling
* UPDATE: display all scheduling errors
* UPDATE: make new competition seasons draft on creation
* UPDATE: send fixture list to captains as well as link

= 6.25.0 =
* UPDATE: simplify season get
* UPDATE: remove unused select options
* UPDATE: allow draft seasons
* UPDATE: only show draft seasons on backend
* UPDATE: show matches for competition in admin and by club
* UPDATE: send league constitution emails to match secretaries when season changed to live
* BUGFIX: refresh competition object when settings updated

= 6.24.0 =
* UPDATE: handle scheduling of teams from same club in first round
* UPDATE: send fixtures to team captains and match secretaries
* BUGFIX: add missing query option to get_matches

= 6.23.0 =
* NEW: allow scheduling of league matches
* UPDATE: handle week offset when checking court availability
* UPDATE: move page templates to own folder
* UPDATE: move entry templates to own folder
* UPDATE: separate season edit into new screen and allow matchday date setting
* BUGFIX: fix match tie break and playoff validation

= 6.22.1 =
* BUGFIX: delete result checker only once per match

= 6.22.0 =
* BUGFIX: use correct colour for playdown class in striped table
* UPDATE: remove competition id from get_leagues calls
* UPDATE: allow new teams to be added for league entry
* UPDATE: check enough courts available for league entry

= 6.21.0 =
* UPDATE: send email when result approved and auto confirmation
* UPDATE: remove redundant match box
* UPDATE: change matchcard print function names
* UPDATE: standard match update email subject format

= 6.20.1 =
* BUGFIX: correct rubber loop
* BUGFIX: use correct value for match result confirmation
* BUGFIX: fix rubber point display

= 6.20.0 =
* UPDATE: store type against rubber
* UPDATE: show team captain in result entry
* UPDATE: remove register link
* UPDATE: delete player checks before each match update
* BUGFIX: handle updatesAllowed check correctly
* BUGFIX: fix button name

= 6.19.0 =
* UPDATE: add validation for player selections for rubbers
* UPDATE: change button spacing on outstanding results screen
* UPDATE: improve admin screen visibility on mobile devices

= 6.18.0 =
* UPDATE: add how to category template
* UPDATE: fix historical player names for system records

= 6.17.0 =
* NEW: add competition scoring option
* NEW: send email to captains to chase result approval
* BUGFIX: remove erroneous semi colon
* UPDATE: link to match from results checker
* UPDATE: change walkover/no player/shared player names
* UPDATE: add unregistered player option
* UPDATE: show login prompt on match details when not logged in

= 6.16.1 =
* BUGFIX: calculate match score after set check
* BUGFIX: use league name for championship matches
* BUGFIX: update javascript to use correct fields for match update

= 6.16.0 =
* UPDATE: display league selection on match page
* UPDATE: add match and set validation
* BUGFIX: handle player not found

= 6.15.0 =
* UPDATE: exclude pending matches from outstanding results list
* UPDATE: fix breadcrumbs display formatting
* UPDATE: show why user cannot update results
* UPDATE: link to match results entry from results screen

= 6.14.0 =
* NEW: show matches with results not provided
* NEW: send email to captains to chase outstanding results
* BUGFIX: add missing break statement
* UPDATE: add filter to only show outstanding result checks
* UPDATE: cc competition secretary on emails
* UPDATE: tidy up results page

= 6.13.0 =
* UPDATE: use bootstrap styling for daily matches / latest results
* UPDATE: hide standings columns on small screens
* UPDATE: reformat set scores on small screens
* UPDATE: allow non-playing captains to confirm results
* UPDATE: move result options into array by competitionType
* UPDATE: move roster options into array
* UPDATE: move player check options into array
* UPDATE: move championship options into array
* UPDATE: send result notification to opposing captain
* UPDATE: display match information

= 6.12.0 =
* UPDATE: format team display in favourite emails
* UPDATE: use tabs in admin screens instead of pills
* UPDATE: fix breadcrumb styling
* NEW: add ability to email league captains

= 6.11.0 =
* BUGFIX: check match confirmed flag for away team captain update allowed
* BUGFIX: remove warning when sorting array
* BUGFIX: remove extra space created in admin by bootstrap use
* UPDATE: show match day for outstanding matches in crosstable
* UPDATE: change standings headings
* UPDATE: make standings use bootstrap styling
* UPDATE: do not display sport columns for standings on small screens
* UPDATE: make matches use bootstrap styling
* UPDATE: make selections use bootstrap styling


= 6.10.1 =
* BUGFIX: add email column to select for rosterrequests
* BUGFIX: use club->approve_player_request
* BUGFIX: remove check for authorised to addplayer
* BUGFIX: use correct label for showRubbers
* BUGFIX: use correct season key for archive
* UPDATE: change layout for match modal for small screens
* UPDATE: remove leading/trailing spaces for first name/surname

= 6.10.0 =
* BUGFIX: check for competitiontype correctly
* BUGFIX: fix admin javascript type problem
* BUGFIX: fix bootstrap styling being overridden in admin screens for radio buttons
* BUGFIX: handle team type not set
* BUGFIX: pass missing parameters to addtournament query
* BUGFIX: use correct reference for tournament notify message
* UPDATE: add default setting for championship number of rounds
* UPDATE: add email address as player optional attribute
* UPDATE: add favourites for clubs
* UPDATE: amend entry form rewrite rules
* UPDATE: change accordion colours
* UPDATE: change link for club
* UPDATE: check for club player request already existing
* UPDATE: hide columns on small screens in admin section
* UPDATE: make match modal fit page better
* UPDATE: show club for finalists
* UPDATE: show seasons by latest first
* UPDATE: use bootstrap floating labels
* UPDATE: use modal backdrop

= 6.9.0 =
* UPDATE: display cup winner
* UPDATE: add favourites for leagues

= 6.8.1 =
* BUGFIX: fix javascript security warnings

= 6.8.0 =
* BUGFIX: pass season to tournament admin pages
* BUGFIX: set correct number of rounds if tournament matches completed
* BUGFIX: fix tab handling for multiple leagues
* UPDATE: set urls correctly for winners
* UPDATE: add tournament planner
* UPDATE: standardise admin screen layouts

= 6.7.0 =
* UPDATE: get modal working with bootstrap
* UPDATE: capture comments for match results
* UPDATE: use bootstrap accordion
* UPDATE: add rewrite rules for cups and tournaments
* UPDATE: use sport independent templates for championshipsWe We
* UPDATE: change password hint message
* UPDATE: use bootstrap form styling for auth pages
* UPDATE: redirect to homepage when login initiated from login page
* UPDATE: allow comments for match result entry
* UPDATE: use bootstrap styling for match/rubber result entry
* UPDATE: allow updates to rubber only if player in team
* UPDATE: update match when status changes

= 6.6.0 =
* BUGFIX: handle no seasons for league
* UPDATE: allow trigger of cup entry open
* UPDATE: allow trigger of tournament entry open
* UPDATE: allow players to be added from club roster directly
* UPDATE: allow roster import
* UPDATE: rearrange tab orders for results in admin menu
* UPDATE: rearrange tab orders for admin in admin menu
* UPDATE: remove export function
* UPDATE: update documentation

= 6.5.0 =
* UPDATE: move results/resultschecker into own area in admin menu
* UPDATE: move tournaments to own area in admin menu
* UPDATE: move clubs to own area in admin menu
* UPDATE: move teams under clubs in admin
* UPDATE: move rosters under clubs in admin
* BUGFIX: ensure pages shown within racketmanager section of admin menu
* UPDATE: create admin section in admin menu and move season maintenance to it
* UPDATE: move players under admin
* UPDATE: move roster requests under admin
* UPDATE: move cups to own area in admin menu
* UPDATE: move leagues to own area in admin menu
* UPDATE: return competitionTypes as array
* UPDATE: add competition uses explicit type (league/cup/tournament)
* UPDATE: send constitution to league secretary when season added / delete withdrawn teams

= 6.4.0 =
* BUGFIX: fix season setting
* UPDATE: add competition constitution option
* UPDATE: allow league entry by match secretaries
* UPDATE: give archive links correct name
* UPDATE: set correct rewrite rules
* UPDATE: set number of rounds correctly in championship construct
* UPDATE: use bootstrap v5 for admin screens

= 6.3.0 =
* BUGFIX: check team found in competition->get_team_info
* BUGFIX: correct html element in email_welcome template
* BUGFIX: get roster request auto confirmation working correctly
* UPDATE: allow cup entries by match secretaries
* UPDATE: fadeout success messages from ajax after 10 seconds
* UPDATE: format roster request emails
* UPDATE: format tournament entry email
* UPDATE: get date and time formats once and store in global variable for use
* UPDATE: get options once and store in global variable for use
* UPDATE: get site info once and store in global variable for use
* UPDATE: only update match if result changed
* UPDATE: pass from email address into matchnotification
* UPDATE: redirect to referral page after login
* UPDATE: remove depreciated variable for wp_new_user_notification
* UPDATE: standardise result notification emails

= 6.2.2 =
* UPDATE: sort tournaments by date in admin pages
* UPDATE: use separate display format for tournament dates

= 6.2.1 =
* BUGFIX: fix player result check bug with playedrounds

= 6.2.0 =
* BUGFIX: add missing end of statement to do_action in admin/includes/match.php
* BUGFIX: fix admin player stats
* BUGFIX: handle debug handle correctly when using ajax
* BUGFIX: only check match update allowed if affiliatedclub is set
* BUGFIX: replace &mdash; with '-'
* BUGFIX: send result notification emails correctly
* UPDATE: add ability to notify teams of match details at individual level
* UPDATE: add braces around if statement in admin/show-league and reformat lines
* UPDATE: add password complexity check to frontend
* UPDATE: align points correctly in championship admin screens
* UPDATE: allow headers to be passed to email send
* UPDATE: create dummy team object for "Bye"
* UPDATE: fix styling errors
* UPDATE: format change password confirmation email
* UPDATE: format password reset emails
* UPDATE: format privacy emails
* UPDATE: get team details in match.php
* UPDATE: link to league match page from admin results page
* UPDATE: move match notification function to racketmanager class
* UPDATE: move racketmanager.js file to js folder
* UPDATE: organise email templates into separate folder
* UPDATE: send notification of next tournament match details
* UPDATE: send notification of next tournament match details for first rounds
* UPDATE: store results options by competition type
* UPDATE: style welcome and reset password emails
* UPDATE: use competitiontype
* UPDATE: use league-tab rather than jquery-ui-tab
* UPDATE: use standard get_tournaments for all tournament queries

= 6.1.0 =
* UPDATE: allow non-rubber match results to be entered
* UPDATE: add result confirmation email addresses for cups and tournaments
* UPDATE: replace &#8211; with -
* UPDATE: make match confirmation status able to be passed in league->update_match_results
* UPDATE: make tournament bracket header flexible width to allow headings to appear correctly
* UPDATE: make modal close button not overflow
* UPDATE: make modal fit screens correctly
* UPDATE: remove number incrementors on modal screen
* UPDATE: rename updateRubbers to updateResults
* UPDATE: make function to get confirmation email address
* BUGFIX: remove extra parameter from $league->update_match_results in championship->update_final_results
* BUGFIX: user correct variables for num_sets and num_rubbers
* BUGFIX: ensure correct parameters passed to getMatchUpdateAllowed function

= 6.0.5 =
* BUGFIX: sort matches by id for championship proceed to next round

= 6.0.4 =
* BUGFIX: fix group explode error in admin
* UPDATE: use tournament name on tournament entry form
* UPDATE: change tab name to "Draw" on championship pages
* UPDATE: allow match card to be printed for player tournaments
* UPDATE: reformat lines
* UPDATE: set and show home team for championships

= 6.0.3 =
* BUGFIX: make privacy exporter work
* UPDATE: make championship proceed function clearer

= 6.0.2 =
* UPDATE: apply consistent widths for standings and crosstable fields
* UPDATE: replace css style num with column-num in admin
* UPDATE: display set and total scrore boxes when printing match card

= 6.0.1 =
* BUGFIX: use correct plugin name in widget.php to load Javascript
* BUGFIX: use correct plugin name in admin.php for ajax settings
* UPDATE: remove tinymce

= 6.0.0 =
* NEW: use racketmanager instead of leaguemanager

= 5.6.22 =
* BUGFIX: user correct team field for checkPlayerResult
* BUGFIX: in championship match only call setteams when round specified
* BUGFIX: reset queryargs when getting matches in tennis.php
* BUGFIX: check action is set in login
* UPDATE: made match modal full width in front end
* UPDATE: reformat lib class code layouts
* UPDATE: remove unused core.php
* UPDATE: remove penalty and overtime checks
* UPDATE: remove -:- as score
* UPDATE: set match score consistently in shortcodes.php
* UPDATE: only show start time if specified
* UPDATE: allow match results for tournaments

= 5.6.21 =
* BUGFIX: make club admin page work
* BUGFIX: handle missing team in results checker
* BUGFIX: use correct tournament->id

= 5.6.20 =
* UPDATE: use CDN to pull in datatables files (js and css)
* UPDATE: tidy up code

= 5.6.19 =
* BUGFIX: remove debug statements
* BUGFIX: print array details in error_log correctly in debug_to_console
* BUGFIX: advance winning team correctly in championship admin
* BUGFIX: remove modal css directs includes in css files
* BUGFIX: handle cache bypass correctly
* BUGFIX: tidy up AJAX functions to display rubbers
* UPDATE: allow match calendar by league, competition and club

= 5.6.18 =
* BUGFIX: remove duplicate heading on competition template
* BUGFIX: remove labels for archive drop-downs
* BUGFIX: format league titles correctly on competition page
* BUGFIX: handle missing league in shortcode
* UPDATE: make league heading <h1> in league archive template
* UPDATE: add page templates correctly
* UPDATE: create category template for rules

= 5.6.17 =
* UPDATE: show latest results on club page

= 5.6.16 =
* UPDATE: show latest results

= 5.6.15 =
* UPDATE: use common function for email send and use HTML format
* UPDATE: add option to mark result checker entries as handled
* UPDATE: sort result checker entries in descending order

= 5.6.14 =
* BUGFIX: sort tournaments by descending name
* BUGFIX: remove shortcode debug statements and extraneous return
* BUGFIX: fix deletion of matches in league
* BUGFIX: add debug logging to console
* UPDATE: tidy up indentions in code

= 5.6.13 =
* BUGFIX: remove debug statement from tennis.php calculatepoints function
* UPDATE: show winners of tournaments

= 5.6.12 =
* BUGFIX: remove link from results checker

= 5.6.11 =
* BUGFIX: ensure result and standings updates use correct season

= 5.6.10 =
* BUGFIX: allow delete of players correctly
* UPDATE: delete duplicate player teams

= 5.6.9 =
* BUGFIX: handle championship proceed to first round team names correctly
* BUGFIX: use final_round instead of final for match object
* BUGFIX: correct team display for championship match edit

= 5.6.8 =
* BUGFIX: do not exit when no matches for daily match check
* BUGFIX: pull details from correct table in get_club_player
* BUGFIX: make addTeamCompetition function public
* BUGFIX: use correct path for match.php in admin.php
* BUGFIX: handle no time in match edit
* BUGFIX: allow reset of teamqueryarg

= 5.6.7 =
* UPDATE: do not check results for system records
* BUGFIX: check result on played rounds when in relevant timeframe

= 5.6.6 =
* UPDATE: add tennis specific is_tie for ranking

= 5.6.5 =
* UPDATE: change wp_cache_add to wp_cache_add
* UPDATE: remove unused getTable function
* UPDATE: name cache entries properly
* BUGFIX: save custom fields on standingsdata update
* BUGFIX: delete cached data after update

= 5.6.4 =
* BUGFIX: handle missing round in championship admin
* BUGFIX: handle no matches in template tags
* BUGFIX: delete competition cache after season changes
* BUGFIX: ensure team name calculation ignores player teams
* UPDATE: restructure admin area
* UPDATE: add club name to partner on tournament entry form

= 5.6.3 =
* UPDATE: change tournament entry order display
* UPDATE: add documentation for tournaments
* BUGFIX: fix tournament entry season update

= 5.6.2 =
* BUGFIX: stop conflict with elementor plugin
* BUGFIX: fix match card popup error
* BUGFIX: handle popup blocker for matchcard print
* BUGFIX: ensure current match day is correctly set

= 5.6.1 =
* UPDATE: get season from tournament details

= 5.6.0 =
* NEW: allow payers to enter tournaments online
* BUGFIX: fix table import

= 5.5.12 =
* UPDATE: update the way error messages are displayed
* BUGFIX: redirect on login if required for non-admin users

= 5.5.11 =
* UPDATE: change player team template
* UPDATE: add front end form validation
* UPDATE: make ajax frontend call synchronous

= 5.5.10 =
* UPDATE: restrict team selection to competition type
* BUGFIX: handle no primary league set

= 5.5.9 =
* BUGFIX: handle not found correctly in shortcodes

= 5.5.8 =
* NEW: how to documentation for administrators
* UPDATE: make racketmanager section on admin menu
* NEW: add player locked to team check

= 5.5.7 =
* UPDATE: change member account update to highlight specific errors

= 5.5.6 =
* NEW: match result validation for administrators
* UPDATE: automatically generate team name from club and type

= 5.5.5 =
* UPDATE: change profile screen template
* UPDATE: allow password visibility toggle
* UPDATE: use svg icons
* BUGFIX: create player when roster request player_id not set
* BUGFIX: only action incomplete roster requests

= 5.5.4 =
* UPDATE: highlight winner of matches and rubbers
* UPDATE: change admin form layouts
* UPDATE: remove dashboard widget
* UPDATE: allow club updates by match secretary

= 5.5.3 =
* UPDATE: allow match secretaries to update team captain information
* BUGFIX: roster count to check for affiliated club

= 5.5.2 =
* UPDATE: send email to inform administrator of pending roster requests and pending results
* UPDATE: remove UpdateResultsMatch function and replace by admin UpdateResults
* BUGFIX: fix championship match updates from frontend

= 5.5.1 =
* BUGFIX: ensure login pages work with shortcodes
* BUGFIX: make match modal popup work for cups
* UPDATE: make css files versioned

= 5.5.0 =
This is release contains major restructuring on the technical level to improve performance and security.
* NEW: Major restructuring of plugin code
* NEW: mobile responsive admin panel
* NEW: wordpress style template tags system
* NEW: Generalization of sports modules for easier implementation of new sport types
* BUGFIX: league settings issue due to caching

= 5.4.7 =
* UPDATE: allow seasons to be added across competitions

= 5.4.6 =
* UPDATE: use modal popup not thickbox
* UPDATE: use modal popup for user tennis match updates
* BUGFIX: ensure user match updates are reflected in tables

= 5.4.5 =
* BUGFIX: change get_matches to return all results when final parameter not specified
* UPDATE: display drawn matches in player stats if a draw is possible
* UPDATE: make additional points handle half points
* UPDATE: show pending player requests on club page
* UPDATE: allow match secretaries to remove players from their roster
* UPDATE: allow club shortcode as search term
* UPDATE: make add to calendar text white
* UPDATE: show user who removed roster record
* UPDATE: remove stats

= 5.4.4 =
* BUGFIX: fixed match entry to only allow logged in user to access
* UPDATE: show created date in player admin screen
* UPDATE: show splash page when loading/updating match results
* UPDATE: show user created date

= 5.4.3 =
* UPDATE: get daily match day short url working

= 5.4.2 =
* NEW:    add club management
* BUGFIX: pass email address on account form when already entered
* UPDATE: allow matches to be completed in rounds when previous round is not complete

= 5.4.1 =
* NEW:    allow match secretaries to request players to be added to club
* UPDATE: store creation date and created user against roster record

= 5.4.0 =
* UPDATE: make user match update work for cups
* UPDATE: allow rosters to exclude system records (NO PLAYER, WALKOVER, SHARE)
* UPDATE: allow club records to be edited outside of league
* UPDATE: reduce font size on playerstats view
* BUGFIX: fix height of rubber view popup

= 4.1.1 =
* BUGFIX: fixed problem that matches per page was not set correctly with league mysql cache
* BUGFIX: fixed issue with negative offsets for matches

= 4.1 =
* BUGFIX: fixed double logos in widget
* BUGFIX: fixed issue with negtive offset error when multiple leagues are on same page
* BUGFIX: fixed some issues with cache system
* BUGFIX: fixed issue with fatal error on plugin activation
* BUGFIX: fixed error in Fancy Slideshows filter

= 4.0.9 =
* NEW: show standingstable for home/away matches and after each match day
* NEW: functions get_next_matches() and get_last_matches() (functions.php) to get next or last matches for specific team
* NEW: added hebrew translation by Bar Shai
* BUGFIX: fixed match_day option in [matches] shortcode when using multiple shortcodes on same page
* BUGFIX: fixed calculation of apparatus points for gymnastics
* BUGFIX: improved page loading times by caching MySQL query results and prevent unneccessary queries
* BUGFIX: fix in matches pagination
* BUGFIX: several small fixes

= 4.0.8 =
* NEW: options to set various logo sizes and cropping options
* NEW: button to regenerate all thumbnails
* NEW: button to scan for and delete unused logo files
* NEW: set relegation teams for up and down
* NEW: use last5 standings template in championship mode
* UPDATE: some style updates
* BUGFIX: fixed standings table colors in widget
* BUGFIX: fixed soccer team ranking
* BUGFIX: fixed pool team ranking
* BUGFIX: fixed an AJAX issue

= 4.0.7 =
* NEW: show team logos in final matches and final results tree in championship mode including marking home team in bold
* BUGFIX: some additional fixes for championship mode
* BUGIFX: fixed logo size in widget
* BUGFIX: some small fixed to prevent notices in championship mode

= 4.0.6 =
* NEW: new shortcode [league id=ID] to display all contents of a league, i.e. standings, crosstable, matchlist and teamlist with fancy jQuery UI Tabs
* BUGFIX: several fixes for championship mode
* UPDATE: updated some german translations for championship mode

= 4.0.5 =
* NEW: added logos to crosstable
* SECURITY: fixed SQL injection and XSS vulnerabilities reported by islamoc (https://wordpress.org/support/topic/responding-to-security-problems-and-credit). I am pretty sure the SQL injection vulnerability had been already fixed before
* BUGFIX: some style fixes

= 4.0.4 =
* BUGFIX: only automatically calculated final results scores if none are provided by the user
* BUGFIX: fixed problem with Yoast SEO due to loading scriptaculous drag&drop
* BUGFIX: fixed some issues with matches shortcode
* BUGFIF: some style fixes

= 4.0.3 =
* BUGFIX: fixed some issues in matches shortcode

= 4.0.2 =
* NEW: don't automatically calculate results for basketball if a final score is submitted
* BUGFIX: added again logo copying upgrade routine to copy logos to new upload folder structure
* BUGFIX: fixed [leaguearchive league_id=X] bug
* UPDATE: updated French translation

= 4.0.1 =
* BUGFIX: fixed problem with logo upload

= 4.0 =
* NEW: fancy slideshows of matches using the [Fancy Slideshows Plugin](https://wordpress.org/plugins/sponsors-slideshow-widget/)
* NEW: point rule for volleyball giving 3 points for wins and 2 points for 3:2 wins and 1 point for 3:2 loss
* NEW: improved security for data export
* NEW: some new fancy styles using jQuery UI tabs
* NEW: jQuery UI sortable standings table if team ranking is manual
* NEW: accordion styled list of teams
* NEW: updated teams and matches single view with fancy new style
* NEW: documentation on data import file structures
* NEW: gymnastics sport - support for score points of each apparatus and automatic apparatus points and score points calculation upon updating competition results
* BUGFIX: fixed some issues in data import and export
* BUGFIX: fixed some smaller issues
* BUGFIX: fixed a small issue in plugin activation with notifications giving unexpected output
* BUGFIX: fixed activation issue for missing roles
* BUGFIX: fixed issue with team export and import
* BUGFIX: fixed logo URLs upon export/import

= 3.9.8 =
* BUGFIX: fixed an issue with deleting logos used also by other teams

= 3.9.7 =
* BUGFIX: fixed an important issue with editing seasons

= 3.9.6 =
* some fixes

= 3.9.5 =
* NEW: load custom sport files from stylesheet directory in subdirectory sports
* BUGFIX: fixed problem saving match report

= 3.9.4 =
* BUGFIX: fixed an issue with saving match results
* BUGFIX: some small fixes

= 3.9.3 =
* NEW: show multiple leagues on the same page
* NEW: global options to set support news widget options in dashboard
* BUGFIX: limit in matches shortcode
* BUGFIX: get next and previous matches in widget on a scale of minutes instead of 1 day

= 3.9.2 =
* BUGFIX: fixed some poor file location calling
* BUGFIX: fixed TinyMCE window width

= 3.9.1.9 =
* BUGFIX: fixed issue with wrong next match in last-5 standings table

= 3.9.1.8 =
* NEW: matches pagination
* NEW: team filter for matches in admin panel
* BUGFIX: fixed home_only argument in matches shortcode
* BUGFIX: fixed some styling issues

= 3.9.1.7 =
* UPDATED: updated french translation
* BUGFIX: fixed setting getting stuck on user-defined point rule
* BUGFIX: fixed an SQL query error in get_matches()

= 3.9.1.6 =
* BUGFIX: fixed team selection in matches template
* BUGFIX: fixed getting league by name

= 3.9.1.5 =
* SECURITY: major change in retrieving teams (get_teams() in core.php) and matches (get_matches() in core.php) to avoid sql injections
* SECURITY: fixed multiple possible sql injection vulnerabilities
* BUGFIX: add stripslashes
* BUGFIX: correctly load stylesheet and javascript scripts
* BUGFIX: limit the number of matches to add to 50 (to avoid problems with memory limit)
* BUGFIX: fixed some possible security issues
* BUGFIX: fixed several small issues
* BUGFIX: fixed issues with match statistics
* BUGFIX: fixed some small issues with undefined variable notices in different sports

= 3.9.1.4 =
* BUGFIX: ordering of teams by rank

= 3.9.1.3 =
* SECURITY: fixed security issues

= 3.9.1.2 =

= 3.9.1.1 =
* NEW: load custom templates from child themes
* BUGFIX: some fixes in championship mode

= 3.9.1 =
* NEW: new template to show individual racer standings table
* CHANGE: changed fields for racing results (points and time)
* BUGFIX: fixed ajax in widget to navigate through next and last matches
* BUGFIX: fixed bridge to projectmanager for compatibility with latest version
* BUGFIX: several small fixes for racing mode
* BUGFIX: added missing template matches-by_matchday.php to svn repository

= 3.9.0.9 =
* BUGFIX: fixed last-5 standings table to reflect scores of matches with overtime or penalty
* BUGFIX: some small fixes

= 3.9.0.8 =
* BUGFIX: fixed issue with zero scores not displaying in tennis sports
* BUGFIX: several small fixes

= 3.9.0.7 =
* NEW: new match_day values in [matches] shortcode: "next" to show matches of upcoming match day, "last" for last match day, "current" or "latest" for match day closest to current date
* NEW: new template to display matches separated by match day. Use "template=by_matchday" in the shortcode to load template matches-by_matchday.php
* NEW: show logo in matches tables
* NEW: show home team in standings table and home team matches in bold in admin interface
* NEW: added paramters $match->homeScore and $match->awayScore holding the match score depending if game has been finished after regular time, overtime or penalty. This can be used in the templates loaded by the [matches] or [match] shortcodes
* NEW: don't show match day selection if specific match day is selected. Using "next", "last", "current" or "latest" will still show match day selection dropdown
* NEW: new shortcode options for [matches] shortcode: "show_match_day_selection" and "show_team_selection" to force showing or hiding match day or team selection dropdown menus, respectively
* BUGFIX: fixed problem with zeros in matches with empty scores
* BUGFIX: fixed datepicker in match adding/editing page

= 3.9.0.6 =
* BUGFIX: Manual ranking of teams
* BUGFIX: fixed several small bugs
* BUGFIX: AJAX in widget

= 3.9.0.5 =
* BUGFIX: small fixes

= 3.9.0.4 =
* BUGFIX: fix colorpicker in global settings page
* BUGFIX: fix default match day to -1 (all matches) in matches shortcode

= 3.9.0.3 =
* BUGFIX: fix match day issue in shortcode

= 3.9.0.2 =
* BUGFIX: show all matches in admin panel due to problems
* BUGFIX: team edit save button not showing

= 3.9.0.1 =
* UPDATE: show first matches of first match day by default

= 3.9 =
* BUGFIX: fixed TinyMCE for Wordpress >= 3.9 preserving backwards compatibility
* BUGFIX: removed broken sortable standings table
* UPDATE: saving standings manually using POST button
* BUGFIX: setting point-rule
* BUGFIX: fixed several XSS Vulnerabilities
* BUGFIX: fixed match day match editing

= 3.8.9 =
* UPDATE: Numerous files have been worked on to remove PHP Strict Mode warnings. These warnings didn't affect RacketManager use, but if your WordPress installation had debugging mode turned on there were many, many warnings being thrown at you. There are no doubt more that will need to be fixed, but a conservative guess is that over 100 fixes have been applied.
* BUGFIX: Fixed the error with the Widget not changing
* BUGFIX: Permissions error on documenation page
* UPDATE: Added completed games to soccer ranking
* UPDATE: Numerous areas with deprecated code
* UPDATE: Started to get into the sport files to get a consistent look to the output, centering headings over input fields, centering input fields in the space allocated and centering the text in the input fields.
* UPDATE: Started work on the Championship mode, fixed a few none working areas, much work left to do, let me know if you've got suggestions...
* ADDED:  Ability to allow for matches between groups (out of group/division games)
* BUGFIX: Fixed issue with sport files throwing error regarding a not-found function (I hope! I can't duplicate it, let me know if there are still issues)
* UPDATE: Removed all traces of dropdowns for date, replaced with Datepicker
* UPDATE: Fixed a number of areas to keep the user in the same group when adding or updating teams or matches. If you are working in one group and add a team the group knows where you came from and when you click submit you go back to the group you started from (your welcome!)

= 3.8.8.5 =
* BUGFIX: Fix standings numbers
* BUGFIX: Fix widget issues after adding groups
* ADDED:  US Football Sport file

= 3.8.8.4 =
* BUGFIX: Wrong numbers on standing positions

= 3.8.8.3 =
* BUGFIX: Permission error
* UPDATE: Changed some internal code from 'leagues' to 'racketmanager'
* ADDED: Dashboard Widget

= 3.8.8.2 =
* ADDED: Code for Widget to show/hide logos and limit to group
* BUGFIX: "Clas=" in a number of sport files, changed to "Class="
* ADDED: JQuery tooltip for 'Last 5' to show date, score and teams of a game in the standings
* ADDED: Ability to change color of Widget title in 'style.css'
* ADDED: Limited code to set up out of group matches
* Code clean up, removed extra whitespace in a number of files, replaced deprecated _c tag with _x or _e.

= 3.8.8.1 =
* TEST: Test version to add 'Last 5' function to standings. Only update to this version if you're willing to test.
use this shortcode to test:
[standings league_id=1 template=last5] or
[standings league_id=1 group=A template=last5 logo=true]
(group and logo are optional)

If you test and find that the icons at the end of each line in the standings are moving to a second line it means you don't have enough room on your template for five past results. You can then change to a lesser number in the template, named 'standings-last5.php' in the 'admin/templates' folder. Go to 43:

    <th width="100" class="last5"><?php _e( 'Last 5', 'racketmanager' ) ?></th>

change the 'Last 5' text to 'Last 3' if you're going to use three past results, or whatever you choose. Then go to line 93:

    $results = get_latest_results($team->id, 5);

Change the '5' at the end to '3' if you want three past results.

The final version will probably have this as a preference option.

= 3.8.8 =
* BUGFIX: add matches in championship mode not working.

= 3.8.7 =
* BUGFIX: various
* ADDED: Shortcode additions for: option of using website link on standings, standings and crosstables by group.
* ADDED: when adding a team from db, bring the stadium info into the form with the rest of the information.

= 3.8.6 =
* BUGFIX: standings

= 3.8.5 =
*** IF YOU'VE DONE ANY MANUAL MODIFICATIONS, DOWNLOAD THIS AND CHECK THAT YOU AREN'T GOING TO LOSE THEM WHEN YOU UPDATE (INSTEAD OF DOING AN AUTO UPDATE). THIS UPDATE TOUCHES A NUMBER OF FILES (17). IF YOU HAVE QUESTIONS BEFORE UPDATING, LEAVE A MESSAGE ON THE SUPPORT FORUM ON WORDPRESS.ORG. A LIST OF ALL FILES UPDATED IS LISTED IN A POST THERE. ***

http://wordpress.org/support/topic/racketmanager-385-changes-info

* CHANGED: 'championchip' to 'championship' throughout the plugin
* BUGFIX: fixed missing '>' in core.php that was causing white screen after adding or editing matches.
* BUGFIX: fixed date format in widget.php so date shows.

= 3.8.4 =
* BUGFIX: export function

= 3.8.3 =
* BUGFIX: export function

= 3.8.2 =
* BUGFIX: Undefined function in racketmanager.php upon export

= 3.8.1 =
* BUGFIX: Fixed security vulnerability of SQL Injection. Added security check current_user_can('manage_leagues') and cast $_POST['league_id'] as (int)

= 3.8 =
* BUGFIX: Fixed reported XSS Vulnerabilities

= 3.7 =
* BUGFIX: decimals for add points field

= 3.6.9 =
* BUGFIX: upgrade process

= 3.6.8 =
* BUGFIX: Language
* BUGFIX: Team names with ' or similar

= 3.6.7 =
* BUGFIX: upgrade

= 3.6.6 =
* BUGFIX: changed DATEDIFF to TIMEDIFF in lib/widget.php
* BUGFIX: season update. also update teams and matches

= 3.6.5 =
* NEW: allow half points in match scores
* CHANGED: score after penalty is calculated by the plugin as "penalty score + overtime score"

= 3.6.4 =
* NEW: user defined point rule with win/loose overtime points. only works with certain sport types
* BUGFIX: team ranking for pool first by points
* BUGFIX: javascript problems

= 3.6.3 =
* CHANGED: change database field for team points to float to support half points
* BUGFIX: user defined point rule

= 3.6.2 =
* NEW: Score Point-Rule. Teams get one point according to the game score
* BUGFIX: only load javascript files on racketmanager pages to avoid malfunction of WP image editor
* BUGFIX: Widget option

= 3.6.1 =
* NEW: don't remove logo if other teams are using the same one
* CHANGED: sort teams in alphabetical order in match list on frontend
* BUGFIX: problem of displaying matches on same date
* BUGFIX: drag & drop sorting of teams

= 3.6 =
* NEW: documentation
* NEW: add stadium for teams and automatically add location for matches when choosing team
* NEW: Arabian translation
* CHANGED: add 15 matches at once independent of team number
* BUGFIX: Link to match report in widget
* BUGFIX: Championship advancement to finals
* UPDATED: French translation

= 3.5.6 =
* NEW: limit number of matches in shortcode [matches] with limit=X

= 3.5.5 =
* CHANGED: use first group if none is selected to add matches in championship preliminary rounds

= 3.5.4 =
* BUGFIX: stripslashes for team name to allow ' and "

= 3.5.3 =
* UPDATED: swedish translation

= 3.5.2 =
* BUGFIX: last match on single team page was not correct

= 3.5.1 =
* NEW: css class "relegation" for teams that need to go into relegation
* NEW: settings for number of teams that ascend, descend or need to go into relegation
* NEW: set background colors for teams that ascend, descend or need to go into relegation
* BUGFIX: row colors for ascending/descending teams

= 3.5 =
* NEW: cut down standings to home teams with surrounding teams. Attribute home=X where X is an integer controlling the number of surrounding teams up and down
* BUGFIX: teams tied only when they have same points, point difference and goals
* BUGFIX: championship mode
* NEW: css class "ascend" and "descend" for first and last two teams. class "homeTeam" for home team row. Table rows (tr)
* CHANGED: ranking of teams in soccer by points, goal difference and shot goals

= 3.4.2 =
* BUGFIX: crosstable popup
* BUGFIX: improved time attribute for matches shortcode
* BUGFIX: crosstable with home and away match

= 3.4.1 =
* BUGFIX: team website in next match box of widget
* BUGFIX: get matches of current match day in matches shortcode

= 3.4 =
* NEW: shortcode attribute 'match_day' for matches
* NEW: shortcode attribute 'group' for matches
* NEW: shortcode attribute 'time' ("prev" or "next") for matches to display upcoming or past matches
* NEW: shortcode attribute 'group' for standings
* BUGFIX: widget AJAX match navigation
* BUGFIX: scores with 0 possible in Rugby

= 3.4-RC3 =
* NEW: template tags for next and previous match boxes of widget
* UPDATED: template tag for single team to display individual team member information
* BUGFIX: match scrambling
* BUGFIX: ranking in soccer
* BUGFIX: plus/minus points affects ranking (reload of page necessary)
* BUGFIX: widget prev match does not show latest match

= 3.4-RC2 =
* NEW: improved administration of championship
* NEW: template tag for championship
* NEW: updated championship template and archive template
* NEW: display team roster if present on team info page (requires ProjectManager 2.8+)
* CHANGED: Widget design upgrade
* CHANGED: single match template layout
* CHANGED: updated template tags


* NEW: group teams and individual ranking in groups
* NEW: full championship mode
* NEW: mach with unspecific date N/A
* NEW: Widget with 2.8 API

= 3.3.1 =
* BUGFIX: empty query when adding League
* BUGFIX: 0-0 score if game not played changed to -:-

= 3.3 =
* NEW: double matches for tennis with individual standings

= 3.2.2 =
* BUGIFX: parse error

= 3.2.1 =
* BUGFIX: no default value for longtext fields

= 3.2 =
* NEW: options to display played, won, tie and lost games in standings table
* BUGIFX: Tennis scoring

= 3.2-RC1 =
* NEW: Tennis, Rugby and Volleyball Rules and Scoring
* NEW: set logo upload directory
* NEW: set default start time for matches
* BUGFIX: chmod of logos

= 3.1.9 =
* BUGFIX: spacer between teams in widget

= 3.1.8 =
* BUGFIX: widget match JQuery Navigation

= 3.1.7 =
* I hate bugfixing

= 3.1.6 =
* BUGFIX: team logos

= 3.1.5 =
* BUGFIX: fixed permission for upload directory

= 3.1.4 =
* BUGFIX: match stats and results saving data loss (IMPORTANT)

= 3.1.3 =
* BUGFIX: add teams from previous season with season as string
* BUGIFIX: export matches
* BUGFIX: create new thumbnails upon upgrade

= 3.1.2 =
* BUGFIX: load Thickbox stylesheet
* BUGFIX: edit of match day
* BUGFIX: created new thumbnail

= 3.1.1 =
* NEW: add Logo from url (for WPMU)
* BUGFIX: call-time pass-by-reference deprecated
* BUGFIX: match import

= 3.1 =
* NEW: supercool dynamic match statistics
* NEW: edit season
* BUGFIX: match days in frontend

= 3.0.4 =
* CHANGED: moved AJAX functions to own class
* BUGFIX: shortcode display with season as string, e.g. 08/09
* BUGFIX: Team Roster

= 3.0.3 =
* BUGFIX: archive template
* BUGFIX: racketmanager_matches function
* BUGFIX: team display in matches template

= 3.0.2 =
* CHANGED: static function for display

= 3.0.1 =
* BUGIFX: database table creation

= 3.0 =
* NEW: Team Roster for each team if ProjectManager is installed
* NEW: Basic support for racing
* NEW: standings actions in Frontend templates
* CHANGED: restructured settings in one database longtext field
* BUGFIX: crosstable score

= 2.9.3 =
* BUGFIX: match days in matches shortcode

= 2.9.2 =
* NEW: upgrade page to set seasons for teams and matches
* BUGFIX: Add old teams upon adding of new season
* BUGFIX: match edit
* BUGFIX: matches display shortcode

= 2.9.1 =
* NEW: added games behind for baseball
* NEW: TinyMCE Button for Teamlist and Team page
* NEW: AJAX adding team from database
* BUGFIX: display of goals, ap etc.
* BUGFIX: added hidden fields to team edit page where necessary to avoid loss of data
* BUGFIX: unsetting of widget options if deleted
* BUGFIX: TinyMCE Button

= 2.9 =
* NEW: modular setup of plugin
* NEW: actions and filters for specific sport types
* NEW: shortcodes to display list of teams and team info
* NEW: three drop-down menus for leagues, seasons and matches on post page
* NEW: track status of team ranking compared to last standing
* NEW: several new sports
* NEW: Match Statistics with Team Roster from ProjectManager
* CHANGED: changed shortcodes, deleted convert function

= 2.9-RC2 =
* BUGFIX: adding matches with seasons like 2008/2009

= 2.9-RC1 =
* NEW: seasons support
* NEW: League Archive and single match view

= 2.8 =
* NEW: add Team data from database
* NEW: Option to insert standings manually on admin page
* NEW: import and export of teams/matches (experimental)
* NEW: option to manually save standings in admin panel
* NEW: manually rank teams via drag & drop if needed
* NEW: field to add/subtract points (useful, e.g. for Rugby)
* NEW: option to show/hide logos in match widget
* BUGFIX: display of next match in widget
* BUGFIX: no update of diff if saving standings manually
* CHANGED: Update logo name in database if image already exists on server
* CHANGED: included updated dutch translation
* CHANGED: added some descriptions to translation

= 2.7.1 =
* BUGFIX: plugin installation missed `coach` field for teams

= 2.7 =
* NEW: predefined point rules
* NEW: support for Hockey and Basketball leagues to insert points of thirds and quarters respectively
* NEW: set point format
* NEW: short documentation on league types and point rules
* NEW: add website and coach for each team
* NEW: remove logo directory upon plugin uninstallation
* NEW: global option to set language file
* NEW: add separate results for overtime and penalty
* NEW: template system
* BUGFIX: Logo upload and thumbnail creation
* BUGFIX: upgrade
* CHANGED: New Widget with jQuery Sliding of matches
* CHANGED: simplified frontend templates

= 2.6.3 =
* BUGFIX: database upgrade

= 2.6.2 =
* BUGFIX: database upgrade

= 2.6.1 =
* BUGFIX: TinyMCE Button
* BUGFIG: PHP4 compatibility
* CHANGED: don't show match day drop-down if number of match days is 0
* CHANGED: warning message if number of match days is 0

= 2.6 =
* NEW: nicer upgrade method
* NEW: enter halftime results for ballgame leagues
* NEW: meta box on post writing screen to write match reports
* NEW: insert standings manually with simple constant switch
* NEW: templates for each shortcode to make customization easy
* CHANGED: major restructuring of plugin structure
* CHANGED: using shortcodes from Wordpress API
* CHANGED: new icon for menu and TinyMCE Button

= 2.5.2 =
* BUGFIX: match display in widget

= 2.5.1 =
* NEW: separate Date and Time Format for widget
* NEW: display of match start time in widget

= 2.5 =
* NEW: weekly based match ordering
* NEW: bulk editing of weekly matches
* NEW: date based grouping of matches in widget
* BUGFIX: crosstable popup
* CHANGED: css styling
* CHANGED: moved logo directory to wp-content/uploads
* REMOVED: match display of specific dates

= 2.4.1 =
* BUGFIX: database bug

= 2.4 =
* NEW: logo support
* NEW: change color scheme for frontend tables via admin interface
* NEW: display of matches for specific dates
* NEW: dividers in standings table

= 2.3.1 =
* BUGFIX: database collation

= 2.3 =
* NEW: optional display of crosstable in popup window

= 2.2 =
* NEW: implemented crosstable for easy overview of all match results
* NEW: TinyMCE Button
* BUGFIX: secondary ranking of teams by goal difference if not gymnastics league
* CHANGED: css styling

= 2.1 =
* NEW: adding of up to 15 matches simultaneously for one date
* NEW: using date and time formats from Wordpress settings
* BUGFIX: results determination if score was 0:0

= 2.0 =
* NEW: automatic point calculation
* REMOVED: dynamic table columns

= 1.5 =
* NEW: design standings table display in widget

= 1.4.2 =
* BUGFIX: check_admin_referer for WP 2.3.x

= 1.4.1 =
* BUGFIX: saving of standings table

= 1.4 =
* NEW: wp_nonce_field for higher security
* NEW: separate capability to control access
* BUGFIX: some minor bugfixes

= 1.3 =
* NEW: activation/deactivation switch
* NEW: widget for every active league
* NEW: use of short title for widget

= 1.2.2 =
* BUGFIX: Javascript for adding table columns

= 1.2.1 =
* BUGFIX: database creation

= 1.2 =
* BUGFIX: teams sorting in widget
* CHANGED: load javascript only on Racketmanager admin pages
* CHANGED: remodeling of the plugin structure

= 1.1 =
* NEW: deletion of multiple leagues, teams or competitions
* NEW: display widget statically
* NEW: uninstallation method
* BUGFIX: table structure settings and deleting leagues, teams or competitions

= 1.0 =
* initial release
