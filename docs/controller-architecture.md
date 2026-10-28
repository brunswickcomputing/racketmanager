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

#### 7. Modern Admin Controller Architecture (Implemented in P2-9)

The admin interface uses a unified, decoupled architecture across all seven screen bridges (`Admin_Tournament`, `Admin_League`, `Admin_Cup`, `Admin_Club`, `Admin_Finances`, `Admin_Player`, `Admin_Competition`).

##### Core Components & Pattern

1. **Thin Readonly Bridges (`src/php/Admin/Admin_<Screen>.php`)**:
   - Declared `final readonly`.
   - Do **not** extend `Admin_Display`.
   - Consume the `Completes_Admin_Requests` trait (`$actions`, `$screens`, `complete_request()`).
   - Define a `$view_map` mapping view queries to internal display methods.
   - Resolve controllers lazily from `Simple_Container` (`$container->get(...)`).
   - Catch domain exceptions (`Invalid_Status_Exception`, `*_Not_Found_Exception`) and surface them via `Admin_Message_Service`.

2. **The Request Completion Trait (`src/php/Admin/Completes_Admin_Requests.php`)**:
   - Encapsulates interaction with `Admin_Action_Adapter` (for PRG redirects) and `Admin_Screen_Presenter` (for template rendering).
   - If the controller result contains a `'redirect'` key: executes PRG redirect with user-meta flash data and exits.
   - If the controller result contains `'view_model'`: renders the strict `$vm` template via `Admin_Screen_Presenter::present()`.

3. **Context-Aware Shared Views (`src/php/Admin/Controllers/Admin_Page_Context.php`)**:
   - Encapsulates originating menu page (`racketmanager-tournaments`, `racketmanager-leagues`, `racketmanager-cups`) and target entity context (`tournament`, `league`, `cup`).
   - Powers shared `Teams_Admin_Controller` and `Contact_Admin_Controller`, ensuring redirect URLs return to the originating screen; `Competition_Seasons_Admin_Controller` uses the competition query context directly.

4. **Per-View Controllers (`src/php/Admin/Controllers/`)**:
   - `final readonly class <Context>_<View>_Admin_Controller`.
   - Page entry points generally take `( array $query, array $post )`; shared teams/contact pages also take `Admin_Page_Context`, and schedule/plan/invoices pages may take flash data or a tab. Existing tournament `handle()` controllers are retained.
   - Input security: capability assertions via `Action_Guard_Interface`.
   - Mutations: delegate to domain Action Dispatchers (`Services/Admin/**/*_Action_Dispatcher`).
   - Redirect URL construction: centralized in `Admin_Redirect_Url_Builder`.
   - Pure Unit testing: fully testable without WordPress bootstrap using stubs and `Action_Guard_Interface`.

##### Complete Controller & View Mapping Table

| Screen Bridge | View (`view=`) | Controller | View Model | Template |
|---|---|---|---|---|
| **League** | default (leagues) | `League_Competitions_Admin_Controller` | `Competitions_Page_View_Model` | `admin/show-competitions` |
| | `overview` | `League_Overview_Admin_Controller` | `League_Overview_Page_View_Model` | `admin/league/show-season` |
| | `setup` | `League_Setup_Admin_Controller` | `Setup_Page_View_Model` | `admin/includes/setup` |
| | `setup-event` | `League_Setup_Event_Admin_Controller` | `Setup_Page_View_Model` | `admin/includes/setup` |
| | `event` | `League_Event_Admin_Controller` | `League_Event_Page_View_Model` | `admin/event/show-leagues` |
| | `constitution` | `League_Constitution_Admin_Controller` | `League_Constitution_Page_View_Model` | `admin/league/show-constitution` |
| | `league` (standings) | `League_Standings_Admin_Controller` | `League_Standings_Page_View_Model` | `admin/show-league` |
| | `matches` | `League_Matches_Admin_Controller` | `League_Matches_Page_View_Model` | `admin/event/show-matches` |
| | `match` | `League_Match_Admin_Controller` | `Tournament_Fixtures_Page_View_Model` | `admin/includes/match` |
| | `plan` (schedule) | `League_Schedule_Admin_Controller` | `Admin_League_Schedule_Page_View_Model` | `admin/league/show-schedule` |
| | `teams` | `Teams_Admin_Controller` (shared) | `Teams_List_Page_View_Model` | `admin/includes/teams-list` |
| | `contact` | `Contact_Admin_Controller` (shared) | `Contact_Page_View_Model` | `admin/includes/contact` |
| | `seasons` | `Competition_Seasons_Admin_Controller` (shared) | `Competition_Seasons_Page_View_Model` | `admin/includes/show-seasons` |
| | `team` | via `Admin_Club` → `Club_Team_Admin_Controller` | `Tournament_Team_Page_View_Model` | `admin/includes/teams/team` or `admin/includes/teams/player-team` (dynamic via `$vm->file`) |
| | `config` | via `Admin_Competition` → `Competition_Config_Admin_Controller` | `Tournament_Competition_Config_Page_View_Model` | `admin/includes/competition-config` |
| | `modify` | via `Admin_Competition` → `Competition_Season_Modify_Admin_Controller` | `Season_Modify_Page_View_Model` | `admin/includes/season-edit` |
| | `event-config` | via `Admin_Event::display_config_page()` | `Tournament_Event_Config_Page_View_Model` | `admin/includes/event-config` |
| **Cup** | default (cups) | `Cup_Competitions_Admin_Controller` | `Competitions_Page_View_Model` | `admin/show-competitions` |
| | `overview` | `Cup_Overview_Admin_Controller` | `Cup_Overview_Page_View_Model` | `admin/cup/show-season` |
| | `setup` | `Cup_Setup_Admin_Controller` | `Setup_Page_View_Model` | `admin/cup/setup` |
| | `setup-event` | `Cup_Setup_Event_Admin_Controller` | `Setup_Page_View_Model` | `admin/cup/setup` |
| | `draw` | `Cup_Draw_Admin_Controller` | `Cup_Draw_Page_View_Model` | `admin/cup/draw` |
| | `matches` | `Cup_Matches_Admin_Controller` | `Tournament_Fixtures_Page_View_Model` | `admin/includes/match` |
| | `match` | `Cup_Match_Admin_Controller` | `Tournament_Fixtures_Page_View_Model` | `admin/includes/match` |
| | `plan` | `Cup_Plan_Admin_Controller` | `Cup_Plan_Page_View_Model` | `admin/cup/plan` |
| | `teams` | `Teams_Admin_Controller` (shared) | `Teams_List_Page_View_Model` | `admin/includes/teams-list` |
| | `contact` | `Contact_Admin_Controller` (shared) | `Contact_Page_View_Model` | `admin/includes/contact` |
| | `seasons` | `Competition_Seasons_Admin_Controller` (shared) | `Competition_Seasons_Page_View_Model` | `admin/includes/show-seasons` |
| | `config` | via `Admin_Competition` → `Competition_Config_Admin_Controller` | `Tournament_Competition_Config_Page_View_Model` | `admin/includes/competition-config` |
| | `modify` | via `Admin_Competition` → `Competition_Season_Modify_Admin_Controller` | `Season_Modify_Page_View_Model` | `admin/includes/season-edit` |
| | `team` | via `Admin_Club` → `Club_Team_Admin_Controller` | `Tournament_Team_Page_View_Model` | `admin/includes/teams/team` or `admin/includes/teams/player-team` (dynamic via `$vm->file`) |
| | `event` / `event-config` | via `Admin_Event::display_config_page()` | `Tournament_Event_Config_Page_View_Model` | `admin/includes/event-config` |
| **Club** | default (clubs) | `Clubs_Admin_Controller` | `Clubs_Page_View_Model` | `admin/show-clubs` |
| | `club` | `Club_Admin_Controller` | `Club_Page_View_Model` | `admin/includes/club` |
| | `players` | `Club_Players_Admin_Controller` | `Club_Players_Page_View_Model` | `admin/club/show-club-players` |
| | `teams` | `Club_Teams_Admin_Controller` | `Club_Teams_Page_View_Model` | `admin/club/show-teams` |
| | `team` | `Club_Team_Admin_Controller` | `Tournament_Team_Page_View_Model` | `admin/includes/teams/team` or `admin/includes/teams/player-team` (dynamic via `$vm->file`) |
| | `roles` | `Club_Roles_Admin_Controller` | `Club_Roles_Page_View_Model` | `admin/club/show-roles` |
| | `player` | via `Admin_Player` → `Player_Admin_Controller` | `Player_Page_View_Model` | `admin/players/show-player` |
| **Finances** | default (home) | `Finances_Home_Admin_Controller` | `Finances_Charges_Page_View_Model` | `admin/finances/show-charges` |
| | `charges` | `Finances_Charges_Admin_Controller` | `Finances_Charges_Page_View_Model` | `admin/finances/show-charges` |
| | `club-invoices` | `Finances_Invoices_Admin_Controller` | `Finances_Invoices_Page_View_Model` | `admin/finances/show-invoices` |
| | `player-invoices` | `Finances_Invoices_Admin_Controller` | `Finances_Invoices_Page_View_Model` | `admin/finances/show-invoices` |
| | `charge` | `Finances_Charge_Admin_Controller` | `Finances_Charge_Page_View_Model` | `admin/finances/charge` |
| | `invoice` | `Finances_Invoice_Admin_Controller` | `Finances_Invoice_Page_View_Model` | `admin/finances/invoice` |
| **Player** | default / `errors` | `Player_Errors_Admin_Controller` | `Player_Errors_Page_View_Model` | `admin/players/show-errors` |
| | `players` | `Players_Admin_Controller` | `Players_Page_View_Model` | `admin/players/show-players` |
| | `player` | `Player_Admin_Controller` | `Player_Page_View_Model` | `admin/players/show-player` |
| | `requests` | `Player_Requests_Admin_Controller` | `Player_Requests_Page_View_Model` | `admin/players/show-requests` |
| **Competition** | `config` | `Competition_Config_Admin_Controller` | `Tournament_Competition_Config_Page_View_Model` | `admin/includes/competition-config` |
| | `modify` | `Competition_Season_Modify_Admin_Controller` | `Season_Modify_Page_View_Model` | `admin/includes/season-edit` |
| **Tournament** | `tournaments` (default) | `Tournament_Tournaments_Admin_Controller` | `Tournament_Tournaments_Page_View_Model` | `admin/show-tournaments` |
| | `tournament` | `Tournament_Overview_Admin_Controller` | `Tournament_Overview_Page_View_Model` | `admin/tournament/show-tournament` |
| | `modify` | `Tournament_Admin_Controller` | `Tournament_Modify_Page_View_Model` | `admin/tournament-edit` |
| | `plan` | `Tournament_Plan_Admin_Controller` | `Tournament_Plan_Page_View_Model` | `admin/tournament/plan` |
| | `draw` | `Tournament_Draw_Admin_Controller` | `Tournament_Draw_Page_View_Model` | `admin/tournament/draw` |
| | `setup` | `Tournament_Setup_Admin_Controller` | `Tournament_Setup_Page_View_Model` | `admin/tournament/setup` |
| | `setup-event` | `Tournament_Setup_Event_Admin_Controller` | `Tournament_Setup_Page_View_Model` | `admin/tournament/setup-event` |
| | `fixtures` / `fixture` | `Tournament_Fixtures_Admin_Controller` | `Tournament_Fixtures_Page_View_Model` | `admin/includes/match` |
| | `teams` | `Teams_Admin_Controller` (shared) | `Teams_List_Page_View_Model` | `admin/includes/teams-list` |
| | `contact` | `Contact_Admin_Controller` (shared) | `Contact_Page_View_Model` | `admin/includes/contact` |
| | `information` | `Tournament_Information_Admin_Controller` | `Tournament_Information_Page_View_Model` | `admin/tournament/information` |
| | `config` | `Tournament_Competition_Config_Admin_Controller` | `Tournament_Competition_Config_Page_View_Model` | `admin/includes/competition-config` |
| | `event-config` | `Tournament_Event_Config_Admin_Controller` | `Tournament_Event_Config_Page_View_Model` | `admin/includes/event-config` |
| | `team` | `Tournament_Team_Admin_Controller` | `Tournament_Team_Page_View_Model` | `admin/includes/teams/team` or `admin/includes/teams/player-team` (dynamic via `$vm->file`) |
