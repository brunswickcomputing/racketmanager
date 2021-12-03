<?php
/**
Template page to display a tournament entry form

The following variables are usable:

    $tournament: tournament object
	$competitions: competitions object
    $player: player object
    $rosters: rosters array
    $season: season name
    $type: competition type
    $malePartners: male partners object
    $femalePartners: female partners object

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
?>
<h1 class="title-post"><?php echo ucfirst($type)." ".$season." " ?> Tournament Entry Form</h1>
<div class="entry-content">
<?php if ( is_user_logged_in() ) { ?>
    <form id="form-tournamententry" action="" method="post">
        <?php wp_nonce_field( 'tournament-entry' ) ?>
        <div class="input">
            <label for "venueName"><?php _e( 'Finals Venue', 'racketmanager' ) ?></label>
            <input type="text" class="form-control" id="venueName" name="venueName" value="<?php if ( $tournament->venueName != '' ) echo $tournament->venueName; else _e('TBC', 'racketmanager'); ?>" disabled />
        </div>
        <div class="input">
            <label for "date"><?php _e( 'Finals Date', 'racketmanager' ) ?></label>
            <input type="text" class="form-control" id="date" name="date" value="<?php echo $tournament->date ?>" disabled />
        </div>
        <div class="input">
            <label for "closingdate"><?php _e( 'Closing Date For Entries', 'racketmanager' ) ?></label>
            <input type="text" class="form-control" id="closingdate" name="closingdate" value="<?php echo $tournament->closingdate ?>" disabled />
        </div>
        <div class="form-group">
            <fieldset class="form-fieldset">
                <legend class="fieldset-legend">
                    <h3>Which events would you like to enter?</h3>
                </legend>
                <div id="event-hint" class="hint">
                    Select all events that you would like to enter.
                </div>
                <div class="form-checkboxes">
                    <?php foreach ($competitions AS $competition) { ?>
                        <?php if ( ( $player->gender == 'M' && substr($competition->type,0,1) != 'W' ) || ( $player->gender == 'F' && substr($competition->type,0,1) != 'M' ) ) { ?>
                    <div class="form-checkboxes__item">
                        <input class="form-checkboxes__input" id="competition[<?php echo $competition->id ?>]" name="competition[<?php echo $competition->id ?>]" type="checkbox" value=<?php echo $competition->id ?> aria-controls="conditional-competition-<?php echo $competition->id ?>">
                        <label class="form-label form-checkboxes__label" for="competition[<?php echo $competition->id ?>]">
                            <?php echo $competition->name ?>
                        </label>
                    </div>
                <?php if ( substr($competition->type,1,1) == 'D') {
                    if ( $player->gender == 'M' ) {
                        if ( substr($competition->type,0,1) == 'M' ) {
                            $partnerList = $malePartners;
                        } else {
                            $partnerList = $femalePartners;
                        }
                    } elseif ( $player->gender == 'F' ) {
                        if ( substr($competition->type,0,1) == 'W' ) {
                            $partnerList = $femalePartners;
                        } else {
                            $partnerList = $malePartners;
                        }
                    }
                    ?>

                    <div class="form-checkboxes__conditional form-checkboxes__conditional--hidden" id="conditional-competition-<?php echo $competition->id ?>">
                        <label class="form-label" for="partner[<?php echo $competition->id ?>]"><?php _e( 'Partner', 'racketmanager' ) ?></label>
                            <select size="1" name="partner[<?php echo $competition->id ?>]" id="partner[<?php echo $competition->id ?>]" >
                                <option value="0"><?php _e( 'Select partner' , 'racketmanager') ?></option>
                                <?php foreach ( $partnerList AS $roster ) { ?>
                                <option value="<?php echo $roster->roster_id ?>"><?php echo $roster->fullname." - ".get_club($roster->affiliatedclub)->name ?></option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>
                        <?php } ?>
                    <?php } ?>
                <div>
            </fieldset>
        </div>
        <div class="input">
            <label for "playername"><?php _e( 'Name', 'racketmanager' ) ?></label>
            <input type="text" class="teamcaptain form-control" id="playername" name="" value="<?php echo $player->display_name ?>" disabled />
            <input type="hidden" id="playerId" name="playerId" value="<?php echo $player->ID ?>" />
        </div>
        <div class="form-group">
            <label for "affiliatedclub"><?php _e( 'Affiliated Club', 'racketmanager' ) ?></label>
            <div class="input">
            <?php if (count($rosters) == 1) { ?>
                <input type="text" class="form-control" id="affiliatedclubname" name="affiliatedclubname" value="<?php echo get_club($rosters[0]->affiliatedclub)->name ?>" disabled />
                <input type="hidden" id="affiliatedclub" name="affiliatedclub" value="<?php echo $rosters[0]->affiliatedclub ?>" />
            <?php } else { ?>
                <select size="1" name="affiliatedclub" id="affiliatedclub" >
                    <option value="0"><?php _e( 'Select club' , 'racketmanager') ?></option>
                    <?php foreach ( $rosters AS $roster ) {
                        $club = get_club($roster->affiliatedclub); ?>
                    <option value="<?php echo $club->id ?>"><?php echo $club->name ?></option>
                    <?php } ?>
                </select>
            <?php } ?>
            </div>
        </div>
        <div class="form-group">
            <label for "contactno"><?php _e( 'Contact Number', 'racketmanager' ) ?></label>
            <div class="input">
                <input type="tel" class="form-control width-one-quarter" id="contactno" name="contactno" value="<?php echo $player->contactno ?>" />
            </div>
        </div>
        <div class="form-group">
            <label for "contactemail"><?php _e( 'Contact Email', 'racketmanager' ) ?></label>
            <div class="input">
                <input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo $player->user_email ?>" size="30" />
            </div>
        </div>
        <div>
            <h3>Notes</h3>
            <ol>
                <li>See regulations 46-56 re Tournaments</li>
                <li>Competitors may enter <strong>3</strong> events only</li>
                <li>Competitors conceding their first match are not eligible for the Plate event</li>
                <li>Competitors unavailable to play on finals day must retire at match point in the semi-finals</li>
            </ol>
        </div>
        <div class="form-checkboxes">
            <div class="form-checkboxes__item">
                <input class="form-checkboxes__input" id="acceptance" name="acceptance" type="checkbox">
                <label class="form-label form-checkboxes__label" for="acceptance">
                    <?php _e('I agree to abide by the rules of the tournament', 'racketmanager') ?>
                </label>
            </div>
        <div>
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <input type="hidden" name="tournamentSeason" value="<?php echo $type ?>" />
        <input type="hidden" name="tournamentSecretaryEmail" value="<?php echo $tournament->tournamentSecretaryEmail ?>" />
        <button class="btn" type="button" id="tournamentEntrySubmit" name="tournamentEntrySubmit" onclick="Racketmanager.tournamentEntryRequest(this)">Enter Tournament</button>
        <div class="updateResponse" id="tournamentEntryResponse" name="tournamentEntryResponse"></div>
    </form>
<?php } else { ?>
    <p class="contact-login-msg">You need to <a href="<?php echo wp_login_url(); ?>">login</a> to enter a tournament</p>
<?php } ?>
        </div><!-- .entry-content -->
