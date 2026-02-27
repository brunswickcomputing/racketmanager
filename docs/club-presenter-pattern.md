### Architectural Review: Club Presenter Pattern

In modern **Domain-Driven Design (DDD)**, separating domain logic from presentation concerns is critical for maintaining a clean and testable codebase.

#### 1. The Problem with the Current Approach
The `Club` entity currently sets its own URL link in its constructor:
```php
$this->link = '/clubs/' . seo_url( $this->shortcode ) . '/';
```
This couples the **Domain Layer** to **Routing** and **Global Helper Functions**, making it harder to maintain and test in isolation.

#### 2. The Solution: `Club_Presenter`
A **Presenter** acts as a wrapper around your Domain Entity to handle view-specific logic (like URL generation or HTML labels).

##### Class Implementation
File: `src/php/Presentation/Club_Presenter.php`

```php
namespace Racketmanager\Presentation;

use Racketmanager\Domain\Club;
use function Racketmanager\seo_url;

class Club_Presenter {
    private Club $club;

    public function __construct( Club $club ) {
        $this->club = $club;
    }

    /**
     * Generate the URL link for the club.
     * This keeps the routing logic out of the Domain Entity.
     */
    public function get_link(): string {
        $shortcode = $this->club->get_shortcode();
        return '/clubs/' . seo_url( $shortcode ) . '/';
    }

    /**
     * Formatting a name for display.
     */
    public function get_display_name(): string {
        return esc_html( $this->club->get_name() );
    }

    /**
     * Proxy other calls to the underlying club entity.
     */
    public function __call( $name, $arguments ) {
        return call_user_func_array( [ $this->club, $name ], $arguments );
    }
}
```

#### 3. Usage Example
Wrap the `Club` entity at the **Application** or **Controller** level before passing it to a view.

**In a Controller/Handler:**
```php
$club = $club_repository->get_by_id( 123 );
$view_data = new Club_Presenter( $club );
include 'templates/club-profile.php';
```

**In the Template:**
```php
<a href="<?php echo esc_url( $view_data->get_link() ); ?>">
    <?php echo $view_data->get_display_name(); ?>
</a>
```

#### 4. Benefits
1. **Clean Domain**: No routing logic in entities.
2. **Flexible Routing**: Change URLs in one place without touching business logic.
3. **Decoupled**: Entities remain "pure" POPOs (Plain Old PHP Objects).
