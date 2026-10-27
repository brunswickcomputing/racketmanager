<?php
declare( strict_types=1 );

namespace Racketmanager\Repositories;

use Closure;
use Racketmanager\Domain\Player\User;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Util\Util;
use stdClass;
use function add_user_meta;
use function count;
use function delete_user_meta;
use function error_log;
use function get_user_by;
use function get_user_meta;
use function get_userdata;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function is_wp_error;
use function trim;
use function update_user_meta;
use function wp_set_auth_cookie;
use function wp_set_password;
use function wp_update_user;

class User_Repository implements User_Repository_Interface {
    private ?Closure $league_repository_resolver;
    private ?Closure $club_repository_resolver;
    private ?Closure $event_repository_resolver;
    private ?Closure $player_repository_resolver;
    private ?Closure $team_repository_resolver;
    private ?League_Repository_Interface $league_repository = null;
    private ?Club_Repository_Interface $club_repository = null;
    private ?Event_Repository_Interface $event_repository = null;
    private ?Player_Repository_Interface $player_repository = null;
    private ?Team_Repository_Interface $team_repository = null;

    /**
     * Favourite lookups are resolved lazily so that the container can hand the
     * repositories over without instantiating them when a user is loaded.
     */
    public function __construct(
        ?Closure $league_repository_resolver = null,
        ?Closure $club_repository_resolver = null,
        ?Closure $event_repository_resolver = null,
        ?Closure $player_repository_resolver = null,
        ?Closure $team_repository_resolver = null
    ) {
        $this->league_repository_resolver = $league_repository_resolver;
        $this->club_repository_resolver   = $club_repository_resolver;
        $this->event_repository_resolver  = $event_repository_resolver;
        $this->player_repository_resolver = $player_repository_resolver;
        $this->team_repository_resolver   = $team_repository_resolver;
    }

    private function league_repository(): League_Repository_Interface {
        return $this->league_repository ??= $this->league_repository_resolver
            ? ( $this->league_repository_resolver )()
            : new League_Repository( null, fn() => $this->event_repository() );
    }

    private function club_repository(): Club_Repository_Interface {
        return $this->club_repository ??= $this->club_repository_resolver ? ( $this->club_repository_resolver )() : new Club_Repository();
    }

    private function event_repository(): Event_Repository_Interface {
        return $this->event_repository ??= $this->event_repository_resolver ? ( $this->event_repository_resolver )() : new Event_Repository();
    }

    private function player_repository(): Player_Repository_Interface {
        return $this->player_repository ??= $this->player_repository_resolver ? ( $this->player_repository_resolver )() : new Player_Repository();
    }

    private function team_repository(): Team_Repository_Interface {
        return $this->team_repository ??= $this->team_repository_resolver ? ( $this->team_repository_resolver )() : new Team_Repository();
    }
    public function find( int|string $user_id ): ?User {
        $id = 0;
        if ( is_numeric( $user_id ) ) {
            $id = (int) $user_id;
        } elseif ( is_string( $user_id ) && '' !== $user_id ) {
            $user_obj = get_user_by( 'login', $user_id );
            if ( $user_obj && isset( $user_obj->ID ) ) {
                $id = (int) $user_obj->ID;
            }
        }

        if ( $id <= 0 ) {
            return null;
        }

        $user_data = get_userdata( $id );
        if ( ! $user_data ) {
            return null;
        }

        return $this->hydrate_user( $user_data );
    }

    public function find_by_id( int|string|null $id ): ?User {
        if ( null === $id ) {
            return null;
        }
        return $this->find( $id );
    }

    public function save( object $entity ): bool|int {
        if ( ! $entity instanceof User || empty( $entity->id ) ) {
            return false;
        }

        $user_id = (int) $entity->id;
        $changed = false;

        $stored_email      = $this->stored_email( $user_id );
        $stored_firstname  = (string) get_user_meta( $user_id, 'first_name', true );
        $stored_surname    = (string) get_user_meta( $user_id, 'last_name', true );
        $stored_contactno  = (string) get_user_meta( $user_id, 'contactno', true );
        $stored_gender     = (string) get_user_meta( $user_id, 'gender', true );
        $stored_btm        = get_user_meta( $user_id, 'btm', true );
        $stored_yob        = get_user_meta( $user_id, 'year_of_birth', true );

        $new_email     = (string) ( $entity->email ?: $entity->user_email ?: '' );
        $new_firstname = isset( $entity->firstname ) ? (string) $entity->firstname : $stored_firstname;
        $new_surname   = isset( $entity->surname ) ? (string) $entity->surname : $stored_surname;

        $core_update = array( 'ID' => $user_id );

        if ( '' !== $new_email && $stored_email !== $new_email ) {
            $core_update['user_email'] = $new_email;
        }
        if ( $stored_firstname !== $new_firstname || $stored_surname !== $new_surname ) {
            $core_update['display_name'] = trim( $new_firstname . ' ' . $new_surname );
        }

        if ( count( $core_update ) > 1 ) {
            $updated = wp_update_user( $core_update );
            if ( is_wp_error( $updated ) ) {
                error_log( 'Unable to update user ' . $user_id . ' - ' . $updated->get_error_message() );
                unset( $core_update['user_email'] );
                if ( count( $core_update ) > 1 ) {
                    $updated = wp_update_user( $core_update );
                    if ( ! is_wp_error( $updated ) ) {
                        $changed = true;
                        $entity->display_name = $core_update['display_name'] ?? $entity->display_name ?? '';
                        $entity->fullname     = $entity->display_name;
                    }
                }
            } else {
                $changed = true;
                if ( isset( $core_update['user_email'] ) ) {
                    $entity->user_email = $new_email;
                    $entity->email      = $new_email;
                }
                if ( isset( $core_update['display_name'] ) ) {
                    $entity->display_name = $core_update['display_name'];
                    $entity->fullname     = $core_update['display_name'];
                }
            }
        }

        if ( isset( $entity->firstname ) && $stored_firstname !== (string) $entity->firstname ) {
            update_user_meta( $user_id, 'first_name', $entity->firstname );
            $changed = true;
        }
        if ( isset( $entity->surname ) && $stored_surname !== (string) $entity->surname ) {
            update_user_meta( $user_id, 'last_name', $entity->surname );
            $changed = true;
        }
        if ( isset( $entity->contactno ) && $stored_contactno !== (string) $entity->contactno ) {
            update_user_meta( $user_id, 'contactno', $entity->contactno );
            $changed = true;
        }
        if ( isset( $entity->gender ) && $stored_gender !== (string) $entity->gender ) {
            update_user_meta( $user_id, 'gender', $entity->gender );
            $changed = true;
        }
        if ( $this->btm_changed( $stored_btm, $entity->btm ?? null ) ) {
            update_user_meta( $user_id, 'btm', $entity->btm );
            $changed = true;
        }
        if ( isset( $entity->year_of_birth ) && (int) $stored_yob !== (int) $entity->year_of_birth ) {
            update_user_meta( $user_id, 'year_of_birth', $entity->year_of_birth );
            $changed = true;
        }

        if ( ! empty( $entity->password ) ) {
            wp_set_password( $entity->password, $user_id );
            wp_set_auth_cookie( $user_id, 1, true );
            $changed = true;
        }

        if ( isset( $entity->opt_ins ) && is_array( $entity->opt_ins ) ) {
            if ( $this->sync_opt_ins( $user_id, $entity ) ) {
                $changed = true;
            }
        }

        return $changed ? $user_id : 0;
    }

    public function update_contact( User $user, string $contact_no, string $contact_email ): bool {
        $updates               = false;
        $user_id               = (int) $user->id;
        $current_contact_no    = (string) get_user_meta( $user_id, 'contactno', true );
        $current_contact_email = (string) $user->user_email;
        if ( $current_contact_no !== $contact_no ) {
            update_user_meta( $user_id, 'contactno', $contact_no );
            $user->contactno = $contact_no;
            $updates         = true;
        }
        if ( $current_contact_email !== $contact_email ) {
            $result = wp_update_user(
                array(
                    'ID'         => $user_id,
                    'user_email' => $contact_email,
                )
            );
            if ( is_wp_error( $result ) ) {
                error_log( 'Unable to update user email ' . $user_id . ' - ' . $contact_email . ' - ' . $result->get_error_message() );
                return false;
            }
            $user->user_email = $contact_email;
            $user->email      = $contact_email;
            $updates          = true;
        }
        return $updates;
    }

    public function get_favourites( User $user, ?string $type = null ): array {
        $favourites_types = array( 'competition', 'league', 'club', 'team', 'player' );
        $favourites       = array();
        if ( $type ) {
            if ( in_array( $type, $favourites_types, true ) ) {
                $favourites = $this->get_favourites_for_type( $user, $type );
            }
            return $favourites;
        }
        foreach ( $favourites_types as $f => $favourites_type ) {
            $favourites[ $f ]['name']       = $favourites_type;
            $type_favourites                = $this->get_favourites_for_type( $user, $favourites_type );
            array_multisort( $type_favourites );
            $favourites[ $f ]['favourites'] = $type_favourites;
        }
        return $favourites;
    }

    public function delete( int $id ): bool {
        return false;
    }

    private function get_favourites_for_type( User $user, string $favourites_type ): array {
        $userid          = (int) $user->ID;
        $meta_key        = 'favourite-' . $favourites_type;
        $meta_favourites = get_user_meta( $userid, $meta_key );
        if ( ! is_array( $meta_favourites ) ) {
            $meta_favourites = array();
        }
        $favourites = array();
        foreach ( $meta_favourites as $i => $favourite ) {
            $favourite_item = new stdClass();
            if ( 'league' === $favourites_type ) {
                $league                 = $this->find_favourite_league( $favourite );
                $favourite_item->name   = is_object( $league ) ? ( $league->title ?? '' ) : '';
                $favourite_item->detail = is_object( $league ) ? $league : null;
            } elseif ( 'club' === $favourites_type ) {
                $club                   = $favourite ? $this->club_repository()->find_by_id( $favourite ) : null;
                $favourite_item->name   = is_object( $club ) ? ( $club->name ?? '' ) : '';
                $favourite_item->detail = is_object( $club ) ? $club : null;
            } elseif ( 'competition' === $favourites_type ) {
                $event                  = $favourite ? $this->event_repository()->find_by_id( $favourite ) : null;
                $favourite_item->name   = is_object( $event ) ? ( $event->name ?? '' ) : '';
                $favourite_item->detail = is_object( $event ) ? $event : null;
            } elseif ( 'player' === $favourites_type ) {
                $player                 = $favourite ? $this->player_repository()->find( $favourite ) : null;
                $favourite_item->name   = is_object( $player ) ? ( $player->display_name ?? '' ) : '';
                $favourite_item->detail = is_object( $player ) ? $player : null;
            } elseif ( 'team' === $favourites_type ) {
                $team                   = $favourite ? $this->team_repository()->find_by_id( $favourite ) : null;
                $favourite_item->name   = is_object( $team ) ? ( $team->title ?? '' ) : '';
                $favourite_item->detail = is_object( $team ) ? $team : null;
            }
            $favourite_item->id = $favourite;
            $favourites[ $i ]   = $favourite_item;
        }
        return $favourites;
    }

    private function find_favourite_league( mixed $favourite ): ?object {
        if ( empty( $favourite ) ) {
            return null;
        }
        return is_numeric( $favourite )
            ? $this->league_repository()->find_by_id( (int) $favourite )
            : $this->league_repository()->find_by_name( (string) $favourite );
    }

    private function sync_opt_ins( int $user_id, User $entity ): bool {
        $updates          = false;
        $opt_in_choices   = Util::get_email_opt_ins();
        $existing_opt_ins = get_user_meta( $user_id, 'racketmanager_opt_in' ) ?: array();
        $existing_opt_ins = array_map( 'strval', is_array( $existing_opt_ins ) ? $existing_opt_ins : array() );
        $desired          = array_values( array_unique( array_map( 'strval', $entity->opt_ins ) ) );
        $opt_ins          = array();
        foreach ( $opt_in_choices as $opt_in_choice => $opt_in_desc ) {
            $choice_str = (string) $opt_in_choice;
            $has_choice = in_array( $choice_str, $desired, true );
            if ( in_array( $choice_str, $existing_opt_ins, true ) ) {
                if ( ! $has_choice ) {
                    delete_user_meta( $user_id, 'racketmanager_opt_in', $opt_in_choice );
                    $updates = true;
                } else {
                    $opt_ins[] = $choice_str;
                }
            } elseif ( $has_choice ) {
                add_user_meta( $user_id, 'racketmanager_opt_in', $opt_in_choice );
                $opt_ins[] = $choice_str;
                $updates   = true;
            }
        }
        $entity->opt_ins = $opt_ins;
        return $updates;
    }

    private function btm_changed( mixed $stored, mixed $incoming ): bool {
        if ( empty( $stored ) ) {
            return ! empty( $incoming );
        }
        return (int) $stored !== (int) $incoming;
    }

    private function stored_email( int $user_id ): string {
        $userdata = get_userdata( $user_id );
        if ( ! $userdata ) {
            return '';
        }
        if ( isset( $userdata->user_email ) ) {
            return (string) $userdata->user_email;
        }
        if ( isset( $userdata->data->user_email ) ) {
            return (string) $userdata->data->user_email;
        }
        return '';
    }

    private function hydrate_user( object $wp_user ): User {
        // User_Hydration_DTO reads the WP_User `data` columns and resolves the ID/email/display_name aliases.
        $user = User::from_object( $wp_user );
        $id   = $user->ID;

        $user->firstname     = (string) get_user_meta( $id, 'first_name', true );
        $user->surname       = (string) get_user_meta( $id, 'last_name', true );
        $user->gender        = (string) get_user_meta( $id, 'gender', true );
        $user->type          = (string) get_user_meta( $id, 'racketmanager_type', true );
        $user->btm           = (string) get_user_meta( $id, 'btm', true );
        $user->year_of_birth = (string) get_user_meta( $id, 'year_of_birth', true );
        if ( ! empty( $user->year_of_birth ) ) {
            $user->age = (int) gmdate( 'Y' ) - (int) $user->year_of_birth;
        } else {
            $user->age = 0;
        }
        $user->contactno     = (string) get_user_meta( $id, 'contactno', true );
        $user->removed_date  = (string) get_user_meta( $id, 'remove_date', true );
        $user->removed_user  = (string) get_user_meta( $id, 'remove_user', true );
        $user->locked        = (string) get_user_meta( $id, 'locked', true );
        $user->locked_date   = (string) get_user_meta( $id, 'locked_date', true );
        $user->locked_user   = (string) get_user_meta( $id, 'locked_user', true );
        if ( ! empty( $user->locked_user ) ) {
            $locked_ud = get_userdata( (int) $user->locked_user );
            $user->locked_user_name = $locked_ud ? $locked_ud->display_name : '';
        } else {
            $user->locked_user_name = '';
        }
        $user->system_record = (string) get_user_meta( $id, 'racketmanager_type', true );
        $opt_ins             = get_user_meta( $id, 'racketmanager_opt_in' );
        $user->opt_ins       = is_array( $opt_ins ) ? array_values( array_map( 'strval', $opt_ins ) ) : array();

        return $user;
    }
}
