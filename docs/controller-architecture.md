### Controller Architecture in RacketManager

In a modern **Domain-Driven Design (DDD)** architecture, **Controllers** act as the entry point for the application. They are responsible for receiving requests, orchestrating services, and returning responses (views or data).

#### 1. Current State (Pseudo-Controllers)
The plugin currently handles requests through several components that effectively function as controllers:
- **Public**: `Shortcodes_*` classes handle shortcode attributes, fetch data from services, and return rendered templates.
- **Admin**: `Admin_Display` routes admin menu requests to domain-specific admin classes (e.g., `Admin_Club`, `Admin_League`), which handle POST/GET data and view rendering.
- **AJAX**: `Ajax_Frontend` and `Ajax_Admin` register WordPress AJAX actions and process requests.

#### 2. Formalizing the Controller Level
To move toward a more robust DDD structure, dedicated **Controller** classes can be introduced to decouple the **Presentation Layer** (WordPress hooks) from the **Application Layer** (Services).

##### Public Controller Example
A dedicated controller handles input validation and data preparation, then passes it to a presenter.

```php
namespace Racketmanager\Public\Controllers;

use Racketmanager\Services\Club_Service;
use Racketmanager\Presentation\Club_Presenter;

class Club_Public_Controller {
    private Club_Service $club_service;

    public function __construct(Club_Service $club_service) {
        $this->club_service = $club_service;
    }

    /**
     * Prepares data for a club profile view.
     */
    public function show_profile(int $club_id): array {
        $club = $this->club_service->get_club($club_id);
        $presenter = new Club_Presenter($club);

        return [
            'club' => $presenter,
            'user_can_edit' => current_user_can('edit_posts'),
        ];
    }
}
```

##### Admin Controller Example
Admin controllers handle logic for processing form submissions and redirects, keeping the `Admin_Display` class focused on routing.

```php
namespace Racketmanager\Admin\Controllers;

class Club_Admin_Controller {
    public function update_club() {
        check_admin_referer('update_club_nonce');
        $this->club_service->update($_POST['club']);
        wp_redirect(admin_url('admin.php?page=racketmanager-clubs&updated=true'));
        exit;
    }
}
```

#### 3. Proposed Layered Architecture

| Layer | Component | Responsibility |
| :--- | :--- | :--- |
| **Presentation** | `Shortcodes`, `Admin_Display`, `Ajax` | Entry point from WordPress hooks/actions. |
| **Controller** | `*_Controller` | Validates input, calls Services, prepares Presenters. |
| **Application** | `*_Service` | Orchestrates business logic (agnostic of HTTP/WP state). |
| **Domain** | Entities (`Club`, `League`), DTOs | Core business rules and data transfer structures. |
| **View** | Templates (`templates/*.php`) | HTML structure, uses `Presenters` for data display. |

#### 4. How Controllers are Called

In the WordPress environment, controllers are not called directly by the routing engine (like in Laravel or Symfony). Instead, they are instantiated and invoked by the existing WordPress entry points (Shortcodes, Admin Menu callbacks, and AJAX actions).

To maintain consistency and leverage Dependency Injection, controllers should be registered in the `Simple_Container` and then retrieved by the "Bridge" classes.

##### A. Bridge from Shortcode
Shortcode classes act as the entry point from WordPress. They should resolve the controller from the container and delegate the logic.

```php
// src/php/Public/Shortcodes_Club.php

public function show_club( array $atts ): string {
    // 1. Resolve controller from container
    $controller = $this->container->get('club_public_controller');

    // 2. Delegate to controller
    $club_id = isset($atts['id']) ? (int)$atts['id'] : 0;
    $data = $controller->show_profile($club_id);

    // 3. Load template with data from controller
    return $this->load_template('club-profile', $data);
}
```

##### B. Bridge from Admin Menu
The `Admin_Display` class routes admin requests. It can instantiate the controller and call the relevant method based on the request.

```php
// src/php/Admin/Admin_Display.php

public function handle_club_update() {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_club']) ) {
        $controller = $this->racketmanager->container->get('club_admin_controller');
        $controller->update_club(); // Controller handles validation, service call, and redirect
    }
}
```

##### C. Bridge from AJAX
AJAX classes bridge WordPress `wp_ajax_*` hooks to the Controller level.

```php
// src/php/Ajax/Ajax_Frontend.php

public function update_club_ajax(): void {
    $controller = $this->container->get('club_admin_controller');
    
    try {
        $result = $controller->process_update($_POST);
        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}
```

#### 5. Registration in the Container

To make this work, update `Container_Bootstrap.php` to include your controllers:

```php
// src/php/Services/Container/Container_Bootstrap.php

$c->set('club_public_controller', function($c) {
    return new Club_Public_Controller($c->get('club_service'));
});
```

#### 6. Benefits of this Calling Pattern
- **Centralized Logic**: The "Bridge" classes become thin wrappers, while the Controller contains the actual orchestration logic.
- **Dependency Management**: Controllers receive their dependencies (Services, Repositories) via the constructor, managed by the container.
- **Decoupled Testing**: You can test the `Club_Public_Controller` in isolation by mocking the `Club_Service`, without needing to boot WordPress or trigger a real shortcode.
