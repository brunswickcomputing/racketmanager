<?php
/**
 * Architecture scanner fixture: an Active-Record style entity with legacy coupling.
 *
 * This comment mentions $wpdb and `global $racketmanager` on purpose: the AST scanner must ignore
 * comments and string literals. Not autoloadable and never executed.
 */

namespace Racketmanager\Tests\Fixtures\Architecture;

use Racketmanager\Util\Util;
use Racketmanager\Repositories\Club_Repository;
use function Racketmanager\get_league;

final class Untyped_Entity {
    public int $id = 0;

    public function __construct( ?object $data = null, int|string|null $flag = null ) {
        if ( null !== $data ) {
            foreach ( get_object_vars( $data ) as $key => $value ) {
                $this->$key = $value;
            }
        }
        $this->id = (int) $flag;
    }

    public function describe(): string {
        $label  = 'prefix: $wpdb->prefix is only text here';
        $league = get_league( $this->id );
        $slug   = Util::seo_url( sanitize_text_field( (string) $league?->title ) );

        return $label . $slug;
    }

    public function repository(): Club_Repository {
        return new Club_Repository();
    }
}
