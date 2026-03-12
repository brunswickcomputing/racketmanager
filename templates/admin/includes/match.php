<?php
/**
 * Match administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;

// Preferred input: a single $vm (view model).
// BC fallback: if $vm isn't provided, this template continues using legacy locals.
$vm = $vm ?? null;

if ( $vm instanceof Tournament_Fixtures_Page_View_Model ) {
    $league          = $vm->league;
    $tournament      = $vm->tournament;
    $competition     = $vm->competition;
    $season          = $vm->season;
    $form_title      = $vm->form_title;
    $submit_title    = $vm->submit_title;
    $fixtures        = $vm->fixtures;
    $edit            = $vm->edit;
    $bulk            = $vm->bulk;
    $is_finals       = $vm->is_finals;
    $mode            = $vm->mode;
    $teams           = $vm->teams;
    $single_cup_game = $vm->single_cup_game;
    $max_fixtures    = $vm->max_fixtures;
    $final_key       = $vm->final_key;

    // Optional fields
    $home_title = $vm->home_title;
    $away_title = $vm->away_title;
    $match_day  = $vm->match_day;
}

/** @var object $league */
/** @var object $tournament */
/** @var object $competition */
/** @var string $season */
/** @var string $form_title */
/** @var string $submit_title */
/** @var array  $fixtures */
/** @var bool   $edit */
/** @var bool   $bulk */
/** @var bool   $is_finals */
/** @var string $mode */
/** @var string $home_title */
/** @var string $away_title */
/** @var array  $teams */
/** @var bool   $single_cup_game */
/** @var int    $max_fixtures */
/** @var string $final_key */
$form_action = '/wp-admin/admin.php?page=racketmanager-' . $vm->league->event->competition->type . 's&amp;';
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <?php
            if ( $vm->league->event->competition->is_league ) {
                $form_action .= 'view=league&amp;league_id';
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $vm->league->event->competition->type ); ?>s"><?php echo esc_html( ucfirst( $vm->league->event->competition->type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $vm->league->event->competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_html( $vm->league->event->competition->id ); ?>"><?php echo esc_html( $vm->league->event->competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $vm->league->event->competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $vm->league->event->competition->id ); ?>&amp;season=<?php echo esc_attr( $vm->season ); ?>"><?php echo esc_html( $vm->season ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $vm->league->event->competition->type ); ?>s&amp;view=event&amp;event_id=<?php echo esc_html( $vm->league->event->id ); ?>&amp;season=<?php echo esc_attr( $vm->league->current_season['name'] ); ?>"><?php echo esc_html( $vm->league->event->name ); ?></a> &raquo;
                <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $vm->league->event->competition->type ); ?>s&amp;view=league&amp;league_id=<?php echo esc_html( $vm->league->id ); ?>&amp;season=<?php echo esc_html( $vm->league->current_season['name'] ); ?>"><?php echo esc_html( $vm->league->title ); ?></a> &raquo;
                <?php
            } elseif ( $vm->league->event->competition->is_tournament ) {
                $form_action .= 'view=draw&amp;tournament=' . $vm->tournament->id . '&amp;league';
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $vm->tournament->id ); ?>&amp;season=<?php echo esc_attr( $vm->tournament->season ); ?>"><?php echo esc_html( $vm->tournament->name ); ?></a>  &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=draw&amp;tournament=<?php echo esc_attr( $vm->tournament->id ); ?>&amp;season=<?php echo esc_attr( $vm->tournament->season ); ?>&amp;league=<?php echo esc_attr( $vm->league->id ); ?>"><?php echo esc_html( $vm->league->title ); ?></a> &raquo;
                <?php
            } elseif ( $vm->league->event->competition->is_cup ) {
                $form_action .= 'view=draw&amp;competition_id=' . $vm->competition->id . '&amp;league';
                ?>
                <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $vm->competition->type ); ?>s"><?php echo esc_html( ucfirst( $vm->competition->type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $vm->competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $vm->competition->id ); ?>"><?php echo esc_html( $vm->competition->name ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $vm->competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $vm->competition->id ); ?>&amp;season=<?php echo esc_attr( $vm->season ); ?>"><?php echo esc_html( $vm->season ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $vm->competition->type ); ?>s&amp;view=draw&amp;competition_id=<?php echo esc_attr( $vm->competition->id ); ?>&amp;season=<?php echo esc_attr( $vm->season ); ?>&amp;league=<?php echo esc_attr( $vm->league->id ); ?>"><?php echo esc_html( $vm->league->title ); ?></a> &raquo;
                <?php
            }
            ?>
            <?php echo esc_html( $vm->form_title ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $vm->league->title ); ?></h1>
    <h2><?php echo esc_html( $vm->form_title ); ?></h2>
    <?php
    if ( $vm->fixtures ) {
        $form_action .= '=' . $vm->league->id . '&amp;season=' . $vm->season;
        if ( isset( $vm->match_day ) ) {
            $form_action .= '&amp;match_day=' . $vm->match_day;
        }
        if ( isset( $vm->final_key ) && $vm->final_key > '' ) {
            $form_action .= '&amp;final=' . $vm->final_key . '&amp;league-tab=fixtures';
        }
        ?>
        <form action="<?php echo esc_html( $form_action ); ?>" method='post'>
            <?php wp_nonce_field( 'racketmanager_manage-fixtures', 'racketmanager_nonce' ); ?>
            <?php
            if ( ! $vm->edit ) {
                ?>
                <p class="match_info"><?php esc_html_e( 'Note: Matches with different Home and Guest Teams will be added to the database.', 'racketmanager' ); ?></p>
                <?php
            }
            ?>

            <table class="table table-striped table-borderless" aria-label="<?php esc_html_e( 'match edit', 'racketmanager' ); ?>">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Id', 'racketmanager' ); ?></th>
                        <?php
                        if ( $vm->bulk || $vm->is_finals || ( 'add' === $vm->mode ) || ( 'edit' === $vm->mode ) ) {
                            ?>
                            <th scope="col"><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
                            <?php
                        }
                        ?>
                        <?php
                        if ( ! empty( $match->final_round ) || $vm->is_finals ) {
                            ?>
                            <?php
                        } else {
                            ?>
                            <th scope="col"><?php esc_html_e( 'Day', 'racketmanager' ); ?></th>
                            <?php
                        }
                        ?>
                        <th scope="col">
                            <?php
                            if ( $vm->league->is_championship ) {
                                esc_html_e( 'Team', 'racketmanager' );
                            } else {
                                esc_html_e( 'Home', 'racketmanager' );
                            }
                            ?>
                        </th>
                        <th scope="col">
                            <?php
                            if ( $vm->league->is_championship ) {
                                esc_html_e( 'Team', 'racketmanager' );
                            } else {
                                esc_html_e( 'Away', 'racketmanager' );
                            }
                            ?>
                        </th>
                        <th scope="col"><?php esc_html_e( 'Location', 'racketmanager' ); ?></th>
                        <?php
                        if ( $vm->league->event->competition->is_team_entry ) {
                            ?>
                            <th scope="col"><?php esc_html_e( 'Begin', 'racketmanager' ); ?></th>
                            <?php
                        }
                        ?>
                        <?php do_action( 'racketmanager_edit_matches_header_' . $vm->league->sport ); ?>
                        <?php
                        if ( $vm->single_cup_game ) {
                            ?>
                            <th scope="col"></th>
                            <?php
                        }
                        ?>
                    </tr>
                </thead>
                <tbody id="the-list" class="lm-form-table">
                    <?php
                    for ( $i = 0; $i < $vm->max_fixtures; $i++ ) {
                        ?>
                        <tr class="">
                            <td>
                                <?php
                                if ( isset( $vm->fixtures[ $i ]->id ) ) {
                                    echo esc_html( $vm->fixtures[ $i ]->id );
                                }
                                ?>
                            </td>
                            <?php
                            if ( $vm->bulk || $vm->is_finals || ( 'add' === $vm->mode ) || 'edit' === $vm->mode ) {
                                if ( isset( $vm->fixtures[ $i ]->date ) ) {
                                    $date = ( substr( $vm->fixtures[ $i ]->date, 0, 10 ) );
                                } else {
                                    $date = '';
                                    if ( ! empty( $final['round'] ) ) {
                                        if ( $vm->league->championship->is_consolation ) {
                                            $round_no = $final['round'];
                                        } else {
                                            $round_no = $final['round'] - 1;
                                        }
                                        $vm->season_dtls = $vm->league->event->get_season_by_name( $vm->season );
                                        if ( ! empty( $vm->season_dtls['match_dates'][ $round_no ] ) ) {
                                            $date = $vm->season_dtls['match_dates'][ $round_no ];
                                        }
                                    }
                                }
                                ?>
                                <td><label for="myDatePicker[<?php echo esc_html( $i ); ?>]"></label><input type="date" name="myDatePicker[<?php echo esc_html( $i ); ?>]" id="myDatePicker[<?php echo esc_html( $i ); ?>]" class="" value="<?php echo esc_html( $date ); ?>" onChange="Racketmanager.setMatchDate(this.value, <?php echo esc_html( $i ); ?>, <?php echo esc_html( $vm->max_fixtures ); ?>, '<?php echo esc_html( $vm->mode ); ?>');" /></td>
                                <?php
                            }
                            ?>
                            <?php
                            if ( ! empty( $vm->fixtures[ $i ]->final_round ) || $vm->is_finals ) {
                                ?>
                                <?php
                            } else {
                                if ( empty( $vm->match_day ) ) {
                                    if ( ! empty( $vm->fixtures[ $i ]->match_day ) ) {
                                        $match_day = $vm->fixtures[ $i ]->match_day;
                                    } else {
                                        $match_day = null;
                                    }
                                }
                                ?>
                                <td>
                                    <label for="match_day_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Match day', 'racketmanager' ); ?></label><select size="1" name="match_day[<?php echo esc_html( $i ); ?>]" id="match_day_<?php echo esc_html( $i ); ?>" onChange="Racketmanager.setMatchDayPopUp(this.value, <?php echo esc_html( $i ); ?>, <?php echo esc_html( $vm->max_fixtures ); ?>, '<?php echo esc_html( $vm->mode ); ?>');">
                                        <?php
                                        for ( $d = 1; $d <= $vm->league->current_season['num_match_days']; $d++ ) {
                                            ?>
                                            <option value="<?php echo esc_html( $d ); ?>"
                                                <?php
                                                if ( intval( $match_day ) === $d ) {
                                                    echo ' selected';
                                                }
                                                ?>
                                            ><?php echo esc_html( $d ); ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </td>
                                <?php
                            }
                            ?>
                            <!-- Home team pop up -->
                            <td>
                                <?php
                                if ( $vm->single_cup_game ) {
                                    ?>
                                    <label for="home_team_title_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Home team', 'racketmanager' ); ?></label><input type="text" disabled name="home_team_title[<?php echo esc_html( $i ); ?>]" id="home_team_title_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $vm->home_title ); ?>" />
                                    <input type="hidden" name="home_team[<?php echo esc_html( $i ); ?>]" id="home_team_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $vm->fixtures[ $i ]->home_team ); ?>" />
                                    <?php
                                } else {
                                    ?>
                                    <label for="home_team_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Home team', 'racketmanager' ); ?></label><select size="1" name="home_team[<?php echo esc_html( $i ); ?>]" id="home_team_<?php echo esc_html( $i ); ?>" onChange="Racketmanager.insertHomeStadium(document.getElementById('home_team_<?php echo esc_html( $i ); ?>').value, <?php echo esc_html( $i ); ?>)">
                                        <?php
                                        foreach ( $vm->teams as $team ) {
                                            ?>
                                            <option value="<?php echo esc_html( $team->id ); ?>" <?php echo isset( $vm->fixtures[ $i ]->home_team ) ? selected( $team->id, $vm->fixtures[ $i ]->home_team ) : null; ?>><?php echo esc_html( $team->title ); ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <?php
                                }
                                ?>
                                <?php
                                if ( $vm->league->is_championship ) {
                                    ?>
                                    <label for="team_host_home[<?php echo esc_html( $i ); ?>]" class="visually-hidden"><?php esc_html_e( 'Home host', 'racketmanager' ); ?></label><input type="radio" name="host[<?php echo esc_html( $i ); ?>]" id="team_host_home[<?php echo esc_html( $i ); ?>]" value="home"
                                        <?php
                                        if ( isset( $vm->fixtures[ $i ]->host ) && 'home' === $vm->fixtures[ $i ]->host ) {
                                            echo ' checked';
                                        }
                                        ?>
                                    />
                                    <?php
                                }
                                ?>
                            </td>
                            <!-- Away team pop up -->
                            <td>
                                <?php
                                if ( $vm->single_cup_game ) {
                                    ?>
                                    <label for="away_team_title_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Away team', 'racketmanager' ); ?></label><input type="text" disabled name="away_team_title[<?php echo esc_html( $i ); ?>]" id="away_team_title_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $vm->away_title ); ?>" />
                                    <input type="hidden" name="away_team[<?php echo esc_html( $i ); ?>]" id="away_team_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $vm->fixtures[ $i ]->away_team ); ?>" />
                                    <?php
                                } else {
                                    ?>
                                    <label for="away_team_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Away team', 'racketmanager' ); ?></label><select size="1" name="away_team[<?php echo esc_html( $i ); ?>]" id="away_team_<?php echo esc_html( $i ); ?>"<?php echo empty( $vm->final_key ) ? null : ' onChange="Racketmanager.insertHomeStadium(document.getElementById(\'home_team_' . esc_html( $i ) . '\').value, ' . esc_html( $i ) . ');"'; ?>>
                                        <?php
                                        foreach ( $vm->teams as $team ) {
                                            ?>
                                            <option value="<?php echo esc_html( $team->id ); ?>" <?php echo isset( $vm->fixtures[ $i ]->away_team ) ? selected( $team->id, $vm->fixtures[ $i ]->away_team ) : null; ?>><?php echo esc_html( $team->title ); ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <?php
                                }
                                ?>
                                <?php
                                if ( $vm->league->is_championship ) {
                                    ?>
                                    <label for="team_host_away[<?php echo esc_html( $i ); ?>]" class="visually-hidden"><?php esc_html_e( 'Away host', 'racketmanager' ); ?></label><input type="radio" name="host[<?php echo esc_html( $i ); ?>]" id="team_host_away[<?php echo esc_html( $i ); ?>]" value="away"
                                        <?php
                                        if ( isset( $vm->fixtures[ $i ]->host ) && 'away' === $vm->fixtures[ $i ]->host ) {
                                            echo ' checked';
                                        }
                                        ?>
                                    />
                                    <?php
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ( isset( $vm->fixtures[ $i ]->location ) ) {
                                    $location = ( $vm->fixtures[ $i ]->location );
                                } else {
                                    $location = '';
                                }
                                ?>
                                <label for="location_<?php echo esc_html( $i ); ?>" class="visually-hidden"><?php esc_html_e( 'Location', 'racketmanager' ); ?></label><input type="text" name="location[<?php echo esc_html( $i ); ?>]" id="location_<?php echo esc_html( $i ); ?>" value="<?php echo esc_html( $location ); ?>" />
                            </td>
                            <?php
                            if ( $vm->league->event->competition->is_team_entry ) {
                                ?>
                                <td>
                                    <label>
                                        <select size="1" name="begin_hour[<?php echo esc_html( $i ); ?>]">
                                            <?php
                                            for ( $hour = 0; $hour <= 23; $hour++ ) {
                                                ?>
                                                <option value="<?php echo esc_html( str_pad( $hour, 2, 0, STR_PAD_LEFT ) ); ?>"<?php selected( $hour, $vm->fixtures[ $i ]->hour ?? null ); ?>><?php echo esc_html( ( isset( $hour ) ) ? str_pad( $hour, 2, 0, STR_PAD_LEFT ) : 00 ); ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </label>
                                    <label>
                                        <select size="1" name="begin_minutes[<?php echo esc_html( $i ); ?>]">
                                            <?php
                                            for ( $minute = 0; $minute <= 60; $minute++ ) {
                                                ?>
                                                <?php
                                                if ( 0 === $minute % 5 && 60 !== $minute ) {
                                                    ?>
                                                    <option value="<?php echo esc_html( str_pad( $minute, 2, 0, STR_PAD_LEFT ) ); ?>"<?php selected( $minute, $vm->fixtures[ $i ]->minutes ?? null ); ?>><?php echo esc_html( ( isset( $minute ) ) ? str_pad( $minute, 2, 0, STR_PAD_LEFT ) : 00 ); ?></option>
                                                    <?php
                                                }
                                                ?>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </label>
                                </td>
                                <?php
                            }
                            ?>
                            <?php do_action( 'racketmanager_edit_matches_columns_' . $vm->league->sport, ( $vm->fixtures[$i] ?? ''), $vm->league, $vm->season, ( $vm->teams ?? ''), $i ); ?>
                            <?php
                            if ( $vm->single_cup_game ) {
                                ?>
                                <td>
                                    <input type="button" value="<?php esc_html_e( 'Notify teams', 'racketmanager' ); ?>" class="btn btn-secondary" onclick="Racketmanager.notifyTeams(<?php echo esc_html( $vm->fixtures[ $i ]->id ); ?>)" /><span class="notify-message" id="notifyMessage-<?php echo esc_html( $vm->fixtures[ $i ]->id ); ?>"></span>
                                </td>
                                <?php
                            }
                            ?>
                        </tr>
                        <input type="hidden" name="fixture[<?php echo esc_html( $i ); ?>]" value="<?php echo esc_html( $vm->fixtures[$i]->id ?? ''); ?>" />
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <input type="hidden" name="mode" value="<?php echo esc_html( $vm->mode ); ?>" />
            <input type="hidden" name="league_id" value="<?php echo esc_html( $vm->league->id ); ?>" />
            <input type="hidden" name="num_rubbers" value="<?php echo esc_html( $vm->league->num_rubbers ); ?>" />
            <input type="hidden" name="season" value="<?php echo esc_html( $vm->season ); ?>" />
            <input type="hidden" name="final" value="<?php echo esc_html( $vm->final_key ); ?>" />
            <input type="hidden" name="updateLeague" value="fixture" />

            <p class="submit"><input type="submit" value="<?php echo esc_html( $vm->submit_title ); ?>" class="btn btn-primary" /></p>
            <div id="feedback" class="feedback">
            </div>
        </form>
        <?php
    } else {
        ?>
        <?php esc_html_e( 'No fixtures found', 'racketmanager' ); ?>
        <?php
    }
    ?>
</div>
