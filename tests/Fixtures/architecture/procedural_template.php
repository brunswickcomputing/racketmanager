<?php
/**
 * Architecture scanner fixture: a procedural template in the plugin root namespace.
 *
 * Unqualified helper calls here must resolve to `Racketmanager\…`; the root-namespace `\seo_url()`
 * call must not. Not autoloadable and never executed.
 *
 * @var array<string, mixed> $vm
 */

namespace Racketmanager;

use Racketmanager\Repositories\{Club_Repository, Interfaces\Club_Repository_Interface};
use function Racketmanager\{get_event, un_seo_url};

global $racketmanager;

if ( empty( $vm ) ) {
    return;
}

$tab    = $_GET['tab'] ?? 'default';
$plugin = $GLOBALS['racketmanager'];
$event  = get_event( (int) ( $_POST['event_id'] ?? 0 ) );
$slug   = seo_url( $event->name );
$root   = \seo_url( $event->name );
$name   = un_seo_url( $slug );
$repo   = $plugin instanceof Club_Repository_Interface ? $plugin : new Club_Repository();
$title  = $event::get_instance()->title;

require_once RACKETMANAGER_PATH . 'templates/admin/includes/' . $tab . '.php';
include_once RACKETMANAGER_PATH . "templates/includes/{$slug}.php";
?>
<div class="fixture" data-tab="<?php echo esc_attr( $tab ); ?>">
    <h2><?php echo esc_html( $name ); ?></h2>
    <?php echo $repo ? 'repo' : 'none'; ?>
</div>
